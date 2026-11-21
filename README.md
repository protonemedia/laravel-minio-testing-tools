# Laravel MinIO Testing Tools

[![Latest Version on Packagist](https://img.shields.io/packagist/v/protonemedia/laravel-minio-testing-tools.svg?style=flat-square)](https://packagist.org/packages/protonemedia/laravel-minio-testing-tools)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/protonemedia/laravel-minio-testing-tools/run-tests?label=tests)](https://github.com/protonemedia/laravel-minio-testing-tools/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/protonemedia/laravel-minio-testing-tools.svg?style=flat-square)](https://packagist.org/packages/protonemedia/laravel-minio-testing-tools)
[![Buy us a tree](https://img.shields.io/badge/Treeware-%F0%9F%8C%B3-lightgreen)](https://plant.treeware.earth/protonemedia/laravel-minio-testing-tools)

This package provides a trait to run your tests against a MinIO S3 server.

📝 Blog post: https://protone.media/en/blog/how-to-use-a-local-minio-s3-server-with-laravel-and-automatically-configure-it-for-your-laravel-dusk-test-suite

> [!WARNING]
> MinIO itself is no longer maintained. The open-source [MinIO Server](https://github.com/minio/minio) and [MinIO Client](https://github.com/minio/mc) repositories are archived, and MinIO no longer publishes binaries or security updates for them. MinIO offers [AIStor](https://min.io/download) as the alternative. You may still build the last community releases from source, as shown in the [Installation](#installation) section.

## Sponsor Us

[<img src="https://inertiaui.com/visit-card.jpg" />](https://inertiaui.com/inertia-table?utm_source=github&utm_campaign=laravel-minio-testing-tools)

❤️ We proudly support the community by developing Laravel packages and giving them away for free. If this package saves you time or if you're relying on it professionally, please consider [sponsoring the maintenance and development](https://github.com/sponsors/pascalbaljet) and check out our latest premium package: [Inertia Table](https://inertiaui.com/inertia-table?utm_source=github&utm_campaign=laravel-minio-testing-tools). Keeping track of issues and pull requests takes time, but we're happy to help!

## Features
* Starts and configures a MinIO server for your tests.
* Updates the `filesystems` disk configuration.
* Updates and restores the `.env` file.
* Works with [Laravel Dusk](https://laravel.com/docs/9.x/dusk).
* Works on [GitHub Actions](#github-actions)
* Compatible with Laravel 12 and 13.
* PHP 8.3 or higher is required.

## Installation

Make sure the MinIO Server (`minio`) and Client (`mc`) are on your `PATH`. MinIO distributes them as source only, so you may build them with Go:

```bash
go install github.com/minio/minio@RELEASE.2025-10-15T17-29-55Z
go install github.com/minio/mc@RELEASE.2025-08-13T08-35-41Z
```

You can install the package via composer:

```bash
composer require protonemedia/laravel-minio-testing-tools --dev
```

Add the trait to your test, and add the `bootUsesMinIOServer` method:

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

    /** @test */
    public function it_can_upload_a_video_using_multipart_upload()
    {
    }
}
```

That's it!

## GitHub Actions

You may build the MinIO Server and Client from source before the tests run, and cache them for later runs:

```yaml
jobs:
  test:
    steps:
      - uses: actions/checkout@v6

      - name: Cache MinIO S3 server and client
        id: minio-cache
        uses: actions/cache@v4
        with:
          path: ~/minio/bin
          key: minio-RELEASE.2025-10-15T17-29-55Z-mc-RELEASE.2025-08-13T08-35-41Z-${{ runner.os }}-${{ runner.arch }}

      - name: Setup Go
        if: steps.minio-cache.outputs.cache-hit != 'true'
        uses: actions/setup-go@v5
        with:
          go-version: stable
          cache: false

      - name: Install MinIO S3 server and client
        run: |
          if [ "${{ steps.minio-cache.outputs.cache-hit }}" != 'true' ]; then
            GOBIN=~/minio/bin CGO_ENABLED=0 go install github.com/minio/minio@RELEASE.2025-10-15T17-29-55Z
            GOBIN=~/minio/bin CGO_ENABLED=0 go install github.com/minio/mc@RELEASE.2025-08-13T08-35-41Z
          fi
          echo ~/minio/bin >> "$GITHUB_PATH"
```

If you're using `php artisan serve`, make sure you don't use the `--no-reload` flag, as the `.env` file will be changed on-the-fly.

Optionally, if you want persistent storage across the test suite, you may start the server manually before the tests are run.

```yaml
jobs:
  test:
    steps:
      # The checkout, cache, Setup Go, and install steps from the example above...

      - name: Run MinIO S3 server
        run: |
          mkdir ~/s3
          minio server ~/s3 --json > minio-log.json &

      - name: Configure MinIO S3
        run: |
          until mc alias set local http://127.0.0.1:9000 minioadmin minioadmin; do sleep 1; done
          mc admin user add local user password
          mc admin policy attach local readwrite --user=user
          mc mb local/bucket-name --region=eu-west-1

      - name: Upload Minio Logs (optional)
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: minio
          path: minio-log.json
```

In this case, you also need to supply an environment file with the MinIO configuration. This makes the configuration also accessible by the browser session when you're running Laravel Dusk.

```env
AWS_ACCESS_KEY_ID=user
AWS_SECRET_ACCESS_KEY=password
AWS_DEFAULT_REGION=eu-west-1
AWS_BUCKET=bucket-name
AWS_URL=http://127.0.0.1:9000
AWS_ENDPOINT=http://127.0.0.1:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Other Laravel packages

- [Inertia Modal](https://inertiaui.com/inertia-modal/docs/introduction): With Inertia Modal, you can easily open any route in a Modal or Slideover without having to change anything about your existing routes or controllers.
* [`Inertia Table`](https://inertiaui.com/inertia-table?utm_source=github&utm_campaign=laravel-minio-testing-tools): The Ultimate Table for Inertia.js with built-in Query Builder.
* [`Laravel Blade On Demand`](https://github.com/protonemedia/laravel-blade-on-demand): Laravel package to compile Blade templates in memory.
* [`Laravel Cross Eloquent Search`](https://github.com/protonemedia/laravel-cross-eloquent-search): Laravel package to search through multiple Eloquent models.
* [`Laravel Eloquent Scope as Select`](https://github.com/protonemedia/laravel-eloquent-scope-as-select): Stop duplicating your Eloquent query scopes and constraints in PHP. This package lets you re-use your query scopes and constraints by adding them as a subquery.
* [`Laravel FFMpeg`](https://github.com/protonemedia/laravel-ffmpeg): This package provides an integration with FFmpeg for Laravel. The storage of the files is handled by Laravel's Filesystem.
* [`Laravel MinIO Testing Tools`](https://github.com/protonemedia/laravel-minio-testing-tools): Run your tests against a MinIO S3 server.
* [`Laravel Mixins`](https://github.com/protonemedia/laravel-mixins): A collection of Laravel goodies.
* [`Laravel Paddle`](https://github.com/protonemedia/laravel-paddle): Paddle.com API integration for Laravel with support for webhooks/events.
* [`Laravel Task Runner`](https://github.com/protonemedia/laravel-task-runner): Write Shell scripts like Blade Components and run them locally or on a remote server.
* [`Laravel Verify New Email`](https://github.com/protonemedia/laravel-verify-new-email): This package adds support for verifying new email addresses: when a user updates its email address, it won't replace the old one until the new one is verified.
* [`Laravel XSS Protection`](https://github.com/protonemedia/laravel-xss-protection): Laravel Middleware to protect your app against Cross-site scripting (XSS). It sanitizes request input, and it can sanatize Blade echo statements.

## Security

If you discover any security-related issues, please email code@protone.media instead of using the issue tracker. Please do not email any questions, open an issue if you have a question.

## Credits

- [Pascal Baljet](https://github.com/pascalbaljet)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

