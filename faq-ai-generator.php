<?php

/**
 * The plugin bootstrap file
 *
 * @link              https://github.com/netingweb/faq-ai-generator-plugin
 * @since             1.1.3
 * @package           Faq_Ai_Generator
 *
 * @wordpress-plugin
 * Plugin Name:       FAQ AI Generator
 * Plugin URI:        https://github.com/netingweb/faq-ai-generator-plugin
 * Description:       Automatically generate FAQs for your WordPress content using artificial intelligence.
 * Version:           1.1.9
 * Requires at least: 5.0
 * Tested up to:      7.1
 * Requires PHP:      7.4
 * Author:            netingweb
 * Author URI:        https://profiles.wordpress.org/netingweb/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       faq-ai-generator
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

// Check WordPress version
if (version_compare(get_bloginfo('version'), '5.0', '<')) {
	add_action('admin_notices', function () {
		echo '<div class="notice notice-error"><p>';
		echo '<strong>FAQ AI Generator</strong> requires WordPress 5.0 or higher. Please upgrade WordPress to use this plugin.';
		echo '</p></div>';
	});
	return;
}

// Check PHP version
if (version_compare(PHP_VERSION, '7.4', '<')) {
	add_action('admin_notices', function () {
		echo '<div class="notice notice-error"><p>';
		echo '<strong>FAQ AI Generator</strong> requires PHP 7.4 or higher. Please upgrade PHP to use this plugin.';
		echo '</p></div>';
	});
	return;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('FAQ_AI_GENERATOR_VERSION', '1.1.9');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-faq-ai-generator-activator.php
 */
function faq_ai_generator_activate()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-faq-ai-generator-activator.php';
	Faq_Ai_Generator_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-faq-ai-generator-deactivator.php
 */
function faq_ai_generator_deactivate()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-faq-ai-generator-deactivator.php';
	Faq_Ai_Generator_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'faq_ai_generator_activate');
register_deactivation_hook(__FILE__, 'faq_ai_generator_deactivate');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-faq-ai-generator.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function faq_ai_generator_run()
{

	$plugin = new Faq_Ai_Generator();
	$plugin->run();

}
faq_ai_generator_run();
