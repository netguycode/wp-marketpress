<?php

class MP_Admin {

	/**
	 * Refers to a single instance of the class
	 *
	 * @since 3.0
	 * @access private
	 * @var object
	 */
	private static $_instance = null;

	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new MP_Admin();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		$this->_init_dash_notices();
		$this->_includes();

		//save orders screen options
		add_filter( 'set-screen-option', array( &$this, 'save_orders_screen_options' ), 10, 3 );
		//set custom post-updated messages
		add_filter( 'post_updated_messages', array( &$this, 'post_updated_messages' ) );
		//enqueue styles and scripts
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles_scripts' ) );
	}

	/**
	 * Includes any necessary files
	 *
	 * @since 3.0
	 * @access public
	 */
	protected function _includes() {
		require_once mp_plugin_dir( 'includes/admin/class-mp-orders-admin.php' );
		require_once mp_plugin_dir( 'includes/admin/class-mp-products-admin.php' );
		require_once mp_plugin_dir( 'includes/admin/class-mp-product-attributes-admin.php' );
		require_once mp_plugin_dir( 'includes/admin/class-mp-store-settings-admin.php' );
		require_once mp_plugin_dir( 'includes/admin/class-mp-setup-wizard.php' );
		require_once mp_plugin_dir( 'includes/admin/class-mp-shortcode-builder.php' );
	}

	/**
	 * Initialize dash notices
	 *
	 * @since 3.0
	 * @access public
	 */
	protected function _init_dash_notices() {
		if ( MP_LITE ) {
			return;
		}

		//! TODO: update screen ids for dash notices
		global $wpmudev_notices;
		$wpmudev_notices[] = array(
			'id'		 => 144,
			'name'		 => 'MarketPress',
			'screens'	 => array(
				'edit-product',
				'edit-mp_product',
				'product',
				'mp_product',
				'edit-product_category',
				'edit-product_tag',
				'settings_page_marketpress-ms-network'
			)
		);

		require_once mp_plugin_dir( 'includes/admin/dash-notice/wpmudev-dash-notification.php' );
	}

	/**
	 * Adds the MarketPress help tab
	 *
	 * @since 3.0
	 * @access public
	 */
	public function add_help_tab() {
		get_current_screen()->add_help_tab( array(
			'id'		 => 'marketpress-help',
			'title'		 => __( 'MarketPress Instructions', 'mp' ),
			'content'	 => '<iframe src="//premium.wpmudev.org/wdp-un.php?action=help&id=144" width="100%" height="600px"></iframe>'
		) );
	}

	/**
	 * Displays the export orders form
	 *
	 * @since 3.0
	 * @access public
	 */
	public function export_orders_form() {
		global $wpdb;

		if ( !isset( $_GET[ 'post_status' ] ) || $_GET[ 'post_status' ] != 'trash' ) {
			?>
			<div class="icon32"><img src="<?php echo mp_plugin_url( 'ui/images/download.png' ); ?>" /></div>
			<h2><?php _e( 'Export Orders', 'mp' ); ?></h2>
			<?php if ( defined( 'MP_LITE' ) ) { ?>
				<a class="mp-pro-update" href="http://premium.wpmudev.org/project/e-commerce/" title="<?php _e( 'Upgrade Now', 'mp' ); ?> &raquo;"><?php _e( 'Upgrade to enable CSV order exports &raquo;', 'mp' ); ?></a><br />
			<?php } ?>
			<form action="<?php echo admin_url( 'admin-ajax.php?action=mp-orders-export' ); ?>" method="post">
				<?php
				$months = $wpdb->get_results( $wpdb->prepare( "
			SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
			FROM $wpdb->posts
			WHERE post_type = %s
			ORDER BY post_date DESC
		", 'mp_order' ) );

				$month_count = count( $months );

				if ( !$month_count || ( 1 == $month_count && 0 == $months[ 0 ]->month ) )
					return;

				$m = isset( $_GET[ 'm' ] ) ? (int) $_GET[ 'm' ] : 0;
				?>
				<select name='m'>
					<option<?php selected( $m, 0 ); ?> value='0'><?php _e( 'Show all dates' ); ?></option>
					<?php
					foreach ( $months as $arc_row ) {
						if ( 0 == $arc_row->year )
							continue;

						$month	 = zeroise( $arc_row->month, 2 );
						$year	 = $arc_row->year;

						printf( "<option %s value='%s'>%s</option>\n", selected( $m, $year . $month, false ), esc_attr( $arc_row->year . $month ), $wp_locale->get_month( $month ) . " $year"
						);
					}

					$status = isset( $_GET[ 'post_status' ] ) ? $_GET[ 'post_status' ] : 'all';
					?>
				</select>
				<select name="order_status">
					<option<?php selected( $status, 'all' ); ?> value="all" selected="selected"><?php _e( 'All Statuses', 'mp' ); ?></option>
					<option<?php selected( $status, 'order_received' ); ?> value="order_received"><?php _e( 'Received', 'mp' ); ?></option>
					<option<?php selected( $status, 'order_paid' ); ?> value="order_paid"><?php _e( 'Paid', 'mp' ); ?></option>
					<option<?php selected( $status, 'order_shipped' ); ?> value="order_shipped"><?php _e( 'Shipped', 'mp' ); ?></option>
					<option<?php selected( $status, 'order_closed' ); ?> value="order_closed"><?php _e( 'Closed', 'mp' ); ?></option>
				</select>
				<input type="submit" value="<?php _e( 'Download &raquo;', 'mp' ); ?>" name="export_orders" class="button-secondary"<?php echo defined( 'MP_LITE' ) ? ' disabled="disabled"' : ''; ?> />
			</form>


			<br class="clear">
		<?php } ?>
		</div>
		<?php
	}

	/**
	 * Enqueue styles and scripts
	 *
	 * @since 3.0
	 * @access public
	 */
	public function enqueue_styles_scripts() {
		global $pagenow, $post_type;

		if ( !empty( $pagenow ) && ('post-new.php' === $pagenow || 'post.php' === $pagenow ) ) {
			if ( $post_type == MP_Product::get_post_type() ) {
				wp_enqueue_style( 'mp-font-awesome', mp_plugin_url( 'includes/admin/ui/css/font-awesome.min.css' ), array(), MP_VERSION );
				wp_enqueue_style( 'mp-product-admin', mp_plugin_url( 'includes/admin/ui/css/admin-product.css' ), array( 'mp-font-awesome' ), MP_VERSION );
				wp_enqueue_script( 'mp-repeatable-fields', mp_plugin_url( 'includes/admin/ui/js/repeatable-fields.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'mp-product-admin', mp_plugin_url( 'includes/admin/ui/js/admin-product.js' ), array( 'mp-repeatable-fields' ), MP_VERSION );

				wp_localize_script( 'mp-product-admin', 'mp_product_admin_i18n', array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'creating_vatiations_message' => __( 'Creating variations, please wait...', 'mp' ),
				) );
			}
		}

		wp_enqueue_style( 'mp-admin', mp_plugin_url( 'includes/admin/ui/css/admin.css' ), array(), MP_VERSION );
	}

	/**
	 * Modifies the post-updated messages for the mp_order, product and mp_coupon post types
	 *
	 * @since 3.0
	 * @access public
	 * @filter post_updated_messages
	 * @param array $messages
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		global $post, $post_ID;

		$post_type = get_post_type( $post_ID );

		if ( $post_type != 'mp_order' && $post_type != MP_Product::get_post_type() && $post_type != 'mp_coupon' ) {
			return $messages;
		}

		$obj		 = get_post_type_object( $post_type );
		$singular	 = $obj->labels->singular_name;

		$messages[ $post_type ] = array(
			0	 => '', // Unused. Messages start at index 1.
			1	 => sprintf( __( $singular . ' updated. <a href="%s">View ' . strtolower( $singular ) . '</a>' ), esc_url( get_permalink( $post_ID ) ) ),
			2	 => __( 'Custom field updated.' ),
			3	 => __( 'Custom field deleted.' ),
			4	 => __( $singular . ' updated.' ),
			5	 => isset( $_GET[ 'revision' ] ) ? sprintf( __( $singular . ' restored to revision from %s' ), wp_post_revision_title( (int) $_GET[ 'revision' ], false ) ) : false,
			6	 => sprintf( __( $singular . ' published. <a href="%s">View ' . strtolower( $singular ) . '</a>' ), esc_url( get_permalink( $post_ID ) ) ),
			7	 => __( 'Page saved.' ),
			8	 => sprintf( __( $singular . ' submitted. <a target="_blank" href="%s">Preview ' . strtolower( $singular ) . '</a>' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			9	 => sprintf( __( $singular . ' scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview ' . strtolower( $singular ) . '</a>' ), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
			10	 => sprintf( __( $singular . ' draft updated. <a target="_blank" href="%s">Preview ' . strtolower( $singular ) . '</a>' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);

		return $messages;
	}

}

MP_Admin::get_instance();
