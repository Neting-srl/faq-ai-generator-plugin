<?php
/**
 * Provide a admin area view for the plugin settings.
 *
 * @link       https://lucamainieri.it
 * @since      1.0.0
 *
 * @package    Faq_Ai_Generator
 * @subpackage Faq_Ai_Generator/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path(dirname(__FILE__)) . '../includes/class-faq-ai-generator-models.php';

$faq_ai_default_model = Faq_Ai_Generator_Models::get_default_model();
$faq_ai_settings = get_option('faq_ai_generator_settings', array(
	'api_key' => '',
	'ai_provider' => 'openai',
	'model' => $faq_ai_default_model,
	'custom_model_id' => '',
	'extra_prompt' => '',
	'enabled_post_types' => array(),
));

$faq_ai_api_status = get_transient('faq_ai_api_status');
$faq_ai_api_error = get_transient('faq_ai_api_error');

$faq_ai_available_models = Faq_Ai_Generator_Models::get_all_models();
$faq_ai_model_options = Faq_Ai_Generator_Models::get_model_options();
$faq_ai_selected_model = isset($faq_ai_settings['model']) ? $faq_ai_settings['model'] : $faq_ai_default_model;
$faq_ai_custom_model_id = isset($faq_ai_settings['custom_model_id']) ? $faq_ai_settings['custom_model_id'] : '';
$faq_ai_model_obsolete = empty($faq_ai_custom_model_id) && Faq_Ai_Generator_Models::is_model_obsolete($faq_ai_selected_model);

if ($faq_ai_model_obsolete) {
	$faq_ai_selected_model = $faq_ai_default_model;
}

settings_errors('faq_ai_generator_settings');
?>

<div class="wrap">
	<h1><?php echo esc_html(get_admin_page_title()); ?></h1>

	<?php if ($faq_ai_api_error) : ?>
		<div class="notice notice-error">
			<p><?php echo esc_html($faq_ai_api_error); ?></p>
		</div>
	<?php endif; ?>

	<?php if ($faq_ai_model_obsolete) : ?>
		<div class="notice notice-warning">
			<p>
				<?php
				esc_html_e(
					'Your previously saved model is no longer in the curated list. The default model will be used until you choose another one or enter a custom model ID.',
					'faq-ai-generator'
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<form method="post" action="options.php">
		<?php
		settings_fields('faq_ai_generator_settings');
		do_settings_sections('faq_ai_generator_settings');
		?>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="api_key"><?php esc_html_e('OpenAI API Key', 'faq-ai-generator'); ?></label>
				</th>
				<td>
					<input type="password"
						   id="api_key"
						   name="faq_ai_generator_settings[api_key]"
						   value="<?php echo esc_attr(isset($faq_ai_settings['api_key']) ? $faq_ai_settings['api_key'] : ''); ?>"
						   class="regular-text" />
					<?php if (!empty($faq_ai_settings['api_key'])) : ?>
						<span class="api-status <?php echo esc_attr($faq_ai_api_status ? $faq_ai_api_status : ''); ?>">
							<?php echo $faq_ai_api_status === 'valid' ? '✓' : '✗'; ?>
						</span>
					<?php endif; ?>
					<p class="description">
						<?php esc_html_e('Enter your OpenAI API Key. You can get it from', 'faq-ai-generator'); ?>
						<a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">https://platform.openai.com/api-keys</a>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="ai_provider"><?php esc_html_e('AI Provider', 'faq-ai-generator'); ?></label>
				</th>
				<td>
					<select id="ai_provider" name="faq_ai_generator_settings[ai_provider]">
						<option value="openai" <?php selected(isset($faq_ai_settings['ai_provider']) ? $faq_ai_settings['ai_provider'] : 'openai', 'openai'); ?>>
							<?php esc_html_e('OpenAI', 'faq-ai-generator'); ?>
						</option>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="model"><?php esc_html_e('Model', 'faq-ai-generator'); ?></label>
				</th>
				<td>
					<select id="model" name="faq_ai_generator_settings[model]">
						<?php foreach ($faq_ai_model_options as $faq_ai_model_id => $faq_ai_model_name) : ?>
							<option value="<?php echo esc_attr($faq_ai_model_id); ?>" <?php selected($faq_ai_selected_model, $faq_ai_model_id); ?>>
								<?php echo esc_html($faq_ai_model_name); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php if (isset($faq_ai_available_models[ $faq_ai_selected_model ]['description'])) : ?>
						<p class="description">
							<?php echo esc_html($faq_ai_available_models[ $faq_ai_selected_model ]['description']); ?>
						</p>
					<?php endif; ?>
					<p>
						<button type="button" class="button" id="faq-ai-sync-models">
							<?php esc_html_e('Sync models from OpenAI', 'faq-ai-generator'); ?>
						</button>
					</p>
					<p id="faq-ai-sync-status" class="description" aria-live="polite"></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="custom_model_id"><?php esc_html_e('Custom model ID', 'faq-ai-generator'); ?></label>
				</th>
				<td>
					<input type="text"
						   id="custom_model_id"
						   name="faq_ai_generator_settings[custom_model_id]"
						   value="<?php echo esc_attr($faq_ai_custom_model_id); ?>"
						   class="regular-text"
						   placeholder="gpt-6-luna" />
					<p class="description">
						<?php esc_html_e('Optional. If set, this OpenAI model ID overrides the select above.', 'faq-ai-generator'); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="extra_prompt"><?php esc_html_e('Extra Prompt', 'faq-ai-generator'); ?></label>
				</th>
				<td>
					<textarea
						id="extra_prompt"
						name="faq_ai_generator_settings[extra_prompt]"
						class="large-text code"
						rows="3"
					><?php echo esc_textarea(isset($faq_ai_settings['extra_prompt']) ? $faq_ai_settings['extra_prompt'] : ''); ?></textarea>
					<p class="description">
						<?php esc_html_e('Global instructions always added to the AI prompt. For per-post topics use the Generation hint in the metabox.', 'faq-ai-generator'); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<?php esc_html_e('Custom Post Types', 'faq-ai-generator'); ?>
				</th>
				<td>
					<?php
					$faq_ai_custom_post_types = get_post_types(array('_builtin' => false), 'objects');
					if (!empty($faq_ai_custom_post_types)) {
						foreach ($faq_ai_custom_post_types as $faq_ai_post_type) {
							$faq_ai_enabled = isset($faq_ai_settings['enabled_post_types'][ $faq_ai_post_type->name ]) ? $faq_ai_settings['enabled_post_types'][ $faq_ai_post_type->name ] : false;
							?>
							<label>
								<input type="checkbox"
									   name="faq_ai_generator_settings[enabled_post_types][<?php echo esc_attr($faq_ai_post_type->name); ?>]"
									   value="1"
									   <?php checked($faq_ai_enabled, true); ?>>
								<?php echo esc_html($faq_ai_post_type->label); ?>
							</label><br>
							<?php
						}
					} else {
						esc_html_e('No custom post types found.', 'faq-ai-generator');
					}
					?>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<?php esc_html_e('Built-in Post Types', 'faq-ai-generator'); ?>
				</th>
				<td>
					<?php
					$faq_ai_default_post_types = array(
						'post' => true,
						'page' => false,
					);
					$faq_ai_enabled_post_types = isset($faq_ai_settings['enabled_post_types']) ? $faq_ai_settings['enabled_post_types'] : array();
					$faq_ai_enabled_post_types = array_merge($faq_ai_default_post_types, $faq_ai_enabled_post_types);
					?>
					<label>
						<input type="checkbox"
							   name="faq_ai_generator_settings[enabled_post_types][post]"
							   value="1"
							   readonly="readonly"
							   class="readonly-checkbox"
							   <?php checked($faq_ai_enabled_post_types['post'], true); ?>>
						<?php esc_html_e('Posts', 'faq-ai-generator'); ?>
						<span class="description"><?php esc_html_e('(Always enabled)', 'faq-ai-generator'); ?></span>
					</label><br>
					<label>
						<input type="checkbox"
							   name="faq_ai_generator_settings[enabled_post_types][page]"
							   value="1"
							   <?php checked($faq_ai_enabled_post_types['page'], true); ?>>
						<?php esc_html_e('Pages', 'faq-ai-generator'); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="debug_mode"><?php esc_html_e('Debug Mode', 'faq-ai-generator'); ?></label>
				</th>
				<td>
					<label>
						<input type="checkbox"
							   id="debug_mode"
							   name="faq_ai_generator_settings[debug_mode]"
							   value="1"
							   <?php checked(isset($faq_ai_settings['debug_mode']) ? $faq_ai_settings['debug_mode'] : false, true); ?>>
						<?php esc_html_e('Enable debug mode', 'faq-ai-generator'); ?>
					</label>
					<p class="description">
						<?php esc_html_e('Enable this option to log debug information to the WordPress debug log. Use this only for development and troubleshooting.', 'faq-ai-generator'); ?>
					</p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>

<style>
.api-status {
	display: inline-block;
	margin-left: 10px;
	padding: 2px 6px;
	border-radius: 3px;
	font-weight: bold;
}

.api-status.valid {
	color: #46b450;
	background: #f0f6f0;
}

.api-status.invalid {
	color: #dc3232;
	background: #fbeaea;
}

.readonly-checkbox {
	opacity: 0.7;
	cursor: not-allowed;
}

.readonly-checkbox + .description {
	color: #666;
	font-style: italic;
	margin-left: 5px;
}

#faq-ai-sync-status.notice-success {
	color: #046b00;
}

#faq-ai-sync-status.notice-error {
	color: #b32d2e;
}
</style>
