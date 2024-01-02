<?php

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Orchestra\Testbench\Concerns\CreatesApplication;
use ProtoneMedia\LaravelMinioTestingTools\UsesMinIOServer;

class DummyTestCase extends TestCase
{
    use CreatesApplication;
    use UsesMinIOServer;
}

beforeEach(function () {
    @unlink(base_path('.env'));
    @unlink(base_path('.env.backup'));
    @unlink(base_path('.env.dusk'));

    Artisan::call('config:clear');

    $this->testCase = new DummyTestCase(Str::random());
});

it('returns the configured port', function () {
    expect($this->testCase->getMinIOPortFromConfig())->toBeNull();

    config(['filesystems.disks.s3.endpoint' => 'http://127.0.0.1:9000']);

    expect($this->testCase->getMinIOPortFromConfig())->toBe(9000);
});

it('finds a free port', function () {
    expect($this->testCase->findFreePort())->toBeNumeric();
});

it('knows whether minio has started', function () {
    config(['filesystems.disks.s3.endpoint' => 'http://127.0.0.1:9000']);

    Http::fake([
        'http://127.0.0.1:9000' => Http::response(),
    ]);

    expect($this->testCase->startMinIOServer())->toBeTrue();
});

it('can start and configure a minio server', function () {
    config([
        'filesystems.disks.s3' => [
            'driver'                  => 's3',
            'key'                     => 'user',
            'secret'                  => 'password',
            'region'                  => 'eu-west-1',
            'bucket'                  => 'bucket-name',
            'use_path_style_endpoint' => false,
            'minio_server_as_root'    => true,
        ],
    ]);

    expect($this->testCase->startMinIOServer())->toBeTrue();

    expect(config('filesystems.disks.s3.endpoint'))->toBeNull();
    expect(config('filesystems.disks.s3.url'))->toBeNull();
    expect(config('filesystems.disks.s3.use_path_style_endpoint'))->toBeFalsy();

    //

    file_put_contents(base_path('.env'), $oldConfig = implode(PHP_EOL, [
        'AWS_ACCESS_KEY_ID=user',
        'AWS_SECRET_ACCESS_KEY=password',
        'AWS_DEFAULT_REGION=eu-west-1',
        'AWS_BUCKET=bucket-name',
        'AWS_URL=http://',
        'AWS_ENDPOINT=http://',
        'AWS_USE_PATH_STYLE_ENDPOINT=false',
    ]));

    $this->testCase->configureMinIO();

    expect(config('filesystems.disks.s3.endpoint'))->toBe('http://127.0.0.1:' . $this->testCase->minIOPort);
    expect(config('filesystems.disks.s3.url'))->toBe('http://127.0.0.1:' . $this->testCase->minIOPort);
    expect(config('filesystems.disks.s3.use_path_style_endpoint'))->toBeTrue();

    expect(file_get_contents(base_path('.env')))->toBe(implode(PHP_EOL, [
        'AWS_ACCESS_KEY_ID=user',
        'AWS_SECRET_ACCESS_KEY=password',
        'AWS_DEFAULT_REGION=eu-west-1',
        'AWS_BUCKET=bucket-name',
        'AWS_URL=http://127.0.0.1:' . $this->testCase->minIOPort,
        'AWS_ENDPOINT=http://127.0.0.1:' . $this->testCase->minIOPort,
        'AWS_USE_PATH_STYLE_ENDPOINT=true',
    ]));

    $this->testCase->restoreEnvironmentFile();

    expect(file_get_contents(base_path('.env')))->toBe($oldConfig);
});
