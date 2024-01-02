# Laravel MinIO Testing Tools

[![Latest Version on Packagist](https://img.shields.io/packagist/v/protonemedia/laravel-minio-testing-tools.svg?style=flat-square)](https://packagist.org/packages/protonemedia/laravel-minio-testing-tools)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/protonemedia/laravel-minio-testing-tools/run-tests?label=tests)](https://github.com/protonemedia/laravel-minio-testing-tools/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/protonemedia/laravel-minio-testing-tools.svg?style=flat-square)](https://packagist.org/packages/protonemedia/laravel-minio-testing-tools)
[![Buy us a tree](https://img.shields.io/badge/Treeware-%F0%9F%8C%B3-lightgreen)](https://plant.treeware.earth/protonemedia/laravel-minio-testing-tools)

This package provides a trait to run your tests against a MinIO S3 server.

📝 Blog post: https://protone.media/en/blog/how-to-use-a-local-minio-s3-server-with-laravel-and-automatically-configure-it-for-your-laravel-dusk-test-suite

## Sponsor this package!

❤️ We proudly support the community by developing Laravel packages and giving them away for free. If this package saves you time or if you're relying on it professionally, please consider [sponsoring the maintenance and development](https://github.com/sponsors/pascalbaljet). Keeping track of issues and pull requests takes time, but we're happy to help!

## Laravel Splade

**Did you hear about Laravel Splade? 🤩**

It's the *magic* of Inertia.js with the *simplicity* of Blade. [Splade](https://github.com/protonemedia/laravel-splade) provides a super easy way to build Single Page Applications using Blade templates. Besides that magic SPA-feeling, it comes with more than ten components to sparkle your app and make it interactive, all without ever leaving Blade.

## Features
* Starts and configures a MinIO server for your tests.
* Updates the `filesystems` disk configuration.
* Updates and restores the `.env` file.
* Works with [Laravel Dusk](https://laravel.com/docs/9.x/dusk).
* Works on [GitHub Actions](#github-actions)
* Compatible with Laravel 10.
* PHP 8.1 or higher is required.

## Installation

Make sure you've downloaded the [MinIO Server and Client](https://min.io/download#/linux) for your OS.

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

The easiest way is to download the MinIO Server and Client before the tests are run:

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

If you're using `php artisan serve`, make sure you don't use the `--no-reload` flag, as the `.env` file will be changed on-the-fly.

Optionally, if you want persistent storage across the test suite, you may start the server manually before the tests are run.

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

* [`Laravel Analytics Event Tracking`](https://github.com/protonemedia/laravel-analytics-event-tracking): Laravel package to easily send events to Google Analytics.
* [`Laravel Blade On Demand`](https://github.com/protonemedia/laravel-blade-on-demand): Laravel package to compile Blade templates in memory.
* [`Laravel Cross Eloquent Search`](https://github.com/protonemedia/laravel-cross-eloquent-search): Laravel package to search through multiple Eloquent models.
* [`Laravel Eloquent Scope as Select`](https://github.com/protonemedia/laravel-eloquent-scope-as-select): Stop duplicating your Eloquent query scopes and constraints in PHP. This package lets you re-use your query scopes and constraints by adding them as a subquery.
* [`Laravel Eloquent Where Not`](https://github.com/protonemedia/laravel-eloquent-where-not): This Laravel package allows you to flip/invert an Eloquent scope, or really any query constraint.
* [`Laravel FFMpeg`](https://github.com/protonemedia/laravel-ffmpeg): This package provides an integration with FFmpeg for Laravel. The storage of the files is handled by Laravel's Filesystem.
* [`Laravel Form Components`](https://github.com/protonemedia/laravel-form-components): Blade components to rapidly build forms with Tailwind CSS Custom Forms and Bootstrap 4. Supports validation, model binding, default values, translations, includes default vendor styling and fully customizable!
* [`Laravel Mixins`](https://github.com/protonemedia/laravel-mixins): A collection of Laravel goodies.
* [`Laravel Paddle`](https://github.com/protonemedia/laravel-paddle): Paddle.com API integration for Laravel with support for webhooks/events.
* [`Laravel Verify New Email`](https://github.com/protonemedia/laravel-verify-new-email): This package adds support for verifying new email addresses: when a user updates its email address, it won't replace the old one until the new one is verified.
* [`Laravel WebDAV`](https://github.com/protonemedia/laravel-webdav): WebDAV driver for Laravel's Filesystem.

## Security

If you discover any security-related issues, please email code@protone.media instead of using the issue tracker. Please do not email any questions, open an issue if you have a question.

## Credits

- [Pascal Baljet](https://github.com/pascalbaljet)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
