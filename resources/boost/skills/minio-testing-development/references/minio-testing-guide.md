# Laravel MinIO Testing Tools Reference

Complete reference for `protonemedia/laravel-minio-testing-tools`. Source: https://github.com/protonemedia/laravel-minio-testing-tools

## Installation

```bash
composer require protonemedia/laravel-minio-testing-tools --dev
```

Requires the MinIO Server and MinIO Client (`mc`) binaries installed on your system. Download from: https://min.io/download

## Basic Test Setup

Add the `UsesMinIOServer` trait and call `bootUsesMinIOServer` in `setUp`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ProtoneMedia\LaravelMinioTestingTools\UsesMinIOServer;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;
    use UsesMinIOServer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootUsesMinIOServer();
    }

    public function test_it_can_upload_a_file()
    {
        // Your S3 upload test logic here.
        // The S3 disk is now configured to use the local MinIO server.
    }
}
```

## Laravel Dusk Setup

```php
<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use ProtoneMedia\LaravelMinioTestingTools\UsesMinIOServer;
use Tests\DuskTestCase;

class UploadVideoTest extends DuskTestCase
{
    use DatabaseMigrations;
    use UsesMinIOServer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootUsesMinIOServer();
    }

    public function test_it_can_upload_a_video()
    {
        // The .env file is updated so the browser session
        // also uses the MinIO server configuration.
    }
}
```

## Customizing the Disk Name

Override the `$minIODisk` property to target a different filesystem disk:

```php
class FileUploadTest extends TestCase
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

The trait reads defaults from `config("filesystems.disks.{$minIODisk}")` and falls back to sensible defaults:

| Key      | Fallback       |
|----------|----------------|
| `key`    | `user`         |
| `secret` | `password`     |
| `region` | `eu-west-1`    |
| `bucket` | `bucket-name`  |

## GitHub Actions Setup

### Automatic server (started by the trait)

Download the MinIO binaries before running tests:

```yaml
jobs:
  test:
    steps:
      - uses: actions/checkout@v2

      - name: Download MinIO S3 server and client
        run: |
          wget https://dl.minio.io/server/minio/release/linux-amd64/minio -q -P /usr/local/bin/
          wget https://dl.minio.io/client/mc/release/linux-amd64/mc -q -P /usr/local/bin/
          chmod +x /usr/local/bin/minio
          chmod +x /usr/local/bin/mc
          minio --version
          mc --version
```

### Pre-started server (persistent storage across tests)

Start MinIO manually before the test suite runs:

```yaml
jobs:
  test:
    steps:
      - uses: actions/checkout@v2

      - name: Download MinIO S3 server and client
        run: |
          wget https://dl.minio.io/server/minio/release/linux-amd64/minio -q -P /usr/local/bin/
          wget https://dl.minio.io/client/mc/release/linux-amd64/mc -q -P /usr/local/bin/
          chmod +x /usr/local/bin/minio
          chmod +x /usr/local/bin/mc
          minio --version
          mc --version

      - name: Run MinIO S3 server
        run: |
          mkdir ~/s3
          sudo minio server ~/s3 --json > minio-log.json &

      - name: Configure MinIO S3
        run: |
          mc config host add local http://127.0.0.1:9000 minioadmin minioadmin
          mc admin user add local user password
          mc admin policy set local readwrite user=user
          mc mb local/bucket-name --region=eu-west-1

      - name: Upload Minio Logs (optional)
        if: failure()
        uses: actions/upload-artifact@v2
        with:
          name: minio
          path: minio-log.json
```

With a pre-started server, supply an `.env` file with the MinIO configuration:

```env
AWS_ACCESS_KEY_ID=user
AWS_SECRET_ACCESS_KEY=password
AWS_DEFAULT_REGION=eu-west-1
AWS_BUCKET=bucket-name
AWS_URL=http://127.0.0.1:9000
AWS_ENDPOINT=http://127.0.0.1:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

The trait detects the running server via the `endpoint` config and skips starting a new one.

## Trait API

### Properties

| Property                     | Type         | Default | Description                                      |
|------------------------------|--------------|---------|--------------------------------------------------|
| `$minIODisk`                 | `string`     | `'s3'`  | The filesystem disk name to configure.           |
| `$minIOPort`                 | `?int`       | `null`  | The port the MinIO server runs on.               |
| `$minIODiskConfig`           | `Collection` | —       | Collection of `EnvironmentConfig` objects.        |
| `$minIODestroyed`            | `bool`       | `false` | Whether the MinIO server has been shut down.     |
| `$minIOEnvironmentRestored`  | `bool`       | `false` | Whether the `.env` file has been restored.       |

### Methods

| Method                      | Returns  | Description                                                      |
|-----------------------------|----------|------------------------------------------------------------------|
| `bootUsesMinIOServer()`     | `void`   | Starts the server and configures the disk. Call in `setUp()`.    |
| `startMinIOServer()`        | `bool`   | Starts a MinIO server or detects an existing one.                |
| `configureMinIO()`          | `void`   | Creates user, policy, bucket, and updates config and `.env`.     |
| `minIOServerHasStarted()`   | `bool`   | Checks if the MinIO server is responding on the configured port. |
| `findFreePort()`            | `int`    | Finds an available port for the MinIO server.                    |
| `getMinIOPortFromConfig()`  | `?int`   | Extracts the port from the disk endpoint configuration.          |
| `updateEnvirionmentFile()`  | `void`   | Writes MinIO config values to the `.env` file.                   |
| `restoreEnvironmentFile()`  | `void`   | Restores original `.env` values after tests complete.            |
