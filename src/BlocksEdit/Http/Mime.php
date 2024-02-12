<?php
namespace BlocksEdit\Http;

/**
 * Class Mime
 */
class Mime
{
    /**
     * @var array
     */
    static $mimeTypes = [
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'docx' => 'application/msword',
        'xlsx' => 'application/vnd.ms-excel',
        'pptx' => 'application/vnd.ms-powerpoint',


        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    ];

    /**
     * @param string $localFilename
     *
     * @return string
     */
    public static function getMimeType(string $localFilename)
    {
        $mime = mime_content_type($localFilename);
        if ($mime === 'text/plain') {
            $mime = 'text/html';
        } else if (!$mime) {
            $ext = strtolower(pathinfo($localFilename, PATHINFO_EXTENSION));
            switch($ext) {
                case 'html':
                    $mime = 'text/html';
                    break;
                case 'jpg':
                    $mime = 'image/jpg';
                    break;
                case 'jpeg':
                    $mime = 'image/jpeg';
                    break;
                case 'gif':
                    $mime = 'image/gif';
                    break;
                case 'png':
                    $mime = 'image/png';
                    break;
            }
        }

        return $mime;
    }

    /**
     * @param string $filename
     * @param string $default
     *
     * @return string
     */
    public function getFromFilename(string $filename, string $default = 'application/octet-stream'): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!isset(self::$mimeTypes[$ext])) {
            return $default;
        }

        return self::$mimeTypes[$ext];
    }
}
