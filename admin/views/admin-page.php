<?php
/**
 * Admin settings page template.
 *
 * @package GlobalAuthenticity\EducationManager
 */

namespace GlobalAuthenticity\EducationManager;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Data queries ───────────────────────────────────────────────────────────────

$per_page    = (int) get_option( 'erm_resources_per_page', 12 );
$enable_api  = (bool) get_option( 'erm_enable_rest_api', true );
$difficulty  = get_option( 'erm_default_difficulty', 'beginner' );
$dl_count    = (bool) get_option( 'erm_enable_download_count', true );

$db          = new Database();
$post_counts = wp_count_posts( Post_Type::POST_TYPE );
$published   = (int) ( $post_counts->publish ?? 0 );
$tracking    = $db->get_tracking_summary();
$top_viewed  = $db->get_top_viewed_resources( 5 );
$monthly     = $db->get_resources_per_month( 6 );

// Pre-compute bar chart dimensions.
$chart_max_h = 120; // px — tallest possible bar.
$count_max   = max( array_values( $monthly ) ?: [ 1 ] );
$count_max   = $count_max > 0 ? $count_max : 1; // avoid division by zero.
?>
<div class="wrap erm-settings-wrap">

	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-welcome-learn-more"></span>
		<?php esc_html_e( 'Education Resources Manager', 'education-resources-manager' ); ?>
	</h1>

	<hr class="wp-header-end">

	<!-- ── Stats cards ──────────────────────────────────────────────────────── -->
	<div class="erm-stats-row">
		<div class="erm-stat-card erm-stat-card--blue">
			<span class="erm-stat-card__number"><?php echo esc_html( number_format_i18n( $published ) ); ?></span>
			<span class="erm-stat-card__label"><?php esc_html_e( 'Published Resources', 'education-resources-manager' ); ?></span>
		</div>
		<div class="erm-stat-card erm-stat-card--green">
			<span class="erm-stat-card__number"><?php echo esc_html( number_format_i18n( $tracking['views'] ) ); ?></span>
			<span class="erm-stat-card__label"><?php esc_html_e( 'Total Views Tracked', 'education-resources-manager' ); ?></span>
		</div>
		<div class="erm-stat-card erm-stat-card--orange">
			<span class="erm-stat-card__number"><?php echo esc_html( number_format_i18n( $tracking['downloads'] ) ); ?></span>
			<span class="erm-stat-card__label"><?php esc_html_e( 'Total Downloads Tracked', 'education-resources-manager' ); ?></span>
		</div>
		<div class="erm-stat-card erm-stat-card--grey">
			<span class="erm-stat-card__number"><?php echo esc_html( ERM_VERSION ); ?></span>
			<span class="erm-stat-card__label"><?php esc_html_e( 'Plugin Version', 'education-resources-manager' ); ?></span>
		</div>
	</div>

	<!-- ── Dashboard row: chart + top resources ─────────────────────────────── -->
	<div class="erm-dashboard-row">

		<!-- Bar chart: resources created per month -->
		<div class="erm-dashboard-panel">
			<h2 class="erm-dashboard-panel__title">
				<?php esc_html_e( 'Resources Published — Last 6 Months', 'education-resources-manager' ); ?>
			</h2>

			<div class="erm-chart" role="img" aria-label="<?php esc_attr_e( 'Bar chart: resources published per month', 'education-resources-manager' ); ?>">
				<?php foreach ( $monthly as $ym => $count ) :
					$bar_h     = (int) round( ( $count / $count_max ) * $chart_max_h );
					$bar_h     = max( $bar_h, $count > 0 ? 4 : 0 ); // min visible height when > 0
					$month_obj = \DateTime::createFromFormat( 'Y-m', $ym );
					$label     = $month_obj ? $month_obj->format( 'M Y' ) : $ym;
					?>
					<div class="erm-chart__col">
						<span class="erm-chart__count"><?php echo esc_html( $count ); ?></span>
						<div
							class="erm-chart__bar<?php echo 0 === $count ? ' erm-chart__bar--empty' : ''; ?>"
							style="height: <?php echo esc_attr( $bar_h ); ?>px;"
						></div>
						<span class="erm-chart__label"><?php echo esc_html( $label ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div><!-- /.erm-dashboard-panel -->

		<!-- Top 5 most viewed resources -->
		<div class="erm-dashboard-panel">
			<h2 class="erm-dashboard-panel__title">
				<?php esc_html_e( 'Top 5 Most Viewed Resources', 'education-resources-manager' ); ?>
			</h2>

			<?php if ( empty( $top_viewed ) ) : ?>
				<p class="erm-no-data"><?php esc_html_e( 'No view events recorded yet.', 'education-resources-manager' ); ?></p>
			<?php else : ?>
				<table class="erm-top-table widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Resource', 'education-resources-manager' ); ?></th>
							<th class="erm-top-table__count-col"><?php esc_html_e( 'Views', 'education-resources-manager' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $top_viewed as $row ) :
							$title    = ! empty( $row->post_title ) ? $row->post_title : __( '(no title)', 'education-resources-manager' );
							$edit_url = get_edit_post_link( (int) $row->resource_id );
							?>
							<tr>
								<td>
									<?php if ( $edit_url ) : ?>
										<a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $title ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $title ); ?>
									<?php endif; ?>
								</td>
								<td class="erm-top-table__count-col">
									<strong><?php echo esc_html( number_format_i18n( (int) $row->view_count ) ); ?></strong>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div><!-- /.erm-dashboard-panel -->

	</div><!-- /.erm-dashboard-row -->

	<!-- ── Settings form + sidebar ──────────────────────────────────────────── -->
	<div class="erm-settings-container">
		<div class="erm-settings-main">
			<form id="erm-settings-form" method="post">
				<?php wp_nonce_field( 'erm_admin_nonce', 'erm_settings_nonce' ); ?>

				<div class="erm-settings-section">
					<h2><?php esc_html_e( 'Display Settings', 'education-resources-manager' ); ?></h2>
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="resources_per_page"><?php esc_html_e( 'Resources Per Page', 'education-resources-manager' ); ?></label>
								</th>
								<td>
									<input
										type="number"
										id="resources_per_page"
										name="resources_per_page"
										value="<?php echo esc_attr( $per_page ); ?>"
										min="1"
										max="100"
										class="small-text"
									/>
									<p class="description"><?php esc_html_e( 'Number of resources shown per page in shortcode and REST API (1–100).', 'education-resources-manager' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="default_difficulty"><?php esc_html_e( 'Default Difficulty', 'education-resources-manager' ); ?></label>
								</th>
								<td>
									<select id="default_difficulty" name="default_difficulty">
										<?php
										$opts = [
											'beginner'     => __( 'Beginner', 'education-resources-manager' ),
											'intermediate' => __( 'Intermediate', 'education-resources-manager' ),
											'advanced'     => __( 'Advanced', 'education-resources-manager' ),
										];
										foreach ( $opts as $val => $label ) :
											?>
											<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $difficulty, $val ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
						</tbody>
					</table>
				</div><!-- /.erm-settings-section -->

				<div class="erm-settings-section">
					<h2><?php esc_html_e( 'API & Tracking', 'education-resources-manager' ); ?></h2>
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row"><?php esc_html_e( 'REST API', 'education-resources-manager' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="enable_rest_api" value="1" <?php checked( $enable_api ); ?> />
										<?php esc_html_e( 'Enable the /erm/v1/ REST API endpoints', 'education-resources-manager' ); ?>
									</label>
									<?php if ( $enable_api ) : ?>
										<p class="description">
											<?php esc_html_e( 'Base URL:', 'education-resources-manager' ); ?>
											<code><?php echo esc_html( rest_url( Rest_Api::NAMESPACE . '/resources' ) ); ?></code>
										</p>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Download Tracking', 'education-resources-manager' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="enable_download_count" value="1" <?php checked( $dl_count ); ?> />
										<?php esc_html_e( 'Track download counts via the REST API', 'education-resources-manager' ); ?>
									</label>
								</td>
							</tr>
						</tbody>
					</table>
				</div><!-- /.erm-settings-section -->

				<p class="submit">
					<button type="submit" id="erm-save-settings" class="button button-primary">
						<?php esc_html_e( 'Save Settings', 'education-resources-manager' ); ?>
					</button>
					<span class="erm-save-status" aria-live="polite"></span>
				</p>

			</form>
		</div><!-- /.erm-settings-main -->

		<div class="erm-settings-sidebar">
			<div class="erm-sidebar-card">
				<h3><?php esc_html_e( 'Quick Links', 'education-resources-manager' ); ?></h3>
				<ul>
					<li>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . Post_Type::POST_TYPE ) ); ?>">
							<?php esc_html_e( 'All Resources', 'education-resources-manager' ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . Post_Type::POST_TYPE ) ); ?>">
							<?php esc_html_e( 'Add New Resource', 'education-resources-manager' ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . Taxonomy::CATEGORY . '&post_type=' . Post_Type::POST_TYPE ) ); ?>">
							<?php esc_html_e( 'Resource Categories', 'education-resources-manager' ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . Taxonomy::TAG . '&post_type=' . Post_Type::POST_TYPE ) ); ?>">
							<?php esc_html_e( 'Resource Tags', 'education-resources-manager' ); ?>
						</a>
					</li>
				</ul>
			</div>

			<div class="erm-sidebar-card">
				<h3><?php esc_html_e( 'Shortcode Usage', 'education-resources-manager' ); ?></h3>
				<code class="erm-shortcode-example">[education_resources]</code>
				<p class="description"><?php esc_html_e( 'Optional attributes:', 'education-resources-manager' ); ?></p>
				<ul class="erm-attr-list">
					<li><code>per_page="12"</code></li>
					<li><code>category="slug"</code></li>
					<li><code>tag="slug"</code></li>
					<li><code>difficulty="beginner"</code></li>
					<li><code>featured="true"</code></li>
					<li><code>orderby="date"</code></li>
					<li><code>order="DESC"</code></li>
				</ul>
			</div>
		</div><!-- /.erm-settings-sidebar -->
	</div><!-- /.erm-settings-container -->

</div><!-- /.wrap -->
