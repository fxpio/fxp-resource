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

use Sonatra\Component\Resource\Handler\ClosureFormConfigList;
use Symfony\Component\Form\Extension\Core\Type\FormType;

/**
 * Tests case for ClosureFormConfigList.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ClosureFormConfigListTest extends \PHPUnit_Framework_TestCase
{
    public function testBasic()
    {
        $config = new ClosureFormConfigList(FormType::class);

        $this->assertTrue($config->isTransactional());
        $config->setTransactional(false);
        $this->assertFalse($config->isTransactional());
    }

    public function testConvertObjectsWithoutClosure()
    {
        $config = new ClosureFormConfigList(FormType::class);
        $list = array('mock');

        $this->assertNotSame($list, $config->convertObjects($list));
        $this->assertEquals(array(), $config->convertObjects($list));
    }

    public function testConvertObjectsWithClosure()
    {
        $config = new ClosureFormConfigList(FormType::class);
        $list = array('mock');

        $config->setObjectConverter(function (array $list) {
            return $list;
        });

        $this->assertEquals($list, $config->convertObjects($list));
    }

    public function testLimit()
    {
        $config = new ClosureFormConfigList(FormType::class);

        $this->assertNull($config->getLimit());
        $config->setLimit(5);
        $this->assertSame(5, $config->getLimit());
    }

    public function testFindList()
    {
        $config = new ClosureFormConfigList(FormType::class);
        $data = array(
            'records' => array(),
        );

        $list = $config->findList($data);
        $this->assertSame($data['records'], $list);
    }

    public function testFindListWithTransactionalOption()
    {
        $config = new ClosureFormConfigList(FormType::class);
        $data = array(
            'records' => array(),
            'transaction' => false,
        );

        $this->assertTrue($config->isTransactional());

        $list = $config->findList($data);
        $this->assertSame($data['records'], $list);
        $this->assertFalse($config->isTransactional());
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\InvalidResourceException
     * @expectedExceptionMessage The records field is required
     */
    public function testFindListWithoutRecords()
    {
        $config = new ClosureFormConfigList(FormType::class);

        $config->findList(array());
    }
}
