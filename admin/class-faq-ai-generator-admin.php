<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://lucamainieri.it
 * @since      1.0.0
 *
 * @package    Faq_Ai_Generator
 * @subpackage Faq_Ai_Generator/admin
 */

// Includi le classi necessarie
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-faq-ai-generator-api.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-faq-ai-generator-models.php';

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Faq_Ai_Generator
 * @subpackage Faq_Ai_Generator/admin
 * @author     Luca Mainieri <info@neting.it>
 */
class Faq_Ai_Generator_Admin {

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
	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-faq-ai-generator-models.php';
	}

	/**
	 * Whether the current admin screen needs metabox assets.
	 *
	 * @since    1.1.3
	 * @param    string $hook Current admin page hook.
	 * @return   bool
	 */
	private function is_metabox_screen($hook) {
		return in_array($hook, array('post.php', 'post-new.php'), true);
	}

	/**
	 * Whether the current admin screen is the plugin settings page.
	 *
	 * @since    1.1.3
	 * @param    string $hook Current admin page hook.
	 * @return   bool
	 */
	private function is_settings_screen($hook) {
		return $hook === 'settings_page_' . $this->plugin_name;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 * @param    string $hook Current admin page hook.
	 */
	public function enqueue_styles($hook = '') {
		if ($this->is_settings_screen($hook)) {
			wp_enqueue_style(
				$this->plugin_name . '-admin',
				plugin_dir_url(__FILE__) . 'css/faq-ai-generator-admin.css',
				array(),
				$this->version,
				'all'
			);
		}

		if ($this->is_metabox_screen($hook)) {
			wp_enqueue_style(
				$this->plugin_name . '-metabox',
				plugin_dir_url(__FILE__) . 'css/faq-ai-generator-metabox.css',
				array(),
				$this->version,
				'all'
			);
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @param    string $hook Current admin page hook.
	 */
	public function enqueue_scripts($hook = '') {
		if ($this->is_settings_screen($hook)) {
			wp_enqueue_script(
				$this->plugin_name . '-admin',
				plugin_dir_url(__FILE__) . 'js/faq-ai-generator-admin.js',
				array('jquery'),
				$this->version,
				true
			);

			wp_localize_script($this->plugin_name . '-admin', 'faqAiGeneratorSettings', array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('faq_ai_nonce'),
				'i18n' => array(
					'syncSuccess' => __('Models synced successfully.', 'faq-ai-generator'),
					'syncError' => __('Unable to sync models from OpenAI.', 'faq-ai-generator'),
					'syncing' => __('Syncing...', 'faq-ai-generator'),
					'syncButton' => __('Sync models from OpenAI', 'faq-ai-generator'),
				),
			));
		}

		if ($this->is_metabox_screen($hook)) {
			wp_enqueue_script(
				$this->plugin_name . '-metabox',
				plugin_dir_url(__FILE__) . 'js/faq-ai-generator-metabox.js',
				array('jquery'),
				$this->version,
				true
			);

			wp_localize_script($this->plugin_name . '-metabox', 'faqAiGenerator', array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('faq_ai_nonce'),
				'i18n' => array(
					'generating' => __('Generating...', 'faq-ai-generator'),
					'generateSuccess' => __('FAQs generated successfully!', 'faq-ai-generator'),
					'generateError' => __('An error occurred while generating FAQs.', 'faq-ai-generator'),
					'questionPlaceholder' => __('Question', 'faq-ai-generator'),
					'answerPlaceholder' => __('Answer', 'faq-ai-generator'),
					'remove' => __('Remove', 'faq-ai-generator'),
				),
			));
		}
	}

	/**
	 * Add menu page
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		add_options_page(
			esc_html__('FAQ AI Generator', 'faq-ai-generator'),
			esc_html__('FAQ AI Generator', 'faq-ai-generator'),
			'manage_options',
			$this->plugin_name,
			array($this, 'display_plugin_setup_page')
		);
	}

	/**
	 * Register settings
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		register_setting(
			'faq_ai_generator_settings',
			'faq_ai_generator_settings',
			array($this, 'validate_settings')
		);
	}

	/**
	 * Validate settings
	 *
	 * @since    1.0.0
	 * @param    array    $input    The settings input
	 * @return   array              The validated settings
	 */
	public function validate_settings($input) {
		$valid = array();
		$existing = get_option('faq_ai_generator_settings', array());

		$valid['api_key'] = isset($input['api_key']) ? sanitize_text_field($input['api_key']) : '';
		$valid['ai_provider'] = isset($input['ai_provider']) ? sanitize_text_field($input['ai_provider']) : 'openai';
		$valid['extra_prompt'] = isset($input['extra_prompt']) ? sanitize_textarea_field($input['extra_prompt']) : '';
		$valid['custom_model_id'] = isset($input['custom_model_id']) ? sanitize_text_field($input['custom_model_id']) : '';

		$requested_model = isset($input['model']) ? sanitize_text_field($input['model']) : '';
		$all_models = Faq_Ai_Generator_Models::get_all_models();

		if ($valid['custom_model_id'] !== '') {
			$valid['model'] = $valid['custom_model_id'];
		} elseif ($requested_model !== '' && isset($all_models[ $requested_model ])) {
			$valid['model'] = $requested_model;
		} else {
			$valid['model'] = Faq_Ai_Generator_Models::get_default_model();
			if ($requested_model !== '' && !isset($all_models[ $requested_model ])) {
				add_settings_error(
					'faq_ai_generator_settings',
					'invalid_model',
					__('Selected model is not available. Falling back to the default model.', 'faq-ai-generator'),
					'error'
				);
			}
		}

		$valid['enabled_post_types'] = array();
		$default_post_types = array(
			'post' => true,
			'page' => false,
		);

		if (isset($input['enabled_post_types']) && is_array($input['enabled_post_types'])) {
			foreach ($input['enabled_post_types'] as $post_type => $enabled) {
				$valid['enabled_post_types'][ sanitize_key($post_type) ] = (bool) $enabled;
			}
		}

		$valid['enabled_post_types'] = array_merge($default_post_types, $valid['enabled_post_types']);
		$valid['debug_mode'] = isset($input['debug_mode']) ? (bool) $input['debug_mode'] : false;

		$previous_key = isset($existing['api_key']) ? $existing['api_key'] : '';
		if (!empty($valid['api_key']) && $valid['api_key'] !== $previous_key) {
			$api = new Faq_Ai_Generator_Api();
			$test_result = $api->test_api_key($valid['api_key']);

			if (is_wp_error($test_result)) {
				set_transient('faq_ai_api_error', $test_result->get_error_message(), 45);
				set_transient('faq_ai_api_status', 'invalid', 45);
				$valid['api_key'] = '';
			} else {
				set_transient('faq_ai_api_status', 'valid', 45);
				delete_transient('faq_ai_api_error');
			}
		} elseif (empty($valid['api_key'])) {
			delete_transient('faq_ai_api_status');
			delete_transient('faq_ai_api_error');
		}

		return $valid;
	}

	/**
	 * Display plugin setup page
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_setup_page() {
		include_once 'partials/settings-page.php';
	}

	/**
	 * Add FAQ meta box
	 *
	 * @since    1.0.0
	 */
	public function add_faq_meta_box() {
		// Recupera le impostazioni
		$settings = get_option('faq_ai_generator_settings', array());
		$enabled_post_types = isset($settings['enabled_post_types']) ? $settings['enabled_post_types'] : array();

		// Post types di default (post sempre abilitato)
		$post_types = array('post');

		// Aggiungi page solo se abilitata nelle impostazioni
		if (isset($enabled_post_types['page']) && $enabled_post_types['page']) {
			$post_types[] = 'page';
		}

		// Aggiungi i custom post types abilitati
		foreach ($enabled_post_types as $post_type => $enabled) {
			if ($enabled && !in_array($post_type, array('post', 'page'))) {
				$post_types[] = $post_type;
			}
		}

		// Aggiungi il metabox solo se ci sono post types abilitati
		if (!empty($post_types)) {
			add_meta_box(
				'faq_ai_generator_meta_box',
				esc_html__('FAQ AI Generator', 'faq-ai-generator'),
				array($this, 'display_faq_meta_box'),
				$post_types,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Display FAQ meta box
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    The post object
	 */
	public function display_faq_meta_box($post) {
		$faq_data = get_post_meta($post->ID, '_faq_ai_data', true);
		$model_info = isset($faq_data['model_used']) ? $faq_data['model_used'] : null;

		if ($model_info) {
			echo '<div class="faq-model-info">';
			echo esc_html__('FAQ generated with', 'faq-ai-generator') . ' ';
			echo '<strong>' . esc_html($model_info['id']) . '</strong> ';
			echo esc_html__('on', 'faq-ai-generator') . ' ';
			echo esc_html(date_i18n(get_option('date_format'), $model_info['timestamp'])) . ' ';
			echo esc_html__('at', 'faq-ai-generator') . ' ';
			echo esc_html(gmdate('H:i', $model_info['timestamp']));
			echo '</div>';
		}

		include_once 'partials/metabox-template.php';
	}

	/**
	 * AJAX handler for generating FAQs
	 *
	 * @since    1.0.0
	 */
	public function ajax_generate_faqs() {
		check_ajax_referer('faq_ai_nonce', 'nonce');

		$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
		if (!$post_id) {
			wp_send_json_error(array('message' => esc_html__('Invalid post ID', 'faq-ai-generator')));
		}

		if (!current_user_can('edit_post', $post_id)) {
			wp_send_json_error(array('message' => esc_html__('Insufficient permissions', 'faq-ai-generator')));
		}

		$post = get_post($post_id);
		if (!$post) {
			wp_send_json_error(array('message' => esc_html__('Post not found', 'faq-ai-generator')));
		}

		$generation_hint = isset($_POST['generation_hint'])
			? sanitize_textarea_field(wp_unslash($_POST['generation_hint']))
			: '';

		$existing_faqs = array();
		$existing_faq_data = get_post_meta($post_id, '_faq_ai_data', true);
		if (isset($_POST['append']) && $_POST['append'] === 'true' && !empty($existing_faq_data['faqs'])) {
			$existing_faqs = $existing_faq_data['faqs'];
		}

		$settings = get_option('faq_ai_generator_settings', array());
		$model_id = Faq_Ai_Generator_Models::resolve_model_id($settings);
		$model_details = Faq_Ai_Generator_Models::get_model_details($model_id);

		if (is_wp_error($model_details)) {
			wp_send_json_error(array('message' => esc_html($model_details->get_error_message())));
		}

		$api = new Faq_Ai_Generator_Api();
		$result = $api->generate_faqs($post->post_content, $existing_faqs, $generation_hint);

		if (is_wp_error($result)) {
			wp_send_json_error(array('message' => esc_html($result->get_error_message())));
		}

		$faqs_to_store = $result;
		if (!empty($existing_faqs)) {
			$faqs_to_store = array_merge($existing_faqs, $result);
		}

		$faq_data = array(
			'faqs' => $faqs_to_store,
			'model_used' => array(
				'id' => $model_id,
				'timestamp' => current_time('timestamp'),
			),
			'display_in_content' => true,
			'generation_hint' => $generation_hint,
		);

		if ($existing_faq_data && isset($existing_faq_data['display_in_content'])) {
			$faq_data['display_in_content'] = (bool) $existing_faq_data['display_in_content'];
		}

		update_post_meta($post_id, '_faq_ai_data', $faq_data);

		wp_send_json_success(array('faqs' => $result));
	}

	/**
	 * AJAX: sync OpenAI chat models into local cache.
	 *
	 * @since    1.1.3
	 */
	public function ajax_sync_models() {
		check_ajax_referer('faq_ai_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => esc_html__('Insufficient permissions', 'faq-ai-generator')));
		}

		$api = new Faq_Ai_Generator_Api();
		$model_ids = $api->list_models();

		if (is_wp_error($model_ids)) {
			wp_send_json_error(array('message' => esc_html($model_ids->get_error_message())));
		}

		$synced = Faq_Ai_Generator_Models::store_synced_models($model_ids);
		$options = Faq_Ai_Generator_Models::get_model_options();

		wp_send_json_success(array(
			'message' => esc_html__('Models synced successfully.', 'faq-ai-generator'),
			'count' => count($synced),
			'models' => $options,
		));
	}

	/**
	 * AJAX handler for saving FAQs
	 *
	 * @since    1.0.0
	 */
	public function ajax_save_faqs() {
		check_ajax_referer('faq_ai_nonce', 'nonce');

		if (!current_user_can('edit_posts')) {
			wp_send_json_error(array('message' => esc_html__('Insufficient permissions', 'faq-ai-generator')));
		}

		$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
		if (!$post_id) {
			wp_send_json_error(array('message' => esc_html__('Invalid post ID', 'faq-ai-generator')));
		}

		$faqs = array();
		$display_in_content = isset($_POST['display_in_content']) ? (bool)$_POST['display_in_content'] : true;

		// Sanitizza l'input prima di qualsiasi controllo
		$raw_faqs = array();
		if (isset($_POST['faqs'])) {
			// Sanitizziamo prima l'input grezzo
			$raw_faqs = json_decode(sanitize_text_field(wp_unslash($_POST['faqs'])), true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				wp_send_json_error(array('message' => esc_html__('Invalid FAQ format', 'faq-ai-generator')));
			}
		}

		if (is_array($raw_faqs)) {
			$faqs_data = array_map(function($faq) {
				return array(
					'question' => isset($faq['question']) ? sanitize_text_field($faq['question']) : '',
					'answer' => isset($faq['answer']) ? wp_kses_post($faq['answer']) : ''
				);
			}, $raw_faqs);

			foreach ($faqs_data as $faq) {
				if (!empty($faq['question']) && !empty($faq['answer'])) {
					$faqs[] = $faq;
				}
			}
		}

		$faq_data = array(
			'last_generated' => current_time('timestamp'),
			'model_used' => get_option('faq_ai_generator_settings')['model'],
			'faqs' => $faqs,
			'display_in_content' => $display_in_content
		);

		$result = update_post_meta($post_id, '_faq_ai_data', $faq_data);

		if ($result === false) {
			wp_send_json_error(array('message' => esc_html__('Error while saving FAQs', 'faq-ai-generator')));
		}

		wp_send_json_success(array(
			'message' => esc_html__('FAQs saved successfully', 'faq-ai-generator'),
			'faqs' => $faqs,
			'display_in_content' => $display_in_content
		));
	}

	/**
	 * Save FAQ data when post is saved
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    The post ID
	 */
	public function save_faq_data($post_id) {
		// Verifica nonce
		if (!isset($_POST['faq_ai_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['faq_ai_nonce'])), 'faq_ai_nonce')) {
			return;
		}

		// Verifica autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		// Verifica permessi
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		// Prepara i dati delle FAQ
		$faqs = array();
		if (isset($_POST['faq_ai_questions']) && isset($_POST['faq_ai_answers']) && 
			is_array($_POST['faq_ai_questions']) && is_array($_POST['faq_ai_answers'])) {
			
			$questions = array_map('sanitize_text_field', wp_unslash($_POST['faq_ai_questions']));
			$answers = array_map('wp_kses_post', wp_unslash($_POST['faq_ai_answers']));
			
			foreach ($questions as $index => $question) {
				if (isset($answers[$index])) {
					$faqs[] = array(
						'question' => $question,
						'answer' => $answers[$index]
					);
				}
			}
		}

		$display_in_content = isset($_POST['faq_ai_display_in_content']);
		$generation_hint = isset($_POST['faq_ai_generation_hint'])
			? sanitize_textarea_field(wp_unslash($_POST['faq_ai_generation_hint']))
			: '';

		$existing_data = get_post_meta($post_id, '_faq_ai_data', true);
		if (!is_array($existing_data)) {
			$existing_data = array();
		}

		$faq_data = array_merge($existing_data, array(
			'faqs' => $faqs,
			'display_in_content' => $display_in_content,
			'generation_hint' => $generation_hint,
		));

		update_post_meta($post_id, '_faq_ai_data', $faq_data);
	}
}
