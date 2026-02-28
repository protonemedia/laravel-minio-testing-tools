{{-- Laravel Minio Testing Tools Guidelines for AI Code Assistants --}}
{{-- Source: https://github.com/protonemedia/laravel-minio-testing-tools --}}
{{-- License: MIT | (c) ProtoneMedia --}}

## Minio Testing Tools

- `protonemedia/laravel-minio-testing-tools` provides a test helper trait that starts and configures a MinIO server for Laravel tests, with support for Dusk and GitHub Actions.
- Always activate the `laravel-minio-testing-tools-development` skill when working with MinIO-backed test storage, the `UsesMinIOServer` trait, or any code that configures S3-compatible disks for testing.
