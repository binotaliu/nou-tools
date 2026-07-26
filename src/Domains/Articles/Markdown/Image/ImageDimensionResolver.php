<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Image;

final class ImageDimensionResolver
{
    /**
     * Resolves the intrinsic pixel dimensions of a local article image so the
     * rendered `<img>` can carry `width`/`height` and avoid layout shift.
     *
     * @return array{width: int, height: int}|null
     */
    public static function resolve(string $url): ?array
    {
        if (\preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $url)) {
            return null;
        }

        $path = \public_path(\ltrim($url, '/'));

        if (! \is_file($path)) {
            return null;
        }

        $size = @\getimagesize($path);

        if ($size === false) {
            return null;
        }

        return ['width' => $size[0], 'height' => $size[1]];
    }
}
