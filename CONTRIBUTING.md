# Contributing to Teleproto

Thank you for considering contributing to Teleproto!

## Development Setup

This repository is a monorepo containing four packages that map to different structural concerns: `teleproto-core`, `teleproto-schema`, `teleproto-laravel`, and `teleproto-bot`.

1. **Clone the repository:**
   ```bash
   git clone https://github.com/MeRezaRezaei/teleproto.git
   cd teleproto
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Run tests and verify:**
   ```bash
   composer verify
   ```

## Pull Request Guidelines

- Ensure all new features and bug fixes include corresponding unit tests in their respective `packages/*/tests/` directories.
- Verify that `composer verify` passes locally (it runs pin checks, all test suites, and PHPStan on all packages).
- Adhere to PSR-12 coding standards and strict type declarations (`declare(strict_types=1);`).
- Do not introduce `illuminate/*` dependencies to `teleproto-core` or `teleproto-schema`.
- **Zero regex in core packages**: Do not use `preg_*()` in package sources.
