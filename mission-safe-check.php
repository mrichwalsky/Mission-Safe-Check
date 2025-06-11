<?php
/*
Plugin Name: Mission Safe Check
Plugin URI:  https://gasmark8.com/mission-safe-check
Description: Search your site for sensitive phrases, track keywords, and receive weekly reports to help your nonprofit stay mission-aligned.
Version:     0.1.0
Author:      Gas Mark 8, Ltd.
Author URI:  https://gasmark8.com
License:     GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
Text Domain: mission-safe-check
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Mission_Safe_Check {

    public function __construct() {
        // Plugin activation/deactivation hooks
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        // Init plugin features
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // AJAX for live search
        add_action( 'wp_ajax_msc_search_content', array( $this, 'ajax_search_content' ) );

        // Weekly email cron
        add_action( 'msc_weekly_email_event', array( $this, 'send_weekly_email' ) );
    }

    public function activate() {
        if ( ! wp_next_scheduled( 'msc_weekly_email_event' ) ) {
            wp_schedule_event( time(), 'weekly', 'msc_weekly_email_event' );
        }
    }

    public function deactivate() {
        wp_clear_scheduled_hook( 'msc_weekly_email_event' );
    }

    public function init() {
        // Register custom option to store saved keywords
        register_setting( 'msc_options_group', 'msc_saved_keywords' );
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_highlight_script'));

    }

    public function add_admin_menu() {
        add_menu_page(
            'Mission Safe Check',
            'Mission Check',
            'manage_options',
            'mission-safe-check',
            array( $this, 'admin_page_html' ),
            'dashicons-search',
            80
        );
    }

    public function enqueue_assets() {
        wp_enqueue_script('msc-admin-script', plugin_dir_url(__FILE__) . 'js/admin.js', ['jquery'], null, true);
        wp_localize_script('msc-admin-script', 'msc_ajax', ['ajax_url' => admin_url('admin-ajax.php')]);

        wp_enqueue_style('msc-admin-style', plugin_dir_url(__FILE__) . 'css/admin.css');

    }

    public function enqueue_frontend_highlight_script() {
        if (is_admin()) return; // Bail if in admin

        if (!isset($_GET['highlight']) || empty($_GET['highlight'])) return; // Only load if needed

        wp_enqueue_script(
            'msc-highlight',
            plugin_dir_url(__FILE__) . 'js/frontend-highlight.js',
            [],
            null,
            true
        );
        wp_enqueue_style(
            'msc-highlight-style',
            plugin_dir_url(__FILE__) . 'css/frontend-highlight.css',
            [],
            null
        );
    }



    public function admin_page_html() {
        include plugin_dir_path( __FILE__ ) . 'templates/admin-page.php';
    }

    public function ajax_search_content() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $keyword = sanitize_text_field( $_POST['keyword'] ?? '' );
        if ( empty( $keyword ) ) {
            wp_send_json_error( 'No keyword provided' );
        }

        $args = array(
            'post_type' => 'any',
            'post_status' => 'publish',
            's' => $keyword,
            'posts_per_page' => -1
        );

        $query = new WP_Query( $args );
        $results = [];

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post ) {
                $results[] = [
                    'title' => get_the_title( $post ),
                    'link'  => get_permalink( $post ),
                    'type'  => get_post_type_object( $post->post_type )->labels->singular_name,
                    'id'    => $post->ID
                ];
            }
        }

        wp_send_json_success( $results );
    }

    public function send_weekly_email() {
        $keywords = get_option( 'msc_saved_keywords', array() );
        if ( empty( $keywords ) ) return;

        $matches = [];

        foreach ( $keywords as $kw ) {
            $args = array(
                'post_type' => 'any',
                'post_status' => 'publish',
                's' => $kw,
                'posts_per_page' => -1
            );
            $query = new WP_Query( $args );
            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    $matches[$kw][] = get_the_title() . ' - ' . get_permalink();
                }
            }
        }
        wp_reset_postdata();

        $body = "Here are the latest matches for your keywords:\n\n";
        foreach ( $matches as $kw => $posts ) {
            $body .= "Keyword: $kw\n" . implode("\n", $posts) . "\n\n";
        }

        wp_mail( get_option('admin_email'), 'Weekly Mission Site Check Results', $body );
    }
}

new Mission_Safe_Check();

// Debug keyword option at each stage to trace what's happening
function msc_log($label, $data) {
    error_log("[MSC] $label: " . print_r($data, true));
}



function msc_create_keywords_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'msc_keywords';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        keyword VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY keyword (keyword)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Register activation hook at root scope
register_activation_hook(__FILE__, 'msc_create_keywords_table');

global $wpdb;
$table = $wpdb->prefix . 'msc_keywords';

// Add keyword
add_action('wp_ajax_msc_add_keyword', function() use ($wpdb, $table) {
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

    $keyword = sanitize_text_field($_POST['keyword'] ?? '');
    if (!$keyword) wp_send_json_error('No keyword provided');

    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE keyword = %s", $keyword));
    if ($exists) wp_send_json_success(); // already exists

    $inserted = $wpdb->insert($table, [
        'keyword' => $keyword,
        'created_at' => current_time('mysql')
    ]);

    if ($inserted !== false) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to insert');
    }
});

// Delete keyword
add_action('wp_ajax_msc_delete_keyword', function() use ($wpdb, $table) {
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

    $keyword = sanitize_text_field($_POST['keyword'] ?? '');
    if (!$keyword) wp_send_json_error('No keyword provided');

    $deleted = $wpdb->delete($table, ['keyword' => $keyword]);
    if ($deleted !== false) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to delete');
    }
});

// Get all keywords
function msc_get_all_keywords() {
    global $wpdb;
    $table = $wpdb->prefix . 'msc_keywords';
    return $wpdb->get_col("SELECT keyword FROM $table ORDER BY keyword ASC");
}

// Replaces get_option-based weekly email task with DB query
add_action('msc_weekly_email_event', 'msc_send_weekly_email_from_db');

function msc_send_weekly_email_from_db() {
    global $wpdb;
    $table = $wpdb->prefix . 'msc_keywords';

    $keywords = $wpdb->get_col("SELECT keyword FROM $table ORDER BY created_at DESC");
    if (empty($keywords)) return;

    $matches = [];

    foreach ($keywords as $kw) {
        $args = array(
            'post_type' => 'any',
            'post_status' => 'publish',
            's' => $kw,
            'posts_per_page' => -1
        );
        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $matches[$kw][] = get_the_title() . ' - ' . get_permalink();
            }
        }
    }
    wp_reset_postdata();

    if (empty($matches)) return;

    $body = "Here are the latest matches for your saved keywords:\n\n";
    foreach ($matches as $kw => $posts) {
        $body .= "Keyword: $kw\n" . implode("\n", $posts) . "\n\n";
    }

    wp_mail(get_option('admin_email'), 'Weekly Mission Site Check Results', $body);
}

// Fix 1: Prevent duplicate declaration of msc_send_weekly_email_from_db()
if (!function_exists('msc_send_weekly_email_from_db')) {
    function msc_send_weekly_email_from_db($is_test = false) {
        global $wpdb;
        $table = $wpdb->prefix . 'msc_keywords';

        $keywords = $wpdb->get_col("SELECT keyword FROM $table ORDER BY created_at DESC");
        if (empty($keywords)) return 'No keywords found';

        $matches = [];
        foreach ($keywords as $kw) {
            $query = new WP_Query([
                'post_type' => 'any',
                'post_status' => 'publish',
                's' => $kw,
                'posts_per_page' => -1
            ]);
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $matches[$kw][] = '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
                }
            }
        }
        wp_reset_postdata();

        if (empty($matches)) return 'No matches found';

        ob_start();
        ?>
        <div style="font-family:sans-serif;">
          <h2>Mission Safe Check - Weekly Report</h2>
          <p>The following keywords were found in your site's public content:</p>
          <ul>
            <?php foreach ($matches as $kw => $posts) : ?>
              <li><strong><?php echo esc_html($kw); ?></strong>
                <ul>
                  <?php foreach ($posts as $p) echo '<li>' . $p . '</li>'; ?>
                </ul>
              </li>
            <?php endforeach; ?>
          </ul>
          <p style="color:#888; font-size:0.85em;">This is an automated email from Mission Safe Check.</p>
        </div>
        <?php
        $body = ob_get_clean();

        add_filter('wp_mail_content_type', fn() => 'text/html');
        wp_mail(get_option('admin_email'), $is_test ? 'Test: Mission Safe Check Results' : 'Weekly Mission Safe Check Results', $body);
        remove_filter('wp_mail_content_type', fn() => 'text/html');

        return 'Email sent';
    }
}



// Set content type for HTML
add_filter('wp_mail_content_type', 'msc_set_html_mail_type');
function msc_set_html_mail_type($content_type) {
    return 'text/html';
}

add_action('wp_ajax_msc_send_test_email', 'msc_send_test_email_ajax');
function msc_send_test_email_ajax() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $recipient = sanitize_email($_POST['test_email_to'] ?? '');
    if (empty($recipient) || !is_email($recipient)) {
        wp_send_json_error('Invalid email address');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'msc_keywords';
    $keywords = $wpdb->get_col("SELECT keyword FROM $table_name ORDER BY keyword ASC");

    $matches = [];
    foreach ($keywords as $kw) {
        $query = new WP_Query([
            'post_type' => 'any',
            'post_status' => 'publish',
            's' => $kw,
            'posts_per_page' => -1
        ]);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $matches[$kw][] = '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
            }
        }
    }
    wp_reset_postdata();

    // Load and populate template
    $template = file_get_contents(plugin_dir_path(__FILE__) . 'templates/email-inlined.html');

    $results_html = '';
    if (!empty($matches)) {
        foreach ($matches as $kw => $posts) {
            $results_html .= '<p><strong>' . esc_html($kw) . '</strong><br>' . implode('<br>', $posts) . '</p>';
        }
    } else {
        $results_html = '<p>No matches found for your saved keywords.</p>';
    }

    $email_body = str_replace([
        'Hi there',
        'Sometimes you just want to send a simple HTML email with a simple design and clear call to action. This is it.'
    ], [
        'Mission Safe Check – Test Report',
        'Here are your keyword results:' . $results_html
    ], $template);

    $subject = 'Test: Mission Safe Check Results';
    $sent = wp_mail($recipient, $subject, $email_body);
    remove_filter('wp_mail_content_type', 'msc_set_html_mail_type');

    if ($sent) {
        wp_send_json_success('Test email sent to ' . $recipient);
    } else {
        wp_send_json_error('Failed to send test email');
    }
}

add_action('wp_ajax_msc_export_csv', function() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized', '', array('response' => 403));
    }

    $selected_keywords = isset($_GET['keywords']) ? array_map('sanitize_text_field', (array) $_GET['keywords']) : [];
    $selected_post_types = isset($_GET['post_types']) ? array_map('sanitize_text_field', (array) $_GET['post_types']) : [];

    if (empty($selected_keywords)) {
        wp_die('No keywords selected.', '', array('response' => 400));
    }

    $results = [];

    foreach ($selected_keywords as $kw) {
        $args = [
            'post_type' => !empty($selected_post_types) ? $selected_post_types : 'any',
            'post_status' => 'publish',
            's' => $kw,
            'posts_per_page' => -1
        ];
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $results[] = [
                    'Keyword'   => $kw,
                    'Title'     => get_the_title(),
                    'URL'       => get_permalink(),
                    'Post Type' => get_post_type()
                ];
            }
        }
    }
    wp_reset_postdata();

    // Output CSV headers with timestamped filename
    $timestamp = date('Ymd_His');
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=mission-check-report-$timestamp.csv");
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Keyword', 'Title', 'URL', 'Post Type']);

    foreach ($results as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
});