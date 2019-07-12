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

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Fxp\Component\DefaultValue\Tests\Fixtures\Object\Foo;
use Fxp\Component\Resource\Domain\DomainInterface;
use Fxp\Component\Resource\Handler\DomainFormConfigList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;

/**
 * Tests case for DomainFormConfigList.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class DomainFormConfigListTest extends TestCase
{
    /**
     * @var DomainInterface|MockObject
     */
    protected $domain;

    /**
     * @var DomainFormConfigList
     */
    protected $config;

    protected function setUp(): void
    {
        $this->domain = $this->getMockBuilder(DomainInterface::class)->getMock();
        $this->config = new DomainFormConfigList($this->domain, FormType::class);
    }

    public function testBasic(): void
    {
        static::assertTrue($this->config->isTransactional());
        $this->config->setTransactional(false);
        $this->config->setDefaultValueOptions([]);
        $this->config->setCreation(false);
        $this->config->setIdentifier('bar');
        static::assertFalse($this->config->isTransactional());
    }

    public function testConvertObjectsCreation(): void
    {
        $defaultValue = ['foo' => 'bar'];
        $this->config->setCreation(true);
        $this->config->setDefaultValueOptions($defaultValue);
        $list = [
            [
                'foo' => 'baz',
                'bar' => 'foo',
            ],
            [
                'baz' => 'foo',
                'bar' => '42',
            ],
        ];

        $instances = [
            new Foo(),
            new Foo(),
        ];

        $this->domain->expects(static::at(0))
            ->method('newInstance')
            ->willReturn($instances[0])
        ;

        $this->domain->expects(static::at(1))
            ->method('newInstance')
            ->willReturn($instances[1])
        ;

        $res = $this->config->convertObjects($list);

        static::assertCount(2, $res);
        static::assertSame($instances[0], $res[0]);
        static::assertSame($instances[1], $res[1]);
    }

    public function testConvertObjectsUpdate(): void
    {
        $defaultValue = ['foo' => 'bar'];
        $this->config->setCreation(false);
        $this->config->setIdentifier('bar');
        $this->config->setDefaultValueOptions($defaultValue);
        $list = [
            [
                'bar' => 'test1',
            ],
            [
                'bar' => 'test2',
            ],
            [
                'test' => 'quill',
            ],
        ];

        $instances = [];
        $instances[0] = new Foo();
        $instances[1] = new Foo();
        $new = new Foo();

        $instances[0]->setBar('test1');
        $instances[1]->setBar('test2');

        $repo = $this->getMockBuilder(ObjectRepository::class)->getMock();
        $repo->expects(static::once())
            ->method('findBy')
            ->willReturn($instances)
        ;

        $this->domain->expects(static::once())
            ->method('getRepository')
            ->willReturn($repo)
        ;

        $this->domain->expects(static::once())
            ->method('newInstance')
            ->willReturn($new)
        ;

        $res = $this->config->convertObjects($list);

        static::assertCount(3, $res);
        static::assertSame($instances[0], $res[0]);
        static::assertSame($instances[1], $res[1]);
        static::assertSame($new, $res[2]);
    }

    public function getOptionsData(): array
    {
        return [
            ['Create', null],
            ['Update', 'test1'],
        ];
    }

    /**
     * @dataProvider getOptionsData
     *
     * @param string          $expected
     * @param null|int|string $id
     */
    public function testGetOptions(string $expected, $id): void
    {
        $this->config->setCreation(false);
        $this->config->setIdentifier('bar');
        $this->config->setOptions([
            'foo_options' => 'bar',
            'validation_groups' => ['Test'],
        ]);

        $object = new Foo();
        $object->setBar($id);

        $om = $this->getMockBuilder(ObjectManager::class)->getMock();
        $metaFactory = $this->getMockBuilder(ClassMetadataFactory::class)->getMock();
        $meta = $this->getMockBuilder(ClassMetadata::class)->getMock();

        $this->domain->expects(static::once())
            ->method('getObjectManager')
            ->willReturn($om)
        ;

        $om->expects(static::once())
            ->method('getMetadataFactory')
            ->willReturn($metaFactory)
        ;

        $metaFactory->expects(static::once())
            ->method('hasMetadataFor')
            ->with(\get_class($object))
            ->willReturn(true)
        ;

        $metaFactory->expects(static::once())
            ->method('getMetadataFor')
            ->with(\get_class($object))
            ->willReturn($meta)
        ;

        $meta->expects(static::once())
            ->method('getIdentifierValues')
            ->with($object)
            ->willReturn(null === $id ? [] : ['bar' => $id])
        ;

        $expectedOptions = [
            'foo_options' => 'bar',
            'validation_groups' => [
                'Test',
                'Default',
                $expected,
            ],
            'method' => 'POST',
        ];

        static::assertSame($expectedOptions, $this->config->getOptions($object));
    }
}
