<?php
// templates/email-page.php
$schedule_enabled = get_option('msc_email_schedule_enabled', false);
$schedule_frequency = get_option('msc_email_schedule_frequency', 'weekly');
$email_recipient = get_option('msc_email_recipient', get_option('admin_email'));
$settings_updated = isset($_GET['settings-updated']);
?>
<!-- Full-width Header -->
<div class="msc-header">
  <h1 style="margin: 0;color:white;">Mission Safe Check - Email Settings</h1>
</div>

<?php if ($settings_updated) : ?>
  <div class="notice notice-success is-dismissible">
    <p>Email schedule settings saved successfully.</p>
  </div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_emails' && isset($_GET['invalid'])) : ?>
  <div class="notice notice-error is-dismissible">
    <p><strong>Error:</strong> The following email address(es) are invalid: <?php echo esc_html(urldecode($_GET['invalid'])); ?>. Please correct them and try again.</p>
  </div>
<?php endif; ?>

<div class="wrap msc-layout-container">
  <!-- Left Column -->
  <div class="msc-main-content">
    <!-- One-off Email Section -->
    <div class="postbox msc-box">
      <h2>Send One-Off Email</h2>
      <p>Send a test email with current keyword results to any email address.</p>
      <div class="msc-test-email" style="margin-bottom: 1em; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <input type="email" id="msc_oneoff_email" placeholder="Enter email address" style="width: 300px; padding: 5px; height: 36px;" />
        <button id="msc_send_oneoff_email" class="button button-primary" style="height: 36px;">Send Email</button>
        <span id="msc_oneoff_status" style="margin-left:10px;"></span>
      </div>
    </div>

    <!-- Scheduled Email Settings Section -->
    <div class="postbox msc-box">
      <h2>Scheduled Email Reports</h2>
      <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
        <input type="hidden" name="action" value="msc_save_email_schedule" />
        <?php wp_nonce_field('msc_save_email_schedule'); ?>
        
        <table class="form-table">
          <tr>
            <th scope="row">Enable Scheduled Emails</th>
            <td>
              <label>
                <input type="checkbox" name="msc_email_schedule_enabled" value="1" <?php checked($schedule_enabled, true); ?> />
                Send automated email reports on a schedule
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="msc_email_schedule_frequency">Frequency</label></th>
            <td>
              <select name="msc_email_schedule_frequency" id="msc_email_schedule_frequency" style="width: 200px;">
                <option value="daily" <?php selected($schedule_frequency, 'daily'); ?>>Daily</option>
                <option value="weekly" <?php selected($schedule_frequency, 'weekly'); ?>>Weekly</option>
                <option value="monthly" <?php selected($schedule_frequency, 'monthly'); ?>>Monthly</option>
              </select>
              <p class="description">How often should automated reports be sent?</p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="msc_email_recipient">Recipient Email</label></th>
            <td>
              <input type="text" name="msc_email_recipient" id="msc_email_recipient" value="<?php echo esc_attr($email_recipient); ?>" style="width: 300px;" />
              <p class="description">Email address(es) to receive scheduled reports. Separate multiple recipients with commas. Defaults to site admin email.</p>
            </td>
          </tr>
        </table>
        
        <?php submit_button('Save Schedule Settings'); ?>
      </form>
    </div>
  </div>

  <!-- Right Sidebar Panels -->
  <div class="msc-sidebar">
    <div class="postbox" style="padding: 1em; margin-bottom: 1em;">
      <h2>About Email Reports</h2>
      <p>Configure how and when Mission Safe Check sends email reports with keyword search results.</p>
      <p>One-off emails can be sent immediately for testing or ad-hoc reporting.</p>
      <p>Scheduled emails will automatically send reports based on your saved keywords.</p>
    </div>
  </div>
</div>

