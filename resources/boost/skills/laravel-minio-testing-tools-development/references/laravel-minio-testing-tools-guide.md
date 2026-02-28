# laravel-minio-testing-tools development guide

For full documentation, see the README: https://github.com/protonemedia/laravel-minio-testing-tools#readme

## At a glance
Testing utilities/trait to run test suites against a **MinIO** (S3-compatible) server.

## Local setup
- Install dependencies: `composer install`
- Keep the dev loop package-focused (avoid adding app-only scaffolding).

## Testing
- Run: `composer test` (preferred) or the repository’s configured test runner.
- Add regression tests for bug fixes.

## Notes & conventions
- Keep tests deterministic and isolated (bucket names, cleanup).
- Prefer configuration via env vars to fit CI.
- Document any required Docker/MinIO setup in README when changing behavior.
