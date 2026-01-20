/**
 * Admin JavaScript
 */
(function ($) {
	'use strict';

	// Check if aiscbAdmin is defined
	if (typeof aiscbAdmin === 'undefined') {
		console.error('aiscbAdmin object is not defined. Make sure the script is properly enqueued.');
		return;
	}

	// Get translations from localized script
	var i18n = aiscbAdmin.i18n;

	// Tab switching
	$('.nav-tab').on('click', function (e) {
		e.preventDefault();
		var targetTab = $(this).data('tab');

		// Update nav tabs
		$('.nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');

		// Update tab content
		$('.tab-content').removeClass('active').hide();
		$('#' + targetTab).addClass('active').show();

		// If social tab activated, show posts list by default
		if (targetTab === 'social-tab') {
			$('#aiscb-social-form-wrap').hide();
			$('#aiscb-posts-wrap').show();
			postsCurrentPage = 1;
			postsCurrentSearch = '';
			$('#aiscb-posts-search').val('');
			loadAiscbPosts(postsCurrentPage, postsCurrentSearch);
			setPostsHeaderActive('list');
		}
	});

	// Get recommended keywords button
	$('#aiscb-get-keywords-btn').on('click', function () {
		var $btn = $(this);
		var $spinner = $('#aiscb-keywords-spinner');
		var $textarea = $('#aiscb_keywords_list');

		$btn.prop('disabled', true);
		$spinner.show();

		// TODO: AJAX call to get keywords from Gemini
		// For now, just simulate
		setTimeout(function () {
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

// Social attachments state
var aiscbSocialAttachments = [];

	// Show existing keywords
	function existingKeywords() {
		var $existingKeywords = $('#existing-keywords');
		$existingKeywords.html('<span class="spinner is-active" style="float: none;position:relative;top:-3px"></span> ' + wp.i18n.__(i18n.loading, 'ai-seo-content-booster'));

		$.ajax({
			url: aiscbAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'aiscb_get_existing_keywords',
				nonce: aiscbAdmin.nonce
			},
			success: function (response) {
				if (response.success) {
					var keywords = response.data.keywords;
					var keywordStrings = keywords.map(function (kw) { return kw.keyword; });
					if (keywordStrings.length === 0) {
						var existingKeywordsHtml = escapeHtml(i18n.noKeywords);
					} else {
						var keywordList = keywordStrings.join(', ');
						var existingKeywordsHtml = escapeHtml(keywordList) + '...';
					}
					$existingKeywords.html('<p>' + existingKeywordsHtml + '</p>');
				} else {
					var errorMsg = response.data && response.data.message ? response.data.message : i18n.loadFailed;
					console.error('Load existing keywords error:', errorMsg);
					$existingKeywords.html('<p style="color: #d63638;">' + escapeHtml(errorMsg) + '</p>');
				}
			},
			error: function (xhr, status, error) {
				console.error('AJAX Error:', status, error);
				console.error('Response:', xhr.responseText);
				$existingKeywords.html('<p style="color: #d63638;">' + escapeHtml(i18n.networkError) + '</p>');
			}
		});
	}

	// Load keywords from database
	function loadKeywords(page, search) {
		page = page || 1;
		search = search || '';

		var $tbody = $('#aiscb-keywords-tbody');
		$tbody.html('<tr><td colspan="4" style="text-align: center; padding: 20px;"><span class="spinner is-active" style="float: none;position:relative;top:-3px"></span> ' + wp.i18n.__(i18n.loading, 'ai-seo-content-booster') + '</td></tr>');

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
			success: function (response) {
				console.log('AJAX Response:', response);
				if (response.success) {
					keywordsData = response.data.keywords;
					currentPage = response.data.pagination.current_page;
					renderKeywordsTable();
					renderPagination(response.data.pagination);
					updateBulkDeleteButton();
				} else {
					var errorMsg = response.data && response.data.message ? response.data.message : i18n.loadFailed;
					console.error('Load keywords error:', errorMsg);
					$tbody.html('<tr><td colspan="4" style="text-align: center; padding: 20px; color: #d63638;">' + escapeHtml(errorMsg) + '</td></tr>');
				}
			},
			error: function (xhr, status, error) {
				console.error('AJAX Error:', status, error);
				console.error('Response:', xhr.responseText);
				$tbody.html('<tr><td colspan="4" style="text-align: center; padding: 20px; color: #d63638;">' + escapeHtml(i18n.networkError) + '</td></tr>');
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

		keywordsData.forEach(function (keyword) {
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
			html += '<button type="button" class="button aiscb-pagination-btn" data-page="' + (pagination.current_page - 1) + '">' + escapeHtml(i18n.prevPage) + '</button>';
		} else {
			html += '<button type="button" class="button aiscb-pagination-btn" disabled>' + escapeHtml(i18n.prevPage) + '</button>';
		}

		// Page numbers
		html += '<span class="aiscb-pagination-info">';
		html += escapeHtml(i18n.pagePrefix + pagination.current_page + i18n.pageSeparator + pagination.total_pages + i18n.pageSuffix);
		html += i18n.totalItemsPrefix + pagination.total_items + i18n.totalItemsSuffix;
		html += '</span>';

		// Next button
		if (pagination.current_page < pagination.total_pages) {
			html += '<button type="button" class="button aiscb-pagination-btn" data-page="' + (pagination.current_page + 1) + '">' + escapeHtml(i18n.nextPage) + '</button>';
		} else {
			html += '<button type="button" class="button aiscb-pagination-btn" disabled>' + escapeHtml(i18n.nextPage) + '</button>';
		}

		html += '</div>';
		$pagination.html(html);
	}

	// Update bulk delete button visibility
	function updateBulkDeleteButton() {
		var checkedCount = $('.aiscb-keyword-checkbox:checked').length;
		if (checkedCount > 0) {
			$('#aiscb-bulk-delete-btn').show().text(i18n.batchDelete + '(' + checkedCount + ')');
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
		return text.replace(/[&<>"']/g, function (m) { return map[m]; });
	}

	// Open keywords modal
	$('#aiscb-manual-keywords-btn').on('click', function () {
		currentPage = 1;
		currentSearch = '';
		$('#aiscb-keywords-search').val('');
		$('#aiscb-select-all-keywords').prop('checked', false);
		loadKeywords(1, '');
		$('#aiscb-keywords-modal').show();
	});

	// Close modal
	$('.aiscb-modal-close, #aiscb-cancel-keyword-btn').on('click', function () {
		if ($(this).attr('id') === 'aiscb-cancel-keyword-btn') {
			$('#aiscb-keyword-edit-modal').hide();
		} else {
			$('.aiscb-modal').hide();
		}
		$('#aiscb-keyword-input').val('').removeData('keyword-id');
		$('#aiscb-keyword-edit-title').text(i18n.addKeyword);
		$('#aiscb-select-all-keywords').prop('checked', false);
	});

	// Close modal when clicking outside
	$(window).on('click', function (e) {
		if ($(e.target).hasClass('aiscb-modal')) {
			$('.aiscb-modal').hide();
			$('#aiscb-keyword-input').val('').removeData('keyword-id');
			$('#aiscb-keyword-edit-title').text(i18n.addKeyword);
			$('#aiscb-select-all-keywords').prop('checked', false);
		}
	});

	// Add keyword button
	$('#aiscb-add-keyword-btn').on('click', function () {
		$('#aiscb-keyword-edit-title').text(i18n.addKeyword);
		$('#aiscb-keyword-input').val('').show();
		$('#aiscb-keyword-input-single').hide().removeData('keyword-id');
		$('#aiscb-keyword-description').show();
		$('#aiscb-keyword-edit-modal').show();
	});


	// Import keywords: show import modal
	$('#aiscb-import-keywords-btn').on('click', function () {
		$('#aiscb-import-modal').show();
	});

	// Cancel import or close modal
	$('#aiscb-cancel-import-btn').on('click', function () {
		$('#aiscb-import-modal').hide();
		$('#aiscb-import-file').val('');
	});

	// Close modal when clicking close icon
	$(document).on('click', '#aiscb-import-modal .aiscb-modal-close', function () {
		$('#aiscb-import-modal').hide();
		$('#aiscb-import-file').val('');
	});

	// When user selects a file, request headers (preview) to populate column select
	$('#aiscb-import-file').on('change', function () {
		var input = this;
		if (!input.files || input.files.length === 0) {
			return;
		}

		var file = input.files[0];
		var formData = new FormData();
		formData.append('action', 'aiscb_import_preview');
		formData.append('nonce', aiscbAdmin.nonce);
		formData.append('keyword_file', file);

		var $selectWrap = $('#aiscb-import-column-wrap');
		var $select = $('#aiscb-import-column-select');
		$selectWrap.hide();
		$select.empty();

		$.ajax({
			url: aiscbAdmin.ajaxUrl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function (response) {
				if (response.success && response.data && Array.isArray(response.data.headers) && response.data.headers.length > 0) {
					var headers = response.data.headers;
					$select.append('<option value="">' + escapeHtml('请选择列') + '</option>');
					headers.forEach(function (h, idx) {
						$select.append('<option value="' + idx + '">' + escapeHtml(h) + '</option>');
					});
					$selectWrap.show();
				} else {
					alert(response.data && response.data.message ? response.data.message : i18n.loadFailed);
				}
			},
			error: function (xhr, status, error) {
				console.error('Preview AJAX Error:', status, error);
				alert(i18n.networkError);
			}
		});
	});

	// Perform import when user clicks import in modal
	$('#aiscb-do-import-btn').on('click', function () {
		var input = document.getElementById('aiscb-import-file');
		if (!input || !input.files || input.files.length === 0) {
			alert(i18n.enterKeyword);
			return;
		}

		var colIndex = $('#aiscb-import-column-select').val();
		if (!colIndex && colIndex !== '0') {
			alert('请选择要导入的列');
			return;
		}

		var file = input.files[0];
		var formData = new FormData();
		formData.append('action', 'aiscb_import_keywords');
		formData.append('nonce', aiscbAdmin.nonce);
		formData.append('keyword_file', file);
		formData.append('col_index', colIndex);

		// Disable import button while uploading
		var $btn = $('#aiscb-do-import-btn');
		var originalText = $btn.text();
		$btn.prop('disabled', true).text(i18n.loading);

		$.ajax({
			url: aiscbAdmin.ajaxUrl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function (response) {
				$btn.prop('disabled', false).text(originalText);
				if (response.success) {
					alert(response.data.message || i18n.operationSuccess);
					$('#aiscb-import-modal').hide();
					$('#aiscb-import-file').val('');
					$('#aiscb-import-column-wrap').hide();
					loadKeywords(currentPage, currentSearch);
					existingKeywords();
				} else {
					alert(response.data && response.data.message ? response.data.message : i18n.operationFailed);
				}
			},
			error: function (xhr, status, error) {
				$btn.prop('disabled', false).text(originalText);
				console.error('Import AJAX Error:', status, error);
				alert(i18n.networkError);
			}
		});
	});

	// Edit keyword
	$(document).on('click', '.aiscb-edit-keyword', function () {
		var keywordId = $(this).data('keyword-id');
		var keyword = $(this).data('keyword');
		$('#aiscb-keyword-edit-title').text(i18n.editKeyword);
		$('#aiscb-keyword-input-single').val(keyword).data('keyword-id', keywordId).show();
		$('#aiscb-keyword-input').hide();
		$('#aiscb-keyword-description').hide();
		$('#aiscb-keyword-edit-modal').show();
	});

	// Delete keyword
	$(document).on('click', '.aiscb-delete-keyword', function () {
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
			success: function (response) {
				if (response.success) {
					loadKeywords(currentPage, currentSearch);
					existingKeywords(); // Refresh existing keywords list
				} else {
					alert(response.data.message || i18n.deleteFailed);
				}
			},
			error: function () {
				alert(i18n.networkError);
			}
		});
	});

	// Save keyword (add or edit)
	$('#aiscb-save-keyword-btn').on('click', function () {
		var isEdit = !!$('#aiscb-keyword-input-single').data('keyword-id');
		var keywordInput = isEdit ? $('#aiscb-keyword-input-single').val().trim() : $('#aiscb-keyword-input').val().trim();
		if (!keywordInput) {
			alert(i18n.enterKeyword);
			return;
		}

		if (isEdit) {
			// Edit single keyword
			var keywordId = $('#aiscb-keyword-input-single').data('keyword-id');
			
			var data = {
				action: 'aiscb_edit_keyword',
				nonce: aiscbAdmin.nonce,
				keyword: keywordInput,
				keyword_id: keywordId
			};

			console.log('Editing keyword:', data);

			// Show loading state
			var $btn = $(this);
			var originalText = $btn.text();
			$btn.prop('disabled', true).text(i18n.saving);

			$.ajax({
				url: aiscbAdmin.ajaxUrl,
				type: 'POST',
				data: data,
				success: function (response) {
					console.log('Edit keyword response:', response);
					$btn.prop('disabled', false).text(originalText);

					if (response.success) {
						$('#aiscb-keyword-edit-modal').hide();
						$('#aiscb-keyword-input').val('').show();
						$('#aiscb-keyword-input-single').val('').hide().removeData('keyword-id');
						$('#aiscb-keyword-description').show();
						$('#aiscb-keyword-edit-title').text(i18n.addKeyword);
						loadKeywords(currentPage, currentSearch);
						existingKeywords(); // Refresh existing keywords list
						//alert(response.data.message || i18n.operationSuccess);
					} else {
						var errorMsg = response.data && response.data.message ? response.data.message : i18n.operationFailed;
						console.error('Edit keyword error:', errorMsg);
						alert(errorMsg);
					}
				},
				error: function (xhr, status, error) {
					console.error('AJAX Error:', status, error);
					console.error('Response:', xhr.responseText);
					$btn.prop('disabled', false).text(originalText);
					alert(i18n.networkErrorWithMsg + error);
				}
			});
		} else {
			// Add multiple keywords (batch)
			var keywords = keywordInput.split('\n').map(function(kw) { return kw.trim(); }).filter(function(kw) { return kw.length > 0; });
			if (keywords.length === 0) {
				alert(i18n.enterKeyword);
				return;
			}

			console.log('Adding keywords:', keywords);

			// Show loading state
			var $btn = $(this);
			var originalText = $btn.text();
			$btn.prop('disabled', true).text(i18n.saving);

			var data = {
				action: 'aiscb_add_keywords_batch',
				nonce: aiscbAdmin.nonce,
				keywords: keywords
			};

			$.ajax({
				url: aiscbAdmin.ajaxUrl,
				type: 'POST',
				data: data,
				success: function (response) {
					console.log('Batch add response:', response);
					$btn.prop('disabled', false).text(originalText);

					if (response.success) {
						$('#aiscb-keyword-edit-modal').hide();
						$('#aiscb-keyword-input').val('').show();
						$('#aiscb-keyword-input-single').val('').hide().removeData('keyword-id');
						$('#aiscb-keyword-description').show();
						loadKeywords(currentPage, currentSearch);
						existingKeywords(); // Refresh existing keywords list
						alert(response.data.message);
					} else {
						alert(response.data.message || i18n.operationFailed);
					}
				},
				error: function (xhr, status, error) {
					console.error('AJAX Error:', status, error);
					$btn.prop('disabled', false).text(originalText);
					alert(i18n.networkError);
				}
			});
		}
	});

	// Save keywords from modal (close modal)
	$('#aiscb-save-keywords-modal-btn').on('click', function () {
		$('#aiscb-keywords-modal').hide();
	});

	// Search keywords
	var searchTimeout;
	$('#aiscb-keywords-search').on('input', function () {
		clearTimeout(searchTimeout);
		var searchTerm = $(this).val().trim();
		currentSearch = searchTerm;
		currentPage = 1;

		searchTimeout = setTimeout(function () {
			loadKeywords(1, searchTerm);
		}, 500);
	});

	// Pagination click
	$(document).on('click', '.aiscb-pagination-btn', function () {
		var page = $(this).data('page');
		if (page && !$(this).prop('disabled')) {
			currentPage = page;
			loadKeywords(page, currentSearch);
		}
	});

	// Select all keywords
	$('#aiscb-select-all-keywords').on('change', function () {
		$('.aiscb-keyword-checkbox').prop('checked', $(this).prop('checked'));
		updateBulkDeleteButton();
	});

	// Individual checkbox change
	$(document).on('change', '.aiscb-keyword-checkbox', function () {
		updateBulkDeleteButton();
		// Update select all checkbox
		var totalCheckboxes = $('.aiscb-keyword-checkbox').length;
		var checkedCheckboxes = $('.aiscb-keyword-checkbox:checked').length;
		$('#aiscb-select-all-keywords').prop('checked', totalCheckboxes === checkedCheckboxes);
	});

	// Bulk delete keywords
	$('#aiscb-bulk-delete-btn').on('click', function () {
		var checkedIds = [];
		$('.aiscb-keyword-checkbox:checked').each(function () {
			checkedIds.push($(this).val());
		});

		if (checkedIds.length === 0) {
			alert(i18n.selectKeywordsToDelete);
			return;
		}

		if (!confirm(i18n.confirmDeleteBulkPrefix + checkedIds.length + i18n.confirmDeleteBulkSuffix)) {
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
			success: function (response) {
				if (response.success) {
					$('#aiscb-select-all-keywords').prop('checked', false);
					loadKeywords(currentPage, currentSearch);
					existingKeywords(); // Refresh existing keywords list
					alert(response.data.message);
				} else {
					alert(response.data.message || i18n.deleteFailed);
				}
			},
			error: function () {
				alert(i18n.networkError);
			}
		});
	});

	// Form submissions (prevent default for now, backend will be implemented later)
	$('#aiscb-keywords-form, #aiscb-article-form').on('submit', function (e) {
		e.preventDefault();
		// TODO: Implement AJAX form submission
		alert(i18n.savePending);
	});

	// Open media library to add attachments
	$('#aiscb-add-attachment-btn').on('click', function (e) {
		e.preventDefault();
		var frame = wp.media({
			title: i18n.chooseMedia || 'Choose Media',
			button: { text: i18n.chooseMedia || 'Choose' },
			multiple: true,
			library: { type: ['image', 'video'] }
		});

		frame.on('select', function () {
			var selection = frame.state().get('selection');
			selection.each(function (attachment) {
				attachment = attachment.toJSON();
				// Keep id and url
				aiscbSocialAttachments.push({ id: attachment.id || 0, url: attachment.url || '', mime: attachment.mime || '' });
			});
			renderAiscbAttachments();
		});

		frame.open();
	});

// Manual URL input for attachments: show/hide and add/cancel handlers
$(document).on('click', '#aiscb-add-attachment-url-link', function (e) {
		e.preventDefault();
		var $wrap = $('#aiscb-add-attachment-url-wrap');
		$wrap.toggle();
		if ($wrap.is(':visible')) { $('#aiscb-attachment-url-input').focus(); }
});

// Cancel manual URL input
$(document).on('click', '#aiscb-attachment-url-cancel-btn', function (e) {
		e.preventDefault();
 		$('#aiscb-attachment-url-input').val('');
 		$('#aiscb-add-attachment-url-wrap').hide();
});

// Add manual URL as attachment
$(document).on('click', '#aiscb-attachment-url-add-btn', function (e) {
		e.preventDefault();
 		var url = ($('#aiscb-attachment-url-input').val() || '').trim();
 		if (!url) { alert(i18n.enterAttachmentUrl || '请输入附件 URL'); return; }

 		// Basic URL validation
 		if (!/^https?:\/\//i.test(url)) { alert(i18n.invalidUrl || '请输入有效的 URL（以 http:// 或 https:// 开头）'); return; }

 		// Guess mime by extension
 		var imgExt = /\.(jpg|jpeg|png|gif|webp|avif|svg)(\?|$)/i;
 		var vidExt = /\.(mp4|webm|ogg|mov|avi)(\?|$)/i;
 		var mime = '';
 		if (imgExt.test(url)) { mime = 'image/*'; }
 		else if (vidExt.test(url)) { mime = 'video/*'; }

 		aiscbSocialAttachments.push({ id: 0, url: url, mime: mime });
 		renderAiscbAttachments();
 		// hide and clear input
 		$('#aiscb-attachment-url-input').val('');
 		$('#aiscb-add-attachment-url-wrap').hide();
});

	// Render attachments list
	function renderAiscbAttachments() {
		var $wrap = $('#aiscb-attachments-list');
		$wrap.empty();
		if (!aiscbSocialAttachments || aiscbSocialAttachments.length === 0) {
			$wrap.html('<p>' + (i18n.noAttachments || '暂无附件') + '</p>');
			return;
		}

		aiscbSocialAttachments.forEach(function (att, idx) {
			var $item = $('<div class="aiscb-attachment-item" data-idx="' + idx + '" style="margin-bottom:10px; display:flex; align-items:center; gap:10px;"></div>');

			var url = att.url || '';
			var isImage = (att.mime && att.mime.indexOf('image') === 0) || /\.(jpg|jpeg|png|gif|webp|avif|svg)(\?|$)/i.test(url);
			var isVideo = (att.mime && att.mime.indexOf('video') === 0) || /video|\.mp4|\.webm|\.ogg|\.mov|\.avi/i.test(url);

			if (isImage && url) {
				$item.append('<img src="' + url + '" style="max-width:120px; max-height:80px; object-fit:cover;" alt="attachment" />');
			} else if (isVideo && url) {
				$item.append('<div style="word-break:break-all;">' + escapeHtml(url) + '</div>');
			} else if (url) {
				$item.append('<div style="word-break:break-all;">' + escapeHtml(url) + '</div>');
			} else {
				$item.append('<div>' + escapeHtml(i18n.noAttachments || '暂无附件') + '</div>');
			}

			$item.append('<button type="button" class="button aiscb-remove-attachment" data-idx="' + idx + '">' + (i18n.removeAttachment || '移除') + '</button>');
			$wrap.append($item);
		});
	}

	// Remove attachment
	$(document).on('click', '.aiscb-remove-attachment', function () {
		var idx = $(this).data('idx');
		if (typeof idx !== 'undefined') {
			aiscbSocialAttachments.splice(idx, 1);
			renderAiscbAttachments();
		}
	});

	// Save social post via AJAX
	$('#aiscb-social-save-btn').on('click', function (e) {
		e.preventDefault();
		var content = $('#aiscb_social_content').val().trim();
		var platforms = [];
		$('input[name="aiscb_social_platforms[]"]:checked').each(function () { platforms.push($(this).val()); });
		var postId = $('#aiscb_social_id').val() || '';

		// Client-side validation
		if (!content) {
			alert('帖子内容不能为空');
			return;
		}
		if (!platforms || platforms.length === 0) {
			alert('请至少选择一个平台');
			return;
		}

		var $btn = $(this);
		var originalText = $btn.text();
		$btn.prop('disabled', true).text(i18n.saveSocialPending || '保存中...');

		$.ajax({
			url: aiscbAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'aiscb_save_social_post',
				nonce: aiscbAdmin.nonce,
				id: postId,
				content: content,
				attachments: JSON.stringify(aiscbSocialAttachments),
				platforms: platforms
			},
			success: function (response) {
				$btn.prop('disabled', false).text(originalText);
				if (response.success) {
					alert(response.data && response.data.message ? response.data.message : (i18n.saveSocialSuccess || '保存成功'));
					// clear form and id
					$('#aiscb_social_content').val('');
					$('input[name="aiscb_social_platforms[]"]').prop('checked', false);
					aiscbSocialAttachments = [];
					renderAiscbAttachments();
					$('#aiscb_social_id').val('');
					// always switch back to list view and refresh
					$('#aiscb-social-form-wrap').hide();
					$('#aiscb-posts-wrap').show();
					postsCurrentPage = 1;
					postsCurrentSearch = '';
					$('#aiscb-posts-search').val('');
					setPostsHeaderActive('list');
					loadAiscbPosts(postsCurrentPage, postsCurrentSearch);
					// update header count
					updateAiscbPostsCount();
				} else {
					alert(response.data && response.data.message ? response.data.message : (i18n.saveSocialFailed || '保存失败'));
				}
			},
			error: function (xhr, status, error) {
				$btn.prop('disabled', false).text(originalText);
				console.error('AJAX Error:', status, error);
				alert(i18n.networkError || '网络错误');
			}
		});
	});

	// Posts list state
	var postsCurrentPage = 1;
	var postsPerPage = 10;
	var postsCurrentSearch = '';

	// Show list (全部) and refresh - delegated to support links in both list and form header
	$(document).on('click', '.aiscb-posts-all-link', function (e) {
		e.preventDefault();
		$('#aiscb-social-form-wrap').hide();
		$('#aiscb-posts-wrap').show();
		postsCurrentPage = 1;
		postsCurrentSearch = '';
		$('#aiscb-posts-search').val('');
		loadAiscbPosts(postsCurrentPage, postsCurrentSearch);
	});

	// Show add form - delegated
	$(document).on('click', '.aiscb-posts-add-link', function (e) {
		e.preventDefault();
		$('#aiscb-posts-wrap').hide();
		$('#aiscb-social-form-wrap').show();
		// clear form
		$('#aiscb_social_id').val('');
		$('#aiscb_social_content').val('');
		$('input[name="aiscb_social_platforms[]"]').prop('checked', false);
		aiscbSocialAttachments = [];
		renderAiscbAttachments();
		setPostsHeaderActive('add');
	});

	// Toggle header active state
	function setPostsHeaderActive(mode) {
		// mode: 'list' or 'add'
		if (mode === 'list') {
			$('.aiscb-posts-all-link').addClass('active');
			$('.aiscb-posts-add-link').removeClass('active');
		} else if (mode === 'add') {
			$('.aiscb-posts-add-link').addClass('active');
			$('.aiscb-posts-all-link').removeClass('active');
		}
	}

	// Update posts count element text
	function updateAiscbPostsCount() {
		$.ajax({
			url: aiscbAdmin.ajaxUrl,
			type: 'POST',
			data: { action: 'aiscb_get_social_count', nonce: aiscbAdmin.nonce },
			success: function (response) {
				if (response.success && typeof response.data.count !== 'undefined') {
					$('.aiscb-posts-count').text(response.data.count);
				}
			}
		});
	}

	// Search posts
	$('#aiscb-posts-search-btn').on('click', function () {
		postsCurrentSearch = $('#aiscb-posts-search').val().trim();
		postsCurrentPage = 1;
		loadAiscbPosts(postsCurrentPage, postsCurrentSearch);
	});

	$('#aiscb-posts-search').on('keypress', function (e) {
		if (e.which === 13) { // enter
			e.preventDefault();
			$('#aiscb-posts-search-btn').trigger('click');
		}
	});

	function loadAiscbPosts(page, search) {
		page = page || 1;
		search = search || '';
		var $tbody = $('#aiscb-posts-tbody');
		$tbody.html('<tr><td colspan="6" style="text-align:center;padding:20px;"><span class="spinner is-active" style="float:none;position:relative;top:-3px"></span> 加载中...</td></tr>');

		$.ajax({
			url: aiscbAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'aiscb_get_social_posts',
				nonce: aiscbAdmin.nonce,
				page: page,
				per_page: postsPerPage,
				search: search
			},
			success: function (response) {
				if (response.success) {
					var posts = response.data.posts;
					var pagination = response.data.pagination;
					renderAiscbPostsTable(posts);
					renderAiscbPostsPagination(pagination);
						// update posts count header
						updateAiscbPostsCount();
					// ensure header active state
					setPostsHeaderActive('list');
				} else {
					$tbody.html('<tr><td colspan="6" style="text-align:center;color:#d63638;">' + (response.data && response.data.message ? escapeHtml(response.data.message) : '加载失败') + '</td></tr>');
				}
			},
			error: function (xhr, status, error) {
				console.error('AJAX Error:', status, error);
				$tbody.html('<tr><td colspan="6" style="text-align:center;color:#d63638;">网络错误</td></tr>');
			}
		});
	}

	function renderAiscbPostsTable(posts) {
		var $tbody = $('#aiscb-posts-tbody');
		$tbody.empty();
		if (!posts || posts.length === 0) {
			$tbody.append('<tr><td colspan="6" style="text-align:center;">暂无贴子</td></tr>');
			return;
		}

		posts.forEach(function (p) {
			var attachments = [];
			try { attachments = p.attachment ? JSON.parse(p.attachment) : []; } catch (e) { attachments = []; }
			var platform = [];
			try { platform = p.platform ? JSON.parse(p.platform) : []; } catch (e) { platform = []; }

			var content = p.content ? p.content.replace(/<[^>]*>?/gm, '') : '';
			if (content.length > 120) content = content.substring(0, 120) + '...';

			var $tr = $('<tr>');
			$tr.append('<td>' + p.id + '</td>');
			$tr.append('<td>' + escapeHtml(content) + '</td>');
			$tr.append('<td>' + escapeHtml(platform.join(', ')) + '</td>');
			$tr.append('<td>' + attachments.length + '</td>');
			$tr.append('<td>' + (p.created_at || '') + '</td>');
			var $actions = $('<td>');
			$actions.append('<button type="button" class="button aiscb-edit-post" data-post="' + encodeURIComponent(JSON.stringify(p)) + '" style="margin-right:6px;">编辑</button>');
			$actions.append('<button type="button" class="button aiscb-delete-post" data-id="' + p.id + '">删除</button>');
			$tr.append($actions);
			$tbody.append($tr);
		});
	}

	function renderAiscbPostsPagination(pagination) {
		var $wrap = $('#aiscb-posts-pagination');
		$wrap.empty();
		if (!pagination || pagination.total_pages <= 1) return;

		var html = '<div class="aiscb-pagination-wrapper">';
		if (pagination.current_page > 1) html += '<button type="button" class="button aiscb-posts-page-btn" data-page="' + (pagination.current_page - 1) + '">上一页</button>';
		else html += '<button type="button" class="button" disabled>上一页</button>';
		html += '<span style="margin:0 8px;">第 ' + pagination.current_page + ' 页 / 共 ' + pagination.total_pages + ' 页（共 ' + pagination.total_items + ' 条）</span>';
		if (pagination.current_page < pagination.total_pages) html += '<button type="button" class="button aiscb-posts-page-btn" data-page="' + (pagination.current_page + 1) + '">下一页</button>';
		else html += '<button type="button" class="button" disabled>下一页</button>';
		html += '</div>';
		$wrap.html(html);
	}

	// Pagination click
	$(document).on('click', '.aiscb-posts-page-btn', function () {
		var page = $(this).data('page');
		if (page) {
			postsCurrentPage = page;
			loadAiscbPosts(postsCurrentPage, postsCurrentSearch);
		}
	});

	// Delete post
	$(document).on('click', '.aiscb-delete-post', function () {
		if (!confirm('确定要删除该贴子吗？')) return;
		var postId = $(this).data('id');
		$.ajax({
			url: aiscbAdmin.ajaxUrl,
			type: 'POST',
			data: { action: 'aiscb_delete_social_post', nonce: aiscbAdmin.nonce, post_id: postId },
			success: function (response) {
				if (response.success) {
					alert(response.data && response.data.message ? response.data.message : '删除成功');
					loadAiscbPosts(postsCurrentPage, postsCurrentSearch);
					updateAiscbPostsCount();
				} else {
					alert(response.data && response.data.message ? response.data.message : '删除失败');
				}
			},
			error: function () { alert('网络错误'); }
		});
	});

	// Edit post -> populate form and switch to social tab
	$(document).on('click', '.aiscb-edit-post', function () {
		var raw = $(this).data('post');
		try {
			var p = JSON.parse(decodeURIComponent(raw));
		} catch (e) { console.error(e); return; }
		// populate form
		$('#aiscb_social_id').val(p.id);
		$('#aiscb_social_content').val(p.content || '');
		// platforms
		try { var plats = p.platform ? JSON.parse(p.platform) : []; } catch (e) { var plats = []; }
		$('input[name="aiscb_social_platforms[]"]').prop('checked', false);
		plats.forEach(function (pl) { $('input[name="aiscb_social_platforms[]"][value="' + pl + '"]').prop('checked', true); });
		// attachments
		try { var atts = p.attachment ? JSON.parse(p.attachment) : []; } catch (e) { var atts = []; }
		aiscbSocialAttachments = atts.map(function (a) { return { id: a.id || 0, url: a.url || '', mime: a.type === 'image' ? 'image/*' : (a.type === 'video' ? 'video/*' : '') }; });
		renderAiscbAttachments();
		// switch to social tab and show form for editing
		$('.nav-tab').removeClass('nav-tab-active');
		$('.tab-content').removeClass('active').hide();
		$('.nav-tab[data-tab="social-tab"]').addClass('nav-tab-active');
		$('#social-tab').addClass('active').show();
		$('#aiscb-posts-wrap').hide();
		$('#aiscb-social-form-wrap').show();
		setPostsHeaderActive('add');
	});

	// Call existingKeywords on page load to populate the keywords list
	$(function () {
		existingKeywords();
	});

	// Handle keys form submission
	$('#aiscb-keys-form').on('submit', function(e) {
		e.preventDefault();
		
		var $form = $(this);
		var $submitBtn = $form.find('button[type="submit"]');
		var originalText = $submitBtn.text();
		
		$submitBtn.prop('disabled', true).text(i18n.saving);
		
		$.ajax({
			url: aiscbAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'aiscb_save_keys',
				nonce: aiscbAdmin.nonce,
				aiscb_gemini_key: $('#aiscb_gemini_key').val(),
				aiscb_facebook_key: $('#aiscb_facebook_key').val(),
				aiscb_instagram_key: $('#aiscb_instagram_key').val(),
				aiscb_youtube_key: $('#aiscb_youtube_key').val(),
				aiscb_openrouter_key: $('#aiscb_openrouter_key').val()
			},
			success: function(response) {
				if (response.success) {
					alert(response.data.message);
				} else {
					alert(response.data.message);
				}
			},
			error: function(xhr, status, error) {
				alert(i18n.networkError + error);
			},
			complete: function() {
				$submitBtn.prop('disabled', false).text(originalText);
			}
		});
	});

})(jQuery);

