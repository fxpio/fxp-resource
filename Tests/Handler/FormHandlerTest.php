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
use Symfony\Component\Form\FormBuilderInterface;
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
 *
 * @internal
 */
final class FormHandlerTest extends TestCase
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
     * @var MockObject|Request
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

    protected function setUp(): void
    {
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        /** @var MockObject|RequestStack $requestStack */
        $requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $requestStack->expects(static::any())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

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

    public function testBuildFormHandlerWithoutCurrentRequest(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The current request is required in request stack');

        /** @var MockObject|RequestStack $requestStack */
        $requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $requestStack->expects(static::once())
            ->method('getCurrentRequest')
        ;

        new FormHandler(
            $this->converterRegistry,
            $this->formFactory,
            $requestStack,
            $this->translator,
            $this->defaultLimit
        );
    }

    public function testGetDefaultLimit(): void
    {
        static::assertSame($this->defaultLimit, $this->formHandler->getDefaultLimit());
    }

    public function testProcessForm(): void
    {
        $object = new \stdClass();
        $object->foo = 'bar';
        $config = $this->configureProcessForms([$object], FormConfigInterface::class, '{}');

        $form = $this->formHandler->processForm($config, $object);
        static::assertInstanceOf(FormInterface::class, $form);
    }

    public function testProcessForms(): void
    {
        $object = new \stdClass();
        $object->foo = 'bar';
        $objects = [
            $object,
        ];
        $config = $this->configureProcessForms($objects, FormConfigListInterface::class, '{records: [{}]}');

        $forms = $this->formHandler->processForms($config, $objects);

        static::assertInternalType('array', $forms);
        static::assertCount(1, $forms);
        static::assertInstanceOf(FormInterface::class, $forms[0]);
    }

    public function testProcessFormWithCreationOfNewObject(): void
    {
        $objects = [
            new \stdClass(),
        ];
        $config = $this->configureProcessForms($objects, FormConfigListInterface::class, '{records: [{}]}');
        $config->expects(static::once())
            ->method('convertObjects')
            ->with($objects)
            ->willReturn($objects)
        ;

        $forms = $this->formHandler->processForms($config, []);

        static::assertInternalType('array', $forms);
        static::assertCount(1, $forms);
        static::assertInstanceOf(FormInterface::class, $forms[0]);
    }

    public function testProcessFormWithExceededPermittedLimit(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\InvalidResourceException::class);
        $this->expectExceptionMessage('The list of resource sent exceeds the permitted limit (1)');

        $objects = [
            new \stdClass(),
            new \stdClass(),
        ];
        $config = $this->configureProcessForms($objects, FormConfigListInterface::class, '{records: [{}]}', 1);

        $this->formHandler->processForms($config, $objects);
    }

    public function testProcessFormWithDifferentSize(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\InvalidResourceException::class);
        $this->expectExceptionMessage('The size of the request data list (0) is different that the object instance list (1)');

        $objects = [
            new \stdClass(),
        ];
        $config = $this->configureProcessForms([], FormConfigListInterface::class, '{records: [{}]}');
        $this->formHandler->processForms($config, $objects);
    }

    public function testProcessFormWithBuilderHandler(): void
    {
        $objects = [
            new \stdClass(),
        ];
        $builderHandlerCalled = false;
        $config = $this->configureProcessForms($objects, FormConfigListInterface::class, '{records: [{}]}');
        $config->expects(static::once())
            ->method('convertObjects')
            ->with($objects)
            ->willReturn($objects)
        ;
        $config->expects(static::once())->method('getBuilderHandlers')->willReturn([
            static function () use (&$builderHandlerCalled): void {
                $builderHandlerCalled = true;
            },
        ]);

        $forms = $this->formHandler->processForms($config, []);

        static::assertInternalType('array', $forms);
        static::assertCount(1, $forms);
        static::assertInstanceOf(FormInterface::class, $forms[0]);
        static::assertTrue($builderHandlerCalled);
    }

    /**
     * Configure the handler mocks to process forms.
     *
     * @param array    $objects        The objects
     * @param string   $configClass    The config classname
     * @param string   $requestContent The content of request
     * @param null|int $limit          The config limit
     *
     * @return FormConfigInterface|FormConfigListInterface|MockObject
     */
    protected function configureProcessForms(array $objects, $configClass, $requestContent, $limit = null)
    {
        /** @var FormConfigInterface|FormConfigListInterface|MockObject $config */
        $config = $this->getMockBuilder($configClass)->getMock();

        if (FormConfigListInterface::class === $configClass) {
            $config->expects(static::once())
                ->method('getLimit')
                ->willReturn($limit)
            ;
        }

        /** @var ConverterInterface|MockObject $converter */
        $converter = $this->getMockBuilder(ConverterInterface::class)->getMock();

        $config->expects(static::once())
            ->method('getConverter')
            ->willReturn('json')
        ;

        $this->converterRegistry->expects(static::once())
            ->method('get')
            ->willReturn($converter)
        ;

        $this->request->expects(static::any())
            ->method('getContent')
            ->willReturn($requestContent)
        ;

        if (FormConfigListInterface::class === $configClass) {
            $dataList = [
                'records' => $objects,
            ];
            $expectedConvert = [
                'records' => array_map(static function ($value) {
                    return \is_object($value) ? get_object_vars($value) : $value;
                }, $objects),
            ];
            $expectedSubmit = $dataList['records'][0] ?? [];
        } else {
            $dataList = $objects[0];
            $expectedConvert = get_object_vars($dataList);
            $expectedSubmit = $expectedConvert;
        }

        $converter->expects(static::once())
            ->method('convert')
            ->with($requestContent)
            ->willReturn($expectedConvert)
        ;

        if (FormConfigListInterface::class === $configClass) {
            $config->expects(static::once())
                ->method('findList')
                ->with($expectedConvert)
                ->willReturn($dataList['records'])
            ;
        }

        if (\count($objects) > 0 && null === $limit) {
            $config->expects(static::once())
                ->method('getType')
                ->willReturn(FormType::class)
            ;

            $config->expects(static::once())
                ->method('getSubmitClearMissing')
                ->willReturn(false)
            ;

            $config->expects(static::once())
                ->method('getOptions')
                ->willReturn([])
            ;

            $form = $this->getMockBuilder(FormInterface::class)->getMock();
            $form->expects(static::any())
                ->method('getData')
                ->willReturn($objects[0])
            ;

            $formBuilder = $this->getMockBuilder(FormBuilderInterface::class)->getMock();
            $formBuilder->expects(static::any())
                ->method('getForm')
                ->willReturn($form)
            ;
            $formBuilder->expects(static::any())
                ->method('addEventListener')
                ->willReturn($form)
            ;

            $this->formFactory->expects(static::at(0))
                ->method('createBuilder')
                ->willReturn($formBuilder)
            ;

            $form->expects(static::once())
                ->method('submit')
                ->with($expectedSubmit)
            ;
        }

        return $config;
    }
}
