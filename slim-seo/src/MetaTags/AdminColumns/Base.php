<?php
namespace SlimSEO\MetaTags\AdminColumns;

use SlimSEO\MetaTags\Settings\Base as Settings;
use SlimSEO\MetaTags\Title;
use SlimSEO\MetaTags\Description;
use SlimSEO\MetaTags\Robots;

abstract class Base {
	protected $settings;
	protected $title;
	protected $description;
	protected $robots;
	protected $object_type;
	protected $types;
	protected $manual_indicator = '<span class="ss-manual-content"></span>';

	public function __construct( Settings $settings, Title $title, Description $description, Robots $robots ) {
		$this->settings    = $settings;
		$this->title       = $title;
		$this->description = $description;
		$this->robots      = $robots;
	}

	public function setup() {
		add_action( 'admin_init', [ $this, 'setup_admin' ] );
	}

	public function setup_admin() {
		$this->types = $this->settings->get_types();

		$this->types = apply_filters( "slim_seo_admin_columns_{$this->object_type}", $this->types );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );

		// Quick edit.
		add_action( 'quick_edit_custom_box', [ $this, 'output_quick_edit_fields' ] );
		add_action( "wp_ajax_ss_quick_edit_{$this->object_type}", [ $this, 'get_quick_edit_data' ] );

		// Bulk edit.
		add_action( 'bulk_edit_custom_box', [ $this, 'output_bulk_edit_fields' ] );
		add_action( 'bulk_edit_posts', [ $this, 'save_bulk_edit' ], 10, 2 );
	}

	public function enqueue() {
		if ( ! $this->is_screen() ) {
			return;
		}

		wp_enqueue_style( 'slim-seo-admin-columns', SLIM_SEO_URL . 'css/admin-columns.css', [], filemtime( SLIM_SEO_DIR . 'css/admin-columns.css' ) );
		wp_enqueue_script( 'slim-seo-admin-columns', SLIM_SEO_URL . 'js/admin-columns.js', [], filemtime( SLIM_SEO_DIR . 'js/admin-columns.js' ), true );
		wp_add_inline_script( 'slim-seo-admin-columns', "let ssObjectType = '{$this->object_type}'", 'before' );
	}

	public function columns( $columns ) {
		$columns['meta_title']       = esc_html__( 'Meta title', 'slim-seo' );
		$columns['meta_description'] = esc_html__( 'Meta desc.', 'slim-seo' );
		$columns['index']            = esc_html__( 'Index', 'slim-seo' );

		return $columns;
	}

	public function output_quick_edit_fields( $column ) {
		if ( 'meta_title' !== $column || ! $this->is_screen() ) {
			return;
		}
		wp_nonce_field( 'save', 'ss_nonce' );
		?>
		<p class="wp-clearfix"></p>
		<fieldset class="inline-edit-col-left">
			<legend class="inline-edit-legend"><?php esc_html_e( 'Search Engine Optimization', 'slim-seo' ) ?></legend>
			<div class="inline-edit-col">
				<label>
					<span class="title"><?php esc_html_e( 'Meta title', 'slim-seo' ) ?></span>
					<span class="input-text-wrap">
						<input type="text" name="slim_seo[title]" value="">
					</span>
				</label>
				<label>
					<span class="title"><?php esc_html_e( 'Meta desc.', 'slim-seo' ) ?></span>
					<span class="input-text-wrap">
						<textarea name="slim_seo[description]" value=""></textarea>
					</span>
				</label>
				<div class="inline-edit-group wp-clearfix">
					<label class="alignleft">
						<input type="checkbox" name="slim_seo[noindex]" value="1">
						<span class="checkbox-title"><?php esc_html_e( 'Hide from search results', 'slim-seo' ) ?></span>
					</label>
				</div>
			</div>
		</fieldset>
		<?php
	}

	public function output_bulk_edit_fields( $column ) {
		if ( 'meta_title' !== $column || ! $this->is_screen() ) {
			return;
		}
		wp_nonce_field( 'save', 'ss_nonce' );
		?>
		<p class="wp-clearfix"></p>
		<div class="inline-edit-col-left">
			<legend class="inline-edit-legend"><?php esc_html_e( 'Search Engine Optimization', 'slim-seo' ) ?></legend>
			<div class="inline-edit-col">
				<div class="inline-edit-group wp-clearfix">
					<label>
						<span class="title"><?php esc_html_e( 'Hide from search results', 'slim-seo' ) ?></span>
						<select name="noindex">
							<option value="-1"><?php esc_html_e( '— No Change —', 'slim-seo' ) ?></option>
							<option value="1"><?php esc_html_e( 'Yes', 'slim-seo' ) ?></option>
							<option value="0"><?php esc_html_e( 'No', 'slim-seo' ) ?></option>
						</select>
					</label>
				</div>
			</div>
		</div>
		<?php
	}

	public function get_quick_edit_data() {
		check_ajax_referer( 'save', 'nonce' );

		$id = intval( $_GET['id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error();
		}

		$data = get_metadata( $this->object_type, $id, 'slim_seo', true ) ?: [];
		$data = array_merge( [
			'title'       => '',
			'description' => '',
			'noindex'     => 0,
		], $data );

		/**
		 * Make the meta filters work in the back end.
		 */
		$data['title']       = apply_filters( 'slim_seo_meta_title', $data['title'], $id );
		$data['description'] = apply_filters( 'slim_seo_meta_description', $data['description'], $id );
		$data['noindex']     = ! apply_filters( 'slim_seo_robots_index', ! $data['noindex'], $id );

		wp_send_json_success( $data );
	}

	public function save_bulk_edit( $updated, $shared_post_data ) {
		if ( empty( $updated ) || ! isset( $shared_post_data['noindex'] ) ) {
			return;
		}

		$noindex = (int) $shared_post_data['noindex'];
		if ( ! in_array( $noindex, [ -1, 0, 1 ], true ) || $noindex === -1 ) {
			return;
		}

		foreach ( $updated as $id ) {
			$data            = get_metadata( $this->object_type, $id, 'slim_seo', true ) ?: [];
			$data['noindex'] = $noindex;

			$data = array_filter( $data );

			if ( empty( $data ) ) {
				delete_metadata( $this->object_type, $id, 'slim_seo' );
			} else {
				update_metadata( $this->object_type, $id, 'slim_seo', $data );
			}
		}
	}

	abstract protected function is_screen(): bool;
}
