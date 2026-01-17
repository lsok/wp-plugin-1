<?php
/**
 * Settings page view
 *
 * @package Ai_Seo_Content_Booster
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get saved settings
$keywords = get_option( 'aiscb_keywords', '' );
$gemini_key = get_option( 'aiscb_gemini_key', '' );
$facebook_key = get_option( 'aiscb_facebook_key', '' );
$article_category = get_option( 'aiscb_article_category', '' );
$publish_frequency = get_option( 'aiscb_publish_frequency', 'daily' );
$publish_time = get_option( 'aiscb_publish_time', '09:00' );
$is_active = get_option( 'aiscb_is_active', false );
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<nav class="nav-tab-wrapper">
		<a href="#keywords-tab" class="nav-tab nav-tab-active" data-tab="keywords-tab">
			<?php esc_html_e( '关键词设置', 'ai-seo-content-booster' ); ?>
		</a>
		<a href="#keys-tab" class="nav-tab" data-tab="keys-tab">
			<?php esc_html_e( '密钥设置', 'ai-seo-content-booster' ); ?>
		</a>
		<a href="#article-tab" class="nav-tab" data-tab="article-tab">
			<?php esc_html_e( '文章发布设置', 'ai-seo-content-booster' ); ?>
		</a>
	</nav>

	<!-- Tab 1: 关键词设置 -->
	<div id="keywords-tab" class="tab-content active">
		<form method="post" action="options.php" id="aiscb-keywords-form">
			<?php wp_nonce_field( 'aiscb_keywords_nonce', 'aiscb_keywords_nonce' ); ?>
			
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="aiscb_recommended_keywords"><?php esc_html_e( '推荐关键词', 'ai-seo-content-booster' ); ?></label>
						</th>
						<td>
							<p>
								<button type="button" class="button button-secondary" id="aiscb-get-keywords-btn">
									<?php esc_html_e( '获取推荐关键词', 'ai-seo-content-booster' ); ?>
								</button>
								<span class="spinner is-active" id="aiscb-keywords-spinner" style="float: none; margin-left: 10px; display: none;"></span>
							</p>
							<p class="description">
								<?php esc_html_e( '根据首页 title、description 和 WooCommerce 产品名称自动生成关键词列表', 'ai-seo-content-booster' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="aiscb_keywords_list"><?php esc_html_e( '关键词列表', 'ai-seo-content-booster' ); ?></label>
						</th>
						<td>
							<div id="existing-keywords"></div>
						</td>
					</tr>
					<tr>
						<th scope="row"></th>
						<td>
							<button type="button" class="button" id="aiscb-manual-keywords-btn">
								<?php esc_html_e( '手动设置关键词', 'ai-seo-content-booster' ); ?>
							</button>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit">
				<button type="submit" name="submit" id="submit" class="button button-primary">
					<?php esc_html_e( '保存', 'ai-seo-content-booster' ); ?>
				</button>
			</p>
		</form>
	</div>

	<!-- Tab 2: 密钥设置 -->
	<div id="keys-tab" class="tab-content" style="display: none;">
		<form method="post" action="options.php" id="aiscb-keys-form">
			<?php wp_nonce_field( 'aiscb_keys_nonce', 'aiscb_keys_nonce' ); ?>
			
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="aiscb_gemini_key"><?php esc_html_e( 'Gemini API Key', 'ai-seo-content-booster' ); ?></label>
						</th>
						<td>
							<input 
								type="password" 
								name="aiscb_gemini_key" 
								id="aiscb_gemini_key" 
								value="<?php echo esc_attr( $gemini_key ); ?>" 
								class="regular-text"
							/>
							<p class="description">
								<?php esc_html_e( '输入您的 Gemini API 密钥', 'ai-seo-content-booster' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="aiscb_facebook_key"><?php esc_html_e( 'Facebook API Key', 'ai-seo-content-booster' ); ?></label>
						</th>
						<td>
							<input 
								type="password" 
								name="aiscb_facebook_key" 
								id="aiscb_facebook_key" 
								value="<?php echo esc_attr( $facebook_key ); ?>" 
								class="regular-text"
							/>
							<p class="description">
								<?php esc_html_e( '输入您的 Facebook API 密钥', 'ai-seo-content-booster' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit">
				<button type="submit" name="submit" id="submit" class="button button-primary">
					<?php esc_html_e( '保存', 'ai-seo-content-booster' ); ?>
				</button>
			</p>
		</form>
	</div>

	<!-- Tab 3: 文章发布设置 -->
	<div id="article-tab" class="tab-content" style="display: none;">
		<form method="post" action="options.php" id="aiscb-article-form">
			<?php wp_nonce_field( 'aiscb_article_nonce', 'aiscb_article_nonce' ); ?>
			
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="aiscb_article_category"><?php esc_html_e( '文章类别', 'ai-seo-content-booster' ); ?></label>
						</th>
						<td>
							<?php
							$categories = get_categories( array( 'hide_empty' => false ) );
							?>
							<select name="aiscb_article_category" id="aiscb_article_category" class="regular-text">
								<option value=""><?php esc_html_e( '-- 选择类别 --', 'ai-seo-content-booster' ); ?></option>
								<?php foreach ( $categories as $category ) : ?>
									<option value="<?php echo esc_attr( $category->term_id ); ?>" <?php selected( $article_category, $category->term_id ); ?>>
										<?php echo esc_html( $category->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">
								<?php esc_html_e( '选择自动发布文章所属的类别', 'ai-seo-content-booster' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="aiscb_publish_frequency"><?php esc_html_e( '发布频率', 'ai-seo-content-booster' ); ?></label>
						</th>
						<td>
							<select name="aiscb_publish_frequency" id="aiscb_publish_frequency" class="regular-text">
								<option value="daily" <?php selected( $publish_frequency, 'daily' ); ?>>
									<?php esc_html_e( '每天', 'ai-seo-content-booster' ); ?>
								</option>
								<option value="weekly" <?php selected( $publish_frequency, 'weekly' ); ?>>
									<?php esc_html_e( '每周', 'ai-seo-content-booster' ); ?>
								</option>
								<option value="twice_weekly" <?php selected( $publish_frequency, 'twice_weekly' ); ?>>
									<?php esc_html_e( '每周两次', 'ai-seo-content-booster' ); ?>
								</option>
								<option value="monthly" <?php selected( $publish_frequency, 'monthly' ); ?>>
									<?php esc_html_e( '每月', 'ai-seo-content-booster' ); ?>
								</option>
							</select>
							<p class="description">
								<?php esc_html_e( '选择文章自动发布的频率', 'ai-seo-content-booster' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="aiscb_publish_time"><?php esc_html_e( '发布时间', 'ai-seo-content-booster' ); ?></label>
						</th>
						<td>
							<input 
								type="time" 
								name="aiscb_publish_time" 
								id="aiscb_publish_time" 
								value="<?php echo esc_attr( $publish_time ); ?>" 
								class="regular-text"
							/>
							<p class="description">
								<?php esc_html_e( '设置每天发布文章的时间（小时:分钟）', 'ai-seo-content-booster' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="aiscb_is_active"><?php esc_html_e( '启动/停止', 'ai-seo-content-booster' ); ?></label>
						</th>
						<td>
							<label class="aiscb-switch">
								<input 
									type="checkbox" 
									name="aiscb_is_active" 
									id="aiscb_is_active" 
									value="1" 
									<?php checked( $is_active, true ); ?>
								/>
								<span class="aiscb-slider"></span>
							</label>
							<p class="description">
								<?php esc_html_e( '开启或关闭自动发布功能', 'ai-seo-content-booster' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit">
				<button type="submit" name="submit" id="submit" class="button button-primary">
					<?php esc_html_e( '保存', 'ai-seo-content-booster' ); ?>
				</button>
			</p>
		</form>
	</div>
</div>

<!-- Modal for Manual Keywords Management -->
<div id="aiscb-keywords-modal" class="aiscb-modal" style="display: none;">
	<div class="aiscb-modal-content">
		<div class="aiscb-modal-header">
			<h2><?php esc_html_e( '手动设置关键词', 'ai-seo-content-booster' ); ?></h2>
			<span class="aiscb-modal-close">&times;</span>
		</div>
		<div class="aiscb-modal-body">
			<div class="aiscb-keywords-toolbar">
				<div class="aiscb-toolbar-left">
					<button type="button" class="button button-primary" id="aiscb-add-keyword-btn">
						<?php esc_html_e( '添加', 'ai-seo-content-booster' ); ?>
					</button>
					<button type="button" class="button" id="aiscb-bulk-delete-btn" style="display: none;">
						<?php esc_html_e( '批量删除', 'ai-seo-content-booster' ); ?>
					</button>
				</div>
				<div class="aiscb-toolbar-right">
					<input 
						type="search" 
						id="aiscb-keywords-search" 
						class="regular-text" 
						placeholder="<?php esc_attr_e( '搜索关键词', 'ai-seo-content-booster' ); ?>"
					/>
				</div>
			</div>
			<table class="wp-list-table widefat fixed striped" id="aiscb-keywords-table">
				<thead>
					<tr>
						<th class="check-column" style="width: 15px;">
							<input type="checkbox" id="aiscb-select-all-keywords" />
						</th>
						<th class="column-keyword" style="width: 45%;">
							<span class="keyword-header"><?php esc_html_e( '关键词', 'ai-seo-content-booster' ); ?></span>
						</th>
						<th class="column-status" style="width: 25%;">
							<span class="status-header"><?php esc_html_e( '状态', 'ai-seo-content-booster' ); ?></span>
						</th>
						<th class="column-actions" style="width: 15%;text-align: left;">
							<span class="actions-header"><?php esc_html_e( '操作', 'ai-seo-content-booster' ); ?></span>
						</th>
					</tr>
				</thead>
				<tbody id="aiscb-keywords-tbody">
					<!-- Keywords will be populated here by JavaScript -->
				</tbody>
			</table>
			<div class="aiscb-pagination" id="aiscb-keywords-pagination">
				<!-- Pagination will be populated here by JavaScript -->
			</div>
		</div>
		<div class="aiscb-modal-footer">
			<button type="button" class="button button-primary" id="aiscb-save-keywords-modal-btn">
				<span class="close-text"><?php esc_html_e( '关闭', 'ai-seo-content-booster' ); ?></span>
			</button>
		</div>
	</div>
</div>

<!-- Modal for Add/Edit Keyword -->
<div id="aiscb-keyword-edit-modal" class="aiscb-modal" style="display: none;">
	<div class="aiscb-modal-content" style="max-width: 600px;">
		<div class="aiscb-modal-header">
			<h2 id="aiscb-keyword-edit-title"><?php esc_html_e( '添加关键词', 'ai-seo-content-booster' ); ?></h2>
			<span class="aiscb-modal-close">&times;</span>
		</div>
		<div class="aiscb-modal-body">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="aiscb-keyword-input"><?php esc_html_e( '关键词', 'ai-seo-content-booster' ); ?></label>
						</th>
						<td>
							<input 
								type="text" 
								id="aiscb-keyword-input-single" 
								class="regular-text" 
								placeholder="<?php esc_attr_e( '输入关键词', 'ai-seo-content-booster' ); ?>"
								style="display: none;"
							/>
							<textarea 
								id="aiscb-keyword-input" 
								class="regular-text" 
								placeholder="<?php esc_attr_e( '输入关键词，每行一个', 'ai-seo-content-booster' ); ?>"
								rows="5"
								style="width: 100%;"
							></textarea>
							<p class="description" id="aiscb-keyword-description">
								<?php esc_html_e( '每行输入一个关键词，或从其他文档复制粘贴多个关键词。', 'ai-seo-content-booster' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="aiscb-modal-footer">
			<button type="button" class="button button-primary" id="aiscb-save-keyword-btn">
				<span class="save-text"><?php esc_html_e( '保存', 'ai-seo-content-booster' ); ?></span>
			</button>
			<button type="button" class="button" id="aiscb-cancel-keyword-btn">
				<span class="cancel-text"><?php esc_html_e( '取消', 'ai-seo-content-booster' ); ?></span>
			</button>
		</div>
	</div>
</div>

