<?php

/**
 * The models handler class for FAQ AI Generator
 *
 * @since      1.0.0
 * @package    Faq_Ai_Generator
 * @subpackage Faq_Ai_Generator/includes
 */

class Faq_Ai_Generator_Models {

	const SYNCED_OPTION = 'faq_ai_synced_models';
	const SYNCED_TRANSIENT = 'faq_ai_synced_models_cache';

	/**
	 * Curated OpenAI chat models suitable for FAQ generation (Oct 2026).
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public static function get_available_models() {
		return array(
			'gpt-6-luna' => array(
				'name' => 'GPT-6 Luna',
				'description' => __('Efficient model for focused, high-volume tasks. Recommended default for FAQ generation.', 'faq-ai-generator'),
				'max_tokens' => 128000,
			),
			'gpt-6.1-sol' => array(
				'name' => 'GPT-6.1 Sol',
				'description' => __('Near-flagship quality for complex work at a lower cost.', 'faq-ai-generator'),
				'max_tokens' => 128000,
			),
			'gpt-6-astra' => array(
				'name' => 'GPT-6 Astra',
				'description' => __('Most capable model for demanding FAQ quality and nuanced content.', 'faq-ai-generator'),
				'max_tokens' => 128000,
			),
			'gpt-5.6-sol' => array(
				'name' => 'GPT-5.6 Sol',
				'description' => __('Previous-generation strong chat model still suitable for FAQ generation.', 'faq-ai-generator'),
				'max_tokens' => 128000,
			),
			'gpt-5.4-mini' => array(
				'name' => 'GPT-5.4 Mini',
				'description' => __('Cost-efficient smaller model for simpler FAQ sets.', 'faq-ai-generator'),
				'max_tokens' => 128000,
			),
			'gpt-5.4' => array(
				'name' => 'GPT-5.4',
				'description' => __('General-purpose GPT-5.4 chat model.', 'faq-ai-generator'),
				'max_tokens' => 128000,
			),
			'gpt-5.5' => array(
				'name' => 'GPT-5.5',
				'description' => __('GPT-5.5 general chat model.', 'faq-ai-generator'),
				'max_tokens' => 128000,
			),
		);
	}

	/**
	 * Models synced from the OpenAI API.
	 *
	 * @since    1.1.3
	 * @return   array
	 */
	public static function get_synced_models() {
		$cached = get_transient(self::SYNCED_TRANSIENT);
		if (is_array($cached)) {
			return $cached;
		}

		$stored = get_option(self::SYNCED_OPTION, array());
		return is_array($stored) ? $stored : array();
	}

	/**
	 * Merge curated and synced models (curated wins on id collision).
	 *
	 * @since    1.1.3
	 * @return   array
	 */
	public static function get_all_models() {
		return array_merge(self::get_synced_models(), self::get_available_models());
	}

	/**
	 * Get model options for select field.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public static function get_model_options() {
		$options = array();
		foreach (self::get_all_models() as $id => $model) {
			$options[ $id ] = isset($model['name']) ? $model['name'] : $id;
		}
		return $options;
	}

	/**
	 * Get default model ID.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public static function get_default_model() {
		return 'gpt-6-luna';
	}

	/**
	 * Resolve the model to use from settings (custom ID wins).
	 *
	 * @since    1.1.3
	 * @param    array|null $settings Settings array.
	 * @return   string
	 */
	public static function resolve_model_id($settings = null) {
		if ($settings === null) {
			$settings = get_option('faq_ai_generator_settings', array());
		}

		if (!empty($settings['custom_model_id'])) {
			return sanitize_text_field($settings['custom_model_id']);
		}

		if (!empty($settings['model'])) {
			$model_id = sanitize_text_field($settings['model']);
			$all = self::get_all_models();
			if (isset($all[ $model_id ])) {
				return $model_id;
			}
		}

		return self::get_default_model();
	}

	/**
	 * Whether a saved model id is obsolete / unknown.
	 *
	 * @since    1.1.3
	 * @param    string $model_id Model ID.
	 * @return   bool
	 */
	public static function is_model_obsolete($model_id) {
		if ($model_id === '') {
			return false;
		}
		$all = self::get_all_models();
		return !isset($all[ $model_id ]);
	}

	/**
	 * Get model details.
	 *
	 * @since    1.0.0
	 * @param    string $model_id The model ID.
	 * @return   array|WP_Error
	 */
	public static function get_model_details($model_id) {
		if ($model_id === '') {
			return new WP_Error('invalid_model', __('Invalid model ID', 'faq-ai-generator'));
		}

		$all = self::get_all_models();
		if (isset($all[ $model_id ])) {
			return $all[ $model_id ];
		}

		// Allow custom / newly released model IDs without hard failure.
		return array(
			'name' => $model_id,
			'description' => __('Custom or synced OpenAI model ID.', 'faq-ai-generator'),
			'max_tokens' => 128000,
		);
	}

	/**
	 * Whether a model id looks like a chat-capable OpenAI model.
	 *
	 * @since    1.1.3
	 * @param    string $model_id Model ID.
	 * @return   bool
	 */
	public static function is_chat_model_id($model_id) {
		$model_id = strtolower($model_id);

		$exclude_needles = array(
			'image',
			'realtime',
			'audio',
			'transcribe',
			'tts',
			'whisper',
			'embedding',
			'dall-e',
			'moderation',
			'search',
			'codex',
			'computer-use',
			'deep-research',
		);

		foreach ($exclude_needles as $needle) {
			if (strpos($model_id, $needle) !== false) {
				return false;
			}
		}

		return (bool) preg_match('/^(gpt-|o[0-9]|chatgpt-)/', $model_id);
	}

	/**
	 * Store synced models from OpenAI API list response.
	 *
	 * @since    1.1.3
	 * @param    array $model_ids List of model id strings.
	 * @return   array Stored models map.
	 */
	public static function store_synced_models(array $model_ids) {
		$models = array();
		sort($model_ids);

		foreach ($model_ids as $id) {
			$id = sanitize_text_field($id);
			if ($id === '' || !self::is_chat_model_id($id)) {
				continue;
			}
			$models[ $id ] = array(
				'name' => $id,
				'description' => __('Synced from your OpenAI account.', 'faq-ai-generator'),
				'max_tokens' => 128000,
			);
		}

		update_option(self::SYNCED_OPTION, $models, false);
		set_transient(self::SYNCED_TRANSIENT, $models, DAY_IN_SECONDS);

		return $models;
	}
}
