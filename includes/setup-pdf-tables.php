<?php

function msc_create_pdf_index_table_if_enabled() {
    $enabled = get_option('msc_enable_media_scan', false);
    if (!$enabled) return;

    global $wpdb;
    $table_name = $wpdb->prefix . 'msc_media_index';
    $charset_collate = $wpdb->get_charset_collate();

    // Check if the table already exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            attachment_id BIGINT UNSIGNED NOT NULL,
            file_path TEXT NOT NULL,
            content LONGTEXT,
            indexed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY attachment_id (attachment_id)
        ) $charset_collate;";
        dbDelta($sql);
    }
}

add_action('admin_init', 'msc_create_pdf_index_table_if_enabled');
