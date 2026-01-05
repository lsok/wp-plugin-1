<?php

/**
 * Admin class
 *
 * @package Ai_Seo_Content_Booster
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// AJAX handlers
		add_action( 'wp_ajax_aiscb_get_keywords', array( $this, 'ajax_get_keywords' ) );
		add_action( 'wp_ajax_aiscb_get_existing_keywords', array( $this, 'ajax_get_existing_keywords' ) );
		add_action( 'wp_ajax_aiscb_add_keyword', array( $this, 'ajax_add_keyword' ) );
		add_action( 'wp_ajax_aiscb_edit_keyword', array( $this, 'ajax_edit_keyword' ) );
		add_action( 'wp_ajax_aiscb_delete_keyword', array( $this, 'ajax_delete_keyword' ) );
		add_action( 'wp_ajax_aiscb_bulk_delete_keywords', array( $this, 'ajax_bulk_delete_keywords' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu()
	{
		add_menu_page(
			__( 'AI SEO Content Booster', 'ai-seo-content-booster' ),
			__( 'AI SEO Booster', 'ai-seo-content-booster' ),
			'manage_options',
			'ai-seo-content-booster',
			array( $this, 'admin_page' ),
			'dashicons-admin-tools',
			30
		);
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook )
	{
		if ( strpos( $hook, 'toplevel_page_ai-seo-content-booster' ) === false ) {
			return;
		}

		wp_enqueue_script( 'aiscb-admin-js', plugin_dir_url( dirname( __FILE__ ) ) . 'admin/assets/js/admin.js', array( 'jquery', 'wp-i18n' ), '1.0.0', true );
		wp_enqueue_style( 'aiscb-admin-css', plugin_dir_url( dirname( __FILE__ ) ) . 'admin/assets/css/admin.css', array( ), '1.0.0' );

		wp_localize_script( 'aiscb-admin-js', 'aiscbAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'aiscb_admin_nonce' ),
			'i18n'    => array(
				'loading'                 => __( '加载中...', 'ai-seo-content-booster' ),
				'getKeywordsPending'      => __( '获取关键词功能待实现', 'ai-seo-content-booster' ),
				'noKeywords'              => __( '暂无关键词', 'ai-seo-content-booster' ),
				'edit'                    => __( '修改', 'ai-seo-content-booster' ),
				'delete'                  => __( '删除', 'ai-seo-content-booster' ),
				'confirmDelete'           => __( '确定要删除这个关键词吗？', 'ai-seo-content-booster' ),
				'enterKeyword'            => __( '请输入关键词', 'ai-seo-content-booster' ),
				'keywordExists'           => __( '该关键词已存在', 'ai-seo-content-booster' ),
				'savePending'             => __( '保存功能待实现', 'ai-seo-content-booster' ),
				'addKeyword'              => __( '添加关键词', 'ai-seo-content-booster' ),
				'editKeyword'             => __( '修改关键词', 'ai-seo-content-booster' ),
				'searchKeywords'          => __( '搜索关键词', 'ai-seo-content-booster' ),
				'batchDelete'             => __( '批量删除', 'ai-seo-content-booster' ),
				'processed'               => __( '已处理', 'ai-seo-content-booster' ),
				'unprocessed'             => __( '未处理', 'ai-seo-content-booster' ),
				'loadFailed'              => __( '加载失败', 'ai-seo-content-booster' ),
				'networkError'            => __( '网络错误，请重试', 'ai-seo-content-booster' ),
				'prevPage'                => __( '上一页', 'ai-seo-content-booster' ),
				'pagePrefix'              => __( '第 ', 'ai-seo-content-booster' ),
				'pageSeparator'           => __( ' 页，共 ', 'ai-seo-content-booster' ),
				'pageSuffix'              => __( ' 页', 'ai-seo-content-booster' ),
				'totalItemsPrefix'        => __( '（共 ', 'ai-seo-content-booster' ),
				'totalItemsSuffix'        => __( ' 条）', 'ai-seo-content-booster' ),
				'nextPage'                => __( '下一页', 'ai-seo-content-booster' ),
				'deleteFailed'            => __( '删除失败', 'ai-seo-content-booster' ),
				'saving'                  => __( '保存中...', 'ai-seo-content-booster' ),
				'operationSuccess'        => __( '操作成功', 'ai-seo-content-booster' ),
				'operationFailed'         => __( '操作失败', 'ai-seo-content-booster' ),
				'networkErrorWithMsg'     => __( '网络错误，请重试。错误信息: ', 'ai-seo-content-booster' ),
				'selectKeywordsToDelete'  => __( '请选择要删除的关键词', 'ai-seo-content-booster' ),
				'confirmDeleteBulkPrefix' => __( '确定要删除选中的 ', 'ai-seo-content-booster' ),
				'confirmDeleteBulkSuffix' => __( ' 个关键词吗？', 'ai-seo-content-booster' ),
				'deleteSuccess'           => __( '删除成功', 'ai-seo-content-booster' ),
			),
		) );
	}

	/**
	 * Admin page callback
	 */
	public function admin_page()
	{
		include plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/settings.php';
	}

	/**
	 * AJAX handler: Add keyword
	 */
	public function ajax_add_keyword()
	{
		check_ajax_referer( 'aiscb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( '权限不足', 'ai-seo-content-booster' ) ) );
		}

		$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( trim( $_POST['keyword'] ) ) : '';

		if ( empty( $keyword ) ) {
			wp_send_json_error( array( 'message' => __( '请输入关键词', 'ai-seo-content-booster' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiscb_keywords';

		// Check if keyword already exists
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE keyword = %s AND is_deleted = 0",
			$keyword
		) );

		if ( $exists > 0 ) {
			wp_send_json_error( array( 'message' => __( '该关键词已存在', 'ai-seo-content-booster' ) ) );
		}

		$result = $wpdb->insert(
			$table_name,
			array(
				'keyword' => $keyword,
				'status' => 'unprocessed',
				'is_deleted' => 0,
			),
			array( '%s', '%s', '%d' )
		);

		if ( $result === false ) {
			wp_send_json_error( array( 'message' => __( '添加失败', 'ai-seo-content-booster' ) ) );
		}

		wp_send_json_success( array( 'message' => __( '添加成功', 'ai-seo-content-booster' ) ) );
	}

	/**
	 * AJAX handler: Edit keyword
	 */
	public function ajax_edit_keyword()
	{
		check_ajax_referer( 'aiscb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( '权限不足', 'ai-seo-content-booster' ) ) );
		}

		$keyword_id = isset( $_POST['keyword_id'] ) ? absint( $_POST['keyword_id'] ) : 0;
		$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( trim( $_POST['keyword'] ) ) : '';

		if ( empty( $keyword_id ) || empty( $keyword ) ) {
			wp_send_json_error( array( 'message' => __( '参数错误', 'ai-seo-content-booster' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiscb_keywords';

		// Check if keyword already exists (excluding current keyword)
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE keyword = %s AND is_deleted = 0 AND id != %d",
			$keyword,
			$keyword_id
		) );

		if ( $exists > 0 ) {
			wp_send_json_error( array( 'message' => __( '该关键词已存在', 'ai-seo-content-booster' ) ) );
		}

		$result = $wpdb->update(
			$table_name,
			array( 'keyword' => $keyword ),
			array( 'id' => $keyword_id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( $result === false ) {
			wp_send_json_error( array( 'message' => __( '编辑失败', 'ai-seo-content-booster' ) ) );
		}

		wp_send_json_success( array( 'message' => __( '编辑成功', 'ai-seo-content-booster' ) ) );
	}

	/**
	 * AJAX handler: Get keywords
	 */
	public function ajax_get_keywords()
	{
		check_ajax_referer( 'aiscb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( '权限不足', 'ai-seo-content-booster' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiscb_keywords';

		$page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 10;
		$search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';

		$offset = ( $page - 1 ) * $per_page;

		$where = 'WHERE is_deleted = 0';
		if ( ! empty( $search ) ) {
			$where .= $wpdb->prepare( ' AND keyword LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' );
		}

		$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} {$where}" );

		$keywords = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, keyword, status, created_at FROM {$table_name} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
			$per_page,
			$offset
		) );

		$total_pages = ceil( $total / $per_page );

		wp_send_json_success( array(
			'keywords' => $keywords,
			'pagination' => array(
				'current_page' => $page,
				'total_pages' => $total_pages,
				'total_items' => $total,
				'per_page' => $per_page,
			),
		) );
	}

	/**
	 * AJAX handler: Get existing keywords
	 */
	public function ajax_get_existing_keywords()
	{
		check_ajax_referer( 'aiscb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( '权限不足', 'ai-seo-content-booster' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiscb_keywords';

		$keywords = $wpdb->get_results( 
			"SELECT id, keyword FROM {$table_name} WHERE is_deleted = 0 ORDER BY created_at DESC LIMIT 15"
		);

		wp_send_json_success( array(
			'keywords' => $keywords,
		) );
	}

	/**
	 * AJAX handler: Delete keyword
	 */
	public function ajax_delete_keyword()
	{
		check_ajax_referer( 'aiscb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( '权限不足', 'ai-seo-content-booster' ) ) );
		}

		$keyword_id = isset( $_POST['keyword_id'] ) ? absint( $_POST['keyword_id'] ) : 0;

		if ( empty( $keyword_id ) ) {
			wp_send_json_error( array( 'message' => __( '参数错误', 'ai-seo-content-booster' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiscb_keywords';

		$result = $wpdb->update(
			$table_name,
			array( 'is_deleted' => 1 ),
			array( 'id' => $keyword_id ),
			array( '%d' ),
			array( '%d' )
		);

		if ( $result === false ) {
			wp_send_json_error( array( 'message' => __( '删除失败', 'ai-seo-content-booster' ) ) );
		}

		wp_send_json_success( array( 'message' => __( '删除成功', 'ai-seo-content-booster' ) ) );
	}

	/**
	 * AJAX handler: Bulk delete keywords
	 */
	public function ajax_bulk_delete_keywords()
	{
		check_ajax_referer( 'aiscb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( '权限不足', 'ai-seo-content-booster' ) ) );
		}

		$keyword_ids = isset( $_POST['keyword_ids'] ) ? array_map( 'absint', $_POST['keyword_ids'] ) : array( );

		if ( empty( $keyword_ids ) ) {
			wp_send_json_error( array( 'message' => __( '参数错误', 'ai-seo-content-booster' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiscb_keywords';

		$placeholders = implode( ',', array_fill( 0, count( $keyword_ids ), '%d' ) );

		$result = $wpdb->query( $wpdb->prepare(
			"UPDATE {$table_name} SET is_deleted = 1 WHERE id IN ({$placeholders})",
			$keyword_ids
		) );

		if ( $result === false ) {
			wp_send_json_error( array( 'message' => __( '批量删除失败', 'ai-seo-content-booster' ) ) );
		}

		/* translators: %d: 成功删除的关键词数量 */
		wp_send_json_success( array( 'message' => sprintf( __( '成功删除 %d 个关键词', 'ai-seo-content-booster' ), $result ) ) );
	}
}
