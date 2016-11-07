<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Tests\Handler;

use Doctrine\Common\Persistence\ObjectRepository;
use Sonatra\Component\DefaultValue\Tests\Fixtures\Object\Foo;
use Sonatra\Component\Resource\Domain\DomainInterface;
use Sonatra\Component\Resource\Handler\DomainFormConfigList;
use Symfony\Component\Form\Extension\Core\Type\FormType;

/**
 * Tests case for DomainFormConfigList.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainFormConfigListTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DomainInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $domain;

    /**
     * @var DomainFormConfigList
     */
    protected $config;

    protected function setUp()
    {
        $this->domain = $this->getMockBuilder(DomainInterface::class)->getMock();
        $this->config = new DomainFormConfigList($this->domain, FormType::class);
    }

    public function testBasic()
    {
        $this->assertTrue($this->config->isTransactional());
        $this->config->setTransactional(false);
        $this->config->setDefaultValueOptions(array());
        $this->config->setCreation(false);
        $this->config->setIdentifier('bar');
        $this->assertFalse($this->config->isTransactional());
    }

    public function testConvertObjectsCreation()
    {
        $defaultValue = array('foo' => 'bar');
        $this->config->setCreation(true);
        $this->config->setDefaultValueOptions($defaultValue);
        $list = array(
            array(
                'foo' => 'baz',
                'bar' => 'foo',
            ),
            array(
                'baz' => 'foo',
                'bar' => '42',
            ),
        );

        $instances = array(
            new Foo(),
            new Foo(),
        );

        $this->domain->expects($this->at(0))
            ->method('newInstance')
            ->will($this->returnValue($instances[0]));

        $this->domain->expects($this->at(1))
            ->method('newInstance')
            ->will($this->returnValue($instances[1]));

        $res = $this->config->convertObjects($list);

        $this->assertCount(2, $res);
        $this->assertSame($instances[0], $res[0]);
        $this->assertSame($instances[1], $res[1]);
    }

    public function testConvertObjectsUpdate()
    {
        $defaultValue = array('foo' => 'bar');
        $this->config->setCreation(false);
        $this->config->setIdentifier('bar');
        $this->config->setDefaultValueOptions($defaultValue);
        $list = array(
            array(
                'bar' => 'test1',
            ),
            array(
                'bar' => 'test2',
            ),
            array(
                'test' => 'quill',
            ),
        );

        $instances = array();
        $instances[0] = new Foo();
        $instances[1] = new Foo();
        $new = new Foo();

        $instances[0]->setBar('test1');
        $instances[1]->setBar('test2');

        $repo = $this->getMockBuilder(ObjectRepository::class)->getMock();
        $repo->expects($this->once())
            ->method('findBy')
            ->will($this->returnValue($instances));

        $this->domain->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repo));

        $this->domain->expects($this->once())
            ->method('newInstance')
            ->will($this->returnValue($new));

        $res = $this->config->convertObjects($list);

        $this->assertCount(3, $res);
        $this->assertSame($instances[0], $res[0]);
        $this->assertSame($instances[1], $res[1]);
        $this->assertSame($new, $res[2]);
    }
}
