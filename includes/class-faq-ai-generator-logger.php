<?php

/**
 * Logger utility for the plugin.
 *
 * Handles logging functionality with different levels of severity.
 *
 * @link       https://lucamainieri.it
 * @since      1.1.0
 *
 * @package    Faq_Ai_Generator
 * @subpackage Faq_Ai_Generator/includes
 */

/**
 * Logger utility for the plugin.
 *
 * @package    Faq_Ai_Generator
 * @subpackage Faq_Ai_Generator/includes
 * @author     Luca Mainieri <info@neting.it>
 */
class Faq_Ai_Generator_Logger
{

	/**
	 * Log levels
	 */
	const DEBUG = 10;    // Detailed information for debugging
	const INFO = 20;     // General information about the operation
	const WARNING = 30;  // Non-critical warnings
	const ERROR = 40;    // Errors that require attention

	/**
	 * The current log level
	 * 
	 * @var int
	 */
	private static $log_level = self::ERROR; // Default: only errors

	/**
	 * Prefix for the log messages
	 * 
	 * @var string
	 */
	private static $prefix = 'FAQ AI Generator: ';

	/**
	 * Flag to force debug (regardless of the settings)
	 * 
	 * @var bool
	 */
	public static $force_debug = false;

	/**
	 * Initialize the logger
	 *
	 * @since    1.1.0
	 */
	public static function init()
	{
		$settings = get_option('faq_ai_generator_settings', []);

		// Set the log level based on the settings
		if ((isset($settings['debug_mode']) && $settings['debug_mode']) || self::$force_debug) {
			// If debug_mode is active, set the level to DEBUG (everything is logged)
			self::$log_level = self::DEBUG;
		}
	}

	/**
	 * Register a log message
	 *
	 * @since    1.1.0
	 * @param    mixed     $message    The message to log (string or array/object)
	 * @param    int       $level      The log level
	 * @param    array     $context    Additional contextual information
	 * @return   void
	 */
	public static function log($message, $level = self::INFO, $context = [])
	{
		// Skip if the level is lower than the configured one
		if ($level < self::$log_level) {
			return;
		}

		// Check if the WordPress debugging is active
		if (!defined('WP_DEBUG') || !WP_DEBUG) {
			return;
		}

		// Format the log header
		$log_entry = gmdate('[Y-m-d H:i:s]') . ' ' . self::get_level_name($level) . ': ';

		// Format the message
		if (is_array($message) || is_object($message)) {
			$log_entry .= print_r($message, true);
		} else {
			$log_entry .= $message;
		}

		// Add the context if present
		if (!empty($context)) {
			$log_entry .= ' - Context: ' . print_r($context, true);
		}

		// Add the plugin prefix
		$log_entry = self::$prefix . $log_entry;

		// Write in the WordPress log
		if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			error_log($log_entry);
		}
	}

	/**
	 * Get the textual name of the log level
	 *
	 * @since    1.1.0
	 * @param    int       $level    The numeric log level
	 * @return   string              The name of the level
	 */
	private static function get_level_name($level)
	{
		switch ($level) {
			case self::DEBUG:
				return 'DEBUG';
			case self::INFO:
				return 'INFO';
			case self::WARNING:
				return 'WARNING';
			case self::ERROR:
				return 'ERROR';
			default:
				return 'UNKNOWN';
		}
	}

	/**
	 * Register a debug message
	 *
	 * @since    1.1.0
	 * @param    mixed     $message    The message to log
	 * @param    array     $context    Contextual information
	 * @return   void
	 */
	public static function debug($message, $context = [])
	{
		self::log($message, self::DEBUG, $context);
	}

	/**
	 * Register an informational message
	 *
	 * @since    1.1.0
	 * @param    mixed     $message    The message to log
	 * @param    array     $context    Contextual information
	 * @return   void
	 */
	public static function info($message, $context = [])
	{
		self::log($message, self::INFO, $context);
	}

	/**
	 * Register a warning message
	 *
	 * @since    1.1.0
	 * @param    mixed     $message    The message to log
	 * @param    array     $context    Contextual information
	 * @return   void
	 */
	public static function warning($message, $context = [])
	{
		self::log($message, self::WARNING, $context);
	}

	/**
	 * Register an error message
	 *
	 * @since    1.1.0
	 * @param    mixed     $message    The message to log
	 * @param    array     $context    Contextual information
	 * @return   void
	 */
	public static function error($message, $context = [])
	{
		self::log($message, self::ERROR, $context);
	}

	/**
	 * Rotate the log files if they become too large
	 * 
	 * @since    1.1.0
	 * @return   void
	 */
	public static function rotate_logs()
	{
		if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
			$log_file = WP_DEBUG_LOG;
		} else {
			$log_file = WP_CONTENT_DIR . '/debug.log';
		}

		if (file_exists($log_file) && filesize($log_file) > 10 * 1024 * 1024) { // 10MB
			// Use WP_Filesystem instead of rename() for better security
			global $wp_filesystem;

			// Initialize WP_Filesystem if not already done
			if (empty($wp_filesystem)) {
				require_once(ABSPATH . '/wp-admin/includes/file.php');
				WP_Filesystem();
			}

			$backup_file = $log_file . '.' . gmdate('Y-m-d-H-i-s') . '.bak';
			$wp_filesystem->move($log_file, $backup_file, true);
		}
	}
}

// Initialize the logger when the plugin is loaded
add_action('plugins_loaded', ['Faq_Ai_Generator_Logger', 'init']);

// Schedule the log rotation
add_action('faq_ai_rotate_logs', ['Faq_Ai_Generator_Logger', 'rotate_logs']);

// Register the schedule if not already done
if (!wp_next_scheduled('faq_ai_rotate_logs')) {
	wp_schedule_event(time(), 'daily', 'faq_ai_rotate_logs');
}

// Remove the schedule when the plugin is deactivated
register_deactivation_hook(dirname(dirname(__FILE__)) . '/faq-ai-generator.php', function () {
	wp_clear_scheduled_hook('faq_ai_rotate_logs');
});