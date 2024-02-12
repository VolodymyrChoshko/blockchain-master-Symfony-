<?php
namespace Tests\BlocksEdit\Html;

use BlocksEdit\Html\FormErrors;
use BlocksEdit\Test\TestCase;
use Exception;

/**
 * @coversDefaultClass \BlocksEdit\Html\FormErrors
 */
class FormErrorsTest extends TestCase
{
    /**
     * @covers ::add
     * @throws Exception
     */
    public function testAdd()
    {
        $formErrors = new FormErrors();
        $actual     = $formErrors->add('testing', 'Hello, World!');
        $this->assertEquals($actual, $formErrors);
    }

    /**
     * @covers ::getErrors
     * @throws Exception
     */
    public function testGetErrors()
    {
        $formErrors = new FormErrors();
        $formErrors->add('testing', 'Hello, World!');
        $actual = $formErrors->getErrors();
        $this->assertEquals([
            'testing' => 'Hello, World!'
        ], $actual);
    }

    /**
     * @covers ::get
     * @throws Exception
     */
    public function testGetError()
    {
        $formErrors = new FormErrors();
        $formErrors->add('testing', 'Hello, World!');
        $actual = $formErrors->getError('testing');
        $this->assertEquals('Hello, World!', $actual);
    }

    /**
     * @covers ::hasErrors
     * @throws Exception
     */
    public function testHasErrors()
    {
        $formErrors = new FormErrors();
        $this->assertFalse($formErrors->hasErrors());

        $formErrors->add('testing', 'Hello, World!');
        $this->assertTrue($formErrors->hasErrors());
    }

    /**
     * @covers ::hasError
     * @throws Exception
     */
    public function testHasError()
    {
        $formErrors = new FormErrors();
        $this->assertFalse($formErrors->hasError('testing'));

        $formErrors->add('testing', 'Hello, World!');
        $this->assertTrue($formErrors->hasError('testing'));
        $this->assertFalse($formErrors->hasError('foo'));
    }
}
