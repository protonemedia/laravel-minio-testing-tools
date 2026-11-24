{{-- Laravel MinIO Testing Tools Guidelines for AI Code Assistants --}}
{{-- Source: https://github.com/protonemedia/laravel-minio-testing-tools --}}
{{-- License: MIT | (c) Protone Media --}}

## MinIO Testing Tools

- `protonemedia/laravel-minio-testing-tools` provides a trait to run your Laravel tests against a MinIO S3 server, with automatic server startup, configuration, and environment file management.
- Always activate the `minio-testing-development` skill when working with MinIO-based test setups, S3 testing configuration, or any code that uses the `UsesMinIOServer` trait.
