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

//Include the needed files
require_once __DIR__ . '/vendor/autoload.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-pdf2text.php';
require_once plugin_dir_path(__FILE__) . 'includes/setup-pdf-tables.php';
require_once plugin_dir_path(__FILE__) . 'includes/pdf-indexer.php';
// Always include your indexing functions if AJAX is available
require_once plugin_dir_path(__FILE__) . 'includes/media-indexer.php';



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
        $this->reschedule_email_cron();
    }

    public function deactivate() {
        wp_clear_scheduled_hook( 'msc_weekly_email_event' );
    }

    public function reschedule_email_cron() {
        // Clear existing schedule
        wp_clear_scheduled_hook( 'msc_weekly_email_event' );

        // Schedule if enabled
        if ( get_option( 'msc_email_schedule_enabled', false ) ) {
            $frequency = get_option( 'msc_email_schedule_frequency', 'weekly' );
            if ( ! wp_next_scheduled( 'msc_weekly_email_event' ) ) {
                wp_schedule_event( time(), $frequency, 'msc_weekly_email_event' );
            }
        }
    }

    public function save_email_schedule() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        check_admin_referer( 'msc_save_email_schedule' );

        $enabled = isset( $_POST['msc_email_schedule_enabled'] ) ? 1 : 0;
        $frequency = sanitize_text_field( $_POST['msc_email_schedule_frequency'] ?? 'weekly' );
        $recipient_input = sanitize_text_field( $_POST['msc_email_recipient'] ?? '' );

        // Parse and validate comma-separated email addresses
        $recipient = '';
        if ( ! empty( $recipient_input ) ) {
            $emails = array_map( 'trim', explode( ',', $recipient_input ) );
            $valid_emails = [];
            $invalid_emails = [];

            foreach ( $emails as $email ) {
                if ( empty( $email ) ) {
                    continue; // Skip empty entries
                }
                $sanitized = sanitize_email( $email );
                if ( is_email( $sanitized ) ) {
                    $valid_emails[] = $sanitized;
                } else {
                    $invalid_emails[] = $email;
                }
            }

            if ( ! empty( $invalid_emails ) ) {
                // Show error and redirect back
                wp_redirect( add_query_arg( 
                    array( 
                        'settings-updated' => 'false',
                        'error' => 'invalid_emails',
                        'invalid' => urlencode( implode( ', ', $invalid_emails ) )
                    ), 
                    admin_url( 'admin.php?page=mission-safe-check-email' ) 
                ) );
                exit;
            }

            $recipient = implode( ', ', $valid_emails );
        }

        // If no valid emails and enabled, use admin email as fallback
        if ( empty( $recipient ) && $enabled ) {
            $recipient = get_option( 'admin_email' );
        }

        update_option( 'msc_email_schedule_enabled', $enabled );
        update_option( 'msc_email_schedule_frequency', $frequency );
        update_option( 'msc_email_recipient', $recipient );

        $this->reschedule_email_cron();

        wp_redirect( add_query_arg( 'settings-updated', 'true', admin_url( 'admin.php?page=mission-safe-check-email' ) ) );
        exit;
    }

    public function init() {
        // Register custom option to store saved keywords
        register_setting( 'msc_options_group', 'msc_saved_keywords' );
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_highlight_script'));
        register_setting( 'msc_options_group', 'msc_enable_media_scan', [
            'type' => 'boolean',
            'sanitize_callback' => function( $value ) {
                return (bool) $value;
            },
            'default' => false,
        ] );

        // Register email schedule settings
        register_setting( 'msc_email_group', 'msc_email_schedule_enabled', [
            'type' => 'boolean',
            'sanitize_callback' => function( $value ) {
                return (bool) $value;
            },
            'default' => false,
        ] );
        register_setting( 'msc_email_group', 'msc_email_schedule_frequency', [
            'type' => 'string',
            'sanitize_callback' => function( $value ) {
                $allowed = ['daily', 'weekly', 'monthly'];
                return in_array($value, $allowed) ? $value : 'weekly';
            },
            'default' => 'weekly',
        ] );
        register_setting( 'msc_email_group', 'msc_email_recipient', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_email',
            'default' => '',
        ] );

        // Handle email schedule form submission
        add_action('admin_post_msc_save_email_schedule', array($this, 'save_email_schedule'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Mission Safe Check',
            'Mission Safe Check',
            'manage_options',
            'mission-safe-check',
            array( $this, 'admin_page_html' ),
            'dashicons-search',
            80
        );
        
        add_submenu_page(
            'mission-safe-check',
            'Email Settings',
            'Email',
            'manage_options',
            'mission-safe-check-email',
            array( $this, 'email_page_html' )
        );
        
        add_submenu_page(
            'mission-safe-check',
            'Settings',
            'Settings',
            'manage_options',
            'mission-safe-check-settings',
            array( $this, 'settings_page_html' )
        );
    }

    public function enqueue_assets() {
        wp_enqueue_script('msc-admin-script', plugin_dir_url(__FILE__) . 'js/admin.js', ['jquery'], null, true);
        wp_localize_script('msc-admin-script', 'msc_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('msc_ajax_nonce')
        ]);

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

    public function email_page_html() {
        include plugin_dir_path( __FILE__ ) . 'templates/email-page.php';
    }

    public function settings_page_html() {
        include plugin_dir_path( __FILE__ ) . 'templates/settings-page.php';
    }

    public function ajax_search_content() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        check_ajax_referer( 'msc_ajax_nonce', 'nonce' );

        $keyword = sanitize_text_field( $_POST['keyword'] ?? '' );
        if ( empty( $keyword ) ) {
            wp_send_json_error( 'No keyword provided' );
        }

        $results = [];

        // Search regular content
        $args = array(
            'post_type'      => 'any',
            'post_status'    => 'publish',
            's'              => $keyword,
            'posts_per_page' => -1,
        );

        $query = new WP_Query( $args );
        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post ) {
                $results[] = [
                    'title' => esc_html( get_the_title( $post ) ),
                    'link'  => esc_url( get_permalink( $post ) ),
                    'type'  => esc_html( get_post_type_object( $post->post_type )->labels->singular_name ),
                    'id'    => absint( $post->ID )
                ];
            }
        }

        // Search PDF content if enabled
        if ( get_option( 'msc_enable_media_scan' ) ) {
            global $wpdb;
            $table = $wpdb->prefix . 'msc_media_index';

            $pdfs = $wpdb->get_results( $wpdb->prepare(
                "SELECT attachment_id FROM $table WHERE content LIKE %s",
                '%' . $wpdb->esc_like( $keyword ) . '%'
            ) );

            foreach ( $pdfs as $pdf ) {
                $results[] = [
                    'title' => esc_html( get_the_title( $pdf->attachment_id ) ),
                    'link'  => esc_url( wp_get_attachment_url( $pdf->attachment_id ) ),
                    'type'  => 'PDF Document',
                    'id'    => absint( $pdf->attachment_id )
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

    check_ajax_referer('msc_ajax_nonce', 'nonce');

    $keyword = sanitize_text_field($_POST['keyword'] ?? '');
    if (!$keyword) wp_send_json_error('No keyword provided');

    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE keyword = %s", $keyword));
    if ($exists) wp_send_json_success(); // already exists

    $inserted = $wpdb->insert($table, [
        'keyword' => $keyword,
        'created_at' => current_time('mysql')
    ], ['%s', '%s']);

    if ($inserted !== false) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to insert');
    }
});

// Delete keyword
add_action('wp_ajax_msc_delete_keyword', function() use ($wpdb, $table) {
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

    check_ajax_referer('msc_ajax_nonce', 'nonce');

    $keyword = sanitize_text_field($_POST['keyword'] ?? '');
    if (!$keyword) wp_send_json_error('No keyword provided');

    $deleted = $wpdb->delete($table, ['keyword' => $keyword], ['%s']);
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
    $keywords = $wpdb->get_col("SELECT keyword FROM $table ORDER BY keyword ASC");
    // Note: Keywords are sanitized when stored, but we escape on output
    return $keywords;
}

// Helper function to generate email content using template
function msc_generate_email_content($matches, $is_test = false) {
    $report_title = $is_test ? 'Test: Mission Safe Check Report' : 'Mission Safe Check Report';
    $site_name = get_bloginfo('name');
    $report_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'));

    // Format matches for template - convert to HTML links
    $formatted_matches = [];
    foreach ($matches as $kw => $posts) {
        $formatted_matches[$kw] = [];
        foreach ($posts as $post) {
            if (is_array($post)) {
                // Format as HTML link
                $formatted_matches[$kw][] = '<a href="' . esc_url($post['link']) . '" style="color: #0867ec; text-decoration: none;">' . esc_html($post['title']) . '</a>';
            } else {
                // Plain text, convert to link if it's a URL
                if (filter_var($post, FILTER_VALIDATE_URL)) {
                    $formatted_matches[$kw][] = '<a href="' . esc_url($post) . '" style="color: #0867ec; text-decoration: none;">' . esc_html($post) . '</a>';
                } else {
                    $formatted_matches[$kw][] = esc_html($post);
                }
            }
        }
    }
    
    // Update $matches to use formatted version
    $matches = $formatted_matches;

    // Load template
    $template_path = plugin_dir_path(__FILE__) . 'templates/email-report-template.php';
    if (!file_exists($template_path)) {
        return false;
    }

    ob_start();
    include $template_path;
    return ob_get_clean();
}

// Replaces get_option-based weekly email task with DB query
add_action('msc_weekly_email_event', 'msc_send_weekly_email_from_db');

function msc_send_weekly_email_from_db() {
    // Check if scheduled emails are enabled
    if (!get_option('msc_email_schedule_enabled', false)) {
        return;
    }

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
                $matches[$kw][] = [
                    'title' => get_the_title(),
                    'link' => get_permalink()
                ];
            }
        }
    }
    wp_reset_postdata();

    if (empty($matches)) return;

    $body = msc_generate_email_content($matches, false);
    if (!$body) {
        // Fallback to plain text
        $body = "Here are the latest matches for your saved keywords:\n\n";
        foreach ($matches as $kw => $posts) {
            $body .= "Keyword: $kw\n";
            foreach ($posts as $post) {
                $body .= "  - " . (is_array($post) ? $post['title'] . ' - ' . $post['link'] : $post) . "\n";
            }
            $body .= "\n";
        }
    }

    $recipient_string = get_option('msc_email_recipient', get_option('admin_email'));
    $frequency = get_option('msc_email_schedule_frequency', 'weekly');
    $subject = ucfirst($frequency) . ' Mission Safe Check Results';

    // Parse comma-separated recipients and send individual emails
    $recipients = array_map('trim', explode(',', $recipient_string));
    
    add_filter('wp_mail_content_type', function() { return 'text/html'; });
    
    foreach ($recipients as $recipient) {
        if (!empty($recipient) && is_email($recipient)) {
            wp_mail($recipient, $subject, $body);
        }
    }
    
    remove_filter('wp_mail_content_type', function() { return 'text/html'; });
}

function msc_media_scan_enabled() {
    return get_option('msc_enable_media_scan', false);
}

add_action('wp_ajax_msc_send_test_email', 'msc_send_test_email_ajax');
function msc_send_test_email_ajax() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    check_ajax_referer('msc_ajax_nonce', 'nonce');

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
                $matches[$kw][] = [
                    'title' => get_the_title(),
                    'link' => get_permalink()
                ];
            }
        }
    }
    wp_reset_postdata();

    $body = msc_generate_email_content($matches, true);
    if (!$body) {
        // Fallback to plain text
        $body = "Test: Mission Safe Check Report\n\n";
        if (!empty($matches)) {
            foreach ($matches as $kw => $posts) {
                $body .= "Keyword: $kw\n";
                foreach ($posts as $post) {
                    $body .= "  - " . (is_array($post) ? $post['title'] . ' - ' . $post['link'] : $post) . "\n";
                }
                $body .= "\n";
            }
        } else {
            $body .= "No matches found for your saved keywords.\n";
        }
    }

    $subject = 'Test: Mission Safe Check Results';
    add_filter('wp_mail_content_type', function() { return 'text/html'; });
    $sent = wp_mail($recipient, $subject, $body);
    remove_filter('wp_mail_content_type', function() { return 'text/html'; });

    if ($sent) {
        wp_send_json_success('Test email sent to ' . esc_html($recipient));
    } else {
        wp_send_json_error('Failed to send test email');
    }
}

add_action('wp_ajax_msc_export_csv', function() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized', '', array('response' => 403));
    }

    // For GET requests, use wp_verify_nonce directly
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'msc_ajax_nonce')) {
        wp_die('Security check failed', '', array('response' => 403));
    }

    $selected_keywords = isset($_GET['keywords']) ? array_map('sanitize_text_field', (array) $_GET['keywords']) : [];
    $selected_post_types = isset($_GET['post_types']) ? array_map('sanitize_text_field', (array) $_GET['post_types']) : [];

    if (empty($selected_keywords)) {
        wp_die('No keywords selected.', '', array('response' => 400));
    }

    global $wpdb;
    $results = [];

    foreach ($selected_keywords as $kw) {
        // Search posts/pages
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

        $include_pdfs = isset($_GET['include_pdfs']) && $_GET['include_pdfs'] === '1';

        // Include PDFs if enabled
        if ($include_pdfs && get_option('msc_enable_media_scan')) {
            $table_name = $wpdb->prefix . 'msc_media_index';
            $pdf_matches = $wpdb->get_results(
                $wpdb->prepare("SELECT attachment_id, file_path FROM $table_name WHERE content LIKE %s", '%' . $wpdb->esc_like($kw) . '%')
            );

            foreach ($pdf_matches as $pdf) {
                $results[] = [
                    'Keyword'   => $kw,
                    'Title'     => basename($pdf->file_path),
                    'URL'       => wp_get_attachment_url($pdf->attachment_id),
                    'Post Type' => 'PDF'
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


function msc_scan_media_library_for_keywords($keywords) {
    $matches = [];
    $args = [
        'post_type' => 'attachment',
        'post_mime_type' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'posts_per_page' => -1,
        'post_status' => 'inherit'
    ];

    $query = new WP_Query($args);
    foreach ($query->posts as $attachment) {
        $file_path = get_attached_file($attachment->ID);
        if (!file_exists($file_path)) continue;

        $content = msc_extract_text_from_file($file_path);
        foreach ($keywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $matches[] = [
                    'keyword' => $keyword,
                    'title' => get_the_title($attachment),
                    'link' => wp_get_attachment_url($attachment->ID),
                    'post_type' => 'attachment'
                ];
                break; // Only list file once even if multiple keywords match
            }
        }
    }
    return $matches;
}



