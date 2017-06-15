<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Tests;

use PHPUnit\Framework\TestCase;
use Sonatra\Component\Resource\ResourceItem;
use Sonatra\Component\Resource\ResourceStatutes;
use Symfony\Component\Form\Test\FormInterface;

/**
 * Tests case for resource item.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ResourceItemTest extends TestCase
{
    public function testDefaultGetterSetter()
    {
        $data = $this->getMockBuilder(\stdClass::class)->getMock();
        $resource = new ResourceItem($data);

        $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
        $resource->setStatus(ResourceStatutes::CANCELED);
        $this->assertSame(ResourceStatutes::CANCELED, $resource->getStatus());

        $this->assertSame($data, $resource->getData());
        $this->assertSame($data, $resource->getRealData());
        $this->assertCount(0, $resource->getErrors());
        $this->assertFalse($resource->isForm());
        $this->assertTrue($resource->isValid());
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\InvalidArgumentException
     * @expectedExceptionMessage The data of resource is not a form instance, used the "getErrors()" method
     */
    public function testGetFormErrorsWithObjectData()
    {
        $resource = new ResourceItem($this->getMockBuilder(\stdClass::class)->getMock());
        $resource->getFormErrors();
    }

    public function testGetFormErrorsWithFormData()
    {
        $fErrors = $this->getMockBuilder('Symfony\Component\Form\FormErrorIterator')
            ->disableOriginalConstructor()
            ->getMock();

        /* @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')->getMock();
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($this->getMockBuilder(\stdClass::class)->getMock()));
        $form->expects($this->any())
            ->method('getErrors')
            ->will($this->returnValue($fErrors));

        $resource = new ResourceItem($form);
        $errors = $resource->getFormErrors();

        $this->assertInstanceOf('Symfony\Component\Form\FormErrorIterator', $errors);
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "object", "integer" given
     */
    public function testUnexpectedTypeException()
    {
        /* @var object $object */
        $object = 42;

        new ResourceItem($object);
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "object", "integer" given
     */
    public function testUnexpectedTypeExceptionWithForm()
    {
        /* @var object $object */
        $object = 42;

        /* @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')->getMock();
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($object));

        new ResourceItem($form);
    }
}
