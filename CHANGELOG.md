# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [Unreleased]

## [1.1.0] - 2025-08-12
### Added
- Circular dependency prevention in the dependency graph (detects A→B→A and multi-node cycles).
- Unit tests and docs for cycle detection.
- GitHub issue templates (bug report, feature request).
- SECURITY policy.

### Changed
- Switch to trunk-based development (PRs to `main`, `develop` removed); streamlined CI triggers.
- Composer `support` links (homepage, source tree, docs, security).
- Composer branch-alias: `dev-main` → `1.x-dev`.
- Tightened `.gitattributes` for clean dist exports.

## [1.0.0] - 2025-08-08
### Added
- Initial release of **Dynamic Forms Bundle**.
- Dynamic field dependencies (single & multiple), nested chains, circular dependency detection.
- Server-driven updates via Symfony Form events (`POST_SUBMIT`), **no JS required**.
- Example integration with **Symfony UX Live Components**.
- README, CONTRIBUTING, CODEOWNERS, CI (PHP 8.2/8.3/8.4; “lowest” deps on 8.2), paths filtering.

[Unreleased]: https://github.com/sauberdigital/dynamic-forms-bundle/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/sauberdigital/dynamic-forms-bundle/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/sauberdigital/dynamic-forms-bundle/releases/tag/v1.0.0
