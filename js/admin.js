// assets/admin.js
jQuery(document).ready(function($) {
  // Quick search
  $('#msc-search-form').on('submit', function(e) {
    e.preventDefault();
    const keyword = $('#msc-keyword').val().trim();
    if (!keyword) return;

    $('#msc-search-spinner').show(); // Show spinner

    $.post(msc_ajax.ajax_url, {
      action: 'msc_search_content',
      keyword
    }, function(response) {
      const table = $('#msc-results table');
      const tbody = table.find('tbody');
      tbody.empty();

      if (response.success && response.data.length) {
        response.data.forEach(function(result) {
          const rowHtml = `
            <tr>
              <td>${result.title}</td>
              <td>${result.type}</td>
              <td><a href="${result.link}?highlight=${encodeURIComponent(keyword)}" target="_blank">View</a></td>
            </tr>
          `;
          tbody.append(rowHtml);
        });
      } else {
        tbody.append('<tr><td colspan="3">No matches found.</td></tr>');
      }


      table.show();
      $('#msc-search-spinner').hide(); // Hide spinner after results
    });
  });

  // Add new keyword
  $('#msc-add-keyword').on('click', function(e) {
    e.preventDefault();
    const input = $('#msc-new-keyword');
    const newKeyword = input.val().trim();
    if (!newKeyword) return;

    $.post(msc_ajax.ajax_url, {
      action: 'msc_add_keyword',
      keyword: newKeyword
    }, function(response) {
      if (response.success) {
        const tag = `<span class="msc-keyword-pill" data-keyword="${newKeyword}"><a href="#" class="keyword-run-report" data-keyword="${newKeyword}">${newKeyword}</a><button class="delete-keyword" type="button">&times;</button></span>`;
        $('#msc-keyword-tags').append(tag);
        input.val('');
      } else {
        alert('Failed to add keyword.');
      }
    });
  });

  // Delete keyword
  $(document).on('click', '.delete-keyword', function(e) {
    e.preventDefault();
    const pill = $(this).closest('.msc-keyword-pill');
    const keyword = pill.data('keyword');
    if (!confirm(`Are you sure you want to delete "${keyword}"?`)) return;

    $.post(msc_ajax.ajax_url, {
      action: 'msc_delete_keyword',
      keyword
    }, function(response) {
      if (response.success) {
        pill.fadeOut(200, function() { $(this).remove(); });
      } else {
        alert('Failed to delete keyword.');
      }
    });
  });

  // Run report
  $(document).on('click', '.keyword-run-report', function(e) {
    e.preventDefault();
    const keyword = $(this).data('keyword');
    $('#msc-keyword').val(keyword);
    $('#msc-search-form').submit();
  });

  $('#msc-reindex-pdfs').on('click', function () {
  const status = $('#msc-reindex-status');
  status.text('Indexing in progress…').css('color', 'black');

  $.post(msc_ajax.ajax_url, {
    action: 'msc_reindex_pdfs'
  }, function (response) {
    if (response.success) {
      status.text(response.data).css('color', 'green');
    } else {
      status.text(response.data || 'Reindexing failed.').css('color', 'red');
    }
  });
});


  // Send test email
  $('#msc_send_test_email').on('click', function() {
    const email = $('#msc_test_email').val().trim();
    const status = $('#msc_test_status');
    status.text('Sending...').css('color', 'black');

    $.post(msc_ajax.ajax_url, {
      action: 'msc_send_test_email',
      test_email_to: email
    }, function(response) {
      if (response.success) {
        status.text(response.data).css('color', 'green');
      } else {
        status.text(response.data || 'Failed to send').css('color', 'red');
      }
    });
  });
});
