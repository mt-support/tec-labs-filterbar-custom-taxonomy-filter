<?php
/**
 * Plugin Class.
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\CustomTaxonomyFilter
 */

namespace Tribe\Extensions\CustomTaxonomyFilter;

use TEC\Common\Contracts\Service_Provider;
/**
 * Class Plugin
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\CustomTaxonomyFilter
 */
class Plugin extends Service_Provider {
	/**
	 * Stores the version for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Stores the base slug for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const SLUG = 'custom-taxonomy-filter';

	/**
	 * Stores the base slug for the extension.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const FILE = TRIBE_EXTENSION_CUSTOM_TAXONOMY_FILTER_FILE;

	/**
	 * @since 1.0.0
	 *
	 * @var string Plugin Directory.
	 */
	public $plugin_dir;

	/**
	 * @since 1.0.0
	 *
	 * @var string Plugin path.
	 */
	public $plugin_path;

	/**
	 * @since 1.0.0
	 *
	 * @var string Plugin URL.
	 */
	public $plugin_url;

	/**
	 * @since 1.0.0
	 *
	 * @var Settings
	 *
	 * TODO: Remove if not using settings
	 */
	private $settings;

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		// Set up the plugin provider properties.
		$this->plugin_path = trailingslashit( dirname( static::FILE ) );
		$this->plugin_dir  = trailingslashit( basename( $this->plugin_path ) );
		$this->plugin_url  = plugins_url( $this->plugin_dir, $this->plugin_path );

		// Register this provider as the main one and use a bunch of aliases.
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'extension.custom_taxonomy_filter', $this );
		$this->container->singleton( 'extension.custom_taxonomy_filter.plugin', $this );
		$this->container->register( PUE::class );

		if ( ! $this->check_plugin_dependencies() ) {
			// If the plugin dependency manifest is not met, then bail and stop here.
			return;
		}

		// Do the settings.
		// TODO: Remove if not using settings
		$this->get_settings();

		// Start binds.

		// Register filter on the back-end
		add_action( 'tribe_events_filters_create_filters', [ $this, 'tec_kb_create_filter' ] );

		// Make it work with calendar views.
		add_filter( 'tribe_context_locations', [ $this, 'tec_kb_filter_context_locations' ] );
		add_filter( 'tribe_events_filter_bar_context_to_filter_map', [ $this, 'tec_kb_filter_map' ] );

		// End binds.

		$this->container->register( Hooks::class );
		$this->container->register( Assets::class );
	}

	/**
	 * Checks whether the plugin dependency manifest is satisfied or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the plugin dependency manifest is satisfied or not.
	 */
	protected function check_plugin_dependencies() {
		$this->register_plugin_dependencies();

		return tribe_check_plugin( static::class );
	}

	/**
	 * Registers the plugin and dependency manifest among those managed by Tribe Common.
	 *
	 * @since 1.0.0
	 */
	protected function register_plugin_dependencies() {
		$plugin_register = new Plugin_Register();
		$plugin_register->register_plugin();

		$this->container->singleton( Plugin_Register::class, $plugin_register );
		$this->container->singleton( 'extension.custom_taxonomy_filter', $plugin_register );
	}

	/**
	 * Includes the custom filter class and creates an instance of it.
	 */
	function tec_kb_create_filter() {
		if ( ! class_exists( 'Tribe__Events__Filterbar__Filter' ) ) {
			return;
		}

		include_once $this->plugin_path . 'src/Filters/Custom_Taxonomy_Filter.php';

		new \Custom_Taxonomy_Filter(
			__( 'Event Tag', 'tribe-events-filter-view' ),
			'custom_taxonomy'
		);
	}

	/**
	 * Filters the Context locations to let the Context know how to fetch the value of the filter from a request.
	 *
	 * Here we add the `time_of_day_custom` as a read-only Context location: we'll not need to write it.
	 *
	 * @param array<string,array> $locations A map of the locations the Context supports and is able to read from and write to.
	 *
	 * @return array<string,array> The filtered map of Context locations, with the one required from the filter added to it.
	 */
	function tec_kb_filter_context_locations( array $locations ) {
		// Read the filter-selected values, if any, from the URL request vars.
		$locations['custom_taxonomy'] = [
			'read' => [
				\Tribe__Context::QUERY_VAR   => [ 'tribe_custom_taxonomy' ],
				\Tribe__Context::REQUEST_VAR => [ 'tribe_custom_taxonomy' ]
			],
		];

		// Return the modified $locations.
		return $locations;
	}

	/**
	 * Filters the map of filters available on the front-end to include the custom one.
	 *
	 * @param array<string,string> $map A map relating the filter slugs to their respective classes.
	 *
	 * @return array<string,string> The filtered slug to filter the class map.
	 */
	function tec_kb_filter_map( array $map ) {
		if ( ! class_exists( 'Tribe__Events__Filterbar__Filter' ) ) {
			// This would not make much sense, but let's be cautious.
			return $map;
		}

		// Include the filter class.
		include_once $this->plugin_path . 'src/Filters/Custom_Taxonomy_Filter.php';

		// Add the filter class to our filters map.
		$map['custom_taxonomy'] = 'Custom_Taxonomy_Filter';

		// Return the modified $map.
		return $map;
	}

	/**
	 * Get this plugin's options prefix.
	 *
	 * Settings_Helper will append a trailing underscore before each option.
	 *
	 * @return string
     *
	 * @see \Tribe\Extensions\CustomTaxonomyFilter\Settings::set_options_prefix()
	 *
	 * TODO: Remove if not using settings
	 */
	private function get_options_prefix() {
		return (string) str_replace( '-', '_', 'tec-labs-custom-taxonomy-filter' );
	}

	/**
	 * Get Settings instance.
	 *
	 * @return Settings
	 *
	 * TODO: Remove if not using settings
	 */
	private function get_settings() {
		if ( empty( $this->settings ) ) {
			$this->settings = new Settings( $this->get_options_prefix() );
		}

		return $this->settings;
	}

	/**
	 * Get all of this extension's options.
	 *
	 * @return array
	 *
	 * TODO: Remove if not using settings
	 */
	public function get_all_options() {
		$settings = $this->get_settings();

		return $settings->get_all_options();
	}

	/**
	 * Get a specific extension option.
	 *
	 * @param $option
	 * @param string $default
	 *
	 * @return array
	 *
	 * TODO: Remove if not using settings
	 */
	public function get_option( $option, $default = '' ) {
		$settings = $this->get_settings();

		return $settings->get_option( $option, $default );
	}
}
