<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Tests\Converter;

use Sonatra\Component\Resource\Converter\ConverterInterface;
use Sonatra\Component\Resource\Converter\JsonConverter;
use Sonatra\Component\Resource\ResourceInterface;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Tests case for json converter.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class JsonConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConverterInterface
     */
    protected $converter;

    protected function setUp()
    {
        $translator = new Translator('en');
        $ref = new \ReflectionClass(ResourceInterface::class);
        $translator->addResource('xml', realpath(dirname($ref->getFileName()).'/Resources/translations/SonatraResource.en.xlf'), 'en', 'SonatraResource');
        $translator->addLoader('xml', new XliffFileLoader());

        $this->converter = new JsonConverter($translator);
    }

    public function testBasic()
    {
        $this->assertSame('json', $this->converter->getName());
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\InvalidConverterException
     * @expectedExceptionMessage Request body should be a JSON object
     */
    public function testInvalidConversion()
    {
        $this->converter->convert('<xml>content</xml>');
    }

    public function testConversion()
    {
        $content = $this->converter->convert('{"foo": "bar"}');

        $this->assertEquals(array('foo' => 'bar'), $content);
    }
}
