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

use Sonatra\Component\Resource\Model\SoftDeletableInterface;
use Sonatra\Component\Resource\ResourceListInterface;
use Sonatra\Component\Resource\ResourceUtil;
use Symfony\Component\Form\FormInterface;

/**
 * Tests case for resource util.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ResourceUtilTest extends \PHPUnit_Framework_TestCase
{
    public function getAllowForm()
    {
        return array(
            array(true),
            array(false),
        );
    }

    /**
     * @dataProvider getAllowForm
     *
     * @param bool $allowForm The allow form value
     */
    public function testConvertObjectsToResourceList($allowForm)
    {
        $objects = array(
            new \stdClass(),
            new \stdClass(),
            new \stdClass(),
        );
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
        ResourceUtil::validateObjectResource(new \stdClass(), \stdClass::class, $allowForm);
    }

    /**
     * @dataProvider getAllowForm
     *
     * @param bool $allowForm The allow form value
     *
     * @expectedException \Sonatra\Component\Resource\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Sonatra\Component\Resource\Model\SoftDeletableInterface", "stdClass" given
     */
    public function testValidateObjectResourceWithInvalidClass($allowForm)
    {
        ResourceUtil::validateObjectResource(new \stdClass(), SoftDeletableInterface::class, 0, $allowForm);
    }

    public function testValidateObjectResourceWithForm()
    {
        /* @var FormInterface|\PHPUnit_Framework_MockObject_MockObject */
        $form = $this->getMockBuilder(FormInterface::class)->getMock();
        $form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(new \stdClass()));

        ResourceUtil::validateObjectResource($form, \stdClass::class, 0, true);
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\UnexpectedTypeException
     * @expectedExceptionMessageRegExp /Expected argument of type "stdClass", "([\w\_0-9]+)" given/
     */
    public function testValidateObjectResourceWithoutAllowedForm()
    {
        /* @var FormInterface|\PHPUnit_Framework_MockObject_MockObject */
        $form = $this->getMockBuilder(FormInterface::class)->getMock();
        $form->expects($this->never())
            ->method('getData');

        ResourceUtil::validateObjectResource($form, \stdClass::class, 0, false);
    }
}
