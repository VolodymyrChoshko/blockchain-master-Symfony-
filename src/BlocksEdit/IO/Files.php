<?php
namespace BlocksEdit\IO;

use BlocksEdit\IO\Exception\IOException;
use BlocksEdit\IO\Exception\NotFoundException;

/**
 * Class Files
 */
class Files extends IOBase
{
    /**
     * @param string $filename
     *
     * @return string
     * @throws IOException
     */
    public function read(string $filename): string
    {
        if (!file_exists($filename)) {
            throw new NotFoundException("File does not exist $filename.");
        }
        $this->verifyModifiable($filename);
        $result = file_get_contents($filename);
        if ($result === false) {
            throw new IOException("Failed to read file $filename.");
        }

        return $result;
    }

    /**
     * @param string   $filename
     * @param string   $data
     * @param int      $flags
     * @param int|null $permissions
     *
     * @return int
     * @throws IOException
     */
    public function write(string $filename, string $data, int $flags = 0, ?int $permissions = self::PERMISSIONS): int
    {
        $dir = pathinfo($filename, PATHINFO_DIRNAME);
        $this->verifyModifiable($dir);
        if (!file_exists($dir) && !@mkdir($dir, $permissions, true)) {
            throw new IOException("Could not create directory $dir.");
        }
        if (file_exists($filename) && is_dir($filename)) {
            throw new IOException("File name is an existing directory $filename.");
        }
        if (!is_writable($dir)) {
            throw new IOException("Directory $dir is not writable.");
        }

        $result = file_put_contents($filename, $data, $flags);
        if ($result === false) {
            throw new IOException(
                "Failed to write file '$filename'."
            );
        }
        if (!@chmod($filename, $permissions)) {
            throw new IOException(
                "Failed to change permissions on file '$filename' to $permissions."
            );
        }

        return $result;
    }

    /**
     * @param string $source
     * @param string $dest
     * @param int    $permissions
     *
     * @return bool
     * @throws IOException
     */
    public function copy(string $source, string $dest, int $permissions = self::PERMISSIONS): bool
    {
        $dir = pathinfo($dest, PATHINFO_DIRNAME);
        $this->verifyModifiable($dir);
        if (!file_exists($dir) && !@mkdir($dir, $permissions, true)) {
            throw new IOException("Could not create directory $dir.");
        }
        if (!is_writable($dir)) {
            throw new IOException("Directory $dir is not writable.");
        }
        if (!@copy($source, $dest)) {
            throw new IOException("Could not copy file '$source' to '$dest'.");
        }
        if (!@chmod($dest, $permissions)) {
            throw new IOException(
                "Failed to change permissions on file '$dest' to $permissions."
            );
        }

        return true;
    }

    /**
     * @param string|string[]|FilePathInterface|FilePathInterface[] $filename
     *
     * @return bool
     * @throws IOException
     */
    public function remove($filename): bool
    {
        foreach($this->getPathsFromArgs($filename) as $value) {
            if (file_exists($value)) {
                $this->verifyModifiable($value);
                if (!@unlink($value)) {
                    throw new IOException("Could not unlink file '$value'.");
                }
            }
        }

        return true;
    }

    /**
     * @param string $source
     * @param string $target
     *
     * @return bool
     * @throws Exception\SecurityException
     * @throws IOException
     */
    public function rename(string $source, string $target): bool
    {
        if (!file_exists($source)) {
            throw new IOException("Source file '$source' does not exist.");
        }
        $this->verifyModifiable($target);

        return rename($source, $target);
    }

    /**
     * @param string $source
     * @param string $target
     *
     * @return bool
     * @throws IOException
     */
    public function moveUploaded(string $source, string $target): bool
    {
        $this->verifyModifiable($target);
        if (!@move_uploaded_file($source, $target)) {
            throw new IOException("Failed to move upload $source to $target.");
        }

        return true;
    }

    /**
     * @param string $file
     * @param int $decimals
     *
     * @return string
     */
    public function humanReadableFileSize(string $file, int $decimals = 2): string
    {
        return $this->humanReadableFromBytes(filesize($file), $decimals);
    }

    /**
     * @param int|string $bytes
     * @param int        $decimals
     *
     * @return string
     */
    public function humanReadableFromBytes($bytes, int $decimals = 2): string
    {
        $size   = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getUniqueName(string $name): string
    {
        $increment = 0;
        $dirname   = pathinfo($name, PATHINFO_DIRNAME);
        $filename  = pathinfo($name, PATHINFO_FILENAME);
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        while (file_exists($name)) {
            $increment++;
            $name = Paths::combine($dirname, $filename . '-' . $increment . '.' . $extension);
        }

        return $name;
    }
}
