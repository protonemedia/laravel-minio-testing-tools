<?php

namespace ProtoneMedia\LaravelMinioTestingTools;

class EnvironmentConfig
{
    public function __construct(
        public string $configKey,
        public string $environmentKey,
        public $minioValue = null,
        public $environmentBackupLine = null
    ) {
    }

    public function castMinioValue()
    {
        if (is_bool($this->minioValue)) {
            return $this->minioValue ? 'true' : 'false';
        }

        return $this->minioValue;
    }
}
