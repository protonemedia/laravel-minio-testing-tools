---
name: laravel-minio-testing-tools-development
description: Build and work with protonemedia/laravel-minio-testing-tools features including configuring a MinIO server for Laravel tests, using the UsesMinIOServer trait, and setting up GitHub Actions CI pipelines with S3-compatible storage.
license: MIT
metadata:
  author: ProtoneMedia
---

# Laravel Minio Testing Tools Development

## Overview
Use protonemedia/laravel-minio-testing-tools to run a MinIO S3-compatible server during Laravel tests. Supports automatic disk configuration, `.env` management, Dusk browser tests, and GitHub Actions CI.

## When to Activate
- Activate when working with S3-compatible storage in tests, or configuring MinIO for a Laravel test suite.
- Activate when code references `UsesMinIOServer`, `bootUsesMinIOServer()`, or MinIO disk configuration in tests.
- Activate when the user wants to set up CI pipelines that need an S3-compatible object store.

## Scope
- In scope: trait usage, test setup, disk configuration, Dusk integration, GitHub Actions recipes.
- Out of scope: modifying this package's internal source code unless the user explicitly says they are contributing to the package.

## Workflow
1. Identify the task (test setup, CI configuration, Dusk integration, debugging, etc.).
2. Read `references/laravel-minio-testing-tools-guide.md` and focus on the relevant section.
3. Apply the patterns from the reference, keeping code minimal and Laravel-native.

## Core Concepts

### Trait Setup
Every test that needs MinIO must use the `UsesMinIOServer` trait and boot it in `setUp()`:

```php
use ProtoneMedia\LaravelMinioTestingTools\UsesMinIOServer;

class UploadVideoTest extends DuskTestCase
{
    use UsesMinIOServer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootUsesMinIOServer();
    }
}
```

### GitHub Actions — Download Binaries
```yaml
- name: Download MinIO S3 server and client
  run: |
    wget https://dl.minio.io/server/minio/release/linux-amd64/minio -q -P /usr/local/bin/
    wget https://dl.minio.io/client/mc/release/linux-amd64/mc -q -P /usr/local/bin/
    chmod +x /usr/local/bin/minio
    chmod +x /usr/local/bin/mc
```

### Environment Variables for Dusk
```env
AWS_ACCESS_KEY_ID=user
AWS_SECRET_ACCESS_KEY=password
AWS_DEFAULT_REGION=eu-west-1
AWS_BUCKET=bucket-name
AWS_URL=http://127.0.0.1:9000
AWS_ENDPOINT=http://127.0.0.1:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

## Do and Don't

Do:
- Always call `$this->bootUsesMinIOServer()` inside `setUp()` after `parent::setUp()`.
- Ensure the `minio` and `mc` binaries are installed and executable before running tests.
- Use `AWS_USE_PATH_STYLE_ENDPOINT=true` when configuring S3 disks for MinIO.
- Avoid `php artisan serve --no-reload` because the package modifies `.env` on-the-fly.

Don't:
- Don't forget to install the package as a dev dependency (`--dev`).
- Don't run processes alongside tests that cannot handle `.env` file changes mid-run.
- Don't assume MinIO binaries are present in CI — always add a download step.
- Don't use `env()` outside of config files; rely on Laravel's config system.

## References
- `references/laravel-minio-testing-tools-guide.md`
