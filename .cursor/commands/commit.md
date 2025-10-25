# Git Commit Instructions

Please review all changes in the current working directory and create logical, well-structured commits following conventional commit standards.

## Your Task

1. **Analyze the changes**: Review `git status` and `git diff` to understand what has been modified, added, or deleted
2. **Group related changes**: Identify which changes belong together logically
3. **Determine commit order**: Arrange commits in a logical sequence that reflects how the changes should be applied
4. **Create separate commits**: Make multiple focused commits rather than one large commit
5. **Write clear messages**: Follow conventional commit format with descriptive subjects and bodies

## Commit Ordering Strategy

When creating multiple commits, order them logically based on:

### Chronological/Dependency Order (Preferred)
Commits should follow the natural order of development and dependencies:
1. **Foundation first**: Infrastructure, configuration, dependencies
2. **Data layer**: Database migrations, schema changes, model updates
3. **Business logic**: Services, utilities, core functionality
4. **Integration**: Controllers, API endpoints, command handlers
5. **Presentation**: Views, components, UI changes
6. **Quality**: Tests for the above changes
7. **Documentation**: README, API docs, comments
8. **Cleanup**: Remove deprecated code, unused imports

### Logical Grouping Principles
- **Dependencies before dependents**: Commit code that other code depends on first
- **Breaking changes isolated**: Separate breaking changes into their own commits
- **Fixes follow features**: If fixing a bug in newly added code, commit the feature first
- **Setup before usage**: Configuration and setup before the code that uses it
- **Reversibility**: Order commits so they can be reverted without breaking things

### Example Order for a Feature
```
1. chore(deps): add required packages for OAuth
2. feat(database): add oauth_tokens table migration
3. feat(models): create OAuthToken model
4. feat(services): implement OAuth service layer
5. feat(api): add OAuth authentication endpoints
6. feat(frontend): add OAuth login button
7. test(auth): add OAuth flow test coverage
8. docs(auth): document OAuth setup process
```

## Commit Format

Use the conventional commits specification:

### Type Options
- `feat`: New feature or functionality
- `fix`: Bug fix
- `refactor`: Code restructuring without behavior change
- `perf`: Performance improvement
- `test`: Adding or updating tests
- `docs`: Documentation changes
- `style`: Code style/formatting (whitespace, semicolons, etc.)
- `chore`: Maintenance tasks (dependencies, config, tooling)
- `build`: Build system or external dependencies
- `ci`: CI/CD configuration changes
- `revert`: Reverting a previous commit

### Scope
- Use the module, component, or area affected (e.g., `auth`, `api`, `database`, `models`, `services`)
- Keep it concise and lowercase
- Optional but recommended

### Subject
- Use imperative mood ("add feature" not "added feature")
- Don't capitalize first letter
- No period at the end
- Maximum 50 characters
- Be specific and descriptive

### Body
- Explain WHAT changed and WHY (not how - that's in the code)
- Wrap at 72 characters
- Include motivation for the change
- Contrast with previous behavior if relevant
- Optional but recommended for non-trivial changes

### Footer
- Reference issue numbers (e.g., `Closes #123`, `Fixes #456`)
- Note breaking changes: `BREAKING CHANGE: description`
- Optional

## Guidelines

### Commit Granularity
- **One logical change per commit**: Each commit should represent a single, complete idea
- **Keep commits atomic**: Each commit should leave the codebase in a working state
- **Separate concerns**: Don't mix refactoring with new features or bug fixes
- **Think like a reviewer**: Each commit should be easy to review and understand independently

### What NOT to Do
- ❌ Don't create one massive commit with everything
- ❌ Don't use vague messages like "fix stuff" or "updates"
- ❌ Don't mix unrelated changes in one commit
- ❌ Don't commit commented-out code or debug statements
- ❌ Don't include temporary or generated files
- ❌ Don't commit things out of logical order (e.g., tests before the code they test)
- ❌ Don't create commits that would break the build if checked out individually

### Best Practices
- ✅ Make commits tell a story of how the feature/fix was built
- ✅ Write commit messages for your future self and teammates
- ✅ Order commits so each one builds upon the previous
- ✅ Ensure each commit is independently functional (builds and runs)
- ✅ Use present tense imperative ("add" not "adds" or "added")
- ✅ Reference relevant issues or tickets
- ✅ Explain non-obvious decisions in the body
- ✅ Consider how the commits would appear in a pull request review

## Examples

### Good Commit Messages
```
feat(auth): add OAuth authentication via Twitch

Implement Twitch OAuth flow using Laravel Socialite. Users can now
sign in with their Twitch account instead of email/password.

- Add Twitch OAuth routes and controller
- Create TwitchUser model to store OAuth data
- Link TwitchUser to User model via relationship

Closes #42
```
```
perf(api): optimize bulk import with batch operations

Replace individual database queries with batch upsert operations.
This reduces query count from 2000+ to ~10 per request.

Performance improvement:
- Response time: 7000ms → 500ms
- Memory usage: 36MB → 10MB

Fixes #89
```
```
refactor(services): extract business logic from controllers

Move business logic into dedicated service classes for better
separation of concerns and testability.

- Create TwitchStatsService
- Create TwitchAuthService
- Update controllers to use services
```
```
test(api): add comprehensive test coverage for stats endpoint

Add tests for validation, error handling, and edge cases.
Increases coverage from 45% to 92%.
```
```
chore(deps): remove unused Laravel Fortify package

Remove Fortify as application uses OAuth-only authentication.
Also removes related config files and service providers.
```

### Bad Commit Messages
❌ `fix bug`
❌ `updates`
❌ `WIP`
❌ `asdfasdf`
❌ `Fixed the thing that John mentioned`
❌ `Updated files`
❌ `changes` (too vague)
❌ `stuff` (meaningless)

## Process

1. **Analyze all changes**: Get the full picture before planning commits
2. **Plan commit sequence**: Determine the logical order based on dependencies
3. **Stage related files**: Use `git add` to stage files for the first logical commit
4. **Write commit**: Use `git commit` with a well-formatted message
5. **Repeat in order**: Continue with the next logical commit in sequence
6. **Review**: Use `git log` to verify commits tell a clear, chronological story

## Special Cases

### Large Refactoring
Break into multiple commits in this order:
1. Prepare/setup (create new structure)
2. Move/refactor (migrate old code)
3. Clean up (remove old code)
4. Update tests
5. Update documentation

### Bug Fixes
Include:
- What the bug was
- How it manifested
- What caused it
- How the fix works

If the bug was in code added in the current changeset, commit the feature first, then the fix.

### Breaking Changes
Always include `BREAKING CHANGE:` in footer with migration instructions.
Isolate breaking changes in separate commits when possible.

### Multiple Unrelated Changes
If working directory contains completely unrelated changes:
- Clearly separate them into distinct commit groups
- Use different scopes to indicate different areas
- Consider whether some changes should be in a different branch

## Final Check

Before finishing, verify:
- [ ] Each commit has a clear, descriptive message
- [ ] Commits are logically grouped
- [ ] Commits are ordered chronologically/by dependency
- [ ] Each commit builds upon the previous one logically
- [ ] No commit mixes unrelated changes
- [ ] Commit messages follow conventional format
- [ ] The commit history tells a coherent story
- [ ] Each commit would leave the codebase in a working state
- [ ] No sensitive information in commit messages
- [ ] The order makes sense for code review

Now please proceed to analyze the changes and create the commits!