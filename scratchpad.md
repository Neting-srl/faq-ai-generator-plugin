# Scratchpad — FAQ AI Generator

> Memoria operativa di sessione. Aggiornato dall'agente Cursor a fine lavoro sostanziale.
> **Ultimo aggiornamento:** 2026-10-23 — rebase su origin/main 1.1.4 + release 1.1.5 (fix gen + hint + models + agent tooling).

## Aperti

| ID | Titolo | Stato | Priorità | Contesto | Azione | Note |
|---|---|---|---|---|---|---|
| S-001 | Verifica manuale WP (generate / hint / sync modelli) | aperto | alta | Post-fix 2026-10-23 | Testare in WP locale: Settings sync, metabox hint, append/overwrite, frontend | Nessun ambiente WP in repo |
| S-002 | Tooling dist zip (script bump + build) | aperto | media | Skill make-dist senza script ancora | Opzionale: aggiungere `tools/dist/` come su Fabrick | Per ora skill documenta passi manuali |
| S-003 | PHPCS / quality gate | aperto | bassa | Confronto Fabrick | Valutare `composer` + PHPCS WordPress in un secondo momento | Non bloccante |

## In corso

| ID | Titolo | Stato | Priorità | Contesto | Azione | Note |
|---|---|---|---|---|---|---|

## Bloccati

| ID | Titolo | Stato | Priorità | Contesto | Azione | Note |
|---|---|---|---|---|---|---|

## Risolti (recenti)

| ID | Titolo | Risolto il | Come |
|---|---|---|---|
| S-000 | Generazione FAQ rotta (action AJAX / JS duplicati) | 2026-10-23 | Unificato metabox JS, capability, hint, modelli aggiornati |
