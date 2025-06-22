<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://lucamainieri.it
 * @since      1.0.0
 *
 * @package    Faq_Ai_Generator
 * @subpackage Faq_Ai_Generator/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Faq_Ai_Generator
 * @subpackage Faq_Ai_Generator/public
 * @author     Luca Mainieri <info@neting.it>
 */
class Faq_Ai_Generator_Public
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Aggiungi l'hook per visualizzare le FAQ nel contenuto
		add_filter('the_content', array($this, 'display_faqs_in_content'));

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Faq_Ai_Generator_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Faq_Ai_Generator_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_register_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/faq-ai-generator-public.css', array(), $this->version, 'all');
		wp_enqueue_style($this->plugin_name);

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Faq_Ai_Generator_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Faq_Ai_Generator_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		global $wp_version;

		if (version_compare($wp_version, '6.3', '>=')) {
			// WP >= 6.3: usa il parametro args con strategia 'defer'
			wp_register_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/faq-ai-generator-public.js', array('jquery'), $this->version, array('in_footer' => true, 'strategy' => 'defer'));
		} else {
			// WP < 6.3: usa il parametro in_footer tradizionale
			wp_register_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/faq-ai-generator-public.js', array('jquery'), $this->version, false);
		}

		wp_enqueue_script($this->plugin_name);

	}

	/**
	 * Visualizza le FAQ nel contenuto
	 *
	 * @since    1.0.0
	 * @param    string    $content    Il contenuto del post
	 * @return   string               Il contenuto modificato
	 */
	public function display_faqs_in_content($content)
	{
		// Verifica se siamo in una pagina singola
		if (!is_singular()) {
			return $content;
		}

		// Recupera il post type corrente
		$current_post_type = get_post_type();

		// Recupera le impostazioni
		$settings = get_option('faq_ai_generator_settings', array());
		$enabled_post_types = isset($settings['enabled_post_types']) ? $settings['enabled_post_types'] : array();

		// Verifica se il post type è abilitato
		$is_enabled = $current_post_type === 'post' ||
			$current_post_type === 'page' ||
			(isset($enabled_post_types[$current_post_type]) && $enabled_post_types[$current_post_type]);

		if (!$is_enabled) {
			return $content;
		}

		// Recupera i dati delle FAQ
		$post_id = get_the_ID();
		$faq_data = get_post_meta($post_id, '_faq_ai_data', true);

		if (empty($faq_data['faqs']) || !$faq_data['display_in_content']) {
			return $content;
		}

		// Genera l'HTML delle FAQ
		$faq_html = '<div class="faq-ai-generator-container">';
		$faq_html .= '<h2>' . __('Frequently Asked Questions', 'faq-ai-generator') . '</h2>';
		$faq_html .= '<div class="faq-ai-generator-list">';

		foreach ($faq_data['faqs'] as $faq) {
			$faq_html .= sprintf(
				'<div class="faq-item">
					<div class="faq-question">%s</div>
					<div class="faq-answer">%s</div>
				</div>',
				esc_html($faq['question']),
				wpautop(wp_kses_post($faq['answer']))
			);
		}

		$faq_html .= '</div></div>';

		return $content . $faq_html;
	}

	/**
	 * Add the schema.org for the FAQ
	 *
	 * @since    1.0.0
	 */
	public function add_faq_schema()
	{
		if (!is_singular()) {
			return;
		}

		// Get the current post type
		$current_post_type = get_post_type();

		// Get the settings
		$settings = get_option('faq_ai_generator_settings', array());
		$enabled_post_types = isset($settings['enabled_post_types']) ? $settings['enabled_post_types'] : array();

		// Check if the post type is enabled
		$is_enabled = $current_post_type === 'post' ||
			$current_post_type === 'page' ||
			(isset($enabled_post_types[$current_post_type]) && $enabled_post_types[$current_post_type]);

		if (!$is_enabled) {
			return;
		}

		$post_id = get_the_ID();
		$faq_data = get_post_meta($post_id, '_faq_ai_data', true);

		if (empty($faq_data['faqs'])) {
			return;
		}

		$schema = array(
			'@context' => 'https://schema.org',
			'@type' => 'FAQPage',
			'mainEntity' => array()
		);

		foreach ($faq_data['faqs'] as $faq) {
			$schema['mainEntity'][] = array(
				'@type' => 'Question',
				'name' => wp_strip_all_tags($faq['question']),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text' => wp_strip_all_tags($faq['answer'])
				)
			);
		}

		// Generate the JSON-LD for the schema.org
		// $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
		$settings = get_option('faq_ai_generator_settings', array());
		if (isset($settings['debug']) && $settings['debug']) {
			$flags = JSON_PRETTY_PRINT;
		}
		// JSON_UNESCAPED_UNICODE is necessary to avoid issues with special characters
		$json_data = wp_json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

		// Print the JSON-LD schema directly
		echo "\n<!-- FAQ AI Generator Schema -->\n";
		// We don't use esc_html here because it would convert special characters into HTML entities
		// that are not appropriate in a script tag. wp_json_encode has already sanitized the output.
		echo '<script type="application/ld+json" id="faq-ai-schema">' . $json_data . '</script>' . "\n";
	}

}
