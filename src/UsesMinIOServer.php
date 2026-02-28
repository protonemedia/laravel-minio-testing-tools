<?php

namespace ProtoneMedia\LaravelMinioTestingTools;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Symfony\Component\Process\Process;

trait UsesMinIOServer
{
    public string $minIODisk = 's3';

    public ?int $minIOPort = null;

    public Collection $minIODiskConfig;

    public bool $minIODestroyed = false;

    public bool $minIOEnvironmentRestored = false;

    public function bootUsesMinIOServer()
    {
        $this->startMinIOServer();
        $this->configureMinIO();
    }

    public function initMinIOConfigCollection()
    {
        $this->minIODiskConfig = collect([
            new EnvironmentConfig('key', 'AWS_ACCESS_KEY_ID'),
            new EnvironmentConfig('secret', 'AWS_SECRET_ACCESS_KEY'),
            new EnvironmentConfig('region', 'AWS_DEFAULT_REGION'),
            new EnvironmentConfig('bucket', 'AWS_BUCKET'),
            new EnvironmentConfig('url', 'AWS_URL'),
            new EnvironmentConfig('endpoint', 'AWS_ENDPOINT'),
            new EnvironmentConfig('use_path_style_endpoint', 'AWS_USE_PATH_STYLE_ENDPOINT'),
        ])->keyBy->configKey;
    }

    /**
     * Extracts the port from the 'endpoint' configuration key.
     *
     * @return integer|null
     */
    public function getMinIOPortFromConfig(): ?int
    {
        $url = config("filesystems.disks.{$this->minIODisk}.endpoint");

        return parse_url($url, PHP_URL_PORT) ?: null;
    }

    /**
     * Finds a free port to run the MinIO server on.
     *
     * @return integer
     */
    public function findFreePort(): int
    {
        $sock = socket_create_listen(0);
        socket_getsockname($sock, $addr, $port);
        socket_close($sock);

        return $port;
    }

    /**
     * Returns a boolean whether the server has started.
     *
     * @return boolean
     */
    public function minIOServerHasStarted(): bool
    {
        if (!$this->minIOPort) {
            return false;
        }

        return rescue(
            fn () => Http::timeout(1)
                ->connectTimeout(1)
                ->get("http://127.0.0.1:{$this->minIOPort}/minio/health/live")
                ->ok(),
            false,
            false
        ) === true;
    }

    /**
     * Runs a command with a default timeout, and returns the output.
     *
     * @param string $cmd
     * @param integer $timeout
     * @return string
     */
    private function exec(string $cmd, int $timeout = 10): string
    {
        $process = Process::fromShellCommandline($cmd)->setTimeout($timeout);
        $process->run();

        return trim($process->getOutput());
    }

    /**
     * Verifies if a server is already running, for example in CI, or boots up
     * a new MinIO server and waits for it to be available.
     *
     * @return bool
     */
    public function startMinIOServer(): bool
    {
        $this->minIOPort = $this->getMinIOPortFromConfig();

        if ($this->minIOServerHasStarted()) {
            return true;
        }

        $this->minIOPort = $this->findFreePort();

        $temporaryDirectory = TemporaryDirectory::make(
            storage_path('framework/testing/minio')
        );

        $pid = $this->exec("minio server {$temporaryDirectory->path()} --address :{$this->minIOPort} > /dev/null 2>&1 & echo $!");

        $killMinIOAndDeleteStorage = function () use ($pid, $temporaryDirectory) {
            if ($this->minIODestroyed) {
                return;
            }

            if ($pid) {
                $this->exec("kill {$pid}");
            }

            // deleting the directory might fail when minio is not killed yet
            rescue(fn () => $temporaryDirectory->delete());

            $this->minIODestroyed = true;
        };

        $this->beforeApplicationDestroyed($killMinIOAndDeleteStorage);

        register_shutdown_function($killMinIOAndDeleteStorage);

        $tries = 0;

        while (!$this->minIOServerHasStarted()) {
            usleep(1000);
            $tries++;

            if ($tries === 10 * 1000) {
                $this->fail("Could not start MinIO server.");
            }
        }

        return true;
    }

    /**
     * Configures MinIO with the disk key, secret, region and bucket. Then it
     * updates the configuration to use the correct endpoint and URL.
     *
     * @return void
     */
    public function configureMinIO()
    {
        $url = "http://127.0.0.1:{$this->minIOPort}";

        $username = config("filesystems.disks.{$this->minIODisk}.key") ?: 'user';
        $password = config("filesystems.disks.{$this->minIODisk}.secret") ?: 'password';
        $region   = config("filesystems.disks.{$this->minIODisk}.region") ?: 'eu-west-1';
        $bucket   = config("filesystems.disks.{$this->minIODisk}.bucket") ?: 'bucket-name';

        $addLocalMinIO = $this->exec("until (mc alias set local {$url} minioadmin minioadmin) do echo '...waiting...' && sleep 1; done;");

        if (!Str::contains($addLocalMinIO, 'Added `local` successfully.')) {
            $this->fail('Could not configure MinIO server');
        }

        $this->exec("mc admin user add local {$username} {$password}");
        $this->exec("mc admin policy attach local readwrite --user={$username}");
        $this->exec("mc mb local/{$bucket} --region={$region}");

        $this->initMinIOConfigCollection();

        $this->minIODiskConfig->get('key')->minioValue                     = $username;
        $this->minIODiskConfig->get('secret')->minioValue                  = $password;
        $this->minIODiskConfig->get('region')->minioValue                  = $region;
        $this->minIODiskConfig->get('bucket')->minioValue                  = $bucket;
        $this->minIODiskConfig->get('endpoint')->minioValue                = $url;
        $this->minIODiskConfig->get('url')->minioValue                     = $url;
        $this->minIODiskConfig->get('use_path_style_endpoint')->minioValue = true;

        $this->minIODiskConfig->each(function (EnvironmentConfig $config) {
            config()->set("filesystems.disks.{$this->minIODisk}.{$config->configKey}", $config->minioValue);
        });

        $this->updateEnvirionmentFile();
    }

    /**
     * This updates the environment file so the settings also
     * apply to browser sessions with Laravel Dusk.
     *
     * @return void
     */
    public function updateEnvirionmentFile()
    {
        if (file_exists(base_path('.env.dusk')) && !file_exists(base_path('.env.backup'))) {
            throw new Exception("No environment backup file.");
        }

        $envFilename = base_path('.env');

        $env = file_get_contents($envFilename);

        $this->minIODiskConfig->each(function (EnvironmentConfig $config) use (&$env) {
            // backup current line
            preg_match("^({$config->environmentKey}=)(.)*^", $env, $matches);
            $config->environmentBackupLine = $matches[0] ?? '';

            // replace value
            $env = preg_replace(
                "^({$config->environmentKey}=)(.)*^",
                "{$config->environmentKey}={$config->castMinioValue()}",
                $env
            );
        });

        file_put_contents($envFilename, $env);

        $this->beforeApplicationDestroyed(fn () => $this->restoreEnvironmentFile());

        register_shutdown_function(fn () => $this->restoreEnvironmentFile());
    }

    /**
     * Restores the original values in the environment file
     * before 'updateEnvirionmentFile' ran.
     *
     * @return void
     */
    public function restoreEnvironmentFile()
    {
        if ($this->minIOEnvironmentRestored) {
            return;
        }

        $envFilename = base_path('.env');

        if (!file_exists($envFilename)) {
            return;
        }

        $env = file_get_contents($envFilename);

        $this->minIODiskConfig->each(function (EnvironmentConfig $config) use (&$env) {
            $env = preg_replace(
                "^({$config->environmentKey}=)(.)*^",
                $config->environmentBackupLine,
                $env
            );
        });

        file_put_contents($envFilename, $env);

        $this->minIOEnvironmentRestored = true;
    }
}
