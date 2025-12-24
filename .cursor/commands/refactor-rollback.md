# refactor-rollback

Goal: restore repo back to the pre-refactor checkpoint tag.

1. Locate the most recent checkpoint tag:

- Run: checkpoint_tag="$(git tag --list 'checkpoint/pre-refactor-\*' --sort=-creatordate | head -n 1)"
- Print: $checkpoint_tag
- If empty: STOP and ask me for the tag name.

2. Hard reset to checkpoint:

- Run: git reset --hard "$checkpoint_tag"

3. Ask me before deleting untracked files:
   "Do you also want to remove untracked files (git clean -fd)? YES/NO"

- If YES: run git clean -fd

4. Print:

- git status --porcelain=v1 -uall
- Confirmation we’re restored to $checkpoint_tag
