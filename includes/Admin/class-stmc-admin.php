<?php
/**
 * Admin menu and React shell assets.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * WooCommerce → Maintenance Checklist (page=stmc).
 */
final class STMC_Admin {

	/**
	 * Register admin hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add WooCommerce submenu for the React shell mount.
	 */
	public function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Maintenance Checklist', 'store-maintenance-checklist' ),
			__( 'Maintenance Checklist', 'store-maintenance-checklist' ),
			'manage_woocommerce',
			'stmc',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Output the React root element (no heavy wrap chrome).
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'store-maintenance-checklist' ) );
		}

		echo '<div id="stmc-admin-root" class="stmc-admin-root"></div>';
	}

	/**
	 * Enqueue built admin assets on page=stmc only.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'woocommerce_page_stmc' !== $hook_suffix ) {
			return;
		}

		$asset_file = STMC_PLUGIN_DIR . 'build/index.asset.php';
		if ( ! is_readable( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;
		if ( ! is_array( $asset ) ) {
			return;
		}

		$dependencies = isset( $asset['dependencies'] ) && is_array( $asset['dependencies'] )
			? $asset['dependencies']
			: array();
		$version      = isset( $asset['version'] ) ? (string) $asset['version'] : STMC_VERSION;

		// Ensure expected WP script handles are present (wp-scripts marks them as externals).
		$extra_deps = array( 'wp-api-fetch', 'wp-i18n', 'wp-components', 'wp-element', 'wp-dom-ready' );
		foreach ( $extra_deps as $handle ) {
			if ( ! in_array( $handle, $dependencies, true ) ) {
				$dependencies[] = $handle;
			}
		}

		wp_enqueue_script(
			'stmc-admin',
			STMC_PLUGIN_URL . 'build/index.js',
			$dependencies,
			$version,
			true
		);

		wp_set_script_translations(
			'stmc-admin',
			'store-maintenance-checklist',
			STMC_PLUGIN_DIR . 'languages'
		);

		$host      = wp_parse_url( home_url(), PHP_URL_HOST );
		$site_name = is_string( $host ) && '' !== $host
			? $host
			: (string) get_bloginfo( 'name' );

		$scheduled_actions_url = admin_url( 'admin.php?page=wc-status&tab=action-scheduler' );

		wp_localize_script(
			'stmc-admin',
			'stmcAdmin',
			array(
				'restUrl'             => esc_url_raw( rest_url( 'stmc/v1/' ) ),
				'nonce'               => wp_create_nonce( 'wp_rest' ),
				'adminUrl'            => esc_url_raw( admin_url() ),
				'siteName'            => $site_name,
				'pluginVersion'       => STMC_VERSION,
				'scheduledActionsUrl' => esc_url_raw( $scheduled_actions_url ),
			)
		);

		$style_path = STMC_PLUGIN_DIR . 'build/index.css';
		if ( is_readable( $style_path ) ) {
			wp_enqueue_style(
				'stmc-admin',
				STMC_PLUGIN_URL . 'build/index.css',
				array( 'wp-components' ),
				$version
			);
		}

		wp_register_style( 'stmc-admin-chrome', false, array(), $version );
		wp_enqueue_style( 'stmc-admin-chrome' );
		wp_add_inline_style(
			'stmc-admin-chrome',
			implode(
				'',
				array(
					/* Desktop: hide wp-admin menu (app shell nav). Mobile: keep core menu for hamburger. */
					'@media screen and (min-width:783px){',
					'body.woocommerce_page_stmc #adminmenumain,',
					'body.woocommerce_page_stmc #adminmenuback{display:none!important;}',
					'body.woocommerce_page_stmc #wpcontent{margin-left:0!important;padding-left:0!important;}',
					'body.woocommerce_page_stmc.folded #wpcontent{margin-left:0!important;}',
					'}',
					/* Site Editor–style shell: hide core Thank you / Version footer. */
					'body.woocommerce_page_stmc #wpfooter{display:none!important;}',
					'body.woocommerce_page_stmc #wpbody-content{padding-bottom:0;}',
					'body.woocommerce_page_stmc .wrap{margin:0;max-width:none;}',
					'html.wp-toolbar body.woocommerce_page_stmc{background:#1e1e1e;}',
				)
			)
		);
	}
}
