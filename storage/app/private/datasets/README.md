# Private datasets

This directory stores versioned source datasets and evaluation data used by
SpeakReady AI. Dataset files are ignored by Git by default because they may be
large, licensed, or contain private information.

## Directories

- `manifests/` — source, version, license, checksum, and retrieval metadata.
- `raw/` — immutable files exactly as downloaded from their publisher.
- `normalized/` — cleaned and transformed files ready for application import.
- `quarantine/` — files awaiting license, schema, security, or quality review.
- `evals/` — private, versioned benchmark and human-rated evaluation cases.

Recommended raw-data layout:

```text
raw/{source}/{version}/
```

For example, O*NET 30.3 belongs in `raw/onet/30.3/`.

Laravel can access this location through its private `datasets` filesystem disk:

```php
use Illuminate\Support\Facades\Storage;

Storage::disk('datasets')->put(
    'raw/onet/30.3/db_30_3_mysql.zip',
    $downloadedContents
);
```

Run the storage health check after a new deployment or permissions change:

```shell
php artisan datasets:check
```

The command creates missing directories, then verifies write, read, and delete
access using a temporary file that is removed automatically.

Do not store API tokens, passwords, unredacted resumes, or unnecessary personal
information here. Production deployments should use encrypted private object
storage rather than an application server's temporary disk.
