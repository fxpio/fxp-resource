<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Tests\Functional\Handler;

use Fxp\Component\Resource\Handler\FormConfig;
use Fxp\Component\Resource\Handler\FormConfigList;
use Fxp\Component\Resource\Handler\FormConfigListInterface;
use Fxp\Component\Resource\Tests\Fixtures\Entity\Foo;
use Fxp\Component\Resource\Tests\Fixtures\Form\FooType;
use PHPUnit\Framework\MockObject\Matcher\InvokedRecorder;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for Functional tests for Form Handler.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class FormHandlerTest extends AbstractFormHandlerTest
{
    public function testEmptyCurrentRequestException(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The current request is required in request stack');

        $this->createFormHandler();
    }

    public function testProcessForm(): void
    {
        $data = [
            'name' => 'Bar',
            'detail' => 'Detail',
        ];
        $request = Request::create('test', Request::METHOD_POST, [], [], [], [], json_encode($data));
        $handler = $this->createFormHandler($request);

        $object = new Foo();
        $config = new FormConfig(FooType::class);

        $form = $handler->processForm($config, $object);

        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form);
        $this->assertInstanceOf(\get_class($object), $form->getData());
        $this->assertSame($object, $form->getData());
        $this->assertTrue($form->isSubmitted());
    }

    public function testProcessForms(): void
    {
        $data = [
            'transaction' => true,
            'records' => [
                [
                    'name' => 'Bar 1',
                    'detail' => 'Detail 1',
                ],
                [
                    'name' => 'Bar 2',
                    'detail' => 'Detail 2',
                ],
                [
                    'name' => 'Bar 3',
                    'detail' => 'Detail 3',
                ],
            ],
        ];
        $request = Request::create('test', Request::METHOD_POST, [], [], [], [], json_encode($data));
        $handler = $this->createFormHandler($request);

        $objects = [
            new Foo(),
            new Foo(),
            new Foo(),
        ];
        $config = $this->createFormConfigList($objects, $this->once());

        $forms = $handler->processForms($config);

        $this->assertSame(\count($data['records']), \count($forms));
        $this->assertTrue(\count($forms) > 0);

        foreach ($forms as $i => $form) {
            $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form);
            $this->assertInstanceOf(\get_class($objects[$i]), $form->getData());
            $this->assertSame($objects[$i], $form->getData());
            $this->assertTrue($form->isSubmitted());
        }
    }

    public function testProcessFormsWithoutRecordsField(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\InvalidResourceException::class);
        $this->expectExceptionMessage('The "records" field is required');

        $data = [
            [
                'name' => 'Bar 1',
                'detail' => 'Detail 1',
            ],
            [
                'name' => 'Bar 2',
                'detail' => 'Detail 2',
            ],
            [
                'name' => 'Bar 3',
                'detail' => 'Detail 3',
            ],
        ];
        $request = Request::create('test', Request::METHOD_POST, [], [], [], [], json_encode($data));
        $handler = $this->createFormHandler($request);

        $objects = [
            new Foo(),
            new Foo(),
            new Foo(),
        ];
        $config = $this->createFormConfigList($objects, $this->never());

        $handler->processForms($config);
    }

    public function testProcessFormsWithDifferentSize(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\InvalidResourceException::class);
        $this->expectExceptionMessage('The size of the request data list (1) is different that the object instance list (2)');

        $data = [
            'transaction' => true,
            'records' => [
                [
                    'name' => 'Bar 1',
                    'detail' => 'Detail 1',
                ],
            ],
        ];
        $request = Request::create('test', Request::METHOD_POST, [], [], [], [], json_encode($data));
        $handler = $this->createFormHandler($request);

        $objects = [
            new Foo(),
            new Foo(),
        ];
        $config = $this->createFormConfigList($objects, $this->once());

        $handler->processForms($config);
    }

    public function getLimits()
    {
        return [
            [10, null, 5,    5],
            [10, 5,    null, 5],

            [10, null, 0,    1],
            [10, 0,    null, 1],
        ];
    }

    /**
     * @dataProvider getLimits
     *
     * @param int      $size
     * @param null|int $defaultLimit
     * @param null|int $methodLimit
     */
    public function testLimitMethod($size, $defaultLimit, $methodLimit): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\InvalidResourceException::class);
        $this->expectExceptionMessageRegExp('/The list of resource sent exceeds the permitted limit \\(\\d+\\)/');

        $data = [];
        $objects = [];

        for ($i = 0; $i < $size; ++$i) {
            $data[] = [
                'name' => 'Bar '.($i + 1),
                'detail' => 'Detail '.($i + 1),
            ];
            $objects[] = new Foo();
        }
        $data = [
            'transaction' => true,
            'records' => $data,
        ];

        $request = Request::create('test', Request::METHOD_POST, [], [], [], [], json_encode($data));
        $handler = $this->createFormHandler($request, $defaultLimit);

        $config = $this->createFormConfigList($objects, $this->any());
        $config->setLimit($methodLimit);

        $handler->processForms($config, $objects);
    }

    /**
     * @param array           $objects
     * @param InvokedRecorder $count
     *
     * @return FormConfigListInterface|MockObject
     */
    protected function createFormConfigList($objects, InvokedRecorder $count)
    {
        $config = $this->getMockBuilder(FormConfigList::class)
            ->setConstructorArgs([FooType::class, [], Request::METHOD_POST, 'json'])
            ->getMockForAbstractClass()
        ;
        $config->expects($count)
            ->method('convertObjects')
            ->will($this->returnValue($objects))
        ;

        return $config;
    }
}
