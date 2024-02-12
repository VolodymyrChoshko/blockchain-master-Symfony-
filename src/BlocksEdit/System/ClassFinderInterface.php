<?php
namespace BlocksEdit\System;

use ReflectionException;

/**
 * Class ClassFinder
 */
interface ClassFinderInterface
{
    /**
     * @param string $namespace
     * @param bool   $includePaths
     * @param bool   $recursive
     *
     * @return array
     */
    public function getNamespaceClasses(string $namespace, bool $includePaths = false, bool $recursive = false): array;

    /**
     * get the full name (name \ namespace) of a class from its file path
     * result example: (string) "I\Am\The\Namespace\Of\This\Class"
     *
     * @param string $filePathName
     *
     * @return  string
     */
    public function getClassFullNameFromFile(string $filePathName): string;

    /**
     * Resolves the given class name relative to the given reference class
     *
     * @param string $referenceClassName
     * @param string $className
     *
     * @return string
     * @throws ReflectionException
     */
    public function getFQClassName(string $referenceClassName, string $className): string;
}
