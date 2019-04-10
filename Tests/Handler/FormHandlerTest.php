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

use Fxp\Component\Resource\Converter\ConverterInterface;
use Fxp\Component\Resource\Converter\ConverterRegistryInterface;
use Fxp\Component\Resource\Handler\FormConfigInterface;
use Fxp\Component\Resource\Handler\FormConfigListInterface;
use Fxp\Component\Resource\Handler\FormHandler;
use Fxp\Component\Resource\Handler\FormHandlerInterface;
use Fxp\Component\Resource\ResourceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Tests case for Form Config Handler.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class FormHandlerTest extends TestCase
{
    /**
     * @var ConverterRegistryInterface|MockObject
     */
    protected $converterRegistry;

    /**
     * @var FormFactoryInterface|MockObject
     */
    protected $formFactory;

    /**
     * @var Request|MockObject
     */
    protected $request;

    /**
     * @var Translator
     */
    protected $translator;

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
        /* @var RequestStack|MockObject $requestStack */
        $requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->will($this->returnValue($request));

        $this->converterRegistry = $this->getMockBuilder(ConverterRegistryInterface::class)->getMock();
        $this->formFactory = $this->getMockBuilder(FormFactoryInterface::class)->getMock();
        $this->request = $request;
        $this->defaultLimit = 10;

        $this->translator = new Translator('en');
        $ref = new \ReflectionClass(ResourceInterface::class);
        $this->translator->addResource('xml', realpath(\dirname($ref->getFileName()).'/Resources/translations/FxpResource.en.xlf'), 'en', 'FxpResource');
        $this->translator->addLoader('xml', new XliffFileLoader());

        $this->formHandler = new FormHandler(
            $this->converterRegistry,
            $this->formFactory,
            $requestStack,
            $this->translator,
            $this->defaultLimit
        );
    }

    /**
     * @expectedException \Fxp\Component\Resource\Exception\InvalidArgumentException
     * @expectedExceptionMessage The current request is required in request stack
     */
    public function testBuildFormHandlerWithoutCurrentRequest()
    {
        /* @var RequestStack|MockObject $requestStack */
        $requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $requestStack->expects($this->once())
            ->method('getCurrentRequest');

        new FormHandler(
            $this->converterRegistry,
            $this->formFactory,
            $requestStack,
            $this->translator,
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
        $config = $this->configureProcessForms([$object], FormConfigInterface::class, '{}');

        $form = $this->formHandler->processForm($config, $object);
        $this->assertInstanceOf(FormInterface::class, $form);
    }

    public function testProcessForms()
    {
        $objects = [
            new \stdClass(),
        ];
        $config = $this->configureProcessForms($objects, FormConfigListInterface::class, '{records: [{}]}');

        $forms = $this->formHandler->processForms($config, $objects);

        $this->assertInternalType('array', $forms);
        $this->assertCount(1, $forms);
        $this->assertInstanceOf(FormInterface::class, $forms[0]);
    }

    public function testProcessFormWithCreationOfNewObject()
    {
        $objects = [
            new \stdClass(),
        ];
        $config = $this->configureProcessForms($objects, FormConfigListInterface::class, '{records: [{}]}');
        $config->expects($this->once())
            ->method('convertObjects')
            ->with($objects)
            ->will($this->returnValue($objects));

        $forms = $this->formHandler->processForms($config, []);

        $this->assertInternalType('array', $forms);
        $this->assertCount(1, $forms);
        $this->assertInstanceOf(FormInterface::class, $forms[0]);
    }

    /**
     * @expectedException \Fxp\Component\Resource\Exception\InvalidResourceException
     * @expectedExceptionMessage The list of resource sent exceeds the permitted limit (1)
     */
    public function testProcessFormWithExceededPermittedLimit()
    {
        $objects = [
            new \stdClass(),
            new \stdClass(),
        ];
        $config = $this->configureProcessForms($objects, FormConfigListInterface::class, '{records: [{}]}', 1);

        $this->formHandler->processForms($config, $objects);
    }

    /**
     * @expectedException \Fxp\Component\Resource\Exception\InvalidResourceException
     * @expectedExceptionMessage The size of the request data list (0) is different that the object instance list (1)
     */
    public function testProcessFormWithDifferentSize()
    {
        $objects = [
            new \stdClass(),
        ];
        $config = $this->configureProcessForms([], FormConfigListInterface::class, '{records: [{}]}');
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
     * @return FormConfigInterface|FormConfigListInterface|MockObject
     */
    protected function configureProcessForms(array $objects, $configClass, $requestContent, $limit = null)
    {
        /* @var FormConfigInterface|FormConfigListInterface|MockObject $config */
        $config = $this->getMockBuilder($configClass)->getMock();

        if (FormConfigListInterface::class === $configClass) {
            $config->expects($this->once())
                ->method('getLimit')
                ->will($this->returnValue($limit));
        }

        /* @var ConverterInterface|MockObject $converter */
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
            $dataList = [
                'records' => $objects,
            ];
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

        if (\count($objects) > 0 && null === $limit) {
            $config->expects($this->once())
                ->method('getType')
                ->will($this->returnValue(FormType::class));

            $config->expects($this->once())
                ->method('getSubmitClearMissing')
                ->will($this->returnValue(false));

            $config->expects($this->once())
                ->method('getOptions')
                ->will($this->returnValue([]));

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
