# Git Commit Instructions

Review the working tree and create focused, logically ordered commits
using Conventional Commits.

## Workflow

1. Inspect changes with `git status` and `git diff`.
2. Group by logical unit (one complete idea per commit).
3. Order commits by dependency:
   - foundation/config/deps
   - database/schema
   - business logic
   - integration/API/controllers
   - UI/presentation
   - tests
   - docs/cleanup
4. Stage and commit each group separately.
5. Verify final history with `git log`.

## Commit Rules

- Keep commits atomic and reviewable.
- Do not mix unrelated changes.
- Avoid WIP/vague messages.
- Do not include temporary/debug/generated files.
- Each commit should leave the project in a sane state.

## Conventional Commit Format

`type(scope): subject`

- `type`: `feat`, `fix`, `refactor`, `perf`, `test`, `docs`, `style`,
  `chore`, `build`, `ci`, `revert`
- `scope`: concise area name (optional, recommended)
- `subject`: imperative, lowercase, no period, <= 50 chars
- body (optional): what + why, wrapped at ~72 chars
- footer (optional): issue refs, `BREAKING CHANGE: ...`

## Output Expectations

- Create multiple commits when needed.
- Keep commit sequence coherent for PR review and revertability.
- Include a brief body for non-trivial commits.

Proceed to analyze, plan, stage, and commit accordingly.