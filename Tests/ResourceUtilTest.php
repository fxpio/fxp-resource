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

use Fxp\Component\Resource\Model\SoftDeletableInterface;
use Fxp\Component\Resource\ResourceListInterface;
use Fxp\Component\Resource\ResourceUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

/**
 * Tests case for resource util.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class ResourceUtilTest extends TestCase
{
    public function getAllowForm()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider getAllowForm
     *
     * @param bool $allowForm The allow form value
     */
    public function testConvertObjectsToResourceList($allowForm): void
    {
        $objects = [
            new \stdClass(),
            new \stdClass(),
            new \stdClass(),
        ];
        $list = ResourceUtil::convertObjectsToResourceList($objects, \stdClass::class, $allowForm);

        static::assertInstanceOf(ResourceListInterface::class, $list);
        static::assertCount(3, $list);
    }

    /**
     * @dataProvider getAllowForm
     *
     * @param bool $allowForm The allow form value
     */
    public function testValidateObjectResource($allowForm): void
    {
        $obj = new \stdClass();
        ResourceUtil::validateObjectResource($obj, \stdClass::class, $allowForm);
        static::assertNotNull($obj);
    }

    /**
     * @dataProvider getAllowForm
     *
     * @param bool $allowForm The allow form value
     */
    public function testValidateObjectResourceWithInvalidClass($allowForm): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "Fxp\\Component\\Resource\\Model\\SoftDeletableInterface", "stdClass" given');

        ResourceUtil::validateObjectResource(new \stdClass(), SoftDeletableInterface::class, 0, $allowForm);
    }

    public function testValidateObjectResourceWithForm(): void
    {
        /** @var FormInterface|MockObject */
        $form = $this->getMockBuilder(FormInterface::class)->getMock();
        $form->expects(static::once())
            ->method('getData')
            ->willReturn(new \stdClass())
        ;

        ResourceUtil::validateObjectResource($form, \stdClass::class, 0, true);
    }

    public function testValidateObjectResourceWithoutAllowedForm(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessageRegExp('/Expected argument of type "stdClass", "([\\w\\_0-9]+)" given/');

        /** @var FormInterface|MockObject */
        $form = $this->getMockBuilder(FormInterface::class)->getMock();
        $form->expects(static::never())
            ->method('getData')
        ;

        ResourceUtil::validateObjectResource($form, \stdClass::class, 0, false);
    }
}
