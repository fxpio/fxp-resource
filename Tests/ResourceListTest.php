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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Tests case for resource list.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ResourceListTest extends TestCase
{
    public function getData()
    {
        return array(
            array(ResourceListStatutes::SUCCESSFULLY, array()),
            array(ResourceListStatutes::SUCCESSFULLY, array(ResourceStatutes::CREATED, ResourceStatutes::CREATED)),
            array(ResourceListStatutes::SUCCESSFULLY, array(ResourceStatutes::UPDATED, ResourceStatutes::UPDATED)),
            array(ResourceListStatutes::SUCCESSFULLY, array(ResourceStatutes::DELETED, ResourceStatutes::DELETED)),
            array(ResourceListStatutes::SUCCESSFULLY, array(ResourceStatutes::UNDELETED, ResourceStatutes::UNDELETED)),
            array(ResourceListStatutes::SUCCESSFULLY, array(ResourceStatutes::CREATED, ResourceStatutes::UPDATED)),
            array(ResourceListStatutes::SUCCESSFULLY, array(ResourceStatutes::DELETED, ResourceStatutes::UNDELETED)),
            array(ResourceListStatutes::SUCCESSFULLY, array(ResourceStatutes::CREATED, ResourceStatutes::UPDATED, ResourceStatutes::DELETED, ResourceStatutes::UNDELETED)),
            array(ResourceListStatutes::CANCEL, array(ResourceStatutes::CANCELED, ResourceStatutes::CANCELED)),
            array(ResourceListStatutes::ERROR, array(ResourceStatutes::ERROR, ResourceStatutes::ERROR)),
            array(ResourceListStatutes::PENDING, array(ResourceStatutes::PENDING, ResourceStatutes::PENDING)),
            array(ResourceListStatutes::MIXED, array(ResourceStatutes::CREATED, ResourceStatutes::PENDING)),
            array(ResourceListStatutes::MIXED, array(ResourceStatutes::CREATED, ResourceStatutes::CANCELED)),
            array(ResourceListStatutes::MIXED, array(ResourceStatutes::CREATED, ResourceStatutes::ERROR)),
        );
    }

    /**
     * @dataProvider getData
     *
     * @param string $valid            The valid status of resource list
     * @param array  $resourceStatutes The status of resource in list
     */
    public function testStatus($valid, array $resourceStatutes)
    {
        $resources = array();

        foreach ($resourceStatutes as $rStatus) {
            $resource = $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock();
            $resource->expects($this->any())
                ->method('getStatus')
                ->will($this->returnValue($rStatus));

            $resources[] = $resource;
        }

        $list = new ResourceList($resources);

        $this->assertSame($valid, $list->getStatus());
    }

    public function testGetResources()
    {
        $resources = array(
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
        );

        $list = new ResourceList($resources);
        $this->assertSame($resources, $list->getResources());

        $resources2 = array(
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
        );

        $list2 = new ResourceList($resources2);
        $this->assertSame($resources, $list->getResources());

        $all = array_merge($resources, $resources2);
        $list->addAll($list2);
        $this->assertSame($all, $list->getResources());
        $this->assertSame($all, $list->all());

        $this->assertTrue($list->has(0));
        $this->assertTrue($list->offsetExists(0));
        $this->assertSame($all[0], $list->get(0));
        $this->assertSame($all[0], $list->offsetGet(0));
        $this->assertTrue($list->has(1));
        $this->assertTrue($list->offsetExists(1));
        $this->assertSame($all[1], $list->get(1));
        $this->assertSame($all[1], $list->offsetGet(1));
        $this->assertTrue($list->has(2));
        $this->assertTrue($list->offsetExists(2));
        $this->assertSame($all[2], $list->get(2));
        $this->assertSame($all[2], $list->offsetGet(2));
        $this->assertTrue($list->has(3));
        $this->assertTrue($list->offsetExists(3));
        $this->assertSame($all[3], $list->get(3));
        $this->assertSame($all[3], $list->offsetGet(3));
    }

    /**
     * @expectedException \Fxp\Component\Resource\Exception\OutOfBoundsException
     * @expectedExceptionMessage The offset "0" does not exist.
     */
    public function testGetOUtOfBoundsException()
    {
        $list = new ResourceList(array());
        $list->get(0);
    }

    public function testSet()
    {
        $resources = array(
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
        );
        $list = new ResourceList($resources);

        /* @var ResourceInterface $new */
        $new = $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock();

        $this->assertNotSame($new, $list->get(0));
        $list->set(0, $new);
        $this->assertNotSame($resources[0], $list->get(0));
        $this->assertSame($new, $list->get(0));

        /* @var ResourceInterface $new2 */
        $new2 = $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock();

        $this->assertNotSame($new2, $list->offsetGet(1));
        $list->offsetSet(1, $new2);
        $this->assertNotSame($resources[1], $list->offsetGet(1));
        $this->assertSame($new2, $list->offsetGet(1));

        /* @var ResourceInterface $new3 */
        $new3 = $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock();

        $this->assertCount(2, $list);

        $list->offsetSet(null, $new3);
        $this->assertCount(3, $list);
        $this->assertSame($new3, $list->offsetGet(2));
    }

    public function testRemove()
    {
        $resources = array(
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
        );
        $list = new ResourceList($resources);

        $this->assertCount(2, $list);

        $list->remove(0);
        $this->assertCount(1, $list);
        $this->assertFalse($list->has(0));
        $this->assertSame($resources[1], $list->get(1));

        $list->offsetUnset(1);
        $this->assertCount(0, $list);
    }

    public function testGetEmptyErrorsAndEmptyChildrenErrors()
    {
        $resources = array(
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
        );
        $list = new ResourceList($resources);

        $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolationListInterface', $list->getErrors());
        $this->assertCount(0, $list->getErrors());
        $this->assertFalse($list->hasErrors());
    }

    public function testGetErrorsAndEmptyChildrenErrors()
    {
        $resources = array(
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
        );
        $list = new ResourceList($resources);

        $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolationListInterface', $list->getErrors());

        /* @var ConstraintViolationInterface $error */
        $error = $this->getMockBuilder('Symfony\Component\Validator\ConstraintViolationInterface')->getMock();
        $list->getErrors()->add($error);
        $this->assertCount(1, $list->getErrors());
        $this->assertTrue($list->hasErrors());
    }

    public function testGetEmptyErrorsAndChildrenErrors()
    {
        /* @var ResourceInterface|\PHPUnit_Framework_MockObject_MockObject $errorResource */
        $errorResource = $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock();
        $errorResource->expects($this->any())
            ->method('getStatus')
            ->will($this->returnValue(ResourceStatutes::ERROR));
        $errorResource->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue(false));

        $resources = array(
            $errorResource,
            $this->getMockBuilder('Fxp\Component\Resource\ResourceInterface')->getMock(),
        );
        $list = new ResourceList($resources);

        $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolationListInterface', $list->getErrors());
        $this->assertCount(0, $list->getErrors());
        $this->assertTrue($list->hasErrors());
    }
}
