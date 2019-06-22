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

use Fxp\Component\Resource\ResourceItem;
use Fxp\Component\Resource\ResourceStatutes;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Test\FormInterface;

/**
 * Tests case for resource item.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class ResourceItemTest extends TestCase
{
    public function testDefaultGetterSetter(): void
    {
        $data = $this->getMockBuilder(\stdClass::class)->getMock();
        $resource = new ResourceItem($data);

        static::assertSame(ResourceStatutes::PENDING, $resource->getStatus());
        $resource->setStatus(ResourceStatutes::CANCELED);
        static::assertSame(ResourceStatutes::CANCELED, $resource->getStatus());

        static::assertSame($data, $resource->getData());
        static::assertSame($data, $resource->getRealData());
        static::assertCount(0, $resource->getErrors());
        static::assertFalse($resource->isForm());
        static::assertTrue($resource->isValid());
    }

    public function testGetFormErrorsWithObjectData(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The data of resource is not a form instance, used the "getErrors()" method');

        $resource = new ResourceItem($this->getMockBuilder(\stdClass::class)->getMock());
        $resource->getFormErrors();
    }

    public function testGetFormErrorsWithFormData(): void
    {
        $fErrors = $this->getMockBuilder('Symfony\Component\Form\FormErrorIterator')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        /** @var FormInterface|MockObject $form */
        $form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')->getMock();
        $form->expects(static::any())
            ->method('getData')
            ->willReturn($this->getMockBuilder(\stdClass::class)->getMock())
        ;
        $form->expects(static::any())
            ->method('getErrors')
            ->willReturn($fErrors)
        ;

        $resource = new ResourceItem($form);
        $errors = $resource->getFormErrors();

        static::assertInstanceOf('Symfony\Component\Form\FormErrorIterator', $errors);
    }

    public function testUnexpectedTypeException(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "object", "integer" given');

        /** @var object $object */
        $object = 42;

        new ResourceItem($object);
    }

    public function testUnexpectedTypeExceptionWithForm(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "object", "integer" given');

        /** @var object $object */
        $object = 42;

        /** @var FormInterface|MockObject $form */
        $form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')->getMock();
        $form->expects(static::any())
            ->method('getData')
            ->willReturn($object)
        ;

        new ResourceItem($form);
    }
}
