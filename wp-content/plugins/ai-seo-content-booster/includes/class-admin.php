<?php

/**
 * Admin class
 *
 * @package Ai_Seo_Content_Booster
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

/**
 * Admin class
 */
class AISCB_Admin
{

	/**
	 * Initialize the admin
	 */
	public function init()
	{
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

		// AJAX handlers
		add_action('wp_ajax_aiscb_save_social_post', array($this, 'ajax_save_social_post'));
		add_action('wp_ajax_aiscb_get_social_posts', array($this, 'ajax_get_social_posts'));
		add_action('wp_ajax_aiscb_delete_social_post', array($this, 'ajax_delete_social_post'));
		add_action('wp_ajax_aiscb_get_social_count', array($this, 'ajax_get_social_count'));
		add_action('wp_ajax_aiscb_get_keywords', array($this, 'ajax_get_keywords'));
		add_action('wp_ajax_aiscb_get_existing_keywords', array($this, 'ajax_get_existing_keywords'));
		add_action('wp_ajax_aiscb_add_keyword', array($this, 'ajax_add_keyword'));
		add_action('wp_ajax_aiscb_add_keywords_batch', array($this, 'ajax_add_keywords_batch'));
		add_action('wp_ajax_aiscb_import_keywords', array($this, 'ajax_import_keywords'));
		add_action('wp_ajax_aiscb_import_preview', array($this, 'ajax_import_preview'));
		add_action('wp_ajax_aiscb_edit_keyword', array($this, 'ajax_edit_keyword'));
		add_action('wp_ajax_aiscb_delete_keyword', array($this, 'ajax_delete_keyword'));
		add_action('wp_ajax_aiscb_bulk_delete_keywords', array($this, 'ajax_bulk_delete_keywords'));
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu()
	{
		add_menu_page(
			__('AI SEO Content Booster', 'ai-seo-content-booster'),
			__('AI SEO Booster', 'ai-seo-content-booster'),
			'manage_options',
			'ai-seo-content-booster',
			array($this, 'admin_page'),
			'dashicons-admin-tools',
			30
		);
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets($hook)
	{
		if (strpos($hook, 'toplevel_page_ai-seo-content-booster') === false) {
			return;
		}

		// Ensure media scripts are available for media picker
		wp_enqueue_media();

		wp_enqueue_script('aiscb-admin-js', plugin_dir_url(dirname(__FILE__)) . 'admin/assets/js/admin.js', array('jquery', 'wp-i18n'), '1.0.0', true);
		wp_enqueue_style('aiscb-admin-css', plugin_dir_url(dirname(__FILE__)) . 'admin/assets/css/admin.css', array(), '1.0.0');

		wp_localize_script('aiscb-admin-js', 'aiscbAdmin', array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce'   => wp_create_nonce('aiscb_admin_nonce'),
			'i18n'    => array(
				'getKeywordsPending'      => __('获取关键词功能待实现', 'ai-seo-content-booster'),
				'noKeywords'              => __('暂无关键词', 'ai-seo-content-booster'),
				'edit'                    => __('修改', 'ai-seo-content-booster'),
				'delete'                  => __('删除', 'ai-seo-content-booster'),
				'confirmDelete'           => __('确定要删除这个关键词吗？', 'ai-seo-content-booster'),
				'enterKeyword'            => __('请输入关键词', 'ai-seo-content-booster'),
				'keywordExists'           => __('该关键词已存在', 'ai-seo-content-booster'),
				'savePending'             => __('保存功能待实现', 'ai-seo-content-booster'),
				'addKeyword'              => __('添加关键词', 'ai-seo-content-booster'),
				'editKeyword'             => __('修改关键词', 'ai-seo-content-booster'),
				'searchKeywords'          => __('搜索关键词', 'ai-seo-content-booster'),
				'batchDelete'             => __('批量删除', 'ai-seo-content-booster'),
				'processed'               => __('已处理', 'ai-seo-content-booster'),
				'unprocessed'             => __('未处理', 'ai-seo-content-booster'),
				'loadFailed'              => __('加载失败', 'ai-seo-content-booster'),
				'networkError'            => __('网络错误，请重试', 'ai-seo-content-booster'),
				'prevPage'                => __('上一页', 'ai-seo-content-booster'),
				'pagePrefix'              => __('第 ', 'ai-seo-content-booster'),
				'pageSeparator'           => __(' 页，共 ', 'ai-seo-content-booster'),
				'pageSuffix'              => __(' 页', 'ai-seo-content-booster'),
				'totalItemsPrefix'        => __('（共 ', 'ai-seo-content-booster'),
				'totalItemsSuffix'        => __(' 条）', 'ai-seo-content-booster'),
				'nextPage'                => __('下一页', 'ai-seo-content-booster'),
				'deleteFailed'            => __('删除失败', 'ai-seo-content-booster'),
				'saving'                  => __('保存中...', 'ai-seo-content-booster'),
				'operationSuccess'        => __('操作成功', 'ai-seo-content-booster'),
				'operationFailed'         => __('操作失败', 'ai-seo-content-booster'),
				'networkErrorWithMsg'     => __('网络错误，请重试。错误信息: ', 'ai-seo-content-booster'),
				'selectKeywordsToDelete'  => __('请选择要删除的关键词', 'ai-seo-content-booster'),
				'confirmDeleteBulkPrefix' => __('确定要删除选中的 ', 'ai-seo-content-booster'),
				'confirmDeleteBulkSuffix' => __(' 个关键词吗？', 'ai-seo-content-booster'),
				'deleteSuccess'           => __('删除成功', 'ai-seo-content-booster'),
					// Social post strings
					'saveSocialPending'       => __('正在保存社媒贴子...', 'ai-seo-content-booster'),
					'saveSocialSuccess'       => __('社媒贴子已保存', 'ai-seo-content-booster'),
					'saveSocialFailed'        => __('保存社媒贴子失败', 'ai-seo-content-booster'),
					'chooseMedia'             => __('从媒体库选择', 'ai-seo-content-booster'),
					'removeAttachment'        => __('移除', 'ai-seo-content-booster'),
					'noAttachments'           => __('暂无附件', 'ai-seo-content-booster'),
			),
		));
	}

	/**
	 * Admin page callback
	 */
	public function admin_page()
	{
		include plugin_dir_path(dirname(__FILE__)) . 'admin/views/settings.php';
	}

	/**
	 * AJAX handler: Add keyword
	 */
	public function ajax_add_keyword()
	{
		check_ajax_referer('aiscb_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('权限不足', 'ai-seo-content-booster')));
		}

		$keyword = isset($_POST['keyword']) ? sanitize_text_field(trim($_POST['keyword'])) : '';

		if (empty($keyword)) {
			wp_send_json_error(array('message' => __('请输入关键词', 'ai-seo-content-booster')));
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiscb_keywords';

		// Check if keyword already exists
		$exists = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE keyword = %s AND is_deleted = 0",
			$keyword
		));

		if ($exists > 0) {
			wp_send_json_error(array('message' => __('该关键词已存在', 'ai-seo-content-booster')));
		}

		$result = $wpdb->insert(
			$table_name,
			array(
				'keyword' => $keyword,
				'status' => 'unprocessed',
				'is_deleted' => 0,
			),
			array('%s', '%s', '%d')
		);

		if ($result === false) {
			wp_send_json_error(array('message' => __('添加失败', 'ai-seo-content-booster')));
		}

		wp_send_json_success(array('message' => __('添加成功', 'ai-seo-content-booster')));
	}

	/**
	 * AJAX handler: Add keywords batch
	 */
	public function ajax_add_keywords_batch()
	{
		check_ajax_referer('aiscb_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('权限不足', 'ai-seo-content-booster')));
		}

		$keywords = isset($_POST['keywords']) ? $_POST['keywords'] : array();
		if (! is_array($keywords) || empty($keywords)) {
			wp_send_json_error(array('message' => __('未提供关键词', 'ai-seo-content-booster')));
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiscb_keywords';
		$success_count = 0;
		$repeat_count = 0;

		foreach ($keywords as $keyword) {
			$keyword = sanitize_text_field(trim($keyword));
			if (empty($keyword)) {
				continue;
			}

			// Check if keyword already exists
			$exists = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE keyword = %s AND is_deleted = 0",
				$keyword
			));

			if ($exists > 0) {
				$repeat_count++;
				continue; // Skip duplicates
			}

			$result = $wpdb->insert(
				$table_name,
				array(
					'keyword' => $keyword,
					'status' => 'unprocessed',
					'is_deleted' => 0,
				),
				array('%s', '%s', '%d')
			);

			if ($result !== false) {
				$success_count++;
			}
		}

		if ($repeat_count > 0) {
			/* _n()函数用于处理英文单复数(即 keyword 和 keywords) */
			$message = sprintf(_n('Successfully added %d keyword', 'Successfully added %d keywords', $success_count, 'ai-seo-content-booster'), $success_count) . ', ' . sprintf(_n('%d keyword are repeated', '%d keywords are repeated', $repeat_count, 'ai-seo-content-booster'), $repeat_count);
		} else {
			$message = sprintf(_n('Successfully added %d keyword', 'Successfully added %d keywords', $success_count, 'ai-seo-content-booster'), $success_count);
		}

		wp_send_json_success(array('message' => $message));
	}

	/**
	 * AJAX handler: Import keywords from Excel (SimpleXLSX)
	 */
	public function ajax_import_keywords()
	{
		check_ajax_referer('aiscb_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('权限不足', 'ai-seo-content-booster')));
		}

		if (empty($_FILES['keyword_file']) || $_FILES['keyword_file']['error'] !== UPLOAD_ERR_OK) {
			wp_send_json_error(array('message' => __('未找到上传的文件或上传失败', 'ai-seo-content-booster')));
		}

		$file = $_FILES['keyword_file'];
		$tmp_name = $file['tmp_name'];
		$filename = $file['name'];

		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if (! in_array($ext, array('xlsx', 'xls', 'csv'))) {
			wp_send_json_error(array('message' => __('仅支持 xls/xlsx/csv 文件', 'ai-seo-content-booster')));
		}

		$vendor_file = plugin_dir_path(dirname(__FILE__)) . 'vendor/SimpleXLSX.php';
		if (! file_exists($vendor_file)) {
			wp_send_json_error(array('message' => __('SimpleXLSX 库未找到', 'ai-seo-content-booster')));
		}

		require_once $vendor_file;

		// SimpleXLSX may be namespaced. Support both global and Shuchkin\SimpleXLSX
		$keywords = array();
		try {
			$selected_col = isset($_POST['col_index']) ? intval($_POST['col_index']) : null;
			if ($ext === 'csv') {
				if (($handle = fopen($tmp_name, 'r')) !== false) {
					// Read header
					$header = fgetcsv($handle);
					$colIndex = 0;
					if ($header && is_array($header)) {
						if ($selected_col !== null) {
							$colIndex = $selected_col;
						} else {
							// find header matching '关键词' or 'keywords'
							foreach ($header as $idx => $h) {
								$h_trim = trim(mb_strtolower((string) $h));
								if ($h_trim === '关键词' || $h_trim === 'keywords') {
									$colIndex = $idx;
									break;
								}
							}
						}
					}
					// read remaining rows
					while (($row = fgetcsv($handle)) !== false) {
						if (isset($row[$colIndex])) {
							$kw = sanitize_text_field(trim((string) $row[$colIndex]));
							if ($kw !== '') {
								$keywords[] = $kw;
							}
						}
					}
					fclose($handle);
				} else {
					wp_send_json_error(array('message' => __('无法打开 CSV 文件', 'ai-seo-content-booster')));
				}
			} else {
				if (class_exists('Shuchkin\\SimpleXLSX')) {
					$xlsx = \Shuchkin\SimpleXLSX::parse($tmp_name);
				} elseif (class_exists('SimpleXLSX')) {
					$xlsx = SimpleXLSX::parse($tmp_name);
				} else {
					wp_send_json_error(array('message' => __('SimpleXLSX 类不可用', 'ai-seo-content-booster')));
				}

				if ($xlsx === false) {
					wp_send_json_error(array('message' => __('解析 Excel 文件失败', 'ai-seo-content-booster')));
				}

				$rows = $xlsx->rows();
				if (!empty($rows)) {
					// first row is header
					$header = $rows[0];
					$colIndex = 0;
					if ($selected_col !== null) {
						$colIndex = $selected_col;
					} else {
						foreach ($header as $idx => $h) {
							$h_trim = trim(mb_strtolower((string) $h));
							if ($h_trim === '关键词' || $h_trim === 'keywords') {
								$colIndex = $idx;
								break;
							}
						}
					}
					// iterate rows skipping header
					for ($i = 1; $i < count($rows); $i++) {
						$row = $rows[$i];
						if (isset($row[$colIndex])) {
							$kw = sanitize_text_field(trim((string) $row[$colIndex]));
							if ($kw !== '') {
								$keywords[] = $kw;
							}
						}
					}
				}
			}
		} catch (Exception $e) {
			wp_send_json_error(array('message' => __('解析 Excel/CSV 文件出错: ', 'ai-seo-content-booster') . $e->getMessage()));
		}

		if (empty($keywords)) {
			wp_send_json_error(array('message' => __('未从文件中读取到关键词', 'ai-seo-content-booster')));
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiscb_keywords';
		$success_count = 0;
		$repeat_count = 0;

		foreach ($keywords as $keyword) {
			$keyword = sanitize_text_field(trim($keyword));
			if (empty($keyword)) {
				continue;
			}

			$exists = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE keyword = %s AND is_deleted = 0",
				$keyword
			));

			if ($exists > 0) {
				$repeat_count++;
				continue;
			}

			$result = $wpdb->insert(
				$table_name,
				array(
					'keyword' => $keyword,
					'status' => 'unprocessed',
					'is_deleted' => 0,
				),
				array('%s', '%s', '%d')
			);

			if ($result !== false) {
				$success_count++;
			}
		}

		if ($repeat_count > 0) {
			/* _n()函数用于处理英文单复数(即 keyword 和 keywords) */
			$message = sprintf(_n('Successfully added %d keyword', 'Successfully added %d keywords', $success_count, 'ai-seo-content-booster'), $success_count) . ', ' . sprintf(_n('%d keyword are repeated', '%d keywords are repeated', $repeat_count, 'ai-seo-content-booster'), $repeat_count);
		} else {
			$message = sprintf(_n('Successfully added %d keyword', 'Successfully added %d keywords', $success_count, 'ai-seo-content-booster'), $success_count);
		}

		wp_send_json_success(array('message' => $message));
	}

	/**
	 * AJAX handler: Preview uploaded file headers
	 */
	public function ajax_import_preview()
	{
		check_ajax_referer('aiscb_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('权限不足', 'ai-seo-content-booster')));
		}

		if (empty($_FILES['keyword_file']) || $_FILES['keyword_file']['error'] !== UPLOAD_ERR_OK) {
			wp_send_json_error(array('message' => __('未找到上传的文件或上传失败', 'ai-seo-content-booster')));
		}

		$file = $_FILES['keyword_file'];
		$tmp_name = $file['tmp_name'];
		$filename = $file['name'];

		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if (! in_array($ext, array('xlsx', 'xls', 'csv'))) {
			wp_send_json_error(array('message' => __('仅支持 xls/xlsx/csv 文件', 'ai-seo-content-booster')));
		}

		$vendor_file = plugin_dir_path(dirname(__FILE__)) . 'vendor/SimpleXLSX.php';
		if (! file_exists($vendor_file) && $ext !== 'csv') {
			wp_send_json_error(array('message' => __('SimpleXLSX 库未找到', 'ai-seo-content-booster')));
		}

		$headers = array();
		try {
			if ($ext === 'csv') {
				if (($handle = fopen($tmp_name, 'r')) !== false) {
					$header = fgetcsv($handle);
					if ($header && is_array($header)) {
						foreach ($header as $h) {
							$headers[] = trim((string) $h);
						}
					}
					fclose($handle);
				} else {
					wp_send_json_error(array('message' => __('无法打开 CSV 文件', 'ai-seo-content-booster')));
				}
			} else {
				require_once $vendor_file;

				if (class_exists('Shuchkin\\SimpleXLSX')) {
					$xlsx = \Shuchkin\SimpleXLSX::parse($tmp_name);
				} elseif (class_exists('SimpleXLSX')) {
					$xlsx = SimpleXLSX::parse($tmp_name);
				} else {
					wp_send_json_error(array('message' => __('SimpleXLSX 类不可用', 'ai-seo-content-booster')));
				}

				if ($xlsx === false) {
					wp_send_json_error(array('message' => __('解析 Excel 文件失败', 'ai-seo-content-booster')));
				}

				$rows = $xlsx->rows();
				if (!empty($rows)) {
					$header = $rows[0];
					foreach ($header as $h) {
						$headers[] = trim((string) $h);
					}
				}
			}
		} catch (Exception $e) {
			wp_send_json_error(array('message' => __('解析文件出错: ', 'ai-seo-content-booster') . $e->getMessage()));
		}

		if (empty($headers)) {
			wp_send_json_error(array('message' => __('未读取到表头', 'ai-seo-content-booster')));
		}

		wp_send_json_success(array('headers' => $headers));
	}

	/**
	 * AJAX handler: Edit keyword
	 */
	public function ajax_edit_keyword()
	{
		check_ajax_referer('aiscb_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('权限不足', 'ai-seo-content-booster')));
		}

		$keyword_id = isset($_POST['keyword_id']) ? absint($_POST['keyword_id']) : 0;
		$keyword = isset($_POST['keyword']) ? sanitize_text_field(trim($_POST['keyword'])) : '';

		if (empty($keyword_id) || empty($keyword)) {
			wp_send_json_error(array('message' => __('参数错误', 'ai-seo-content-booster')));
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiscb_keywords';

		// Check if keyword already exists (excluding current keyword)
		$exists = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE keyword = %s AND is_deleted = 0 AND id != %d",
			$keyword,
			$keyword_id
		));

		if ($exists > 0) {
			wp_send_json_error(array('message' => __('该关键词已存在', 'ai-seo-content-booster')));
		}

		$result = $wpdb->update(
			$table_name,
			array('keyword' => $keyword),
			array('id' => $keyword_id),
			array('%s'),
			array('%d')
		);

		if ($result === false) {
			wp_send_json_error(array('message' => __('编辑失败', 'ai-seo-content-booster')));
		}

		wp_send_json_success(array('message' => __('编辑成功', 'ai-seo-content-booster')));
	}

	/**
	 * AJAX handler: Get keywords
	 */
	public function ajax_get_keywords()
	{
		check_ajax_referer('aiscb_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('权限不足', 'ai-seo-content-booster')));
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiscb_keywords';

		$page = isset($_POST['page']) ? absint($_POST['page']) : 1;
		$per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 10;
		$search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

		$offset = ($page - 1) * $per_page;

		$where = 'WHERE is_deleted = 0';
		if (! empty($search)) {
			$where .= $wpdb->prepare(' AND keyword LIKE %s', '%' . $wpdb->esc_like($search) . '%');
		}

		$total = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} {$where}");

		$keywords = $wpdb->get_results($wpdb->prepare(
			"SELECT id, keyword, status, created_at FROM {$table_name} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
			$per_page,
			$offset
		));

		$total_pages = ceil($total / $per_page);

		wp_send_json_success(array(
			'keywords' => $keywords,
			'pagination' => array(
				'current_page' => $page,
				'total_pages' => $total_pages,
				'total_items' => $total,
				'per_page' => $per_page,
			),
		));
	}

	/**
	 * AJAX handler: Get existing keywords
	 */
	public function ajax_get_existing_keywords()
	{
		check_ajax_referer('aiscb_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('权限不足', 'ai-seo-content-booster')));
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiscb_keywords';

		$keywords = $wpdb->get_results(
			"SELECT id, keyword FROM {$table_name} WHERE is_deleted = 0 ORDER BY created_at DESC LIMIT 15"
		);

		wp_send_json_success(array(
			'keywords' => $keywords,
		));
	}

	/**
	 * AJAX handler: Delete keyword
	 */
	public function ajax_delete_keyword()
	{
		check_ajax_referer('aiscb_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('权限不足', 'ai-seo-content-booster')));
		}

		$keyword_id = isset($_POST['keyword_id']) ? absint($_POST['keyword_id']) : 0;

		if (empty($keyword_id)) {
			wp_send_json_error(array('message' => __('参数错误', 'ai-seo-content-booster')));
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiscb_keywords';

		$result = $wpdb->update(
			$table_name,
			array('is_deleted' => 1),
			array('id' => $keyword_id),
			array('%d'),
			array('%d')
		);

		if ($result === false) {
			wp_send_json_error(array('message' => __('删除失败', 'ai-seo-content-booster')));
		}

		wp_send_json_success(array('message' => __('删除成功', 'ai-seo-content-booster')));
	}

	/**
	 * AJAX handler: Bulk delete keywords
	 */
	public function ajax_bulk_delete_keywords()
	{
		check_ajax_referer('aiscb_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('权限不足', 'ai-seo-content-booster')));
		}

		$keyword_ids = isset($_POST['keyword_ids']) ? array_map('absint', $_POST['keyword_ids']) : array();

		if (empty($keyword_ids)) {
			wp_send_json_error(array('message' => __('参数错误', 'ai-seo-content-booster')));
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiscb_keywords';

		$placeholders = implode(',', array_fill(0, count($keyword_ids), '%d'));

		$result = $wpdb->query($wpdb->prepare(
			"UPDATE {$table_name} SET is_deleted = 1 WHERE id IN ({$placeholders})",
			$keyword_ids
		));

		if ($result === false) {
			wp_send_json_error(array('message' => __('批量删除失败', 'ai-seo-content-booster')));
		}

		/* translators: %d: 成功删除的关键词数量 */
		wp_send_json_success(array('message' => sprintf(__('成功删除 %d 个关键词', 'ai-seo-content-booster'), $result)));
	}

	/**
	 * AJAX handler: Save social post
	 */
	public function ajax_save_social_post()
	{
		check_ajax_referer('aiscb_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('权限不足', 'ai-seo-content-booster')));
		}

		$content = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '';
		$attachments_raw = isset($_POST['attachments']) ? wp_unslash($_POST['attachments']) : '[]';
		$attachments = json_decode($attachments_raw, true);
		if (! is_array($attachments)) {
			$attachments = array();
		}

		$sanitized_attachments = array();
		foreach ($attachments as $att) {
			if (is_array($att) && isset($att['id']) && intval($att['id']) > 0) {
				$id = absint($att['id']);
				$url = wp_get_attachment_url($id);
				$mime = get_post_mime_type($id);
				$type = (strpos((string) $mime, 'image') === 0) ? 'image' : ((strpos((string) $mime, 'video') === 0) ? 'video' : 'file');
				$sanitized_attachments[] = array('type' => $type, 'id' => $id, 'url' => $url);
			} elseif (is_array($att) && isset($att['url'])) {
				$url = esc_url_raw($att['url']);
				$type = preg_match('/video|\.mp4|\.webm/i', $url) ? 'video' : 'file';
				$sanitized_attachments[] = array('type' => $type, 'url' => $url);
			}
		}

		$platforms = isset($_POST['platforms']) ? array_map('sanitize_text_field', (array) $_POST['platforms']) : array();

		// Validation: content required and at least one platform
		$plain_content = trim( wp_strip_all_tags( $content ) );
		if (empty($plain_content)) {
			wp_send_json_error(array('message' => __('帖子内容不能为空', 'ai-seo-content-booster')));
		}

		if (empty($platforms) || ! is_array($platforms) || count($platforms) === 0) {
			wp_send_json_error(array('message' => __('请至少选择一个平台', 'ai-seo-content-booster')));
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiscb_social';

		// Support update if id provided
		$post_id = isset($_POST['id']) ? absint($_POST['id']) : 0;

		$data = array(
			'content' => $content,
			'attachment' => wp_json_encode($sanitized_attachments),
			'platform' => wp_json_encode($platforms),
			'status' => 'unpublished',
			'is_deleted' => 0,
		);

		$formats = array('%s', '%s', '%s', '%s', '%d');

		if ($post_id > 0) {
			$result = $wpdb->update(
				$table_name,
				$data,
				array('id' => $post_id),
				$formats,
				array('%d')
			);
			if ($result === false) {
				wp_send_json_error(array('message' => __('更新社媒贴子失败', 'ai-seo-content-booster')));
			}
			wp_send_json_success(array('message' => __('社媒贴子已更新', 'ai-seo-content-booster')));
		} else {
			$result = $wpdb->insert(
				$table_name,
				$data,
				$formats
			);
			if ($result === false) {
				wp_send_json_error(array('message' => __('保存社媒贴子失败', 'ai-seo-content-booster')));
			}
			wp_send_json_success(array('message' => __('社媒贴子已保存', 'ai-seo-content-booster')));
		}
	}

	/**
	 * AJAX handler: Get social posts (with pagination and search)
	 */
	public function ajax_get_social_posts()
	{
		check_ajax_referer('aiscb_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('权限不足', 'ai-seo-content-booster')));
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiscb_social';

		$page = isset($_POST['page']) ? absint($_POST['page']) : 1;
		$per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 10;
		$search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
		$offset = ($page - 1) * $per_page;

		$where = 'WHERE is_deleted = 0';
		$params = array();
		if (! empty($search)) {
			$where .= ' AND content LIKE %s';
			$params[] = '%' . $wpdb->esc_like($search) . '%';
		}

		// Count total
		if (! empty($params)) {
			$total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} {$where}", $params));
			$posts = $wpdb->get_results($wpdb->prepare("SELECT id, content, attachment, platform, created_at FROM {$table_name} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d", array_merge($params, array($per_page, $offset))));
		} else {
			$total = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} {$where}");
			$posts = $wpdb->get_results($wpdb->prepare("SELECT id, content, attachment, platform, created_at FROM {$table_name} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset));
		}

		$total_pages = ceil($total / $per_page);

		wp_send_json_success(array(
			'posts' => $posts,
			'pagination' => array(
				'current_page' => $page,
				'total_pages' => $total_pages,
				'total_items' => intval($total),
				'per_page' => $per_page,
			),
		));
	}

	/**
	 * AJAX handler: Delete social post (soft delete)
	 */
	public function ajax_delete_social_post()
	{
		check_ajax_referer('aiscb_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('权限不足', 'ai-seo-content-booster')));
		}

		$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
		if (empty($post_id)) {
			wp_send_json_error(array('message' => __('参数错误', 'ai-seo-content-booster')));
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiscb_social';

		$result = $wpdb->update(
			$table_name,
			array('is_deleted' => 1),
			array('id' => $post_id),
			array('%d'),
			array('%d')
		);

		if ($result === false) {
			wp_send_json_error(array('message' => __('删除失败', 'ai-seo-content-booster')));
		}

		wp_send_json_success(array('message' => __('删除成功', 'ai-seo-content-booster')));
	}

	/**
	 * AJAX handler: Get social posts count
	 */
	public function ajax_get_social_count()
	{
		check_ajax_referer('aiscb_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('权限不足', 'ai-seo-content-booster')));
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiscb_social';
		$count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE is_deleted = 0");

		wp_send_json_success(array('count' => $count));
	}
}
