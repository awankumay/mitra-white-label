---
paths:
    - "**/*"
---

# General Project Rules

## Output Language

All user-facing output must be written in Bahasa Indonesia.

Keep the following in English:

- Code identifiers
- Class names
- Method names
- Variable names
- File paths
- CLI commands
- Configuration keys
- Package names
- Technical terms where English is the established convention
- Conventional Commit types such as `feat`, `fix`, `docs`, `refactor`, etc.

Do not translate code identifiers.

---

## Source of Truth

Use these documents according to their responsibility:

- `CLAUDE.md` — project context, architecture guardrails, development behavior
- `PRD.md` — product and architectural requirements
- `TODO.md` — implementation roadmap

Do not duplicate large sections of these documents inside individual rules.

When requirements conflict:

1. Follow explicit decisions in `PRD.md`.
2. Follow architectural constraints in `CLAUDE.md`.
3. Use `TODO.md` for implementation sequencing.

If an implementation appears to require changing an architectural decision, stop and explain the conflict before proceeding.

---

## Existing Code First

Before creating a new:

- Class
- Service
- Action
- Contract
- Trait
- Helper
- Component
- Configuration
- Command
- Package integration

inspect the existing codebase first.

Prefer extending an existing correct abstraction over creating a parallel implementation.

Avoid duplicate abstractions.

---

## Minimal Change

Implement the smallest coherent change required by the task.

Do not:

- Rewrite unrelated code
- Perform broad refactors without justification
- Rename unrelated classes
- Change unrelated dependencies
- Modify architecture for convenience

Keep changes focused and reviewable.

---

## Package Discipline

Before adding a dependency:

1. Check Laravel's native capability.
2. Check Filament's native capability.
3. Check existing installed packages.
4. Verify Laravel 13 compatibility.
5. Verify Filament 5 compatibility.
6. Verify PHP compatibility.
7. Check package maintenance.
8. Check license.
9. Evaluate on-premise compatibility.
10. Evaluate whether the package introduces SaaS assumptions.

Do not add dependencies simply because they are convenient.

---

## No SaaS Assumptions

Never introduce SaaS architecture into Core without explicit approval.

Avoid assumptions involving:

- Tenant provisioning
- Tenant billing
- Subscription
- Central tenant management
- Tenant database isolation
- License servers
- Mandatory external APIs

Mitra White Label is standalone-first.
