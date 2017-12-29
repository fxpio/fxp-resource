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
use Fxp\Component\Resource\Converter\JsonConverter;
use Fxp\Component\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Tests case for json converter.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class JsonConverterTest extends TestCase
{
    /**
     * @var ConverterInterface
     */
    protected $converter;

    protected function setUp()
    {
        $translator = new Translator('en');
        $ref = new \ReflectionClass(ResourceInterface::class);
        $translator->addResource('xml', realpath(dirname($ref->getFileName()).'/Resources/translations/FxpResource.en.xlf'), 'en', 'FxpResource');
        $translator->addLoader('xml', new XliffFileLoader());

        $this->converter = new JsonConverter($translator);
    }

    public function testBasic()
    {
        $this->assertSame('json', $this->converter->getName());
    }

    /**
     * @expectedException \Fxp\Component\Resource\Exception\InvalidConverterException
     * @expectedExceptionMessage Request body should be a JSON object
     */
    public function testInvalidConversion()
    {
        $this->converter->convert('<xml>content</xml>');
    }

    public function testConversion()
    {
        $content = $this->converter->convert('{"foo": "bar"}');

        $this->assertEquals(['foo' => 'bar'], $content);
    }
}
