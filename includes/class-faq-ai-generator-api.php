<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The API integration class
 *
 * @since      1.0.0
 *
 * @package    Faq_Ai_Generator
 * @subpackage Faq_Ai_Generator/includes
 */

// Definizione della costante di debug
if (!defined('FAQ_AI_DEBUG')) {
    $faq_ai_generator_debug_settings = get_option('faq_ai_generator_settings', array());
    define('FAQ_AI_DEBUG', !empty($faq_ai_generator_debug_settings['debug_mode']));
}

require_once plugin_dir_path(__FILE__) . 'class-faq-ai-generator-models.php';


class Faq_Ai_Generator_Api
{

    /**
     * The OpenAI API endpoint
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $api_endpoint = 'https://api.openai.com/v1/chat/completions';

    /**
     * The API key
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $api_key;

    /**
     * The model ID
     *
     * @since    1.1.4
     * @access   private
     * @var      string
     */
    private $model_id;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        $this->api_key = get_option('faq_ai_generator_settings')['api_key'] ?? '';
        $this->model_id = get_option('faq_ai_generator_settings')['model'] ?? Faq_Ai_Generator_Models::get_default_model();
    }

    /**
     * Generate nonce for AJAX requests
     *
     * @since    1.0.0
     * @return   string    The generated nonce
     */
    public static function get_nonce()
    {
        return wp_create_nonce('faq_ai_nonce');
    }

    /**
     * Verify nonce for AJAX requests
     *
     * @since    1.0.0
     * @param    string    $nonce    The nonce to verify
     * @return   bool               True if valid, false otherwise
     */
    public static function verify_nonce($nonce)
    {
        return wp_verify_nonce($nonce, 'faq_ai_nonce');
    }

    /**
     * Test the API key
     *
     * @since    1.0.0
     * @param    string    $api_key    The API key to test
     * @return   bool|WP_Error        True if valid, WP_Error if invalid
     */
    public static function test_api_key_static($api_key = null)
    {
        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('API key not configured', 'faq-ai-generator'));
        }

        $test_endpoint = 'https://api.openai.com/v1/models';

        $response = wp_remote_get($test_endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new WP_Error('api_error', $body['error']['message']);
        }

        return true;
    }

    public function test_api_key($api_key = null)
    {
        $this->api_key = $api_key;
        return self::test_api_key_static($api_key);
    }

    /**
     * Genera il prompt base per l'AI
     * 
     * @since    1.0.0
     * @access   private
     * @return   string    Il prompt base
     */
    private function get_base_prompt()
    {
        $locale = get_locale();

        if ($locale === 'en_US') {
            return "You are an SEO expert and professional copywriter with solid SEO skills. " .
                "Your task is to enhance existing articles and content on the site with in-depth questions " .
                "related to the page content. Your task is to read and understand the content and generate " .
                "a list of questions and answers (FAQ) that are useful for site visitors and provide information " .
                "related to the page content and that are based on the provided content.\n\n" .
                "IMPORTANT: The response MUST be in JSON format with this structure:\n" .
                '[{"question": "question1", "answer": "answer1"}, {"question": "question2", "answer": "answer2"}]\n\n';
        } else {
            return "Sei un esperto di SEO e un copywriting professionista con solide competenze di SEO. " .
                "Il tuo compito è integrare degli articoli e contenuti esistenti sul sito con domande di approfondimento " .
                "inerenti al contenuto della pagina. Il tuo compito è leggere e comprendere il contenuto e generare " .
                "un elenco di domande e risposte(FAQ) che siano utili per i visitatori del sito e che forniscano informazioni " .
                "relative al contenuto della pagina e che siano basate sul contenuto fornito.\n\n" .
                "IMPORTANTE: La risposta DEVE essere in formato JSON con questa struttura:\n" .
                '[{"question": "domanda1", "answer": "risposta1"}, {"question": "domanda2", "answer": "risposta2"}]\n\n';
        }
    }

    /**
     * Recupera il contenuto della pagina tramite CURL
     *
     * @since    1.0.0
     * @param    int       $post_id    ID della pagina
     * @return   string               Contenuto della pagina
     */
    private function get_page_content_via_curl($post_id)
    {
        Faq_Ai_Generator_Logger::debug('Starting content extraction for post ID: ' . $post_id);

        // Ottieni l'URL della pagina
        $page_url = get_permalink($post_id);
        Faq_Ai_Generator_Logger::debug('Page URL: ' . $page_url);

        // Esegui la richiesta usando wp_remote_get
        $response = wp_remote_get($page_url, array(
            'timeout' => 30,
            'sslverify' => false,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ));

        // Verifica se ci sono errori
        if (is_wp_error($response)) {
            Faq_Ai_Generator_Logger::error('WP Remote Error: ' . $response->get_error_message());
            return '';
        }

        // Ottieni il contenuto HTML
        $html = wp_remote_retrieve_body($response);
        Faq_Ai_Generator_Logger::debug('HTML content length: ' . strlen($html));

        // Estrai il contenuto principale
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

        // Rimuovi script e stili
        $scripts = $dom->getElementsByTagName('script');
        $styles = $dom->getElementsByTagName('style');

        foreach ($scripts as $script) {
            $script->parentNode->removeChild($script);
        }

        foreach ($styles as $style) {
            $style->parentNode->removeChild($style);
        }

        // Cerca il contenuto principale nel tag main
        $content = '';
        $main = $dom->getElementsByTagName('main')->item(0);

        if ($main) {
            Faq_Ai_Generator_Logger::debug('Found main tag content');
            // Converti il contenuto in markdown
            $content = $this->html_to_markdown($main);
        } else {
            Faq_Ai_Generator_Logger::debug('Main tag not found, trying fallback selectors');
            // Fallback: cerca altri contenitori comuni
            $selectors = array(
                'article',
                '#main-content',
                '#content',
                '.main-content',
                '.content',
                '.entry-content',
                'section'
            );

            foreach ($selectors as $selector) {
                Faq_Ai_Generator_Logger::debug('Trying selector: ' . $selector);
                if (strpos($selector, '#') === 0) {
                    $element = $dom->getElementById(substr($selector, 1));
                } else if (strpos($selector, '.') === 0) {
                    $elements = $dom->getElementsByTagName('*');
                    foreach ($elements as $element) {
                        if (strpos($element->getAttribute('class'), substr($selector, 1)) !== false) {
                            $content = $this->html_to_markdown($element);
                            Faq_Ai_Generator_Logger::debug('Found content in class: ' . $selector);
                            break 2;
                        }
                    }
                } else {
                    $element = $dom->getElementsByTagName($selector)->item(0);
                }

                if ($element) {
                    $content = $this->html_to_markdown($element);
                    Faq_Ai_Generator_Logger::debug('Found content in tag: ' . $selector);
                    break;
                }
            }
        }

        Faq_Ai_Generator_Logger::debug('HTML content length after extraction: ' . strlen($content));

        // Recupera i dati ACF in modo flessibile
        $acf_content = '';

        // Verifica se il plugin ACF è installato e attivo
        $acf_active = false;

        // Verifica tramite is_plugin_active se disponibile
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        if (function_exists('is_plugin_active')) {
            $acf_active = is_plugin_active('advanced-custom-fields/acf.php') ||
                is_plugin_active('advanced-custom-fields-pro/acf.php');
        }

        // Verifica anche tramite la funzione get_fields
        if ($acf_active && function_exists('get_fields')) {
            Faq_Ai_Generator_Logger::debug('ACF plugin is active, attempting to get fields');

            $fields = get_fields($post_id);
            if ($fields) {
                if (FAQ_AI_DEBUG) {
                    $context = [
                        'count' => count($fields),
                        'fields' => $fields,
                        'post_id' => $post_id
                    ];
                    Faq_Ai_Generator_Logger::debug('ACF Fields', $context);
                }

                // Funzione ricorsiva per estrarre il contenuto dai campi ACF
                $extract_acf_content = function ($field_value, $field_name = '', $field_type = '') use (&$extract_acf_content) {
                    $content = '';

                    if (is_string($field_value)) {
                        // Rimuovi HTML e pulisci il testo
                        $clean_text = wp_strip_all_tags($field_value);
                        if (!empty($clean_text)) {
                            $content .= $clean_text . "\n";
                            if (FAQ_AI_DEBUG) {
                                $context = [
                                    'field_name' => $field_name,
                                    'field_type' => $field_type,
                                    'clean_text' => $clean_text,
                                    'length' => strlen($clean_text)
                                ];
                                Faq_Ai_Generator_Logger::debug("ACF Field '{$field_name}' ({$field_type}): Text content found (" . strlen($clean_text) . " chars)", $context);
                            }
                        }
                    } elseif (is_array($field_value)) {
                        // Gestione specifica per diversi tipi di campi ACF
                        switch ($field_type) {
                            case 'repeater':
                                foreach ($field_value as $index => $row) {
                                    if (is_array($row)) {
                                        foreach ($row as $sub_field_name => $sub_field_value) {
                                            $sub_field_content = $extract_acf_content($sub_field_value, "{$field_name}[{$index}].{$sub_field_name}");
                                            if (!empty($sub_field_content)) {
                                                $content .= $sub_field_content;
                                            }
                                        }
                                    }
                                }
                                break;

                            case 'group':
                                foreach ($field_value as $sub_field_name => $sub_field_value) {
                                    $sub_field_content = $extract_acf_content($sub_field_value, "{$field_name}.{$sub_field_name}");
                                    if (!empty($sub_field_content)) {
                                        $content .= $sub_field_content;
                                    }
                                }
                                break;

                            case 'flexible_content':
                                foreach ($field_value as $index => $layout) {
                                    if (isset($layout['acf_fc_layout'])) {
                                        $layout_name = $layout['acf_fc_layout'];
                                        foreach ($layout as $sub_field_name => $sub_field_value) {
                                            if ($sub_field_name !== 'acf_fc_layout') {
                                                $sub_field_content = $extract_acf_content($sub_field_value, "{$field_name}[{$index}].{$layout_name}.{$sub_field_name}");
                                                if (!empty($sub_field_content)) {
                                                    $content .= $sub_field_content;
                                                }
                                            }
                                        }
                                    }
                                }
                                break;

                            default:
                                foreach ($field_value as $key => $value) {
                                    if (is_string($value)) {
                                        $clean_text = wp_strip_all_tags($value);
                                        if (!empty($clean_text)) {
                                            $content .= $clean_text . "\n";
                                            if (FAQ_AI_DEBUG) {
                                                Faq_Ai_Generator_Logger::debug("ACF Field '{$field_name}[{$key}]': Text content found (" . strlen($clean_text) . " chars)");
                                            }
                                        }
                                    } elseif (is_array($value)) {
                                        $nested_content = $extract_acf_content($value, "{$field_name}[{$key}]");
                                        if (!empty($nested_content)) {
                                            $content .= $nested_content;
                                        }
                                    }
                                }
                        }
                    }

                    return $content;
                };

                // Estrai il contenuto da tutti i campi ACF
                foreach ($fields as $field_name => $field_value) {
                    // Ottieni il tipo di campo ACF
                    $field_type = '';
                    if (function_exists('get_field_object')) {
                        $field_object = get_field_object($field_name, $post_id);
                        if ($field_object && isset($field_object['type'])) {
                            $field_type = $field_object['type'];
                        }
                    }

                    $field_content = $extract_acf_content($field_value, $field_name, $field_type);
                    if (!empty($field_content)) {
                        $acf_content .= $field_content;
                    }
                }
            } else {
                Faq_Ai_Generator_Logger::debug('No ACF fields found for post ID: ' . $post_id);
            }
        } else {
            Faq_Ai_Generator_Logger::debug('ACF plugin not active');
        }

        Faq_Ai_Generator_Logger::debug('ACF content length: ' . strlen($acf_content));

        // Combina il contenuto HTML con i dati ACF
        if (!empty($acf_content)) {
            Faq_Ai_Generator_Logger::debug('Adding ACF content to main content');
            $content .= "\n\n" . $acf_content;
        }

        // Pulisci il contenuto
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);

        Faq_Ai_Generator_Logger::debug('Final content length: ' . strlen($content));
        if (empty($content)) {
            Faq_Ai_Generator_Logger::warning('WARNING: Final content is empty!');
        }

        return $content;
    }

    /**
     * Converte HTML in Markdown
     *
     * @since    1.0.0
     * @param    DOMElement    $element    Elemento DOM da convertire
     * @return   string                   Contenuto in formato Markdown
     */
    private function html_to_markdown($element)
    {
        $markdown = '';

        // Funzione ricorsiva per convertire i nodi
        $convert_node = function ($node) use (&$convert_node, &$markdown) {
            if ($node->nodeType === XML_TEXT_NODE) {
                $text = trim($node->textContent);
                if (!empty($text)) {
                    $markdown .= $text . ' ';
                }
                return;
            }

            if ($node->nodeType === XML_ELEMENT_NODE) {
                $tag = strtolower($node->nodeName);

                // Gestisci i tag principali
                switch ($tag) {
                    case 'h1':
                    case 'h2':
                    case 'h3':
                    case 'h4':
                    case 'h5':
                    case 'h6':
                        $level = substr($tag, 1);
                        $markdown .= "\n\n" . str_repeat('#', $level) . ' ';
                        break;

                    case 'p':
                        $markdown .= "\n\n";
                        break;

                    case 'br':
                        $markdown .= "\n";
                        break;

                    case 'strong':
                    case 'b':
                        $markdown .= '**';
                        break;

                    case 'em':
                    case 'i':
                        $markdown .= '*';
                        break;

                    case 'ul':
                        $markdown .= "\n\n";
                        break;

                    case 'ol':
                        $markdown .= "\n\n";
                        break;

                    case 'li':
                        $markdown .= "\n- ";
                        break;

                    case 'blockquote':
                        $markdown .= "\n\n> ";
                        break;

                    case 'code':
                        $markdown .= '`';
                        break;

                    case 'pre':
                        $markdown .= "\n\n```\n";
                        break;
                }

                // Processa i nodi figli
                foreach ($node->childNodes as $child) {
                    $convert_node($child);
                }

                // Chiudi i tag
                switch ($tag) {
                    case 'strong':
                    case 'b':
                        $markdown .= '**';
                        break;

                    case 'em':
                    case 'i':
                        $markdown .= '*';
                        break;

                    case 'code':
                        $markdown .= '`';
                        break;

                    case 'pre':
                        $markdown .= "\n```\n";
                        break;

                    case 'p':
                    case 'h1':
                    case 'h2':
                    case 'h3':
                    case 'h4':
                    case 'h5':
                    case 'h6':
                    case 'ul':
                    case 'ol':
                    case 'blockquote':
                        $markdown .= "\n";
                        break;
                }
            }
        };

        // Converti l'elemento
        $convert_node($element);

        // Pulisci il markdown
        $markdown = preg_replace('/\n{3,}/', "\n\n", $markdown);
        $markdown = preg_replace('/\s+/', ' ', $markdown);
        $markdown = trim($markdown);

        return $markdown;
    }

    /**
     * Costruisce il prompt completo combinando il prompt base, extra prompt e contenuto
     * 
     * @since    1.0.0
     * @access   private
     * @param    string    $content         Il contenuto dell'articolo
     * @param    array     $existing_faqs   FAQ esistenti
     * @return   string                     Il prompt completo
     */
    private function build_prompt($content, $existing_faqs = array(), $generation_hint = '')
    {
        $locale = get_locale();
        $is_en = (strpos($locale, 'en_') === 0);

        // 1. Base prompt
        $prompt = $this->get_base_prompt();

        // 2. Global extra instructions from settings
        $settings = get_option('faq_ai_generator_settings', array());
        if (!empty($settings['extra_prompt'])) {
            $prompt .= ($is_en ? "EXTRA INSTRUCTIONS:\n" : "ISTRUZIONI EXTRA:\n") .
                trim($settings['extra_prompt']) . "\n\n";
        }

        // 3. Per-generation hint from metabox
        if (!empty($generation_hint)) {
            $prompt .= ($is_en ? "GENERATION HINT (follow closely):\n" : "HINT DI GENERAZIONE (segui attentamente):\n") .
                trim($generation_hint) . "\n\n";
        }

        // 4. Existing FAQs
        if (!empty($existing_faqs)) {
            $prompt .= ($is_en ? "EXISTING FAQS (generate different FAQs from these):\n" : "FAQ GIÀ ESISTENTI (genera FAQ diverse da queste):\n");
            foreach ($existing_faqs as $index => $faq) {
                $prompt .= sprintf(
                    "%d. Q: %s\nA: %s\n\n",
                    $index + 1,
                    html_entity_decode(wp_strip_all_tags($faq['question']), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    html_entity_decode(wp_strip_all_tags($faq['answer']), ENT_QUOTES | ENT_HTML5, 'UTF-8')
                );
            }
        }

        // 5. Post content
        $prompt .= ($is_en ? "CONTENT TO ANALYZE:\n\n" : "CONTENUTO DA ANALIZZARE:\n\n") .
            html_entity_decode(wp_strip_all_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $prompt = mb_convert_encoding($prompt, 'UTF-8', 'UTF-8');

        return $prompt;
    }

    /**
     * List chat-capable models available to the configured API key.
     *
     * @since    1.1.5
     * @return   array|WP_Error
     */
    public function list_models()
    {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('API key not configured', 'faq-ai-generator'));
        }

        $response = wp_remote_get('https://api.openai.com/v1/models', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
            ),
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code < 200 || $code >= 300 || empty($body['data']) || !is_array($body['data'])) {
            $message = isset($body['error']['message']) ? $body['error']['message'] : __('Unable to sync models from OpenAI.', 'faq-ai-generator');
            return new WP_Error('sync_failed', $message);
        }

        $ids = array();
        foreach ($body['data'] as $model) {
            if (!empty($model['id']) && Faq_Ai_Generator_Models::is_chat_model_id($model['id'])) {
                $ids[] = $model['id'];
            }
        }

        return $ids;
    }

    /**
     * Generate FAQs from post content
     *
     * @since    1.0.0
     * @param    string    $content           The post content
     * @param    array     $existing_faqs     Existing FAQs to consider
     * @param    string    $generation_hint   Optional per-generation hint
     * @return   array|WP_Error               Generated FAQs or error
     */
    public function generate_faqs($content, $existing_faqs = array(), $generation_hint = '')
    {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('API key not configured', 'faq-ai-generator'));
        }

        if (empty($content)) {
            if (FAQ_AI_DEBUG) {
                Faq_Ai_Generator_Logger::debug('Content is empty, attempting to get content from post');
            }

            // Prova diversi metodi per ottenere l'ID del post
            $post_id = null;

            // 1. Controlla se siamo nella pagina di modifica del post
            if (is_admin() && isset($_GET['post']) && isset($_GET['action']) && sanitize_text_field(wp_unslash($_GET['action'])) === 'edit') {
                $post_id = intval(sanitize_text_field(wp_unslash($_GET['post'])));
                if (FAQ_AI_DEBUG) {
                    $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
                    $context = ['url' => $request_uri];
                    Faq_Ai_Generator_Logger::debug('Edit page context, post ID: ' . $post_id, $context);
                }
            }

            // 2. Controlla se siamo in un contesto AJAX
            if (!$post_id && wp_doing_ajax() && isset($_POST['post_id'])) {
                // Verifica nonce per richieste AJAX usando check_ajax_referer
                try {
                    check_ajax_referer('faq_ai_nonce', 'nonce');
                    $post_id = intval(sanitize_text_field(wp_unslash($_POST['post_id'])));
                    if (FAQ_AI_DEBUG) {
                        Faq_Ai_Generator_Logger::debug('AJAX context, post ID from POST: ' . $post_id);
                    }
                } catch (Exception $e) {
                    if (FAQ_AI_DEBUG) {
                        Faq_Ai_Generator_Logger::warning('AJAX request without valid nonce: ' . $e->getMessage());
                    }
                }
            }

            // 3. Prova get_the_ID()
            if (!$post_id) {
                $post_id = get_the_ID();
                if (FAQ_AI_DEBUG) {
                    Faq_Ai_Generator_Logger::debug('get_the_ID() result: ' . ($post_id ? $post_id : 'false'));
                }
            }

            // 4. Se non funziona, prova a ottenere l'ID dalla query globale
            if (!$post_id && isset($GLOBALS['wp_query']->post)) {
                $post_id = $GLOBALS['wp_query']->post->ID;
                if (FAQ_AI_DEBUG) {
                    Faq_Ai_Generator_Logger::debug('Global query post ID: ' . ($post_id ? $post_id : 'false'));
                }
            }

            if ($post_id) {
                if (FAQ_AI_DEBUG) {
                    $context = [
                        'post_type' => get_post_type($post_id),
                        'context' => wp_doing_ajax() ? 'AJAX' : (is_admin() ? 'Admin' : 'Frontend')
                    ];
                    Faq_Ai_Generator_Logger::debug('Post ID found: ' . $post_id, $context);
                }

                // Verifica che il post esista e sia del tipo corretto
                $post = get_post($post_id);
                if (!$post) {
                    if (FAQ_AI_DEBUG) {
                        Faq_Ai_Generator_Logger::warning('Post not found: ' . $post_id);
                    }
                    return new WP_Error('invalid_post', __('Invalid post ID. The post does not exist.', 'faq-ai-generator'));
                }

                // Verifica che il post sia pubblicato o in bozza
                if ($post->post_status !== 'publish' && $post->post_status !== 'draft') {
                    if (FAQ_AI_DEBUG) {
                        Faq_Ai_Generator_Logger::warning('Post status not valid: ' . $post->post_status);
                    }
                    return new WP_Error('invalid_status', __('The post must be published or in draft status.', 'faq-ai-generator'));
                }

                $content = $this->get_page_content_via_curl($post_id);
                if (empty($content)) {
                    if (FAQ_AI_DEBUG) {
                        Faq_Ai_Generator_Logger::error('No content retrieved from post');
                    }
                    return new WP_Error('no_content', __('No content found to generate FAQs', 'faq-ai-generator'));
                }
                if (FAQ_AI_DEBUG) {
                    Faq_Ai_Generator_Logger::debug('Content retrieved from post, length: ' . strlen($content));
                }
            } else {
                if (FAQ_AI_DEBUG) {
                    $context = ['context' => wp_doing_ajax() ? 'AJAX' : (is_admin() ? 'Admin' : 'Frontend')];
                    Faq_Ai_Generator_Logger::warning('No post ID found in any context', $context);
                }
                return new WP_Error('no_post', __('No post ID found. Please make sure you are on a post or page, or provide a valid post ID.', 'faq-ai-generator'));
            }
        } else {
            if (FAQ_AI_DEBUG) {
                Faq_Ai_Generator_Logger::debug('Using provided content, length: ' . strlen($content));
            }
        }

        $settings = get_option('faq_ai_generator_settings', array());
        $this->model_id = Faq_Ai_Generator_Models::resolve_model_id($settings);

        // Genera il prompt completo
        $prompt = $this->build_prompt($content, $existing_faqs, $generation_hint);
        if (FAQ_AI_DEBUG) {
            Faq_Ai_Generator_Logger::debug('Built prompt, length: ' . strlen($prompt));
            Faq_Ai_Generator_Logger::debug('Prompt content: ' . substr($prompt, 0, 500) . '...');
        }

        // Prepara i dati per la chiamata API
        $request_data = array(
            'model' => $this->model_id,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'Sei un assistente esperto in SEO e creazione di FAQ.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            // GPT-5/GPT-6 models often only allow the default temperature (1); omit the param.
            // GPT-5/GPT-6 reject max_tokens; use max_completion_tokens.
            'max_completion_tokens' => 4000
        );

        // Modifica dei log per usare la costante di debug
        if (FAQ_AI_DEBUG) {
            Faq_Ai_Generator_Logger::debug('Request Parameters:');
            Faq_Ai_Generator_Logger::debug('Model: ' . $this->model_id);
            Faq_Ai_Generator_Logger::debug('Prompt Length: ' . strlen($prompt));
            Faq_Ai_Generator_Logger::debug('Existing FAQs Count: ' . count($existing_faqs));
            Faq_Ai_Generator_Logger::debug('Request Data: ' . json_encode($request_data, JSON_PRETTY_PRINT));
        }

        // Chiamata API
        $start_time = microtime(true);
        $response = wp_remote_post($this->api_endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($request_data),
            'timeout' => 60
        ));
        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time) * 1000; // in millisecondi

        // Log del tempo di esecuzione solo in debug
        if (FAQ_AI_DEBUG) {
            Faq_Ai_Generator_Logger::debug('API Call Execution Time: ' . $execution_time . 'ms');
        }

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            if (strpos($error_message, 'Operation timed out') !== false) {
                return new WP_Error('timeout', __('The request took too long. Please try again later.', 'faq-ai-generator'));
            }
            $context = [
                'error_message' => $error_message,
                'error_code' => $response->get_error_code(),
                'response' => $response
            ];
            Faq_Ai_Generator_Logger::error('API Error: ' . $error_message, $context);
            return $response;
        }

        // Log della risposta solo in debug
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        if ($response_code !== 200) {
            $context = [
                'response_code' => $response_code,
                'response_body' => $response_body
            ];
            Faq_Ai_Generator_Logger::error('API Error: ' . $response_code, $context);
            /* translators: %1$s: Response code, %2$s: Response body */
            return new WP_Error('api_error', sprintf(__('API error: %1$s - %2$s', 'faq-ai-generator'), $response_code, $response_body));
        }

        $context = [
            'response_code' => $response_code,
            'response_body' => $response_body
        ];
        Faq_Ai_Generator_Logger::debug('Response Body: ' . $response_body, $context);

        $body = json_decode($response_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $context = [
                'json_error' => json_last_error_msg(),
                'response_body' => $response_body
            ];
            Faq_Ai_Generator_Logger::error('JSON Error: ' . json_last_error_msg(), $context);
            /* translators: %s: JSON error message */
            return new WP_Error('json_error', sprintf(__('Error decoding API response: %s', 'faq-ai-generator'), json_last_error_msg()));
        }

        if (!isset($body['choices'][0]['message']['content'])) {
            $context = [
                'body' => $body
            ];
            Faq_Ai_Generator_Logger::error('Invalid Response Structure', $context);
            return new WP_Error('invalid_response', __('Invalid API response structure', 'faq-ai-generator'));
        }

        $content = $body['choices'][0]['message']['content'];

        $context = [
            'content' => $content
        ];
        Faq_Ai_Generator_Logger::debug('Raw Content', $context);

        // Rimuovi eventuali caratteri di escape e newline
        $content = str_replace(['\n', '\r'], '', $content);

        // Pulisci la risposta rimuovendo i backtick e il tag json
        $content = preg_replace('/^```json\s*|\s*```$/', '', $content);
        $content = trim($content);

        Faq_Ai_Generator_Logger::debug('Cleaned Content: ' . $content);

        // Prova a decodificare come JSON
        $faqs = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $context = [
                'json_error' => json_last_error_msg(),
                'content' => $content
            ];
            Faq_Ai_Generator_Logger::error('JSON Error: ' . json_last_error_msg(), $context);
            /* translators: %s: JSON error message */
            return new WP_Error('json_error', sprintf(__('Error decoding FAQ JSON: %s', 'faq-ai-generator'), json_last_error_msg()));
        }

        if (!is_array($faqs) || empty($faqs)) {
            Faq_Ai_Generator_Logger::error('No valid FAQs found in response');
            return new WP_Error('no_faqs', __('No valid FAQs found in response', 'faq-ai-generator'));
        }

        return $faqs;
    }

    /**
     * Get the prompt for the AI
     *
     * @since    1.0.0
     * @param    string    $content    The post content
     * @return   string               The formatted prompt
     */
    private function get_prompt($content)
    {
        return sprintf(
            "Genera 5-7 FAQ pertinenti per il seguente contenuto. Formatta ogni FAQ come 'Q: domanda\nA: risposta'. Il contenuto è:\n\n%s",
            $content
        );
    }

    /**
     * Parse the API response and format it as FAQ items
     *
     * @since    1.0.0
     * @param    array    $response    The API response
     * @return   array                Formatted FAQ items
     */
    private function parse_response($response)
    {
        if (empty($response['choices'][0]['message']['content'])) {
            return new WP_Error('invalid_response', __('Invalid API response', 'faq-ai-generator'));
        }

        $content = $response['choices'][0]['message']['content'];

        // Log del contenuto per debug
        Faq_Ai_Generator_Logger::debug('Raw Content: ' . $content);

        // Rimuovi eventuali caratteri di escape e newline
        $content = str_replace(['\n', '\r'], '', $content);

        // Prova a decodificare come JSON
        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // Verifica che ogni elemento abbia question e answer
            $valid_faqs = array_filter($decoded, function ($faq) {
                return isset($faq['question']) && isset($faq['answer']) &&
                    !empty($faq['question']) && !empty($faq['answer']);
            });

            if (!empty($valid_faqs)) {
                return array_values($valid_faqs);
            }
        }

        // Se non è JSON valido o non contiene FAQ valide, prova a estrarre FAQ dal testo
        $faqs = array();
        $lines = explode("\n", $content);

        $current_question = '';
        $current_answer = '';

        foreach ($lines as $line) {
            $line = trim($line);

            // Se la riga inizia con "Q:" o "Domanda:", è una nuova domanda
            if (preg_match('/^(Q:|Domanda:|Q\.|Domanda\.)\s*(.+)$/i', $line, $matches)) {
                // Se abbiamo una domanda e risposta precedenti, salvale
                if (!empty($current_question) && !empty($current_answer)) {
                    $faqs[] = array(
                        'question' => trim($current_question),
                        'answer' => trim($current_answer)
                    );
                }
                $current_question = $matches[2];
                $current_answer = '';
            }
            // Se la riga inizia con "R:" o "Risposta:", è una nuova risposta
            elseif (preg_match('/^(R:|Risposta:|R\.|Risposta\.)\s*(.+)$/i', $line, $matches)) {
                $current_answer = $matches[2];
            }
            // Altrimenti, aggiungi alla risposta corrente
            elseif (!empty($current_question)) {
                $current_answer .= "\n" . $line;
            }
        }

        // Aggiungi l'ultima FAQ se presente
        if (!empty($current_question) && !empty($current_answer)) {
            $faqs[] = array(
                'question' => trim($current_question),
                'answer' => trim($current_answer)
            );
        }

        if (empty($faqs)) {
            $context = [
                'json_error' => json_last_error_msg(),
                'content' => $content
            ];
            Faq_Ai_Generator_Logger::error('No valid FAQs found in response', $context);
            return new WP_Error('no_faqs', __('No FAQs found in the response', 'faq-ai-generator'));
        }

        return $faqs;
    }
}