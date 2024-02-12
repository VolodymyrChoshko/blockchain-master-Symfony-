<?php
namespace BlocksEdit\Media;

/**
 * Class Images
 */
class Images
{
    const ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'];
    const MAX_IMAGE = 5194304;

    /**
     * @param string $base64img
     * @param string $extension
     *
     * @return string
     */
    public static function decodeBase64(string  $base64img, string $extension = 'jpeg'): string
    {
        $base64img = str_replace("data:image/${extension};base64,", '', $base64img);

        return base64_decode($base64img);
    }

    /**
     * @param string $mimetype
     *
     * @return bool
     */
    public static function isMimeTypeAllowed(string $mimetype): bool
    {
        return in_array($mimetype, self::ALLOWED_TYPES);
    }

    /**
     * @param string $file
     *
     * @return bool
     */
    public static function isFileAllowed(string $file): bool
    {
        return file_exists($file) && in_array(mime_content_type($file), self::ALLOWED_TYPES);
    }
}
