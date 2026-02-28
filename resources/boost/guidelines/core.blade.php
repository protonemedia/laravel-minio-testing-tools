{{-- Laravel MinIO Testing Tools Guidelines for AI Code Assistants --}}
{{-- Source: https://github.com/protonemedia/laravel-minio-testing-tools --}}
{{-- License: MIT | (c) ProtoneMedia --}}

## Laravel MinIO Testing Tools

- Provides a trait to boot/configure a MinIO S3 server for tests (including Laravel Dusk) and update filesystem config/env on the fly.
- Always activate the `laravel-minio-testing-tools-development` skill when making package-specific changes.
- For setup, GitHub Actions recipes, and caveats, consult:
  - `resources/boost/skills/laravel-minio-testing-tools-development/references/laravel-minio-testing-tools-guide.md`
