<?php

declare(strict_types=1);

namespace App\Csp;

/**
 * The remote origins a scoped filesystem disk (see config/filesystems.php)
 * serves from once its parent disk is S3, so CSP policies can allow them.
 * Both are null while the disk is local and served by this app.
 */
final class ScopedDiskOrigins
{
    /**
     * Origin (scheme://host) files are served from, e.g. a CloudFront domain.
     */
    public static function serving(string $scopedDisk): ?string
    {
        $disk = self::parentDisk($scopedDisk);

        if (($disk['driver'] ?? null) !== 's3' || blank($disk['url'] ?? null)) {
            return null;
        }

        $parts = parse_url((string) $disk['url']);

        return isset($parts['scheme'], $parts['host']) ? "{$parts['scheme']}://{$parts['host']}" : null;
    }

    /**
     * Origin of the S3 bucket itself.
     *
     * Filament's FileUpload previews private files through a presigned
     * `temporaryUrl()`, which always targets the bucket (never AWS_URL) and is
     * fetched by FilePond, so the admin panel needs it in `connect-src`.
     */
    public static function bucket(string $scopedDisk): ?string
    {
        $disk = self::parentDisk($scopedDisk);

        if (($disk['driver'] ?? null) !== 's3' || blank($disk['bucket'] ?? null)) {
            return null;
        }

        if (filled($disk['endpoint'] ?? null)) {
            $parts = parse_url((string) $disk['endpoint']);

            return isset($parts['scheme'], $parts['host']) ? "{$parts['scheme']}://{$parts['host']}".(isset($parts['port']) ? ":{$parts['port']}" : '') : null;
        }

        return "https://{$disk['bucket']}.s3.{$disk['region']}.amazonaws.com";
    }

    /**
     * @return array<string, mixed>
     */
    private static function parentDisk(string $scopedDisk): array
    {
        $parent = config("filesystems.disks.{$scopedDisk}.disk");

        return (array) config("filesystems.disks.{$parent}");
    }
}
