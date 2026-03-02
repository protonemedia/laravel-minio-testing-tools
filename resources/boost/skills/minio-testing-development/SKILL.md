---
name: minio-testing-development
description: Build and work with protonemedia/laravel-minio-testing-tools features including configuring tests to run against a local MinIO S3 server, managing disk configuration, updating environment files, and integrating with Laravel Dusk.
license: MIT
metadata:
  author: Protone Media
---

# MinIO Testing Development

## Overview
Use protonemedia/laravel-minio-testing-tools to run your Laravel tests against a real MinIO S3 server. The package automatically starts a MinIO server, configures the S3 disk, updates the `.env` file for Dusk compatibility, and cleans up after tests complete.

## When to Activate
- Activate when working with S3 file upload tests that need a real object storage server.
- Activate when code references `UsesMinIOServer`, `bootUsesMinIOServer`, or MinIO-related test configuration.
- Activate when the user wants to set up integration tests for S3 storage using MinIO.

## Scope
- In scope: MinIO test server setup, S3 disk configuration for tests, environment file management, Laravel Dusk integration, GitHub Actions setup.
- Out of scope: production MinIO configuration, general S3 usage outside of testing, non-Laravel frameworks.

## Workflow
1. Identify the task (adding the trait, configuring disks, setting up CI, customizing MinIO options, etc.).
2. Read `references/minio-testing-guide.md` and focus on the relevant section.
3. Apply the patterns from the reference, keeping code minimal and Laravel-native.

## Core Concepts

### Basic Setup
Add the `UsesMinIOServer` trait to your test and call `bootUsesMinIOServer` in `setUp`:

```php
use ProtoneMedia\LaravelMinioTestingTools\UsesMinIOServer;

class UploadTest extends TestCase
{
    use UsesMinIOServer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootUsesMinIOServer();
    }
}
```

### How It Works
1. The trait checks if a MinIO server is already running (e.g., in CI).
2. If not, it starts a new MinIO server on a free port using a temporary directory.
3. It configures the MinIO server with a user, policy, and bucket using the `mc` CLI.
4. It updates the Laravel `filesystems` disk configuration at runtime.
5. It updates the `.env` file so Laravel Dusk browser sessions also use the correct config.
6. On teardown, it kills the MinIO process, deletes temporary storage, and restores the `.env` file.

### Customizing the Disk
The default disk is `s3`. Override the `$minIODisk` property to use a different disk:

```php
class UploadTest extends TestCase
{
    use UsesMinIOServer;

    public string $minIODisk = 'minio';

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootUsesMinIOServer();
    }
}
```

### Configuration Keys
The trait manages these environment/config keys automatically:

| Config Key                 | Environment Key                |
|----------------------------|--------------------------------|
| `key`                      | `AWS_ACCESS_KEY_ID`            |
| `secret`                   | `AWS_SECRET_ACCESS_KEY`        |
| `region`                   | `AWS_DEFAULT_REGION`           |
| `bucket`                   | `AWS_BUCKET`                   |
| `url`                      | `AWS_URL`                      |
| `endpoint`                 | `AWS_ENDPOINT`                 |
| `use_path_style_endpoint`  | `AWS_USE_PATH_STYLE_ENDPOINT`  |

## Do and Don't

Do:
- Always call `$this->bootUsesMinIOServer()` in `setUp()` after `parent::setUp()`.
- Ensure the `minio` and `mc` binaries are installed and available in your system PATH.
- Use the default S3 disk config values (`user`, `password`, `eu-west-1`, `bucket-name`) or set your own in `config/filesystems.php`.
- When using Laravel Dusk, let the trait manage the `.env` file — do not use `--no-reload` with `php artisan serve`.
- In CI with a pre-started MinIO server, provide the correct `AWS_ENDPOINT` in the environment so the trait detects the running server.

Don't:
- Don't forget to install both the MinIO Server and MinIO Client (`mc`) before running tests.
- Don't use the `--no-reload` flag with `php artisan serve` when using Dusk, as the `.env` file is updated at runtime.
- Don't manually configure the S3 disk in your test — the trait handles all configuration automatically.
- Don't skip `parent::setUp()` before calling `bootUsesMinIOServer()`.

## References
- `references/minio-testing-guide.md`
