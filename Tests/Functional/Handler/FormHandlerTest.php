<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Tests\Functional\Handler;

use Sonatra\Bundle\ResourceBundle\Handler\FormConfig;
use Sonatra\Bundle\ResourceBundle\Handler\FormConfigList;
use Sonatra\Bundle\ResourceBundle\Handler\FormConfigListInterface;
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Entity\Foo;
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Form\FooType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for Functional tests for Form Handler.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class FormHandlerTest extends AbstractFormHandlerTest
{
    /**
     * @expectedException \Sonatra\Bundle\ResourceBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage The current request is required in request stack
     */
    public function testEmptyCurrentRequestException()
    {
        $this->createFormHandler();
    }

    public function testProcessForm()
    {
        $data = array(
            'name' => 'Bar',
            'detail' => 'Detail',
        );
        $request = Request::create('test', Request::METHOD_POST, array(), array(), array(), array(), json_encode($data));
        $handler = $this->createFormHandler($request);

        $object = new Foo();
        $config = new FormConfig(FooType::class);

        $form = $handler->processForm($config, $object);

        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form);
        $this->assertInstanceOf(get_class($object), $form->getData());
        $this->assertSame($object, $form->getData());
        $this->assertTrue($form->isSubmitted());
    }

    public function testProcessForms()
    {
        $data = array(
            'transaction' => true,
            'records' => array(
                array(
                    'name' => 'Bar 1',
                    'detail' => 'Detail 1',
                ),
                array(
                    'name' => 'Bar 2',
                    'detail' => 'Detail 2',
                ),
                array(
                    'name' => 'Bar 3',
                    'detail' => 'Detail 3',
                ),
            ),
        );
        $request = Request::create('test', Request::METHOD_POST, array(), array(), array(), array(), json_encode($data));
        $handler = $this->createFormHandler($request);

        $objects = array(
            new Foo(),
            new Foo(),
            new Foo(),
        );
        $config = $this->createFormConfigList($objects, $this->once());

        $forms = $handler->processForms($config);

        $this->assertSame(count($data['records']), count($forms));
        $this->assertTrue(count($forms) > 0);

        foreach ($forms as $i => $form) {
            $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form);
            $this->assertInstanceOf(get_class($objects[$i]), $form->getData());
            $this->assertSame($objects[$i], $form->getData());
            $this->assertTrue($form->isSubmitted());
        }
    }

    /**
     * @expectedException \Sonatra\Bundle\ResourceBundle\Exception\InvalidResourceException
     * @expectedExceptionMessage The records field is required
     */
    public function testProcessFormsWithoutRecordsField()
    {
        $data = array(
            array(
                'name' => 'Bar 1',
                'detail' => 'Detail 1',
            ),
            array(
                'name' => 'Bar 2',
                'detail' => 'Detail 2',
            ),
            array(
                'name' => 'Bar 3',
                'detail' => 'Detail 3',
            ),
        );
        $request = Request::create('test', Request::METHOD_POST, array(), array(), array(), array(), json_encode($data));
        $handler = $this->createFormHandler($request);

        $objects = array(
            new Foo(),
            new Foo(),
            new Foo(),
        );
        $config = $this->createFormConfigList($objects, $this->never());

        $handler->processForms($config);
    }

    /**
     * @expectedException \Sonatra\Bundle\ResourceBundle\Exception\InvalidResourceException
     * @expectedExceptionMessage The size of the request data list (1) is different that the object instance list (2)
     */
    public function testProcessFormsWithDifferentSize()
    {
        $data = array(
            'transaction' => true,
            'records' => array(
                array(
                    'name' => 'Bar 1',
                    'detail' => 'Detail 1',
                ),
            ),
        );
        $request = Request::create('test', Request::METHOD_POST, array(), array(), array(), array(), json_encode($data));
        $handler = $this->createFormHandler($request);

        $objects = array(
            new Foo(),
            new Foo(),
        );
        $config = $this->createFormConfigList($objects, $this->once());

        $handler->processForms($config);
    }

    public function getLimits()
    {
        return array(
            array(10, null, 5,    5),
            array(10, 5,    null, 5),

            array(10, null, 0,    1),
            array(10, 0,    null, 1),
        );
    }

    /**
     * @dataProvider getLimits
     *
     * @param int      $size
     * @param int|null $defaultLimit
     * @param int|null $methodLimit
     *
     * @expectedException \Sonatra\Bundle\ResourceBundle\Exception\InvalidResourceException
     * @expectedExceptionMessageRegExp /The list of resource sent exceeds the permitted limit \(\d+\)/
     */
    public function testLimitMethod($size, $defaultLimit, $methodLimit)
    {
        $data = array();
        $objects = array();

        for ($i = 0; $i < $size; ++$i) {
            $data[] = array(
                'name' => 'Bar '.($i + 1),
                'detail' => 'Detail '.($i + 1),
            );
            $objects[] = new Foo();
        }
        $data = array(
            'transaction' => true,
            'records' => $data,
        );

        $request = Request::create('test', Request::METHOD_POST, array(), array(), array(), array(), json_encode($data));
        $handler = $this->createFormHandler($request, $defaultLimit);

        $config = $this->createFormConfigList($objects, $this->any());
        $config->setLimit($methodLimit);

        $handler->processForms($config, $objects);
    }

    /**
     * @param array                                                 $objects
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedRecorder $count
     *
     * @return FormConfigListInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createFormConfigList($objects, \PHPUnit_Framework_MockObject_Matcher_InvokedRecorder $count)
    {
        $config = $this->getMockBuilder(FormConfigList::class)
            ->setConstructorArgs(array(FooType::class, array(), Request::METHOD_POST, 'json'))
            ->getMockForAbstractClass();
        $config->expects($count)
            ->method('convertObjects')
            ->will($this->returnValue($objects));

        return $config;
    }
}
