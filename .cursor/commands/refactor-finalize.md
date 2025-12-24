# refactor-finalize

You are an autonomous GPT-5.2 coding agent.

Goal: (A) remove the checkpoint commit from branch history while keeping ALL current working tree changes, then (B) create logical, well-structured commits following conventional commit standards (same behavior as my /commit command).

## Pre-flight: confirm I'm ready

Ask me: "Have you already tested the app after the refactor and want to FINALIZE + COMMIT now? (YES/NO)"

- If NO: STOP (do not run any git history rewriting or commits).

## A) Finalize: remove checkpoint commit (if present) but keep changes

1. Locate the most recent checkpoint tag:

- Run: checkpoint_tag="$(git tag --list 'checkpoint/pre-refactor-\*' --sort=-creatordate | head -n 1)"
- Print: $checkpoint_tag
- If empty: STOP and ask me for the checkpoint tag name.

2. Safety check: ensure checkpoint tag is on current HEAD (there should be no commits after checkpoint):

- Run: head_hash="$(git rev-parse HEAD)"
- Run: tag_hash="$(git rev-parse "$checkpoint_tag")"
- Print: $head_hash
- Print: $tag_hash
- If they differ: STOP and ask me what to do (do not guess).

3. If the current HEAD commit is the checkpoint commit, drop it while keeping changes:

- Run: head_subject="$(git show -s --format=%s HEAD)"
- Print: $head_subject
- If $head_subject equals "checkpoint: pre-refactor (works)":
    - Run: git reset --mixed HEAD~1
- Otherwise:
    - Do NOT reset; proceed (this covers the "no changes to commit" checkpoint case).

4. Show current state:

- Run: git status --porcelain=v1 -uall
- Run: git diff --stat

## B) Commit: create clean incremental commits (conventional commits)

Now please review all changes in the current working directory and create logical, well-structured commits following conventional commit standards.

### Your Task

1. Analyze the changes: Review `git status` and `git diff` to understand what has been modified, added, or deleted
2. Group related changes: Identify which changes belong together logically
3. Determine commit order: Arrange commits in a logical sequence that reflects how the changes should be applied
4. Create separate commits: Make multiple focused commits rather than one large commit
5. Write clear messages: Follow conventional commit format with descriptive subjects and bodies

### Commit Ordering Strategy (Preferred)

Order commits in dependency order:

1. Foundation first: Infrastructure, configuration, dependencies
2. Data layer: Database migrations, schema changes, model updates
3. Business logic: Services, utilities, core functionality
4. Integration: Controllers, API endpoints, command handlers
5. Presentation: Views, components, UI changes
6. Quality: Tests
7. Documentation
8. Cleanup

### Conventional Commit Format

Types: feat, fix, refactor, perf, test, docs, style, chore, build, ci, revert
Scope: concise lowercase module/area (optional but recommended)
Subject: imperative, <= 50 chars, no period
Body: what/why, wrap ~72 chars when non-trivial

### Granularity rules

- One logical change per commit, keep commits atomic
- Don’t mix unrelated changes
- Don’t commit commented-out code, debug statements, or generated files
- Each commit should leave the codebase in a working state when possible

### Process to execute (use real git commands)

- Use `git diff`, `git diff --name-status`, and `git status --porcelain=v1 -uall` to fully understand scope
- For each planned commit:
    - Stage only what belongs (use `git add -p` when needed)
    - Commit with a conventional commit message
    - Verify with `git status` and `git log --oneline -10`
- Repeat until clean

## Final output

At the end, print:

- `git status --porcelain=v1 -uall`
- `git --no-pager log --oneline -20`
- A short list of commits created (type/scope/subject)
- Remind me the checkpoint tag still exists for rollback if needed: $checkpoint_tag
