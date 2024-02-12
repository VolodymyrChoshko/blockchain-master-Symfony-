<?php
namespace Tests\BlocksEdit\System;

use BlocksEdit\Database\Annotations as DB;
use BlocksEdit\System\ClassFinder;
use BlocksEdit\Test\TestCase;
use Exception;
use ReflectionException;

/**
 * @coversDefaultClass \BlocksEdit\System\ClassFinder
 */
class ClassFinderTest extends TestCase
{
    /**
     * @covers ::getNamespaceClasses
     * @throws Exception
     */
    public function testGetNamespaceClasses()
    {
        $classFinder = new ClassFinder($this->getConfig());
        $actual      = $classFinder->getNamespaceClasses('Tests\\BlocksEdit');
        $this->assertContains('Tests\BlocksEdit\System\fixtures\TestClass1', $actual);
        $this->assertContains('Tests\BlocksEdit\System\fixtures\TestClass2', $actual);
    }

    /**
     * @covers ::getClassFullNameFromFile
     * @throws Exception
     */
    public function testGetClassFullNameFromFile()
    {
        $classFinder = new ClassFinder($this->getConfig());
        $actual      = $classFinder->getClassFullNameFromFile(realpath(__DIR__ . '/fixtures/TestClass1.php'));
        $this->assertEquals('Tests\BlocksEdit\System\fixtures\TestClass1', $actual);
    }

    /**
     * @covers ::getFQClassName
     * @return void
     * @throws ReflectionException
     */
    public function testGetFQClassName()
    {
        $classFinder = new ClassFinder($this->getConfig());
        $resolved = $classFinder->getFQClassName(__CLASS__, 'Entity');
        $this->assertEquals('\\Entity', $resolved);

        $resolved = $classFinder->getFQClassName(__CLASS__, 'ClassFinderTest');
        $this->assertEquals('\\Tests\\BlocksEdit\\System\\ClassFinderTest', $resolved);

        $resolved = $classFinder->getFQClassName(__CLASS__, 'DB');
        $this->assertEquals('\\BlocksEdit\\Database\\Annotations', $resolved);

        $resolved = $classFinder->getFQClassName(__CLASS__, 'DB\\Table');
        $this->assertEquals('\\BlocksEdit\\Database\\Annotations\\Table', $resolved);
    }
}
