<?php
// templates/settings-page.php
$media_scan_enabled = get_option('msc_enable_media_scan', false);
$last_reindex = get_option('msc_last_pdf_reindex', '');
?>
<!-- Full-width Header -->
<div class="msc-header">
  <h1 style="margin: 0;color:white;">Mission Safe Check - Settings</h1>
</div>

<div class="wrap msc-layout-container">
  <!-- Left Column -->
  <div class="msc-main-content">
    <!-- Advanced Options Section -->
    <div class="postbox msc-box">
      <h2>Advanced Options</h2>
      <form method="post" action="options.php">
        <?php settings_fields('msc_options_group'); ?>
        <table class="form-table">
          <tr>
            <th scope="row">PDF Scanning</th>
            <td>
              <label>
                <input type="checkbox" name="msc_enable_media_scan" value="1" <?php checked($media_scan_enabled, true); ?> />
                Enable scanning of PDF and Word document contents (may increase database size)
              </label>
              <p class="description">When enabled, the plugin will extract and index text content from PDF files uploaded to your media library.</p>
            </td>
          </tr>
        </table>
        <?php submit_button('Save Settings'); ?>
      </form>
    </div>

    <!-- PDF Re-indexing Section -->
    <div class="postbox msc-box">
      <h2>PDF Re-Indexing</h2>
      <p>If you've added new PDF files or want to reindex existing ones, click below to refresh the index.</p>
      
      <?php if ( $last_reindex ) : ?>
        <p><strong>Last re-indexed:</strong> <?php echo esc_html( date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime( $last_reindex ) ) ); ?></p>
      <?php else : ?>
        <p><em>No re-indexing has been performed yet.</em></p>
      <?php endif; ?>
      
      <button id="msc-reindex-pdfs" class="button button-primary">Re-Index All PDFs</button>
      <div id="msc-reindex-status" style="margin-top: 0.5em;"></div>
    </div>
  </div>

  <!-- Right Sidebar Panels -->
  <div class="msc-sidebar">
    <div class="postbox" style="padding: 1em; margin-bottom: 1em;">
      <h2>About Settings</h2>
      <p>Configure advanced options for Mission Safe Check, including PDF scanning and re-indexing tools.</p>
    </div>
  </div>
</div>

