<?php
// templates/admin-page.php
$keywords = msc_get_all_keywords();
$post_types = get_post_types(['public' => true], 'objects');
?>
<!-- Full-width Header -->
<div class="msc-header">
  <h1 style="margin: 0;color:white;">Mission Safe Check</h1>
</div>

<?php if (empty($keywords)) : ?>
  <div style="margin: 20px 20px 20px 0; padding: 15px 20px; background: #ffdcff; border-left: 4px solid #660066; border-radius: 4px;" id="msc-welcome-notice">
    <h3 style="margin-top: 0; color: #660066;">Welcome to Mission Safe Check!</h3>
    <p style="margin-bottom: 0.5em;"><strong>Get started in 3 steps:</strong></p>
    <ol style="margin-bottom: 1em; padding-left: 20px;">
      <li style="margin-bottom: 0.5em;"><strong>Step 1:</strong> Do a quick search of your content or add keywords in the "Saved Keyword Reports" area below</li>
      <li style="margin-bottom: 0.5em;"><strong>Step 2:</strong> Click on a saved keyword to do an instant search for that term</li>
      <li style="margin-bottom: 0.5em;"><strong>Step 3:</strong> Export a report, send a one-off report email, or set up scheduled email reports</li>
    </ol>
    <p style="margin-bottom: 0;"><a href="<?php echo esc_url( admin_url('admin.php?page=mission-safe-check-email') ); ?>" class="button button-primary">Set Up Email Reports →</a></p>
  </div>
<?php endif; ?>

<div class="wrap msc-layout-container">
  <!-- Left Column -->
  <div class="msc-main-content">
    <!-- Quick Search Section -->
    <div id="msc-quick-search" class="postbox msc-box">
      <h2>Quick Search</h2>
      <p class="description" style="margin-bottom: 1em;">Search your site content instantly. Results appear below.</p>
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
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1em;">
        <h2 style="margin: 0;">Saved Keywords</h2>
        <?php if (!empty($keywords)) : ?>
          <a href="<?php echo esc_url( admin_url('admin.php?page=mission-safe-check-email') ); ?>" class="button button-primary" style="float: none;">Send Email Report →</a>
        <?php endif; ?>
      </div>
      <p class="description" style="margin-bottom: 1em;">Save keywords to monitor regularly. Use them for CSV exports and email reports.</p>

      <div style="margin-bottom: 1em;">
        <input type="text" id="msc-new-keyword" placeholder="Add new keyword" style="width: 300px; padding: 0.4em;" />
        <button id="msc-add-keyword" class="button">Add</button>
        <p class="description" style="margin-top: 0.5em;">Click a saved keyword to run a report. Use the × to remove it.</p>
      </div>

      <div id="msc-keyword-tags">
        <?php if (!empty($keywords)) : ?>
          <?php foreach ( $keywords as $kw ) : ?>
            <span class="msc-keyword-pill" data-keyword="<?php echo esc_attr( $kw ); ?>">
              <a href="#" class="keyword-run-report" data-keyword="<?php echo esc_attr( $kw ); ?>">
                <?php echo esc_html( $kw ); ?>
              </a>
              <button class="delete-keyword" type="button">&times;</button>
            </span>
          <?php endforeach; ?>
        <?php else : ?>
          <p class="description" style="color: #666; font-style: italic;">No keywords saved yet. Add your first keyword above to get started.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- CSV Export Section -->
    <?php if (!empty($keywords)) : ?>
      <div class="postbox msc-box">
        <h3>Export Report as CSV</h3>
        <p class="description" style="margin-bottom: 1em;">Export a report of all matches for your saved keywords. Select keywords and post types below.</p>
        <form method="get" action="<?php echo esc_url( admin_url('admin-ajax.php') ); ?>">
          <input type="hidden" name="action" value="msc_export_csv" />
          <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce('msc_ajax_nonce') ); ?>" />

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
    <?php else : ?>
      <div class="postbox msc-box">
        <h3>Export Report as CSV</h3>
        <p class="description">Add keywords above to enable CSV export.</p>
      </div>
    <?php endif; ?>

  </div>

  <!-- Right Sidebar Panels -->
  <div class="msc-sidebar">
    <?php if (!empty($keywords)) : ?>
      <div class="postbox" style="padding: 1em; margin-bottom: 1em; background: #ffdcff; border-left: 4px solid #660066;">
        <h3 style="margin-top: 0; color: #660066;">Quick Start</h3>
        <p style="margin-bottom: 0.5em; font-size: 13px;">1. Add keywords to monitor</p>
        <p style="margin-bottom: 0.5em; font-size: 13px;">2. Click keywords to search</p>
        <p style="margin-bottom: 0; font-size: 13px;">3. Export or email reports</p>
      </div>
    <?php endif; ?>
    
    <div class="postbox" style="padding: 1em; margin-bottom: 1em;">
      <h2>About This Plugin</h2>
      <p>This plugin helps your nonprofit monitor site content for sensitive or mission-critical phrases. Stay aligned with your values and protect your organization in uncertain times.</p>
    </div>
    
    <div class="postbox" style="padding: 1.5em;">
      <h2 style="margin-top: 0;">Made with <span style="color: #660066;">❤</span> by Gas Mark 8</h2>
      
      <p style="margin: 0.5em 0;">We build websites, platforms, and AI solutions for nonprofits, educators, and healthcare organizations.</p>
      <p style="margin: 1em 0 0 0;"><a href="https://www.gasmark8.com/?utm_source=mission-safe-check&utm_medium=button&utm_campaign=Mission+Safe+Check+Plugin&utm_content=Sidebar+About+GM8" target="_blank" class="button">Visit Our Website</a></p>
    </div>
  </div>
</div>
