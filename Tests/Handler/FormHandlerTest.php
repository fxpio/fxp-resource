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

use Sonatra\Component\Resource\Converter\ConverterInterface;
use Sonatra\Component\Resource\Converter\ConverterRegistryInterface;
use Sonatra\Component\Resource\Handler\FormConfigInterface;
use Sonatra\Component\Resource\Handler\FormConfigListInterface;
use Sonatra\Component\Resource\Handler\FormHandler;
use Sonatra\Component\Resource\Handler\FormHandlerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests case for Form Config Handler.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class FormHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConverterRegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $converterRegistry;

    /**
     * @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formFactory;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var int
     */
    protected $defaultLimit;

    /**
     * @var FormHandlerInterface
     */
    protected $formHandler;

    protected function setUp()
    {
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        /* @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->will($this->returnValue($request));

        $this->converterRegistry = $this->getMockBuilder(ConverterRegistryInterface::class)->getMock();
        $this->formFactory = $this->getMockBuilder(FormFactoryInterface::class)->getMock();
        $this->request = $request;
        $this->defaultLimit = 10;

        $this->formHandler = new FormHandler(
            $this->converterRegistry,
            $this->formFactory,
            $requestStack,
            $this->defaultLimit
        );
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\InvalidArgumentException
     * @expectedExceptionMessage The current request is required in request stack
     */
    public function testBuildFormHandlerWithoutCurrentRequest()
    {
        /* @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $requestStack->expects($this->once())
            ->method('getCurrentRequest');

        new FormHandler(
            $this->converterRegistry,
            $this->formFactory,
            $requestStack,
            $this->defaultLimit
        );
    }

    public function testGetDefaultLimit()
    {
        $this->assertSame($this->defaultLimit, $this->formHandler->getDefaultLimit());
    }

    public function testProcessForm()
    {
        $object = new \stdClass();
        $config = $this->configureProcessForms(array($object), FormConfigInterface::class, '{}');

        $form = $this->formHandler->processForm($config, $object);
        $this->assertInstanceOf(FormInterface::class, $form);
    }

    public function testProcessForms()
    {
        $objects = array(
            new \stdClass(),
        );
        $config = $this->configureProcessForms($objects, FormConfigListInterface::class, '{records: [{}]}');

        $forms = $this->formHandler->processForms($config, $objects);

        $this->assertTrue(is_array($forms));
        $this->assertCount(1, $forms);
        $this->assertInstanceOf(FormInterface::class, $forms[0]);
    }

    public function testProcessFormWithCreationOfNewObject()
    {
        $objects = array(
            new \stdClass(),
        );
        $config = $this->configureProcessForms($objects, FormConfigListInterface::class, '{records: [{}]}');
        $config->expects($this->once())
            ->method('convertObjects')
            ->with($objects)
            ->will($this->returnValue($objects));

        $forms = $this->formHandler->processForms($config, array());

        $this->assertTrue(is_array($forms));
        $this->assertCount(1, $forms);
        $this->assertInstanceOf(FormInterface::class, $forms[0]);
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\InvalidResourceException
     * @expectedExceptionMessage The list of resource sent exceeds the permitted limit (1)
     */
    public function testProcessFormWithExceededPermittedLimit()
    {
        $objects = array(
            new \stdClass(),
            new \stdClass(),
        );
        $config = $this->configureProcessForms($objects, FormConfigListInterface::class, '{records: [{}]}', 1);

        $this->formHandler->processForms($config, $objects);
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\InvalidResourceException
     * @expectedExceptionMessage The size of the request data list (0) is different that the object instance list (1)
     */
    public function testProcessFormWithDifferentSize()
    {
        $objects = array(
            new \stdClass(),
        );
        $config = $this->configureProcessForms(array(), FormConfigListInterface::class, '{records: [{}]}');
        $this->formHandler->processForms($config, $objects);
    }

    /**
     * Configure the handler mocks to process forms.
     *
     * @param array    $objects        The objects
     * @param string   $configClass    The config classname
     * @param string   $requestContent The content of request
     * @param int|null $limit          The config limit
     *
     * @return FormConfigInterface|FormConfigListInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function configureProcessForms(array $objects, $configClass, $requestContent, $limit = null)
    {
        /* @var FormConfigInterface|FormConfigListInterface|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->getMockBuilder($configClass)->getMock();

        if (FormConfigListInterface::class === $configClass) {
            $config->expects($this->once())
                ->method('getLimit')
                ->will($this->returnValue($limit));
        }

        /* @var ConverterInterface|\PHPUnit_Framework_MockObject_MockObject $converter */
        $converter = $this->getMockBuilder(ConverterInterface::class)->getMock();

        $config->expects($this->once())
            ->method('getConverter')
            ->will($this->returnValue('json'));

        $this->converterRegistry->expects($this->once())
            ->method('get')
            ->will($this->returnValue($converter));

        $this->request->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($requestContent));

        if (FormConfigListInterface::class === $configClass) {
            $dataList = array(
                'records' => $objects,
            );
        } else {
            $dataList = $objects[0];
        }

        $converter->expects($this->once())
            ->method('convert')
            ->with($requestContent)
            ->will($this->returnValue($dataList));

        if (FormConfigListInterface::class === $configClass) {
            $config->expects($this->once())
                ->method('findList')
                ->with($dataList)
                ->will($this->returnValue($dataList['records']));
        }

        if (count($objects) > 0 && null === $limit) {
            $config->expects($this->once())
                ->method('getType')
                ->will($this->returnValue(FormType::class));

            $config->expects($this->once())
                ->method('getSubmitClearMissing')
                ->will($this->returnValue(false));

            $config->expects($this->once())
                ->method('getOptions')
                ->will($this->returnValue(array()));

            $form = $this->getMockBuilder(FormInterface::class)->getMock();
            $form->expects($this->any())
                ->method('getData')
                ->will($this->returnValue($objects[0]));

            $this->formFactory->expects($this->at(0))
                ->method('create')
                ->will($this->returnValue($form));

            $form->expects($this->once())
                ->method('submit')
                ->with($objects[0]);
        }

        return $config;
    }
}
