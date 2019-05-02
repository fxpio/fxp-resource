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

use Fxp\Component\Resource\Handler\ClosureFormConfigList;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;

/**
 * Tests case for ClosureFormConfigList.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class ClosureFormConfigListTest extends TestCase
{
    public function testBasic(): void
    {
        $config = new ClosureFormConfigList(FormType::class);

        $this->assertTrue($config->isTransactional());
        $config->setTransactional(false);
        $this->assertFalse($config->isTransactional());
    }

    public function testConvertObjectsWithoutClosure(): void
    {
        $config = new ClosureFormConfigList(FormType::class);
        $list = ['mock'];

        $this->assertNotSame($list, $config->convertObjects($list));
        $this->assertEquals([], $config->convertObjects($list));
    }

    public function testConvertObjectsWithClosure(): void
    {
        $config = new ClosureFormConfigList(FormType::class);
        $list = ['mock'];

        $config->setObjectConverter(function (array $list) {
            return $list;
        });

        $this->assertEquals($list, $config->convertObjects($list));
    }

    public function testLimit(): void
    {
        $config = new ClosureFormConfigList(FormType::class);

        $this->assertNull($config->getLimit());
        $config->setLimit(5);
        $this->assertSame(5, $config->getLimit());
    }

    public function testFindList(): void
    {
        $config = new ClosureFormConfigList(FormType::class);
        $data = [
            'records' => [],
        ];

        $list = $config->findList($data);
        $this->assertSame($data['records'], $list);
    }

    public function testFindListWithTransactionalOption(): void
    {
        $config = new ClosureFormConfigList(FormType::class);
        $data = [
            'records' => [],
            'transaction' => false,
        ];

        $this->assertTrue($config->isTransactional());

        $list = $config->findList($data);
        $this->assertSame($data['records'], $list);
        $this->assertFalse($config->isTransactional());
    }

    public function testFindListWithoutRecords(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\InvalidResourceException::class);
        $this->expectExceptionMessage('The "records" field is required');

        $config = new ClosureFormConfigList(FormType::class);

        $config->findList([]);
    }
}
