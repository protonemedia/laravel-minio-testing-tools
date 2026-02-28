---
name: laravel-minio-testing-tools-development
description: Development guidance for protonemedia/laravel-minio-testing-tools (boot MinIO S3 during tests).
license: MIT
metadata:
  author: ProtoneMedia
  source: https://github.com/protonemedia/laravel-minio-testing-tools
---

# Laravel MinIO Testing Tools Development

Use this skill when changing code/docs/tests in `protonemedia/laravel-minio-testing-tools`.

## Workflow
1. Treat the README as the public contract (trait usage, env mutation, GH Actions steps).
2. Consult `references/laravel-minio-testing-tools-guide.md` for configuration recipes and pitfalls.
3. Be cautious with any behavior that changes `.env` or filesystem config during tests.

## Reference
- references/laravel-minio-testing-tools-guide.md
