<?php

/**
 * Plugin Name:     AI SEO Content Booster
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     ai-seo-content-booster
 * Domain Path:     /languages
 * Version:    0.1.0
 *
 * @package         Ai_Seo_Content_Booster
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

/**
 * Plugin constants
 */
define('AISCB_VERSION', '0.1.0');
define('AISCB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AISCB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AISCB_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('AISCB_PLUGIN_FILE', __FILE__);

/**
 * Create plugin database tables
 */
function AISCB_create_tables()
{
	global $wpdb;

	// Include upgrade.php for dbDelta function
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();

	// Keywords table: wp_aiscb_keywords
	// id          BIGINT(20) UNSIGNED, primary key, auto increment
	// keyword     VARCHAR(255), keyword text
	// status      VARCHAR(20), status: unprocessed/processed
	// is_deleted  TINYINT(1) UNSIGNED, soft delete flag: 0 = not deleted, 1 = deleted
	// created_at  DATETIME, record created time
	// updated_at  DATETIME, record updated time
	$table_keywords = $wpdb->prefix . 'aiscb_keywords';
	$sql_keywords = "CREATE TABLE $table_keywords (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		keyword VARCHAR(255) NOT NULL,
		status VARCHAR(20) NOT NULL DEFAULT 'unprocessed',
		is_deleted TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY keyword (keyword),
		KEY status (status),
		KEY is_deleted (is_deleted)
	) $charset_collate;";

	dbDelta($sql_keywords);

	// Articles table: wp_aiscb_articles
	// id           BIGINT(20) UNSIGNED, primary key, auto increment
	// post_id      BIGINT(20) UNSIGNED, related WordPress post ID
	// title        VARCHAR(255), article title
	// title_hash   BIGINT(20), article title hash value
	// content      LONGTEXT, article content
	// content_hash BIGINT(20), article content hash value
	// keyword      VARCHAR(255), keyword used to generate the article
	// status       VARCHAR(20), status: unpublished/published
	// created_at   DATETIME, record created time
	// updated_at   DATETIME, record updated time
	$table_articles = $wpdb->prefix . 'aiscb_articles';
	$sql_articles = "CREATE TABLE $table_articles (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		post_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		title VARCHAR(255) NOT NULL,
		title_hash BIGINT(20) NOT NULL,
		content LONGTEXT NOT NULL,
		content_hash BIGINT(20) NOT NULL,
		keyword VARCHAR(255) NOT NULL,
		status VARCHAR(20) NOT NULL DEFAULT 'unpublished',
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY status (status)
	) $charset_collate;";

	dbDelta($sql_articles);

	// Social table: wp_aiscb_social
	$table_social = $wpdb->prefix . 'aiscb_social';
	$sql_social = "CREATE TABLE $table_social (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		content LONGTEXT NOT NULL,
		attachment TEXT NOT NULL,
		platform TEXT NOT NULL,
		status VARCHAR(20) NOT NULL DEFAULT 'unpublished',
		is_deleted TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY status (status)
	) $charset_collate;";

	dbDelta($sql_social);
}

/**
 * Activation hook
 */
function AISCB_activate()
{
	// Create database tables
	AISCB_create_tables();

	// Flush rewrite rules
	flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'AISCB_activate');

/**
 * Deactivation hook
 */
function AISCB_deactivate()
{
	// Deactivation code here
	flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'AISCB_deactivate');

/**
 * Initialize the plugin
 */
function AISCB_init()
{
	// Load plugin text domain
	load_plugin_textdomain('ai-seo-content-booster', false, dirname(AISCB_PLUGIN_BASENAME) . '/languages');

	// Load core classes
	require_once AISCB_PLUGIN_DIR . 'includes/class-admin.php';

	// Initialize admin
	if (is_admin()) {
		$AISCB_Admin = new AISCB_Admin();
		$AISCB_Admin->init();
	}
}
add_action('plugins_loaded', 'AISCB_init');
