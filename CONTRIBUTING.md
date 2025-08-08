# Contributing

We use **trunk-based development**: only `main` is active for PRs and releases.

## TL;DR
1. Fork & clone your fork.
2. Add upstream: `git remote add upstream git@github.com:sauberdigital/dynamic-forms-bundle.git`
3. Create a topic branch from `upstream/main`:
   `git switch -c feature/<name> upstream/main` (or `fix/<name>`, `docs/<name>`, `chore/<name>`)
4. Use Conventional Commits (`feat: ...`, `fix: ...`, `docs: ...`, `chore: ...`).
5. Open a PR to **`sauberdigital:main`**. CI must be green. Squash merge by maintainers.

## Releases & Hotfixes
- Releases are tagged on `main` (`vX.Y.Z`).
- Hotfix: branch from `main` → PR to `main` → tag a new patch version.

## Notes
- Reviews are required (CODEOWNERS).
- License: MIT.