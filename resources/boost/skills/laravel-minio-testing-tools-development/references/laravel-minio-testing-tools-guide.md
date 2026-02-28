# Laravel MinIO Testing Tools Reference

Complete reference for `protonemedia/laravel-minio-testing-tools`.

Primary docs: https://github.com/protonemedia/laravel-minio-testing-tools#readme

## What this package does

Provides a test helper trait that:

- Starts and configures a MinIO server for your tests.
- Updates Laravel filesystem disk configuration.
- Updates and restores the `.env` file as needed.
- Works with Laravel Dusk.
- Includes guidance for GitHub Actions.

## Prerequisites

You must have the MinIO server and client binaries available.

README link: https://min.io/download#/linux

## Installation

Dev dependency:

```bash
composer require protonemedia/laravel-minio-testing-tools --dev
```

## Using the trait in a Dusk test

Add `UsesMinIOServer` and call `bootUsesMinIOServer()` in `setUp()`.

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

That’s the minimal integration described in the README.

## GitHub Actions recipes

### Download MinIO binaries during CI

```yaml
- name: Download MinIO S3 server and client
  run: |
    wget https://dl.minio.io/server/minio/release/linux-amd64/minio -q -P /usr/local/bin/
    wget https://dl.minio.io/client/mc/release/linux-amd64/mc -q -P /usr/local/bin/
    chmod +x /usr/local/bin/minio
    chmod +x /usr/local/bin/mc
    minio --version
    mc --version
```

### Persistent MinIO across the suite (optional)

Start server:

```yaml
- name: Run MinIO S3 server
  run: |
    mkdir ~/s3
    sudo minio server ~/s3 --json > minio-log.json &
```

Configure using `mc`:

```yaml
- name: Configure MinIO S3
  run: |
    mc config host add local http://127.0.0.1:9000 minioadmin minioadmin
    mc admin user add local user password
    mc admin policy set local readwrite user=user
    mc mb local/bucket-name --region=eu-west-1
```

Upload logs on failures:

```yaml
- name: Upload Minio Logs (optional)
  if: failure()
  uses: actions/upload-artifact@v2
  with:
    name: minio
    path: minio-log.json
```

### Environment file for Dusk

If you start MinIO manually, provide an env file (accessible to the browser session) containing:

```env
AWS_ACCESS_KEY_ID=user
AWS_SECRET_ACCESS_KEY=password
AWS_DEFAULT_REGION=eu-west-1
AWS_BUCKET=bucket-name
AWS_URL=http://127.0.0.1:9000
AWS_ENDPOINT=http://127.0.0.1:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

## Common pitfalls / gotchas

- **`.env` mutation:** the package updates/restores `.env` during tests. Avoid running processes that can’t handle env file changes.
- **`php artisan serve`:** README warning: don’t use `--no-reload` because `.env` changes on-the-fly.
- **Binary availability:** failures often come from `minio`/`mc` missing or lacking execute permission.
- **Networking in CI:** ensure `127.0.0.1:9000` is reachable from both PHP and the browser runner.
