<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://lucamainieri.it
 * @since      1.0.0
 *
 * @package    Faq_Ai_Generator
 * @subpackage Faq_Ai_Generator/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$faq_data = get_post_meta(get_the_ID(), '_faq_ai_data', true);
$display_in_content = isset($faq_data['display_in_content']) ? $faq_data['display_in_content'] : true;
$faqs = isset($faq_data['faqs']) ? $faq_data['faqs'] : [];
?>

<div class="faq-ai-generator-metabox">
    <div class="faq-ai-generator-controls">
        <div class="faq-ai-actions">
            <button type="button" id="faq-ai-generate" class="button button-primary">
                <?php esc_html_e('Generate FAQ', 'faq-ai-generator-plugin'); ?>
            </button>
        </div>
    </div>

    <div class="faq-ai-generator-display-option">
        <label>
            <input type="checkbox" name="faq_ai_display_in_content" value="1" <?php checked($display_in_content, true); ?>>
            <?php esc_html_e('Show FAQ in content', 'faq-ai-generator-plugin'); ?>
        </label>
    </div>

    <div class="faq-ai-generator-list">
        <?php foreach ($faqs as $index => $faq): ?>
            <div class="faq-item" data-index="<?php echo esc_attr($index); ?>">
                <div class="faq-question">
                    <input type="text" name="faq_ai_questions[]" value="<?php echo esc_attr($faq['question']); ?>"
                        placeholder="<?php esc_attr_e('Question', 'faq-ai-generator-plugin'); ?>">
                </div>
                <div class="faq-answer">
                    <textarea name="faq_ai_answers[]"
                        placeholder="<?php esc_attr_e('Answer', 'faq-ai-generator-plugin'); ?>"><?php echo esc_textarea($faq['answer']); ?></textarea>
                </div>
                <button type="button" class="button faq-remove">
                    <?php esc_html_e('Remove', 'faq-ai-generator-plugin'); ?>
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="faq-ai-generator-add">
        <button type="button" class="button" id="faq-ai-add">
            <?php esc_html_e('Add new FAQ', 'faq-ai-generator-plugin'); ?>
        </button>
    </div>

    <input type="hidden" name="faq_ai_nonce" value="<?php echo esc_attr(wp_create_nonce('faq_ai_nonce')); ?>">

    <!-- Popup di conferma -->
    <div id="faq-ai-confirm-dialog" style="display: none;">
        <div class="faq-ai-confirm-content">
            <h3><?php esc_html_e('Confirm FAQ generation', 'faq-ai-generator-plugin'); ?></h3>
            <p><?php esc_html_e('Choose how to proceed with FAQ generation:', 'faq-ai-generator-plugin'); ?></p>
            <div class="faq-ai-confirm-buttons">
                <?php if (!empty($faqs)): ?>
                    <button type="button" id="faq-ai-overwrite" class="button button-primary">
                        <?php esc_html_e('Overwrite existing FAQs', 'faq-ai-generator-plugin'); ?>
                    </button>
                <?php endif; ?>
                <button type="button" id="faq-ai-append" class="button button-primary">
                    <?php esc_html_e('Add new FAQs', 'faq-ai-generator-plugin'); ?>
                </button>
                <button type="button" id="faq-ai-cancel" class="button">
                    <?php esc_html_e('Cancel', 'faq-ai-generator-plugin'); ?>
                </button>
            </div>
        </div>
    </div>

    <div class="faq-ai-message">
        <!-- Messaggi di notifica -->
    </div>
</div>