<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Tests\Functional\Domain;

use Fxp\Component\Resource\Domain\DomainInterface;
use Fxp\Component\Resource\Event\PostUpsertsEvent;
use Fxp\Component\Resource\Event\PreUpsertsEvent;
use Fxp\Component\Resource\ResourceInterface;
use Fxp\Component\Resource\ResourceListInterface;
use Fxp\Component\Resource\ResourceListStatutes;
use Fxp\Component\Resource\ResourceStatutes;
use Fxp\Component\Resource\Tests\Fixtures\Entity\Foo;
use Fxp\Component\Resource\Tests\Fixtures\Form\FooType;
use Symfony\Component\Form\FormInterface;

/**
 * Functional tests for upsert methods of Domain with form resources.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class DomainUpsertFormTest extends AbstractDomainTest
{
    public function getUpsertType()
    {
        return [
            [false],
            [true],
        ];
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertWithErrorValidation($isUpdate): void
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $foo = $this->insertResource($domain);
            $foo->setName(null);
        } else {
            $this->loadFixtures([]);
            /** @var Foo $foo */
            $foo = $domain->newInstance();
        }

        $form = $this->buildForm($foo, [
            'name' => null,
            'description' => 'test',
        ]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreUpsertsEvent::class, function (PreUpsertsEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostUpsertsEvent::class, function (PostUpsertsEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        static::assertCount($isUpdate ? 1 : 0, $domain->getRepository()->findAll());

        $resource = $domain->upsert($form);
        static::assertCount(0, $resource->getErrors());
        static::assertCount(1, $resource->getFormErrors());

        $errors = $resource->getFormErrors();
        static::assertRegExp('/This value should not be blank./', $errors[0]->getMessage());

        static::assertTrue($preEvent);
        static::assertTrue($postEvent);

        static::assertCount($isUpdate ? 1 : 0, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertWithErrorDatabase($isUpdate): void
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $foo = $this->insertResource($domain);
            $foo->setDetail(null);
        } else {
            $this->loadFixtures([]);
            /** @var Foo $foo */
            $foo = $domain->newInstance();
            $foo->setName('Bar');
        }

        $form = $this->buildForm($foo, [
            'description' => 'test',
        ]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreUpsertsEvent::class, function (PreUpsertsEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostUpsertsEvent::class, function (PostUpsertsEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        static::assertCount($isUpdate ? 1 : 0, $domain->getRepository()->findAll());

        $resource = $domain->upsert($form);
        static::assertFalse($resource->isValid());
        static::assertCount(1, $resource->getErrors());
        static::assertCount(0, $resource->getFormErrors());

        $errors = $resource->getErrors();
        static::assertRegExp($this->getIntegrityViolationMessage(), $errors[0]->getMessage());

        static::assertTrue($preEvent);
        static::assertTrue($postEvent);

        static::assertCount($isUpdate ? 1 : 0, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsert($isUpdate): void
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $foo = $this->insertResource($domain);
            $foo->setDetail(null);
        } else {
            $this->loadFixtures([]);
            /** @var Foo $foo */
            $foo = $domain->newInstance();
            $foo->setName('Bar');
        }

        $form = $this->buildForm($foo, [
            'name' => 'New Bar',
            'detail' => 'New Detail',
        ]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreUpsertsEvent::class, function (PreUpsertsEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostUpsertsEvent::class, function (PostUpsertsEvent $e) use (&$postEvent, $domain, $isUpdate): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame($isUpdate ? ResourceStatutes::UPDATED
                    : ResourceStatutes::CREATED, $resource->getStatus());
            }
        });

        static::assertCount($isUpdate ? 1 : 0, $domain->getRepository()->findAll());

        $resource = $domain->upsert($form);
        static::assertTrue($resource->isValid());
        static::assertCount(0, $resource->getErrors());
        static::assertCount(0, $resource->getFormErrors());

        static::assertTrue($preEvent);
        static::assertTrue($postEvent);

        static::assertCount(1, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertsWithErrorValidation($isUpdate): void
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            $forms = [
                $this->buildForm($objects[0], [
                    'name' => null,
                    'description' => 'test 1',
                ]),
                $this->buildForm($objects[1], [
                    'name' => null,
                    'description' => 'test 2',
                ]),
            ];
        } else {
            $this->loadFixtures([]);
            /** @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            /** @var Foo $foo2 */
            $foo2 = $domain->newInstance();

            $form1 = $this->buildForm($foo1, [
                'name' => null,
                'description' => 'test',
            ]);
            $form2 = $this->buildForm($foo2, [
                'description' => 'test',
            ]);
            $forms = [$form1, $form2];
        }

        $this->runTestUpsertsException($domain, $forms, '/This value should not be blank./', true, $isUpdate);
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertsWithErrorDatabase($isUpdate): void
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            $forms = [
                $this->buildForm($objects[0], [
                    'detail' => null,
                    'description' => 'test 1',
                ]),
                $this->buildForm($objects[1], [
                    'detail' => null,
                    'description' => 'test 2',
                ]),
            ];
        } else {
            $this->loadFixtures([]);
            /** @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            /** @var Foo $foo2 */
            $foo2 = $domain->newInstance();

            $form1 = $this->buildForm($foo1, [
                'name' => 'Bar',
            ]);
            $form2 = $this->buildForm($foo2, [
                'name' => 'Bar',
            ]);
            $forms = [$form1, $form2];
        }

        $this->runTestUpsertsException($domain, $forms, $this->getIntegrityViolationMessage(), false, $isUpdate);
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpserts($isUpdate): void
    {
        $this->runTestUpserts(false, $isUpdate);
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertsAutoCommitWithErrorValidationAndErrorDatabase($isUpdate): void
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            $forms = [
                $this->buildForm($objects[0], [
                    'name' => null,
                    'description' => 'test 1',
                ]),
                $this->buildForm($objects[1], [
                    'detail' => null,
                    'description' => 'test 2',
                ]),
            ];
        } else {
            $this->loadFixtures([]);
            /** @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            /** @var Foo $foo2 */
            $foo2 = $domain->newInstance();

            $form1 = $this->buildForm($foo1, [
                'name' => null,
            ]);
            $form2 = $this->buildForm($foo2, [
                'name' => 'Bar',
            ]);

            $forms = [$form1, $form2];
        }

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreUpsertsEvent::class, function (PreUpsertsEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostUpsertsEvent::class, function (PostUpsertsEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        static::assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());

        $resources = $domain->upserts($forms, true);
        static::assertInstanceOf(ResourceListInterface::class, $resources);

        static::assertTrue($resources->hasErrors());
        $errors1 = $resources->get(0)->getFormErrors();
        static::assertRegExp('/This value should not be blank./', $errors1[0]->getMessage());
        static::assertRegExp($this->getIntegrityViolationMessage(), $resources->get(1)->getErrors()->get(0)->getMessage());

        static::assertTrue($preEvent);
        static::assertTrue($postEvent);

        static::assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertsAutoCommitWithErrorDatabase($isUpdate): void
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            $forms = [
                $this->buildForm($objects[0], [
                    'detail' => null,
                    'description' => 'test 1',
                ]),
                $this->buildForm($objects[1], [
                    'description' => 'test 2',
                ]),
            ];
        } else {
            $this->loadFixtures([]);
            /** @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            /** @var Foo $foo2 */
            $foo2 = $domain->newInstance();

            $form1 = $this->buildForm($foo1, [
                'name' => 'Bar',
            ]);
            $form2 = $this->buildForm($foo2, [
                'name' => 'Bar',
            ]);

            $forms = [$form1, $form2];
        }

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreUpsertsEvent::class, function (PreUpsertsEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostUpsertsEvent::class, function (PostUpsertsEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        static::assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());

        $resources = $domain->upserts($forms, true);
        static::assertInstanceOf(ResourceListInterface::class, $resources);

        static::assertTrue($resources->hasErrors());
        static::assertCount(0, $resources->get(0)->getFormErrors());
        static::assertCount(0, $resources->get(1)->getFormErrors());

        static::assertCount(1, $resources->get(0)->getErrors());
        static::assertCount(1, $resources->get(1)->getErrors());

        static::assertRegExp($this->getIntegrityViolationMessage(), $resources->get(0)->getErrors()->get(0)->getMessage());
        static::assertRegExp('/Caused by previous internal database error/', $resources->get(1)->getErrors()->get(0)->getMessage());

        static::assertTrue($preEvent);
        static::assertTrue($postEvent);

        static::assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertsAutoCommitWithErrorValidationAndSuccess($isUpdate): void
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            $forms = [
                $this->buildForm($objects[0], [
                    'name' => null,
                    'description' => 'test 1',
                ]),
                $this->buildForm($objects[1], [
                    'name' => 'New Bar 2',
                    'description' => 'test 2',
                ]),
            ];
        } else {
            $this->loadFixtures([]);
            /** @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            /** @var Foo $foo2 */
            $foo2 = $domain->newInstance();

            $form1 = $this->buildForm($foo1, [
                'name' => null,
            ]);
            $form2 = $this->buildForm($foo2, [
                'name' => 'Bar',
                'detail' => 'Detail',
            ]);

            $forms = [$form1, $form2];
        }

        static::assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());
        $resources = $domain->upserts($forms, true);
        static::assertCount($isUpdate ? 2 : 1, $domain->getRepository()->findAll());

        static::assertCount(2, $resources);
        static::assertInstanceOf(ResourceInterface::class, $resources->get(0));
        static::assertInstanceOf(ResourceInterface::class, $resources->get(1));

        static::assertSame(ResourceListStatutes::MIXED, $resources->getStatus());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        static::assertSame($isUpdate ? ResourceStatutes::UPDATED
            : ResourceStatutes::CREATED, $resources->get(1)->getStatus());
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertsAutoCommit($isUpdate): void
    {
        $this->runTestUpserts(true, $isUpdate);
    }

    public function runTestUpserts($autoCommit, $isUpdate): void
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            $forms = [
                $this->buildForm($objects[0], [
                    'name' => 'New Bar 1',
                    'detail' => 'New Detail 1',
                ]),
                $this->buildForm($objects[1], [
                    'name' => 'New Bar 2',
                    'detail' => 'New Detail 2',
                ]),
            ];
        } else {
            $this->loadFixtures([]);
            /** @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            /** @var Foo $foo2 */
            $foo2 = $domain->newInstance();

            $form1 = $this->buildForm($foo1, [
                'name' => 'Bar 1',
                'detail' => 'Detail 1',
            ]);
            $form2 = $this->buildForm($foo2, [
                'name' => 'Bar 2',
                'detail' => 'Detail 2',
            ]);

            $forms = [$form1, $form2];
        }

        static::assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());
        $resources = $domain->upserts($forms, $autoCommit);
        static::assertCount(2, $domain->getRepository()->findAll());

        static::assertCount(2, $resources);
        static::assertInstanceOf(ResourceInterface::class, $resources->get(0));
        static::assertInstanceOf(ResourceInterface::class, $resources->get(1));

        static::assertSame(ResourceListStatutes::SUCCESSFULLY, $resources->getStatus());
        static::assertSame($isUpdate ? ResourceStatutes::UPDATED
            : ResourceStatutes::CREATED, $resources->get(0)->getStatus());
        static::assertTrue($resources->get(0)->isValid());
        static::assertSame($isUpdate ? ResourceStatutes::UPDATED
            : ResourceStatutes::CREATED, $resources->get(1)->getStatus());
        static::assertTrue($resources->get(1)->isValid());
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertWithMissingFormSubmission($isUpdate): void
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $object = $this->insertResource($domain);
            $form = $this->buildForm($object, [
                'name' => null,
                'detail' => 'New Detail 1',
            ]);
        } else {
            /** @var Foo $foo */
            $foo = $domain->newInstance();
            $form = $this->formFactory->create(FooType::class, $foo, []);

            $this->loadFixtures([]);
        }

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreUpsertsEvent::class, function (PreUpsertsEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostUpsertsEvent::class, function (PostUpsertsEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        static::assertCount($isUpdate ? 1 : 0, $domain->getRepository()->findAll());

        $resource = $domain->upsert($form);
        static::assertCount(0, $resource->getErrors());
        static::assertCount(1, $resource->getFormErrors());
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testErrorIdentifier($isUpdate): void
    {
        $this->loadFixtures([]);

        $domain = $this->createDomain();

        if ($isUpdate) {
            /** @var Foo $object */
            $object = $domain->newInstance();
            $form = $this->buildForm($object, [
                'name' => 'Bar',
                'detail' => 'Detail',
            ]);
        } else {
            $object = $this->insertResource($domain);
            $object->setDetail(null);
            $form = $this->buildForm($object, [
                'name' => 'New Bar',
                'detail' => 'New Detail',
            ]);
        }

        $resource = $domain->upsert($form);
        static::assertTrue($resource->isValid());
    }

    protected function runTestUpsertsException(DomainInterface $domain, array $objects, $errorMessage, $autoCommit = false, $isUpdate = false): void
    {
        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreUpsertsEvent::class, function (PreUpsertsEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostUpsertsEvent::class, function (PostUpsertsEvent $e) use (&$postEvent, $autoCommit, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            $resources = $e->getResources();
            $this->assertCount(2, $resources);
            $this->assertSame(ResourceStatutes::ERROR, $resources[0]->getStatus());
            $this->assertSame($autoCommit ? ResourceStatutes::CANCELED
                : ResourceStatutes::ERROR, $resources[1]->getStatus());
        });

        static::assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());

        $resources = $domain->upserts($objects);
        static::assertInstanceOf(ResourceListInterface::class, $resources);
        static::assertTrue($resources->hasErrors());

        $errors = $autoCommit
            ? $resources->get(0)->getFormErrors()
            : $resources->getErrors();
        static::assertRegExp($errorMessage, $errors[0]->getMessage());

        static::assertTrue($preEvent);
        static::assertTrue($postEvent);

        static::assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());
        static::assertSame($autoCommit ? ResourceListStatutes::MIXED
            : ResourceListStatutes::ERROR, $resources->getStatus());
    }

    /**
     * @param object $object
     * @param array  $data
     *
     * @return FormInterface
     */
    protected function buildForm($object, array $data)
    {
        $form = $this->formFactory->create(FooType::class, $object, []);
        $form->submit($data, false);

        return $form;
    }
}
