<?php

// AJAX handler to re-index all PDFs
add_action('wp_ajax_msc_reindex_pdfs', 'msc_ajax_reindex_all_pdfs');

function msc_ajax_reindex_all_pdfs() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    // Only run if the option is enabled
    if (!get_option('msc_enable_media_scan')) {
        wp_send_json_error('PDF scanning is not enabled.');
    }

    // Make sure the function exists
    if (!function_exists('msc_index_all_pdfs')) {
        require_once plugin_dir_path(__FILE__) . 'includes/media-indexer.php';
    }

    $result = msc_index_all_pdfs(); // this returns a string or log summary
    wp_send_json_success($result);
}
