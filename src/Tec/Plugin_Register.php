<?php
/**
 * Handles the Extension plugin dependency manifest registration.
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\CustomTaxonomyFilter
 */

namespace Tribe\Extensions\CustomTaxonomyFilter;

use Tribe__Abstract_Plugin_Register as Abstract_Plugin_Register;

/**
 * Class Plugin_Register.
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\CustomTaxonomyFilter
 *
 * @see Tribe__Abstract_Plugin_Register For the plugin dependency manifest registration.
 */
class Plugin_Register extends Abstract_Plugin_Register {
	protected $base_dir     = Plugin::FILE;
	protected $version      = Plugin::VERSION;
	protected $main_class   = Plugin::class;
	protected $dependencies = [
		'parent-dependencies' => [
			'Tribe__Events__Filterbar__View' => '',
		],
	];
}
