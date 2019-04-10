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
 */
class ResourceUtilTest extends TestCase
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
    public function testConvertObjectsToResourceList($allowForm)
    {
        $objects = [
            new \stdClass(),
            new \stdClass(),
            new \stdClass(),
        ];
        $list = ResourceUtil::convertObjectsToResourceList($objects, \stdClass::class, $allowForm);

        $this->assertInstanceOf(ResourceListInterface::class, $list);
        $this->assertCount(3, $list);
    }

    /**
     * @dataProvider getAllowForm
     *
     * @param bool $allowForm The allow form value
     */
    public function testValidateObjectResource($allowForm)
    {
        $obj = new \stdClass();
        ResourceUtil::validateObjectResource($obj, \stdClass::class, $allowForm);
        $this->assertNotNull($obj);
    }

    /**
     * @dataProvider getAllowForm
     *
     * @param bool $allowForm The allow form value
     *
     * @expectedException \Fxp\Component\Resource\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Fxp\Component\Resource\Model\SoftDeletableInterface", "stdClass" given
     */
    public function testValidateObjectResourceWithInvalidClass($allowForm)
    {
        ResourceUtil::validateObjectResource(new \stdClass(), SoftDeletableInterface::class, 0, $allowForm);
    }

    public function testValidateObjectResourceWithForm()
    {
        /* @var FormInterface|MockObject */
        $form = $this->getMockBuilder(FormInterface::class)->getMock();
        $form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(new \stdClass()));

        ResourceUtil::validateObjectResource($form, \stdClass::class, 0, true);
    }

    /**
     * @expectedException \Fxp\Component\Resource\Exception\UnexpectedTypeException
     * @expectedExceptionMessageRegExp /Expected argument of type "stdClass", "([\w\_0-9]+)" given/
     */
    public function testValidateObjectResourceWithoutAllowedForm()
    {
        /* @var FormInterface|MockObject */
        $form = $this->getMockBuilder(FormInterface::class)->getMock();
        $form->expects($this->never())
            ->method('getData');

        ResourceUtil::validateObjectResource($form, \stdClass::class, 0, false);
    }
}
