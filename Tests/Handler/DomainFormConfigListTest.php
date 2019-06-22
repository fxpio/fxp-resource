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
}
