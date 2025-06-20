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
	const DEBUG = 10;    // Informazioni dettagliate per debugging
	const INFO = 20;     // Informazioni generali sul funzionamento
	const WARNING = 30;  // Avvisi non critici
	const ERROR = 40;    // Errori che richiedono attenzione

	/**
	 * Il livello di log corrente
	 * 
	 * @var int
	 */
	private static $log_level = self::ERROR; // Default: solo errori

	/**
	 * Prefix per i messaggi di log
	 * 
	 * @var string
	 */
	private static $prefix = 'FAQ AI Generator: ';

	/**
	 * Flag per forzare il debug (indipendentemente dalle impostazioni)
	 * 
	 * @var bool
	 */
	public static $force_debug = false;

	/**
	 * Inizializza il logger
	 *
	 * @since    1.1.0
	 */
	public static function init()
	{
		$settings = get_option('faq_ai_generator_settings', []);

		// Imposta il livello di log basato sulle impostazioni
		if ((isset($settings['debug_mode']) && $settings['debug_mode']) || self::$force_debug) {
			// Se debug_mode è attivo, imposta il livello a DEBUG (tutto viene loggato)
			self::$log_level = self::DEBUG;
		}
	}

	/**
	 * Registra un messaggio di log
	 *
	 * @since    1.1.0
	 * @param    mixed     $message    Il messaggio da loggare (stringa o array/oggetto)
	 * @param    int       $level      Il livello di log
	 * @param    array     $context    Informazioni contestuali aggiuntive
	 * @return   void
	 */
	public static function log($message, $level = self::INFO, $context = [])
	{
		// Skip se il livello è inferiore a quello configurato
		if ($level < self::$log_level) {
			return;
		}

		// Controlla se il debugging di WordPress è attivo
		if (!defined('WP_DEBUG') || !WP_DEBUG) {
			return;
		}

		// Formatta l'intestazione del log
		$log_entry = gmdate('[Y-m-d H:i:s]') . ' ' . self::get_level_name($level) . ': ';

		// Formatta il messaggio
		if (is_array($message) || is_object($message)) {
			$log_entry .= print_r($message, true);
		} else {
			$log_entry .= $message;
		}

		// Aggiungi il contesto se presente
		if (!empty($context)) {
			$log_entry .= ' - Context: ' . print_r($context, true);
		}

		// Aggiungi il prefisso del plugin
		$log_entry = self::$prefix . $log_entry;

		// Scrive nel log di WordPress
		if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			error_log($log_entry);
		}
	}

	/**
	 * Ottiene il nome testuale del livello di log
	 *
	 * @since    1.1.0
	 * @param    int       $level    Il livello di log numerico
	 * @return   string              Il nome del livello
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
	 * Registra un messaggio di debug
	 *
	 * @since    1.1.0
	 * @param    mixed     $message    Il messaggio da loggare
	 * @param    array     $context    Informazioni contestuali
	 * @return   void
	 */
	public static function debug($message, $context = [])
	{
		self::log($message, self::DEBUG, $context);
	}

	/**
	 * Registra un messaggio informativo
	 *
	 * @since    1.1.0
	 * @param    mixed     $message    Il messaggio da loggare
	 * @param    array     $context    Informazioni contestuali
	 * @return   void
	 */
	public static function info($message, $context = [])
	{
		self::log($message, self::INFO, $context);
	}

	/**
	 * Registra un messaggio di avviso
	 *
	 * @since    1.1.0
	 * @param    mixed     $message    Il messaggio da loggare
	 * @param    array     $context    Informazioni contestuali
	 * @return   void
	 */
	public static function warning($message, $context = [])
	{
		self::log($message, self::WARNING, $context);
	}

	/**
	 * Registra un messaggio di errore
	 *
	 * @since    1.1.0
	 * @param    mixed     $message    Il messaggio da loggare
	 * @param    array     $context    Informazioni contestuali
	 * @return   void
	 */
	public static function error($message, $context = [])
	{
		self::log($message, self::ERROR, $context);
	}

	/**
	 * Ruota i file di log se diventano troppo grandi
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

// Inizializza il logger quando il plugin viene caricato
add_action('plugins_loaded', ['Faq_Ai_Generator_Logger', 'init']);

// Schedula la rotazione dei log
add_action('faq_ai_rotate_logs', ['Faq_Ai_Generator_Logger', 'rotate_logs']);

// Registra lo schedule se non è già stato fatto
if (!wp_next_scheduled('faq_ai_rotate_logs')) {
	wp_schedule_event(time(), 'daily', 'faq_ai_rotate_logs');
}

// Rimuovi lo schedule quando il plugin viene disattivato
register_deactivation_hook(dirname(dirname(__FILE__)) . '/faq-ai-generator.php', function () {
	wp_clear_scheduled_hook('faq_ai_rotate_logs');
});