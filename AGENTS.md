# AGENTS.md — FAQ AI Generator

Orientamento breve per agenti AI. Dettagli operativi in `.cursor/rules/faq-*.mdc` e skill in `.cursor/skills/`.

## Cos'è

Plugin **WordPress** che genera FAQ da contenuti di post/pagine (e campi ACF) tramite **API OpenAI Chat Completions**, le salva in meta `_faq_ai_data`, le mostra nel content e aggiunge **Schema.org FAQPage**.

Autore / text domain: `faq-ai-generator` · versione corrente: costante `FAQ_AI_GENERATOR_VERSION` (allineare a header plugin + `readme.txt` Stable tag).

## Stack

- PHP classico WordPress (bootstrap + `includes/` + `admin/` + `public/`), non PSR-4 obbligatorio
- Admin: metabox + Settings (`Settings > FAQ AI Generator`)
- AJAX: `faq_ai_generate`, `faq_ai_sync_models`
- Frontend: CSS/JS condizionali solo se ci sono FAQ da mostrare
- i18n: text domain `faq-ai-generator`, cartella `languages/`

## Dove sta cosa

| Path | Ruolo |
|------|--------|
| `faq-ai-generator.php` | Bootstrap, versione |
| `includes/class-faq-ai-generator.php` | Loader hook |
| `includes/class-faq-ai-generator-api.php` | Chiamate OpenAI, `build_prompt`, generazione |
| `includes/class-faq-ai-generator-models.php` | Lista modelli curata + sync |
| `admin/class-faq-ai-generator-admin.php` | Settings, metabox, AJAX, save meta |
| `admin/partials/metabox-template.php` | Markup metabox (hint, lista FAQ) |
| `admin/js/faq-ai-generator-metabox.js` | UI generazione FAQ |
| `admin/js/faq-ai-generator-admin.js` | Sync modelli in Settings |
| `public/` | Render FAQ + schema |
| `scratchpad.md` | Memoria operativa aperta |
| `.cursor/rules/` | Regole agente |
| `.cursor/skills/` | Workflow ripetibili |

## Vincoli non negoziabili

1. **Sicurezza WP**: nonce + capability + sanitize/escape (vedi `faq-security.mdc`).
2. **Mai loggare** API key, prompt completi con dati sensibili, o risposte grezze in produzione senza debug esplicito.
3. **i18n**: niente stringhe UI hardcoded; text domain `faq-ai-generator`.
4. **Prodotto vs tooling**: OpenAI/GPT nei testi prodotto/readme è OK; riferimenti a Cursor/Claude/Copilot in commit/trunk no (vedi `faq-ai-product-speak.mdc`).
5. **Un solo client metabox**: non reintrodurre script inline o file JS duplicati per la generazione.
6. **Versione**: header plugin ↔ `FAQ_AI_GENERATOR_VERSION` ↔ `readme.txt` Stable tag devono coincidere.

## Flusso generazione (ricorda)

1. Metabox: hint opzionale + conferma overwrite/append.
2. AJAX `faq_ai_generate` → API `generate_faqs( $content, $existing, $hint )`.
3. Prompt: base → `extra_prompt` globale → `generation_hint` → FAQ esistenti → contenuto.
4. Persistenza in `_faq_ai_data` (`faqs`, `display_in_content`, `generation_hint`, `model_used`).

## Skill

| Skill | Quando |
|-------|--------|
| `no-ai-leak-git` | Prima di commit / push / PR |
| `make-dist` | Zip installabile / bump versione |

## Fuori scope tipico

Non trasformare il plugin in React/Next, non aggiungere provider non-OpenAI senza richiesta esplicita, non copiare rules dominio WooCommerce/pagamenti da altri progetti.
