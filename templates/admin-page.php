<?php
// templates/admin-page.php
$keywords = msc_get_all_keywords();
$post_types = get_post_types(['public' => true], 'objects');
$media_scan_enabled = get_option('msc_enable_media_scan', false);
?>
<!-- Full-width Header -->
<div class="msc-header">
  <h1 style="margin: 0;color:white;">Mission Safe Check</h1>
</div>

<div class="wrap" style="display: flex; gap: 2em;">
  <!-- Left Column -->
  <div style="flex: 1; min-width: 0;">
    <!-- Quick Search Section -->
    <div id="msc-quick-search" class="postbox msc-box">
      <h2>Quick Search</h2>
      <form id="msc-search-form">
        <input type="text" id="msc-keyword" placeholder="Enter keyword…" required />
        <button type="submit" class="button button-primary">Search</button>
      </form>

      <div id="msc-search-spinner" style="display:none; margin-top: 10px;">
        <span class="spinner is-active" style="float: none;"></span> Searching…
      </div>

      <div id="msc-results">
        <table>
          <thead>
            <tr>
              <th>Title</th>
              <th>Post Type</th>
              <th>Link</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <!-- Saved Keywords Section -->
    <div id="msc-saved-keywords" class="postbox msc-box">
      <h2>Saved Keyword Reports</h2>

      <div style="margin-bottom: 1em;">
        <input type="text" id="msc-new-keyword" placeholder="Add new keyword" style="width: 300px; padding: 0.4em;" />
        <button id="msc-add-keyword" class="button">Add</button>
        <p class="description">Click a saved keyword to run a report. Use the × to remove it.</p>
      </div>

      <div id="msc-keyword-tags">
        <?php foreach ( $keywords as $kw ) : ?>
          <span class="msc-keyword-pill" data-keyword="<?php echo esc_attr( $kw ); ?>">
            <a href="#" class="keyword-run-report" data-keyword="<?php echo esc_attr( $kw ); ?>">
              <?php echo esc_html( $kw ); ?>
            </a>
            <button class="delete-keyword" type="button">&times;</button>
          </span>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Email Test Section -->
    <div class="postbox msc-box">
      <h3>Send Test Email</h3>
      <div class="msc-test-email" style="margin-bottom: 1em; display: flex; gap: 10px; align-items: center;">
        <input type="email" id="msc_test_email" placeholder="Enter email address" style="width: 300px; padding: 5px; height: 36px;" />
        <button id="msc_send_test_email" class="button button-primary" style="height: 36px;">Send Test Email</button>
        <span id="msc_test_status" style="margin-left:10px;"></span>
      </div>
    </div>

    <!-- CSV Export Section -->
    <div class="postbox msc-box">
      <h3>Export Report as CSV</h3>
      <form method="get" action="<?php echo admin_url('admin-ajax.php'); ?>">
        <input type="hidden" name="action" value="msc_export_csv" />

        <p><strong>Keywords:</strong></p>
        <?php foreach ( $keywords as $kw ) : ?>
          <label style="display:block; margin-bottom: 4px;">
            <input type="checkbox" name="keywords[]" value="<?php echo esc_attr( $kw ); ?>" checked>
            <?php echo esc_html( $kw ); ?>
          </label>
        <?php endforeach; ?>

        <p style="margin-top: 1em;"><strong>Post Types:</strong></p>
        <?php foreach ( $post_types as $pt ) : ?>
          <?php if ( $pt->name !== 'attachment' ) : ?>
            <label style="display:block; margin-bottom: 4px;">
              <input type="checkbox" name="post_types[]" value="<?php echo esc_attr( $pt->name ); ?>" checked>
              <?php echo esc_html( $pt->label ); ?>
            </label>
          <?php endif; ?>
        <?php endforeach; ?>

        <?php if ( get_option('msc_enable_media_scan') ) : ?>
          <p style="margin-top: 1em;"><strong>Include PDF results?</strong></p>
          <label style="display:block; margin-bottom: 4px;">
            <input type="checkbox" name="include_pdfs" value="1" checked>
            Yes, include results from scanned PDF files
          </label>
        <?php endif; ?>


        <br>
        <button type="submit" class="button">Download CSV Report</button>
      </form>
    </div>

    <!-- Media Scan Toggle -->
    <div class="postbox msc-box">
      <h3>Advanced Options</h3>
      <form method="post" action="options.php">
        <?php settings_fields('msc_options_group'); ?>
        <label>
          <input type="checkbox" name="msc_enable_media_scan" value="1" <?php checked($media_scan_enabled, true); ?> />
          Enable scanning of PDF and Word document contents (may increase database size)
        </label>
        <br><br>
        <?php submit_button('Save Settings'); ?>
      </form>
    </div>

    <!-- PDF Reindexing Tool -->
    <div class="postbox msc-box">
      <h3>PDF Re-Indexing</h3>
      <p>If you've added new PDF files or want to reindex existing ones, click below to refresh the index.</p>
      <button id="msc-reindex-pdfs" class="button">Re-Index All PDFs</button>
      <div id="msc-reindex-status" style="margin-top: 0.5em;"></div>
    </div>
  </div>

  <!-- Right Sidebar Panels -->
  <div style="width: 320px; flex-shrink: 0;">
    <div class="postbox" style="padding: 1em; margin-bottom: 1em;">
      <h2>About This Plugin</h2>
      <p>This plugin helps your nonprofit monitor site content for sensitive or mission-critical phrases. Stay aligned with your values and protect your organization in uncertain times.</p>
    </div>
    <div class="postbox" style="padding: 1em;">
      <h2>Brought to You By</h2>
      <p><strong>Gas Mark 8, Ltd.</strong></p>
      <p>We build websites, platforms, and AI solutions for nonprofits, educators, and healthcare organizations.</p>
      <p><a href="https://gasmark8.com" target="_blank" class="button">Visit Our Website</a></p>
    </div>
  </div>
</div>
