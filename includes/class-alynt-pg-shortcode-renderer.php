<?php
/**
 * Shortcode renderer.
 *
 * @package Alynt_Products_Grid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the products grid shortcode.
 */
class ALYNT_PG_Shortcode_Renderer {
	/**
	 * Products query service.
	 *
	 * @var ALYNT_PG_Products_Query_Service
	 */
	private $products_query_service;

	/**
	 * Constructor.
	 *
	 * @param ALYNT_PG_Products_Query_Service $products_query_service Products query service instance.
	 */
	public function __construct( $products_query_service ) {
		$this->products_query_service = $products_query_service;
	}

	/**
	 * Renders the shortcode output.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'columns'      => 4,
				'categories'   => '',
				'special'      => '',
				'per_page'     => 12,
				'breakpoint_5' => 1200,
				'breakpoint_4' => 992,
				'breakpoint_3' => 768,
				'breakpoint_2' => 576,
			),
			$atts,
			'alynt_products_grid'
		);

		$atts['columns']  = min( 5, max( 1, intval( $atts['columns'] ) ) );
		$atts['per_page'] = min( 100, max( 1, intval( $atts['per_page'] ) ) );

		ob_start();
		$this->render_products_grid( $atts );
		return ob_get_clean();
	}

	/**
	 * Builds the data needed by the grid template.
	 *
	 * @param array $atts Parsed shortcode attributes.
	 * @return void
	 */
	private function render_products_grid( $atts ) {
		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
			)
		);

		$restricted_categories = $this->parse_category_list( $atts['categories'] );
		$special_categories    = $this->parse_category_list( $atts['special'] );

		if ( ! empty( $restricted_categories ) ) {
			$categories = array_values(
				array_filter(
					$categories,
					function ( $cat ) use ( $restricted_categories ) {
						return in_array( $cat->term_id, $restricted_categories, true );
					}
				)
			);
		}

		$products_data = $this->products_query_service->get_products_data(
			array(
				'categories' => array(),
				'per_page'   => $atts['per_page'],
				'page'       => 1,
				'search'     => '',
			)
		);

		include ALYNT_PG_PLUGIN_DIR . 'public/partials/products-grid.php';
	}

	/**
	 * Parses a comma-separated category list into normalized term IDs.
	 *
	 * @param string $category_list Comma-separated category list.
	 * @return array<int>
	 */
	private function parse_category_list( $category_list ) {
		if ( empty( $category_list ) ) {
			return array();
		}

		return $this->products_query_service->normalize_category_ids( explode( ',', $category_list ) );
	}
}
