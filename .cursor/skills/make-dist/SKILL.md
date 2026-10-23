---
name: make-dist
description: >-
  Build or prepare an installable FAQ AI Generator WordPress plugin zip. Use when
  the user asks for a dist, release zip, plugin package, or version bump before
  distribution. Enforces version sync across header, constant, and readme.txt.
---

# Make production dist — FAQ AI Generator

Crea (o prepara) uno zip WordPress installabile con **solo file produzione** e versioni allineate.

## Tre campi versione (devono coincidere)

| File | Campo |
|------|--------|
| `faq-ai-generator.php` | Header `Version:` + `FAQ_AI_GENERATOR_VERSION` |
| `readme.txt` | `Stable tag:` |

SemVer: PATCH fix, MINOR feature, MAJOR breaking.

## Workflow

### 1. Bump (se il codice è cambiato)

Aggiorna i tre campi alla stessa `X.Y.Z`.

### 2. Changelog e readme

- `CHANGELOG.md` — sezione `## [X.Y.Z] - YYYY-MM-DD` (Git; può restare fuori dallo zip)
- `readme.txt` — voce `= X.Y.Z =` sotto Changelog

### 3. Scan no-tooling-leak sull’artifact

Nello zip **non** devono finire:

- `.cursor/`, `AGENTS.md`, `scratchpad.md`, `.git/`, `.DS_Store`
- riferimenti a Cursor/Claude/Copilot nei file shippati

OpenAI nei testi prodotto è OK.

### 4. Build zip (manuale finché non esistono script)

Dalla root del repo (esempio):

```bash
VERSION=$(grep -E "Version:" faq-ai-generator.php | head -1 | sed 's/.*Version:[[:space:]]*//')
STAGE="dist/faq-ai-generator-${VERSION}"
rm -rf dist
mkdir -p "$STAGE"
rsync -a \
  --exclude='.git' \
  --exclude='.cursor' \
  --exclude='.DS_Store' \
  --exclude='dist' \
  --exclude='scratchpad.md' \
  --exclude='AGENTS.md' \
  --exclude='.gitignore' \
  ./ "$STAGE/faq-ai-generator/"
(cd dist && zip -r "faq-ai-generator-${VERSION}.zip" "faq-ai-generator-${VERSION}/faq-ai-generator")
```

Output atteso:

- `dist/faq-ai-generator-X.Y.Z/faq-ai-generator/` — albero plugin
- `dist/faq-ai-generator-X.Y.Z.zip` — upload WP

La cartella `dist/` è in `.gitignore`.

### 5. Verifica rapida

- Header e Stable tag = versione nello zip
- Nessun path `.cursor` nello zip
- Plugin si attiva su WP di test

## Follow-up opzionale

Quando serve automatizzare: aggiungere `tools/dist/bump-version.sh` e `build-plugin-zip.sh` (come su Fabrick) e aggiornare questa skill.
