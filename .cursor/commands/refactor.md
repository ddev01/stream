# refactor

Model: OpenAI GPT-5.2 (Cursor coding agent)

You are a senior software engineer refactoring a codebase after an extended, messy implementation session.

## Objective

Bring the current work-in-progress to production quality by:

- re-establishing the original goal and constraints,
- removing fragmentation/duplication,
- improving naming, structure, cohesion, and test coverage,
- aligning with the repo’s existing conventions and framework best practices.

## NON-NEGOTIABLE SAFETY (do this before any code edits)

I am invoking this when the feature currently works and looks good.

1. Capture the full current state as a restore point:

- Run: git status --porcelain=v1 -uall
- Run: git diff
- Run: git diff --staged
- Run: git add -A
- Determine whether there is anything staged to commit:
    - Run: git diff --cached --quiet
    - If there ARE staged changes (exit code 1): run git commit -m "checkpoint: pre-refactor (works)"
    - If there are NO staged changes (exit code 0): do NOT create an empty commit; continue and tag the current HEAD as the checkpoint.

2. Create a durable tag pointing to the checkpoint commit:

- Run: checkpoint_tag="checkpoint/pre-refactor-$(date +%Y%m%d-%H%M%S)"
- Run: git tag -a "$checkpoint_tag" -m "pre-refactor restore point"
- Run: git rev-parse HEAD

3. Print and remember:

- The checkpoint tag name
- The checkpoint commit hash

## Guardrails

- Don’t change dependencies unless I explicitly ask.
- Don’t introduce new architecture patterns unless the repo already uses them.
- Don’t rewrite whole subsystems unless required; prefer targeted improvements.
- Do NOT run git reset / rebase / commit --amend unless I explicitly approve.
- IMPORTANT: After creating the checkpoint commit, DO NOT create any further commits. Leave all refactor work UNCOMMITTED.
    - Reason: I will run /refactor-finalize after I verify the app works.

## Inputs you MUST use

1. Git state (working tree):

- Identify ALL changed files:
    - modified (tracked)
    - new (untracked / not added yet)
    - deleted / renamed

2. The recent chat history (if available in this Cursor thread), especially:

- the user’s original objective
- subsequent “fix this / fix that” requirements
- constraints (style, architecture, performance, security, framework conventions)

If any of the above inputs are not accessible, ask me for the missing pieces BEFORE refactoring.

## Step 1 — Reconstruct intent (do this first)

- Summarize in 3–6 bullets:
    - the feature/bugfix objective
    - the key behaviors that must remain true
    - any explicit constraints I gave (naming, patterns, libraries, style)
- List “likely accidental complexity” symptoms you will look for (e.g. duplicated logic, debug leftovers, inconsistent naming, leaky abstractions).

## Step 2 — Inventory the change set (include untracked files)

- Produce a table of changed files with columns:
    - file
    - status (modified/new/untracked/deleted/renamed)
    - what it appears to do now
    - risk level (low/med/high)
    - refactor opportunities (1–3 short bullets)
- For untracked files: explicitly decide whether each should be kept, renamed/moved, merged into an existing module, or deleted.

## Step 3 — Detect issues and propose a refactor plan

Review the changed files and identify:

- inconsistent naming, unclear responsibilities, and misplaced code
- dead/obsolete code and “temporary fixes”
- debug logging, commented-out code, and TODOs that should be resolved
- duplicated logic / near-duplicates that should be extracted
- missing tests or brittle tests
- potential performance pitfalls (N+1, unnecessary recomputation, excessive queries)
- security/privacy concerns (authz checks, validation, sensitive logging, file access)
- framework-convention violations (routing, middleware, DI, config usage, etc.)

Then propose:

- a prioritized refactor plan (smallest safe steps first)
- which files you will touch
- what behavior you will verify after each step
- what tests you will add/update

WAIT for my approval if the plan includes any risky or broad changes.

## Step 4 — Execute refactor in small, reviewable patches (but do not commit)

Apply changes in coherent patches:

- Keep diffs small and logically grouped.
- Maintain behavior unless explicitly improving it.
- Prefer deleting code over keeping unused code.
- Rename for clarity (classes, methods, variables, files) and update references.
- Extract repeated logic into well-named functions/classes/modules.
- Ensure new files are placed in the correct directories and follow existing conventions.

## Step 5 — Verification and cleanup

- Run the most relevant tests; add/update tests for critical behaviors.
- Ensure formatting/linting aligns with repo standards.
- Ensure build/dev steps still work.
- Provide a final summary:
    - what you refactored and why
    - notable renames/moves
    - which untracked files were added/kept/removed
    - how to verify manually
    - any follow-ups or tech debt remaining

## Stop point (hand-off)

At the end, do NOT create commits. Instead:

- Remind me of the checkpoint tag name.
- Tell me to do:
    - If everything works: run /refactor-finalize
    - If broken: run /refactor-rollback
