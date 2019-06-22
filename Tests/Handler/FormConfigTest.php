<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Tests\Handler;

use Fxp\Component\Resource\Handler\FormConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests case for Form Config Handler.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class FormConfigTest extends TestCase
{
    public function testWithStringType(): void
    {
        $type = FormType::class;
        $config = new FormConfig($type);
        static::assertSame('json', $config->getConverter());
        static::assertSame(Request::METHOD_POST, $config->getMethod());
        static::assertEquals(['method' => Request::METHOD_POST], $config->getOptions());
        static::assertSame($type, $config->getType());
        static::assertTrue($config->getSubmitClearMissing());
    }

    public function testWithStringTypeAndPatchMethod(): void
    {
        $type = FormType::class;
        $config = new FormConfig($type, [], Request::METHOD_PATCH);
        static::assertSame('json', $config->getConverter());
        static::assertSame(Request::METHOD_PATCH, $config->getMethod());
        static::assertEquals(['method' => Request::METHOD_PATCH], $config->getOptions());
        static::assertSame($type, $config->getType());
        static::assertFalse($config->getSubmitClearMissing());
    }

    public function testSetType(): void
    {
        $config = new FormConfig(FormType::class);

        static::assertSame(FormType::class, $config->getType());

        $config->setType(FormType::class);
        static::assertSame(FormType::class, $config->getType());
    }

    public function testSetOptions(): void
    {
        $config = new FormConfig(FormType::class);

        static::assertSame(Request::METHOD_POST, $config->getMethod());
        static::assertEquals([
            'method' => Request::METHOD_POST,
        ], $config->getOptions());

        $config->setOptions([
            'method' => Request::METHOD_PATCH,
            'required' => true,
        ]);

        static::assertEquals([
            'method' => Request::METHOD_PATCH,
            'required' => true,
        ], $config->getOptions());
        static::assertSame(Request::METHOD_PATCH, $config->getMethod());
    }

    public function testSetMethod(): void
    {
        $config = new FormConfig(FormType::class);

        static::assertSame(Request::METHOD_POST, $config->getMethod());
        static::assertEquals([
            'method' => Request::METHOD_POST,
        ], $config->getOptions());
        static::assertSame(Request::METHOD_POST, $config->getMethod());

        $config->setMethod(Request::METHOD_PATCH);

        static::assertSame(Request::METHOD_PATCH, $config->getMethod());
        static::assertEquals([
            'method' => Request::METHOD_PATCH,
        ], $config->getOptions());
    }

    public function testAddBuilderHandler(): void
    {
        $config = new FormConfig(FormType::class);
        $handler = static function (): void {};

        static::assertSame([], $config->getBuilderHandlers());

        $config->addBuilderHandler($handler);

        static::assertSame([$handler], $config->getBuilderHandlers());
    }

    public function getRequestMethod()
    {
        return [
            [null, Request::METHOD_HEAD,    true],
            [null, Request::METHOD_GET,     true],
            [null, Request::METHOD_POST,    true],
            [null, Request::METHOD_PUT,     true],
            [null, Request::METHOD_PATCH,   false],
            [null, Request::METHOD_DELETE,  true],
            [null, Request::METHOD_PURGE,   true],
            [null, Request::METHOD_OPTIONS, true],
            [null, Request::METHOD_TRACE,   true],
            [null, Request::METHOD_CONNECT, true],

            [true, Request::METHOD_HEAD,    true],
            [true, Request::METHOD_GET,     true],
            [true, Request::METHOD_POST,    true],
            [true, Request::METHOD_PUT,     true],
            [true, Request::METHOD_PATCH,   true],
            [true, Request::METHOD_DELETE,  true],
            [true, Request::METHOD_PURGE,   true],
            [true, Request::METHOD_OPTIONS, true],
            [true, Request::METHOD_TRACE,   true],
            [true, Request::METHOD_CONNECT, true],

            [false, Request::METHOD_HEAD,    false],
            [false, Request::METHOD_GET,     false],
            [false, Request::METHOD_POST,    false],
            [false, Request::METHOD_PUT,     false],
            [false, Request::METHOD_PATCH,   false],
            [false, Request::METHOD_DELETE,  false],
            [false, Request::METHOD_PURGE,   false],
            [false, Request::METHOD_OPTIONS, false],
            [false, Request::METHOD_TRACE,   false],
            [false, Request::METHOD_CONNECT, false],
        ];
    }

    /**
     * @dataProvider getRequestMethod
     *
     * @param null|bool $submitClearMissing
     * @param string    $method
     * @param bool      $validSubmitClearMissing
     */
    public function testGetSubmitClearMissing($submitClearMissing, $method, $validSubmitClearMissing): void
    {
        $config = new FormConfig(FormType::class);
        $config->setMethod($method);
        $config->setSubmitClearMissing($submitClearMissing);

        static::assertEquals($validSubmitClearMissing, $config->getSubmitClearMissing());
    }
}
