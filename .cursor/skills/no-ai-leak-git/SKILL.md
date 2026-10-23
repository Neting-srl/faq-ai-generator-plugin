---
name: no-ai-leak-git
description: >-
  Enforces no-tooling-leak on Git commit, push, and PR operations for FAQ AI
  Generator. Blocks Cursor/Claude/Copilot attribution in commit messages and PR
  bodies. Use before every git commit, git push, gh pr create, gh pr merge, or
  when the user mentions release or no-ai-leak. OpenAI product mentions in code
  are allowed; see faq-ai-product-speak.mdc.
---

# No-AI-leak Git (FAQ AI Generator)

Policy: `.cursor/rules/faq-ai-product-speak.mdc`.

## Prima di ogni commit

1. **Mai** `Co-authored-by: Cursor|Claude|Copilot|ChatGPT|Gemini` nel messaggio.
2. **Mai** `Generated with`, `AI-assisted`, `cursoragent@`, URL cursor.com nel messaggio.
3. Messaggio in inglese, professionale, stile repo.
4. Dopo il commit verifica:

```bash
git log -1 --format=%B
```

Il body non deve contenere i pattern vietati sopra.

## Prima di push / PR

Controlla i commit in uscita:

```bash
git log @{u}..HEAD --format=%B 2>/dev/null || git log origin/main..HEAD --format=%B
```

Se un commit leakà: `git commit --amend` (solo se amend consentito dalle regole Git del progetto) oppure nuovo commit correttivo / rebase secondo policy utente.

## Checklist agente

```
- [ ] Messaggio commit senza attribuzioni IDE/AI
- [ ] PR body senza "Generated with Cursor/Claude"
- [ ] OpenAI nei file prodotto resta OK (non rimuovere dal readme)
```

## Nota

Hook Git automatici (`.githooks/`) non sono ancora installati in questo repo. L’agente applica comunque questa checklist a mano.
