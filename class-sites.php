<?php
/**
 * Text module class
 *
 * @package Hogan
 */

declare( strict_types = 1 );
namespace DSS\Hogan;

use Urlbox\Screenshots\Urlbox;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get env variable.
 *
 * @param string $key Variable key.
 *
 * @return string
 */
function env( string $key ): string {
	return isset( $_ENV[ $key ] ) ? $_ENV[ $key ] : '';
}

/**
 * Load environment variables
 */
if ( file_exists( __DIR__ . '/.env' ) ) {
	$dotenv = new \Symfony\Component\Dotenv\Dotenv();
	$dotenv->load( __DIR__ . '/.env' );
}

if ( ! class_exists( '\\DSS\\Hogan\\Sites' ) && class_exists( '\\Dekode\\Hogan\\Module' ) ) {

	/**
	 * Text module class (WYSIWYG).
	 *
	 * @extends Modules base class.
	 */
	class Sites extends \Dekode\Hogan\Module {

		/**
		 * WYSIWYG content for use in template.
		 *
		 * @var string $content
		 */
		public $theme;
		public $number_of_sites;

		public $card_type = 'large';
		/**
		 * Module constructor.
		 */
		public function __construct() {

			$this->label    = __( 'Sites', 'dss-hogan-sites' );
			$this->template = __DIR__ . '/assets/template.php';

			parent::__construct();
		}

		/**
		 * Field definitions for module.
		 *
		 * @return array $fields Fields for this module
		 */
		public function get_fields() : array {

			return [
				[
					'type'          => 'select',
					'key'           => $this->field_key . '_theme',
					'label'         => __( 'Sites with the following theme', 'dss-hogan-sites' ),
					'name'          => 'theme',
					'instructions'  => __( 'Choose site theme', 'dss-hogan-sites' ),
					'choices'       => [
						'all'                             => __( 'Alle', 'dss-hogan-sites' ),
						'nettsteder-mal-utvalg'           => __( 'Utvalg', 'dss-hogan-sites' ),
						'nettsteder-mal-fleksibel'        => __( 'Fleksibel', 'dss-hogan-sites' ),
						'nettsteder-mal-standard'         => __( 'Standard', 'dss-hogan-sites' ),
						'nettsteder-gutenberg-mal-utvalg' => __( 'Utvalg (Gutenberg)', 'dss-hogan-sites' ),
						'nettsteder-gutenberg-mal-fleksibel' => __( 'Fleksibel (Gutenberg)', 'dss-hogan-sites' ),
						'nettsteder-gutenberg-mal-standard' => __( 'Standard (Gutenberg)', 'dss-hogan-sites' ),
					],
					'allow_null'    => 0,
					'default_value' => 'all',
					'multiple'      => 1,
					'ui'            => 1,
					'return_format' => 'value',
					'wrapper'       => [
						'width' => '50',
					],
				],
				[
					'type'          => 'number',
					'key'           => $this->field_key . '_number_of_sites',
					'label'         => __( 'Number of items', 'dss-hogan-sites' ),
					'name'          => 'number_of_sites',
					'instructions'  => __( 'Choose the number of items for the list, 0 = all', 'dss-hogan-sites' ),
					'required'      => 0,
					// 'conditional_logic' => [
					// [
					// [
					// 'field'    => $this->field_key . '_list_type',
					// 'operator' => '==',
					// 'value'    => 'automatic',
					// ],
					// ],
					// ],
					'default_value' => 0,
					'min'           => 0,
					'max'           => apply_filters( 'hogan/module/portfolio/number_of_sites', 9 ),
					'step'          => 1,
					'wrapper'       => [
						'width' => '50',
					],
				],
			];
		}

		/**
		 * Map raw fields from acf to object variable.
		 *
		 * @param array $raw_content Content values.
		 * @param int   $counter Module location in page layout.
		 * @return void
		 */
		public function load_args_from_layout_content( array $raw_content, int $counter = 0 ) {

			// $this->theme           = trim( $raw_content['theme'] ?? '' );
			$this->theme           = array_filter( $raw_content['theme'] );
			$this->number_of_sites = filter_var( $raw_content['number_of_sites'], FILTER_VALIDATE_INT, [ 'default' => 0 ] );

			parent::load_args_from_layout_content( $raw_content, $counter );
		}

		/**
		 * Validate module content before template is loaded.
		 *
		 * @return bool Whether validation of the module is successful / filled with content.
		 */
		public function validate_args() : bool {
			return ! empty( $this->theme );
		}


		function portfolio( $attributes = [] ) {
			global $wp_version;

			$attributes = shortcode_atts(
				[
					'sites'   => 0,
					'width'   => 0,
					'height'  => 0,
					'expires' => 600, // 10 minutes
					'orderby' => 'modified=DESC&title=DESC',
					'theme'   => '',
					'num'     => 0,
					'list'    => false,
					'all'     => false,
					'noshow'  => [],
				],
				$attributes,
				'networkportfolio'
			);

			// validate
			// $attributes['cols']     = filter_var( $attributes['cols'],     FILTER_VALIDATE_INT, array( 'default' => 3 ) );
			$attributes['expires'] = filter_var( $attributes['expires'], FILTER_VALIDATE_INT, [ 'default' => 600 ] );
			$attributes['orderby'] = filter_var( $attributes['orderby'], FILTER_SANITIZE_STRING, [ 'default' => 'modified=DESC&title=DESC' ] );
			$attributes['noshow']  = ( 0 !== count( $attributes['noshow'] ) ) ? explode( ',', $attributes['noshow'] ) : [];

			$shortcode_transient_id = 'network_portfolio' . md5( serialize( $attributes ) );// create unique transient id pr shortcode used
			if ( false === ( $network_blogs = get_site_transient( $shortcode_transient_id ) ) ) {
				$sites         = [];
				$network_blogs = [];
				if ( 0 != $attributes['sites'] ) {
					$sites = explode( ',', $attributes['sites'] );
					foreach ( $sites as $site ) {
						$network_blogs = array_merge(
							$network_blogs,
							get_sites(
								[
									'ID'     => $site,
									'public' => true,
								]
							)
						);
					}
					// sort on last_updated, newest first
					usort(
						$network_blogs,
						function( $a, $b ) {
							return $a->last_updated < $b->last_updated;
						}
					);
				} else {
					$network_blogs = get_sites(
						[
							'public'            => true,
							'orderby'           => 'last_updated',
							'order'             => 'DESC',
							'update_site_cache' => true,
							'site__not_in'      => $attributes['noshow'],

						]
					);
				}
			}
			set_site_transient( $shortcode_transient_id, $network_blogs, $attributes['expires'] );

			$current_site = get_current_blog_id();

			$thumb_settings = [
				'width'         => '310', // ( 0 != $attributes['width'] ) ? $attributes['width'] : \NetworkPortfolio\Helper::get_option( 'networkportfolio[width]', '430' ),
				'height'        => '175', // ( 0 != $attributes['height'] ) ? $attributes['height'] : \NetworkPortfolio\Helper::get_option( 'networkportfolio[height]', '225' ),
				'border_width'  => '1', // \NetworkPortfolio\Helper::get_option( 'networkportfolio[border_width]', '0' ),
				'border_radius' => '0', // \NetworkPortfolio\Helper::get_option( 'networkportfolio[border_radius]', '0' ),
				'border_color'  => '#D7D3D3', // \NetworkPortfolio\Helper::get_option( 'networkportfolio[border_color]', '#000000' ),
			];

			$show_in_portfolio = get_site_option( 'network_portfolio' );
			$output_string     = ( false === $attributes['list'] ) ? '<ul class="list-items card-type-large">' : '<ul class="network-portfolio-list">';
			if ( 0 < count( (array) $network_blogs ) ) {
				$num_thumbs = 0;
				$list_sites = [];
				foreach ( $network_blogs as $network_blog_object ) {
					$network_blog = (array) $network_blog_object;
					if ( false === $attributes['all'] && ( ! isset( $show_in_portfolio[ $network_blog['blog_id'] ] ) || 'visible' != $show_in_portfolio[ $network_blog['blog_id'] ] ) ) {
						continue;
					}

					$network_blog_details = get_blog_details( $network_blog['blog_id'] );
					if ( false === $network_blog_details ) {
						continue;
					}

					switch_to_blog( $network_blog_details->blog_id );
					$network_blog_details->theme       = get_stylesheet();
					$site_url                          = ( function_exists( 'domain_mapping_siteurl' ) && 'NA' != domain_mapping_siteurl( 'NA' ) ) ? domain_mapping_siteurl( false ) : $network_blog_details->home;
					$network_blog_details->site_url    = $site_url;
					$network_blog_details->blog_public = get_option( 'blog_public', 1 );
					restore_current_blog();
					if ( 2 == $network_blog_details->blog_public ) {
						// Restricted Site Access plug-in is blocking public access to this site
						continue;
					}
					if ( isset( $attributes['theme'] ) && false === array_search( 'all', $attributes['theme'] ) ) {

						if ( isset( $attributes['theme'] ) && false === array_search( $network_blog_details->theme, $attributes['theme'], true ) ) {
							continue;
						}
					}
					$thumb_settings['url']         = $network_blog_details->site_url;
					$thumb_settings['title']       = $network_blog_details->blogname;
					$thumb_settings['description'] = get_bloginfo( 'description' );

					if ( 0 === $attributes['num'] || $attributes['num'] > $num_thumbs ) {
						if ( false === $attributes['list'] ) {
							$header_image_url = $this->webshot( $thumb_settings );
							$output_string   .= $header_image_url;
						} else {
							$list_sites[] = $network_blog_details;
						}
						$num_thumbs++;
					}
				} // End foreach().
			} // End if().

			if ( false !== $attributes['list'] ) {
				// sort on blogname, ascending order
				usort(
					$list_sites,
					function( $a, $b ) {
						return strtolower( $a->blogname ) > strtolower( $b->blogname );
					}
				);
				foreach ( (array) $list_sites as $list_site ) {
					$output_string .= sprintf( '<li><a href="%s">%s</a></li>', $list_site->site_url, $list_site->blogname );
				}
			}

			$output_string .= '</ul>';

			switch_to_blog( $current_site );
			$GLOBALS['_wp_switched_stack'] = [];
			$GLOBALS['switched']           = false;

			return $output_string;
		}


		function webshot( $arguments ) {
			try {
				$urlbox = Urlbox::fromCredentials( env( 'URLBOX_KEY' ), env( 'URLBOX_SECRET' ) );

				$settings = [
					'width'        => '1600',
					'retina'       => 'true',
					'height'       => '1024',
					'thumb_width'  => $arguments['width'],
					'thumb_height' => $arguments['height'],
					'url'          => $arguments['url'],
				];

				$img_width  = $arguments['width'];
				$img_height = $arguments['height'];
				// if ( 0 !== $arguments['border_width'] ) {
				// $img_width  = $img_width + ( $arguments['border_width'] * 2 );
				// $img_height = $img_height + ( $arguments['border_width'] * 2 );
				// }

				return sprintf(
					'<li class="list-item">
					<a href="%1$s">
						<div class="column">
							<div class="featured-image">
								<img src="%2$s" width="%3$s" height="%4$s" class="attachment-medium-3-2 size-medium-3-2 wp-post-image" sizes="(max-width: 432px) 100vw, 432px"/>
							</div>
						</div>
						<div class="column">
							<h3 class="entry-title">%5$s</h3>
							<div class="entry-summary"><p>%6$s</p></div>
						</div>
					</a>
				</li>',
					$arguments['url'],
					$urlbox->generateUrl( $settings ),
					$img_width,
					$img_height,
					$arguments['title'],
					''
					// $arguments['description']
				);
			} catch ( \Exception $e ) {
				return '<!-- Error: ' . $e->getMessage() . ' -->';
			}
		}
	}
}
