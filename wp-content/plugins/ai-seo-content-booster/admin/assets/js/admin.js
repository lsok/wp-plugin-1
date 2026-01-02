/**
 * Admin JavaScript
 */
(function($) {
	'use strict';

	// Check if aiscbAdmin is defined
	if (typeof aiscbAdmin === 'undefined') {
		console.error('aiscbAdmin object is not defined. Make sure the script is properly enqueued.');
		return;
	}

	// Get translations from localized script
	var i18n = aiscbAdmin.i18n;

	// Tab switching
	$('.nav-tab').on('click', function(e) {
		e.preventDefault();
		var targetTab = $(this).data('tab');
		
		// Update nav tabs
		$('.nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		
		// Update tab content
		$('.tab-content').removeClass('active').hide();
		$('#' + targetTab).addClass('active').show();
	});

	// Get recommended keywords button
	$('#aiscb-get-keywords-btn').on('click', function() {
		var $btn = $(this);
		var $spinner = $('#aiscb-keywords-spinner');
		var $textarea = $('#aiscb_keywords_list');
		
		$btn.prop('disabled', true);
		$spinner.show();
		
		// TODO: AJAX call to get keywords from Gemini
		// For now, just simulate
		setTimeout(function() {
			// This will be replaced with actual AJAX call
			alert(i18n.getKeywordsPending);
			$btn.prop('disabled', false);
			$spinner.hide();
		}, 1000);
	});

	// Manual keywords management
	var currentPage = 1;
	var perPage = 10;
	var currentSearch = '';
	var keywordsData = [];
	
	// Load keywords from database
	function loadKeywords(page, search) {
		page = page || 1;
		search = search || '';
		
		var $tbody = $('#aiscb-keywords-tbody');
		$tbody.html('<tr><td colspan="4" style="text-align: center; padding: 20px;"><span class="spinner is-active" style="float: none;"></span> ' + wp.i18n.__(i18n.loading, 'ai-seo-content-booster') + '</td></tr>');
		
		$.ajax({
			url: aiscbAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'aiscb_get_keywords', // 此 action 对应 class-admin.php 中的 add_action( 'wp_ajax_aiscb_get_keywords', array( $this, 'ajax_get_keywords' ) );
				nonce: aiscbAdmin.nonce,
				page: page,
				per_page: perPage,
				search: search
			},
			success: function(response) {
				console.log('AJAX Response:', response);
				if (response.success) {
					keywordsData = response.data.keywords;
					currentPage = response.data.pagination.current_page;
					renderKeywordsTable();
					renderPagination(response.data.pagination);
					updateBulkDeleteButton();
				} else {
					var errorMsg = response.data && response.data.message ? response.data.message : '加载失败';
					console.error('Load keywords error:', errorMsg);
					$tbody.html('<tr><td colspan="4" style="text-align: center; padding: 20px; color: #d63638;">' + escapeHtml(errorMsg) + '</td></tr>');
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error:', status, error);
				console.error('Response:', xhr.responseText);
				$tbody.html('<tr><td colspan="4" style="text-align: center; padding: 20px; color: #d63638;">' + escapeHtml('网络错误，请重试') + '</td></tr>');
			}
		});
	}

	// Render keywords table
	function renderKeywordsTable() {
		var $tbody = $('#aiscb-keywords-tbody');
		$tbody.empty();
		
		if (keywordsData.length === 0) {
			$tbody.append('<tr><td colspan="4" style="text-align: center; padding: 20px;">' + escapeHtml(i18n.noKeywords) + '</td></tr>');
			return;
		}
		
		keywordsData.forEach(function(keyword) {
			var $row = $('<tr data-keyword-id="' + keyword.id + '">');
			
			// Checkbox
			$row.append('<th scope="row" class="check-column"><input type="checkbox" class="aiscb-keyword-checkbox" value="' + keyword.id + '" /></th>');
			
			// Keyword
			$row.append('<td>' + escapeHtml(keyword.keyword) + '</td>');
			
			// Status
			var statusText = keyword.status === 'processed' ? i18n.processed : i18n.unprocessed;
			var statusClass = keyword.status === 'processed' ? 'status-processed' : 'status-unprocessed';
			$row.append('<td><span class="' + statusClass + '">' + escapeHtml(statusText) + '</span></td>');
			
			// Actions
			var $actions = $('<td>');
			$actions.append('<button type="button" class="button button-small aiscb-edit-keyword" data-keyword-id="' + keyword.id + '" data-keyword="' + escapeHtml(keyword.keyword) + '">' + escapeHtml(i18n.edit) + '</button>');
			$actions.append('<button type="button" class="button button-small aiscb-delete-keyword" data-keyword-id="' + keyword.id + '">' + escapeHtml(i18n.delete) + '</button>');
			$row.append($actions);
			
			$tbody.append($row);
		});
	}

	// Render pagination
	function renderPagination(pagination) {
		var $pagination = $('#aiscb-keywords-pagination');
		$pagination.empty();
		
		if (pagination.total_pages <= 1) {
			return;
		}
		
		var html = '<div class="aiscb-pagination-wrapper">';
		
		// Previous button
		if (pagination.current_page > 1) {
			html += '<button type="button" class="button aiscb-pagination-btn" data-page="' + (pagination.current_page - 1) + '">' + escapeHtml('上一页') + '</button>';
		} else {
			html += '<button type="button" class="button aiscb-pagination-btn" disabled>' + escapeHtml('上一页') + '</button>';
		}
		
		// Page numbers
		html += '<span class="aiscb-pagination-info">';
		html += escapeHtml('第 ' + pagination.current_page + ' 页，共 ' + pagination.total_pages + ' 页');
		html += '（共 ' + pagination.total_items + ' 条）';
		html += '</span>';
		
		// Next button
		if (pagination.current_page < pagination.total_pages) {
			html += '<button type="button" class="button aiscb-pagination-btn" data-page="' + (pagination.current_page + 1) + '">' + escapeHtml('下一页') + '</button>';
		} else {
			html += '<button type="button" class="button aiscb-pagination-btn" disabled>' + escapeHtml('下一页') + '</button>';
		}
		
		html += '</div>';
		$pagination.html(html);
	}

	// Update bulk delete button visibility
	function updateBulkDeleteButton() {
		var checkedCount = $('.aiscb-keyword-checkbox:checked').length;
		if (checkedCount > 0) {
			$('#aiscb-bulk-delete-btn').show().text( i18n.batchDelete + '(' + checkedCount + ')');
		} else {
			$('#aiscb-bulk-delete-btn').hide();
		}
	}

	// Escape HTML
	function escapeHtml(text) {
		var map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.replace(/[&<>"']/g, function(m) { return map[m]; });
	}

	// Open keywords modal
	$('#aiscb-manual-keywords-btn').on('click', function() {
		currentPage = 1;
		currentSearch = '';
		$('#aiscb-keywords-search').val('');
		$('#aiscb-select-all-keywords').prop('checked', false);
		loadKeywords(1, '');
		$('#aiscb-keywords-modal').show();
	});

	// Close modal
	$('.aiscb-modal-close, #aiscb-cancel-keywords-modal-btn, #aiscb-cancel-keyword-btn').on('click', function() {
		$('.aiscb-modal').hide();
		$('#aiscb-keyword-input').val('').removeData('keyword-id');
		$('#aiscb-keyword-edit-title').text(i18n.addKeyword);
		$('#aiscb-select-all-keywords').prop('checked', false);
	});

	// Close modal when clicking outside
	$(window).on('click', function(e) {
		if ($(e.target).hasClass('aiscb-modal')) {
			$('.aiscb-modal').hide();
			$('#aiscb-keyword-input').val('').removeData('keyword-id');
			$('#aiscb-keyword-edit-title').text(i18n.addKeyword);
			$('#aiscb-select-all-keywords').prop('checked', false);
		}
	});

	// Add keyword button
	$('#aiscb-add-keyword-btn').on('click', function() {
		$('#aiscb-keyword-edit-title').text(i18n.addKeyword);
		$('#aiscb-keyword-input').val('');
		$('#aiscb-keyword-edit-modal').show();
	});

	// Edit keyword
	$(document).on('click', '.aiscb-edit-keyword', function() {
		var keywordId = $(this).data('keyword-id');
		var keyword = $(this).data('keyword');
		$('#aiscb-keyword-edit-title').text(i18n.editKeyword);
		$('#aiscb-keyword-input').val(keyword).data('keyword-id', keywordId);
		$('#aiscb-keyword-edit-modal').show();
	});

	// Delete keyword
	$(document).on('click', '.aiscb-delete-keyword', function() {
		if (!confirm(i18n.confirmDelete)) {
			return;
		}
		
		var keywordId = $(this).data('keyword-id');
		var $row = $(this).closest('tr');
		
		$.ajax({
			url: aiscbAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'aiscb_delete_keyword',
				nonce: aiscbAdmin.nonce,
				keyword_id: keywordId
			},
			success: function(response) {
				if (response.success) {
					loadKeywords(currentPage, currentSearch);
				} else {
					alert(response.data.message || '删除失败');
				}
			},
			error: function() {
				alert('网络错误，请重试');
			}
		});
	});

	// Save keyword (add or edit)
	$('#aiscb-save-keyword-btn').on('click', function() {
		var keyword = $('#aiscb-keyword-input').val().trim();
		if (!keyword) {
			alert(i18n.enterKeyword);
			return;
		}
		
		var keywordId = $('#aiscb-keyword-input').data('keyword-id');
		var action = keywordId ? 'aiscb_edit_keyword' : 'aiscb_add_keyword';
		var data = {
			action: action,
			nonce: aiscbAdmin.nonce,
			keyword: keyword
		};
		
		if (keywordId) {
			data.keyword_id = keywordId;
		}
		
		console.log('Saving keyword:', data);
		
		// Show loading state
		var $btn = $(this);
		var originalText = $btn.text();
		$btn.prop('disabled', true).text('保存中...');
		
		$.ajax({
			url: aiscbAdmin.ajaxUrl,
			type: 'POST',
			data: data,
			success: function(response) {
				console.log('Save keyword response:', response);
				$btn.prop('disabled', false).text(originalText);
				
				if (response.success) {
					$('#aiscb-keyword-edit-modal').hide();
					$('#aiscb-keyword-input').val('').removeData('keyword-id');
					$('#aiscb-keyword-edit-title').text(i18n.addKeyword);
					loadKeywords(currentPage, currentSearch);
					alert(response.data.message || '操作成功');
				} else {
					var errorMsg = response.data && response.data.message ? response.data.message : '操作失败';
					console.error('Save keyword error:', errorMsg);
					alert(errorMsg);
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error:', status, error);
				console.error('Response:', xhr.responseText);
				$btn.prop('disabled', false).text(originalText);
				alert('网络错误，请重试。错误信息: ' + error);
			}
		});
	});

	// Save keywords from modal (close modal)
	$('#aiscb-save-keywords-modal-btn').on('click', function() {
		$('#aiscb-keywords-modal').hide();
	});

	// Search keywords
	var searchTimeout;
	$('#aiscb-keywords-search').on('input', function() {
		clearTimeout(searchTimeout);
		var searchTerm = $(this).val().trim();
		currentSearch = searchTerm;
		currentPage = 1;
		
		searchTimeout = setTimeout(function() {
			loadKeywords(1, searchTerm);
		}, 500);
	});

	// Pagination click
	$(document).on('click', '.aiscb-pagination-btn', function() {
		var page = $(this).data('page');
		if (page && !$(this).prop('disabled')) {
			currentPage = page;
			loadKeywords(page, currentSearch);
		}
	});

	// Select all keywords
	$('#aiscb-select-all-keywords').on('change', function() {
		$('.aiscb-keyword-checkbox').prop('checked', $(this).prop('checked'));
		updateBulkDeleteButton();
	});

	// Individual checkbox change
	$(document).on('change', '.aiscb-keyword-checkbox', function() {
		updateBulkDeleteButton();
		// Update select all checkbox
		var totalCheckboxes = $('.aiscb-keyword-checkbox').length;
		var checkedCheckboxes = $('.aiscb-keyword-checkbox:checked').length;
		$('#aiscb-select-all-keywords').prop('checked', totalCheckboxes === checkedCheckboxes);
	});

	// Bulk delete keywords
	$('#aiscb-bulk-delete-btn').on('click', function() {
		var checkedIds = [];
		$('.aiscb-keyword-checkbox:checked').each(function() {
			checkedIds.push($(this).val());
		});
		
		if (checkedIds.length === 0) {
			alert('请选择要删除的关键词');
			return;
		}
		
		if (!confirm('确定要删除选中的 ' + checkedIds.length + ' 个关键词吗？')) {
			return;
		}
		
		$.ajax({
			url: aiscbAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'aiscb_bulk_delete_keywords',
				nonce: aiscbAdmin.nonce,
				keyword_ids: checkedIds
			},
			success: function(response) {
				if (response.success) {
					$('#aiscb-select-all-keywords').prop('checked', false);
					loadKeywords(currentPage, currentSearch);
					alert(response.data.message || '删除成功');
				} else {
					alert(response.data.message || '删除失败');
				}
			},
			error: function() {
				alert('网络错误，请重试');
			}
		});
	});

	// Form submissions (prevent default for now, backend will be implemented later)
	$('#aiscb-keywords-form, #aiscb-keys-form, #aiscb-article-form').on('submit', function(e) {
		e.preventDefault();
		// TODO: Implement AJAX form submission
		alert(i18n.savePending);
	});

})(jQuery);

