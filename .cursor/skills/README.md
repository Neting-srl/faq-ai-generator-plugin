# Cursor skills — FAQ AI Generator

Skill di progetto in `.cursor/skills/`. Gli agenti le leggono quando il task corrisponde alla `description` nel frontmatter.

| Skill | Quando usarla |
|-------|----------------|
| [no-ai-leak-git](no-ai-leak-git/SKILL.md) | Prima di `git commit`, `git push`, `gh pr create` / merge |
| [make-dist](make-dist/SKILL.md) | Zip installabile, bump versione, allineo changelog/readme |

Policy prodotto vs tooling: `.cursor/rules/faq-ai-product-speak.mdc`.
