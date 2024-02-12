<?php
namespace BlocksEdit\IO;

/**
 * An object which represents a file path.
 */
interface FilePathInterface
{
    /**
     * @return string
     */
    public function getFilePath(): string;
}
