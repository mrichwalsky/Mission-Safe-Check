<?php

use Smalot\PdfParser\Parser;

function msc_index_pdf_attachment($attachment_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'msc_media_index';
    $file_path = get_attached_file($attachment_id);

    if (!file_exists($file_path)) {
        return;
    }

    try {
        $parser = new Parser();
        $pdf = $parser->parseFile($file_path);
        $text = trim($pdf->getText());

        if (empty($text)) {
            error_log("No text extracted from PDF ID $attachment_id");
            return;
        }

        $wpdb->replace($table_name, [
            'attachment_id' => $attachment_id,
            'file_path'     => $file_path,
            'content'       => $text,
            'indexed_at'    => current_time('mysql'),
        ]);
    } catch (Exception $e) {
        error_log("PDF parsing error for attachment ID $attachment_id: " . $e->getMessage());
    }
}

function msc_bulk_index_pdfs() {
    $args = [
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => '_wp_attached_file',
                'compare' => 'EXISTS',
            ],
        ],
        'post_mime_type' => 'application/pdf',
    ];

    $query = new WP_Query($args);
    $count = 0;

    foreach ($query->posts as $attachment) {
        msc_index_pdf_attachment($attachment->ID);
        $count++;
    }

    return $count;
}

// AJAX handler for reindex
add_action('wp_ajax_msc_reindex_pdfs', function () {
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

    $count = msc_bulk_index_pdfs();
    wp_send_json_success("Re-indexed {$count} PDFs.");
});
