<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Tests\Converter;

use Fxp\Component\Resource\Converter\ConverterInterface;
use Fxp\Component\Resource\Converter\XmlConverter;
use Fxp\Component\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Tests case for json converter.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class XmlConverterTest extends TestCase
{
    /**
     * @var ConverterInterface
     */
    protected $converter;

    protected function setUp(): void
    {
        $translator = new Translator('en');
        $ref = new \ReflectionClass(ResourceInterface::class);
        $translator->addResource('xml', realpath(\dirname($ref->getFileName()).'/Resources/translations/FxpResource.en.xlf'), 'en', 'FxpResource');
        $translator->addLoader('xml', new XliffFileLoader());

        $this->converter = new XmlConverter($translator);
    }

    public function testBasic(): void
    {
        static::assertSame('xml', $this->converter->getName());
    }

    public function testInvalidConversion(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\InvalidConverterException::class);
        $this->expectExceptionMessage('Request body should be a XML object');

        $this->converter->convert('content');
    }

    public function testConversion(): void
    {
        $content = $this->converter->convert('<object><foo>bar</foo></object>');

        static::assertEquals(['foo' => 'bar'], $content);
    }
}
