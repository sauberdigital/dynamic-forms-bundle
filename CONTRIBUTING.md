# Contributing

This project uses `main` (release) + `develop` (integration). Please follow the process below.

## TL;DR
1. Fork this repo and clone your fork.
2. Add upstream: `git remote add upstream git@github.com:sauberdigital/dynamic-forms-bundle.git`
3. Create a branch from the current integration branch:
   `git switch -c feature/<name> upstream/develop` (or `fix/<name>`, `docs/<name>`, `chore/<name>`)
4. Use Conventional Commits (`feat: ...`, `fix: ...`, `docs: ...`, `chore: ...`).
5. Open a PR to **`sauberdigital:develop`**. CI must be green. Squash merge by maintainers.

## Hotfixes
- Branch from **`main`** → PR to **`main`** → after merge, back-merge `main` into `develop`.

## Notes
- Be respectful. (See `CODE_OF_CONDUCT.md` if present.)
- License: MIT.