<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Tests;

use Fxp\Component\Resource\ResourceInterface;
use Fxp\Component\Resource\ResourceList;
use Fxp\Component\Resource\ResourceListStatutes;
use Fxp\Component\Resource\ResourceStatutes;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Tests case for resource list.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class ResourceListTest extends TestCase
{
    public function getData()
    {
        return [
            [ResourceListStatutes::SUCCESSFULLY, []],
            [ResourceListStatutes::SUCCESSFULLY, [ResourceStatutes::CREATED, ResourceStatutes::CREATED]],
            [ResourceListStatutes::SUCCESSFULLY, [ResourceStatutes::UPDATED, ResourceStatutes::UPDATED]],
            [ResourceListStatutes::SUCCESSFULLY, [ResourceStatutes::DELETED, ResourceStatutes::DELETED]],
            [ResourceListStatutes::SUCCESSFULLY, [ResourceStatutes::UNDELETED, ResourceStatutes::UNDELETED]],
            [ResourceListStatutes::SUCCESSFULLY, [ResourceStatutes::CREATED, ResourceStatutes::UPDATED]],
            [ResourceListStatutes::SUCCESSFULLY, [ResourceStatutes::DELETED, ResourceStatutes::UNDELETED]],
            [ResourceListStatutes::SUCCESSFULLY, [ResourceStatutes::CREATED, ResourceStatutes::UPDATED, ResourceStatutes::DELETED, ResourceStatutes::UNDELETED]],
            [ResourceListStatutes::CANCEL, [ResourceStatutes::CANCELED, ResourceStatutes::CANCELED]],
            [ResourceListStatutes::ERROR, [ResourceStatutes::ERROR, ResourceStatutes::ERROR]],
            [ResourceListStatutes::PENDING, [ResourceStatutes::PENDING, ResourceStatutes::PENDING]],
            [ResourceListStatutes::MIXED, [ResourceStatutes::CREATED, ResourceStatutes::PENDING]],
            [ResourceListStatutes::MIXED, [ResourceStatutes::CREATED, ResourceStatutes::CANCELED]],
            [ResourceListStatutes::MIXED, [ResourceStatutes::CREATED, ResourceStatutes::ERROR]],
        ];
    }

    /**
     * @dataProvider getData
     *
     * @param string $valid            The valid status of resource list
     * @param array  $resourceStatutes The status of resource in list
     */
    public function testStatus($valid, array $resourceStatutes): void
    {
        $resources = [];

        foreach ($resourceStatutes as $rStatus) {
            $resource = $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock();
            $resource->expects(static::any())
                ->method('getStatus')
                ->willReturn($rStatus)
            ;

            $resources[] = $resource;
        }

        $list = new ResourceList($resources);

        static::assertSame($valid, $list->getStatus());
    }

    public function testGetResources(): void
    {
        $resources = [
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
        ];

        $list = new ResourceList($resources);
        static::assertSame($resources, $list->getResources());

        $resources2 = [
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
        ];

        $list2 = new ResourceList($resources2);
        static::assertSame($resources, $list->getResources());

        $all = array_merge($resources, $resources2);
        $list->addAll($list2);
        static::assertSame($all, $list->getResources());
        static::assertSame($all, $list->all());

        static::assertTrue($list->has(0));
        static::assertTrue($list->offsetExists(0));
        static::assertSame($all[0], $list->get(0));
        static::assertSame($all[0], $list->offsetGet(0));
        static::assertTrue($list->has(1));
        static::assertTrue($list->offsetExists(1));
        static::assertSame($all[1], $list->get(1));
        static::assertSame($all[1], $list->offsetGet(1));
        static::assertTrue($list->has(2));
        static::assertTrue($list->offsetExists(2));
        static::assertSame($all[2], $list->get(2));
        static::assertSame($all[2], $list->offsetGet(2));
        static::assertTrue($list->has(3));
        static::assertTrue($list->offsetExists(3));
        static::assertSame($all[3], $list->get(3));
        static::assertSame($all[3], $list->offsetGet(3));
    }

    public function testGetOUtOfBoundsException(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\OutOfBoundsException::class);
        $this->expectExceptionMessage('The offset "0" does not exist.');

        $list = new ResourceList([]);
        $list->get(0);
    }

    public function testSet(): void
    {
        $resources = [
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
        ];
        $list = new ResourceList($resources);

        /** @var ResourceInterface $new */
        $new = $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock();

        static::assertNotSame($new, $list->get(0));
        $list->set(0, $new);
        static::assertNotSame($resources[0], $list->get(0));
        static::assertSame($new, $list->get(0));

        /** @var ResourceInterface $new2 */
        $new2 = $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock();

        static::assertNotSame($new2, $list->offsetGet(1));
        $list->offsetSet(1, $new2);
        static::assertNotSame($resources[1], $list->offsetGet(1));
        static::assertSame($new2, $list->offsetGet(1));

        /** @var ResourceInterface $new3 */
        $new3 = $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock();

        static::assertCount(2, $list);

        $list->offsetSet(null, $new3);
        static::assertCount(3, $list);
        static::assertSame($new3, $list->offsetGet(2));
    }

    public function testRemove(): void
    {
        $resources = [
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
        ];
        $list = new ResourceList($resources);

        static::assertCount(2, $list);

        $list->remove(0);
        static::assertCount(1, $list);
        static::assertFalse($list->has(0));
        static::assertSame($resources[1], $list->get(1));

        $list->offsetUnset(1);
        static::assertCount(0, $list);
    }

    public function testGetEmptyErrorsAndEmptyChildrenErrors(): void
    {
        $resources = [
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
        ];
        $list = new ResourceList($resources);

        static::assertInstanceOf('Symfony\Component\Validator\ConstraintViolationListInterface', $list->getErrors());
        static::assertCount(0, $list->getErrors());
        static::assertFalse($list->hasErrors());
    }

    public function testGetErrorsAndEmptyChildrenErrors(): void
    {
        $resources = [
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
        ];
        $list = new ResourceList($resources);

        static::assertInstanceOf('Symfony\Component\Validator\ConstraintViolationListInterface', $list->getErrors());

        /** @var ConstraintViolationInterface $error */
        $error = $this->getMockBuilder('Symfony\Component\Validator\ConstraintViolationInterface')->getMock();
        $list->getErrors()->add($error);
        static::assertCount(1, $list->getErrors());
        static::assertTrue($list->hasErrors());
    }

    public function testGetEmptyErrorsAndChildrenErrors(): void
    {
        /** @var MockObject|ResourceInterface $errorResource */
        $errorResource = $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock();
        $errorResource->expects(static::any())
            ->method('getStatus')
            ->willReturn(ResourceStatutes::ERROR)
        ;
        $errorResource->expects(static::any())
            ->method('isValid')
            ->willReturn(false)
        ;

        $resources = [
            $errorResource,
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
        ];
        $list = new ResourceList($resources);

        static::assertInstanceOf('Symfony\Component\Validator\ConstraintViolationListInterface', $list->getErrors());
        static::assertCount(0, $list->getErrors());
        static::assertTrue($list->hasErrors());
    }
}
