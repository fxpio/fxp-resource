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
use Fxp\Component\Resource\Event\ResourceEvent;
use Fxp\Component\Resource\ResourceEvents;
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
 */
class DomainUpsertFormTest extends AbstractDomainTest
{
    public function getUpsertType()
    {
        return array(
            array(false),
            array(true),
        );
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertWithErrorValidation($isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $foo = $this->insertResource($domain);
            $foo->setName(null);
        } else {
            $this->loadFixtures(array());
            /* @var Foo $foo */
            $foo = $domain->newInstance();
        }

        $form = $this->buildForm($foo, array(
            'name' => null,
            'description' => 'test',
        ));

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPSERTS, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPSERTS, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount($isUpdate ? 1 : 0, $domain->getRepository()->findAll());

        $resource = $domain->upsert($form);
        $this->assertCount(0, $resource->getErrors());
        $this->assertCount(1, $resource->getFormErrors());

        $errors = $resource->getFormErrors();
        $this->assertRegExp('/This value should not be blank./', $errors[0]->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount($isUpdate ? 1 : 0, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertWithErrorDatabase($isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $foo = $this->insertResource($domain);
            $foo->setDetail(null);
        } else {
            $this->loadFixtures(array());
            /* @var Foo $foo */
            $foo = $domain->newInstance();
            $foo->setName('Bar');
        }

        $form = $this->buildForm($foo, array(
            'description' => 'test',
        ));

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPSERTS, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPSERTS, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount($isUpdate ? 1 : 0, $domain->getRepository()->findAll());

        $resource = $domain->upsert($form);
        $this->assertFalse($resource->isValid());
        $this->assertCount(1, $resource->getErrors());
        $this->assertCount(0, $resource->getFormErrors());

        $errors = $resource->getErrors();
        $this->assertRegExp($this->getIntegrityViolationMessage(), $errors[0]->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount($isUpdate ? 1 : 0, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsert($isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $foo = $this->insertResource($domain);
            $foo->setDetail(null);
        } else {
            $this->loadFixtures(array());
            /* @var Foo $foo */
            $foo = $domain->newInstance();
            $foo->setName('Bar');
        }

        $form = $this->buildForm($foo, array(
            'name' => 'New Bar',
            'detail' => 'New Detail',
        ));

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPSERTS, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPSERTS, function (ResourceEvent $e) use (&$postEvent, $domain, $isUpdate) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame($isUpdate ? ResourceStatutes::UPDATED
                    : ResourceStatutes::CREATED, $resource->getStatus());
            }
        });

        $this->assertCount($isUpdate ? 1 : 0, $domain->getRepository()->findAll());

        $resource = $domain->upsert($form);
        $this->assertTrue($resource->isValid());
        $this->assertCount(0, $resource->getErrors());
        $this->assertCount(0, $resource->getFormErrors());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(1, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertsWithErrorValidation($isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            $forms = array(
                $this->buildForm($objects[0], array(
                    'name' => null,
                    'description' => 'test 1',
                )),
                $this->buildForm($objects[1], array(
                    'name' => null,
                    'description' => 'test 2',
                )),
            );
        } else {
            $this->loadFixtures(array());
            /* @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            /* @var Foo $foo2 */
            $foo2 = $domain->newInstance();

            $form1 = $this->buildForm($foo1, array(
                'name' => null,
                'description' => 'test',
            ));
            $form2 = $this->buildForm($foo2, array(
                'description' => 'test',
            ));
            $forms = array($form1, $form2);
        }

        $this->runTestUpsertsException($domain, $forms, '/This value should not be blank./', true, $isUpdate);
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertsWithErrorDatabase($isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            $forms = array(
                $this->buildForm($objects[0], array(
                    'detail' => null,
                    'description' => 'test 1',
                )),
                $this->buildForm($objects[1], array(
                    'detail' => null,
                    'description' => 'test 2',
                )),
            );
        } else {
            $this->loadFixtures(array());
            /* @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            /* @var Foo $foo2 */
            $foo2 = $domain->newInstance();

            $form1 = $this->buildForm($foo1, array(
                'name' => 'Bar',
            ));
            $form2 = $this->buildForm($foo2, array(
                'name' => 'Bar',
            ));
            $forms = array($form1, $form2);
        }

        $this->runTestUpsertsException($domain, $forms, $this->getIntegrityViolationMessage(), false, $isUpdate);
    }

    protected function runTestUpsertsException(DomainInterface $domain, array $objects, $errorMessage, $autoCommit = false, $isUpdate = false)
    {
        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPSERTS, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPSERTS, function (ResourceEvent $e) use (&$postEvent, $autoCommit, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            $resources = $e->getResources();
            $this->assertCount(2, $resources);
            $this->assertSame(ResourceStatutes::ERROR, $resources[0]->getStatus());
            $this->assertSame($autoCommit ? ResourceStatutes::CANCELED
                : ResourceStatutes::ERROR, $resources[1]->getStatus());
        });

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());

        $resources = $domain->upserts($objects);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);
        $this->assertTrue($resources->hasErrors());

        $errors = $autoCommit
            ? $resources->get(0)->getFormErrors()
            : $resources->getErrors();
        $this->assertRegExp($errorMessage, $errors[0]->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());
        $this->assertSame($autoCommit ? ResourceListStatutes::MIXED
            : ResourceListStatutes::ERROR, $resources->getStatus());
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpserts($isUpdate)
    {
        $this->runTestUpserts(false, $isUpdate);
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertsAutoCommitWithErrorValidationAndErrorDatabase($isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            $forms = array(
                $this->buildForm($objects[0], array(
                    'name' => null,
                    'description' => 'test 1',
                )),
                $this->buildForm($objects[1], array(
                    'detail' => null,
                    'description' => 'test 2',
                )),
            );
        } else {
            $this->loadFixtures(array());
            /* @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            /* @var Foo $foo2 */
            $foo2 = $domain->newInstance();

            $form1 = $this->buildForm($foo1, array(
                'name' => null,
            ));
            $form2 = $this->buildForm($foo2, array(
                'name' => 'Bar',
            ));

            $forms = array($form1, $form2);
        }

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPSERTS, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPSERTS, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());

        $resources = $domain->upserts($forms, true);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);

        $this->assertTrue($resources->hasErrors());
        $errors1 = $resources->get(0)->getFormErrors();
        $this->assertRegExp('/This value should not be blank./', $errors1[0]->getMessage());
        $this->assertRegExp($this->getIntegrityViolationMessage(), $resources->get(1)->getErrors()->get(0)->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertsAutoCommitWithErrorDatabase($isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            $forms = array(
                $this->buildForm($objects[0], array(
                    'detail' => null,
                    'description' => 'test 1',
                )),
                $this->buildForm($objects[1], array(
                    'description' => 'test 2',
                )),
            );
        } else {
            $this->loadFixtures(array());
            /* @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            /* @var Foo $foo2 */
            $foo2 = $domain->newInstance();

            $form1 = $this->buildForm($foo1, array(
                'name' => 'Bar',
            ));
            $form2 = $this->buildForm($foo2, array(
                'name' => 'Bar',
            ));

            $forms = array($form1, $form2);
        }

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPSERTS, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPSERTS, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());

        $resources = $domain->upserts($forms, true);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);

        $this->assertTrue($resources->hasErrors());
        $this->assertCount(0, $resources->get(0)->getFormErrors());
        $this->assertCount(0, $resources->get(1)->getFormErrors());

        $this->assertCount(1, $resources->get(0)->getErrors());
        $this->assertCount(1, $resources->get(1)->getErrors());

        $this->assertRegExp($this->getIntegrityViolationMessage(), $resources->get(0)->getErrors()->get(0)->getMessage());
        $this->assertRegExp('/Caused by previous internal database error/', $resources->get(1)->getErrors()->get(0)->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertsAutoCommitWithErrorValidationAndSuccess($isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            $forms = array(
                $this->buildForm($objects[0], array(
                    'name' => null,
                    'description' => 'test 1',
                )),
                $this->buildForm($objects[1], array(
                    'name' => 'New Bar 2',
                    'description' => 'test 2',
                )),
            );
        } else {
            $this->loadFixtures(array());
            /* @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            /* @var Foo $foo2 */
            $foo2 = $domain->newInstance();

            $form1 = $this->buildForm($foo1, array(
                'name' => null,
            ));
            $form2 = $this->buildForm($foo2, array(
                'name' => 'Bar',
                'detail' => 'Detail',
            ));

            $forms = array($form1, $form2);
        }

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());
        $resources = $domain->upserts($forms, true);
        $this->assertCount($isUpdate ? 2 : 1, $domain->getRepository()->findAll());

        $this->assertCount(2, $resources);
        $this->assertInstanceOf(ResourceInterface::class, $resources->get(0));
        $this->assertInstanceOf(ResourceInterface::class, $resources->get(1));

        $this->assertSame(ResourceListStatutes::MIXED, $resources->getStatus());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        $this->assertSame($isUpdate ? ResourceStatutes::UPDATED
            : ResourceStatutes::CREATED, $resources->get(1)->getStatus());
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertsAutoCommit($isUpdate)
    {
        $this->runTestUpserts(true, $isUpdate);
    }

    public function runTestUpserts($autoCommit, $isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            $forms = array(
                $this->buildForm($objects[0], array(
                    'name' => 'New Bar 1',
                    'detail' => 'New Detail 1',
                )),
                $this->buildForm($objects[1], array(
                    'name' => 'New Bar 2',
                    'detail' => 'New Detail 2',
                )),
            );
        } else {
            $this->loadFixtures(array());
            /* @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            /* @var Foo $foo2 */
            $foo2 = $domain->newInstance();

            $form1 = $this->buildForm($foo1, array(
                'name' => 'Bar 1',
                'detail' => 'Detail 1',
            ));
            $form2 = $this->buildForm($foo2, array(
                'name' => 'Bar 2',
                'detail' => 'Detail 2',
            ));

            $forms = array($form1, $form2);
        }

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());
        $resources = $domain->upserts($forms, $autoCommit);
        $this->assertCount(2, $domain->getRepository()->findAll());

        $this->assertCount(2, $resources);
        $this->assertInstanceOf(ResourceInterface::class, $resources->get(0));
        $this->assertInstanceOf(ResourceInterface::class, $resources->get(1));

        $this->assertSame(ResourceListStatutes::SUCCESSFULLY, $resources->getStatus());
        $this->assertSame($isUpdate ? ResourceStatutes::UPDATED
            : ResourceStatutes::CREATED, $resources->get(0)->getStatus());
        $this->assertTrue($resources->get(0)->isValid());
        $this->assertSame($isUpdate ? ResourceStatutes::UPDATED
            : ResourceStatutes::CREATED, $resources->get(1)->getStatus());
        $this->assertTrue($resources->get(1)->isValid());
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertWithMissingFormSubmission($isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $object = $this->insertResource($domain);
            $form = $this->buildForm($object, array(
                'name' => null,
                'detail' => 'New Detail 1',
            ));
        } else {
            /* @var Foo $foo */
            $foo = $domain->newInstance();
            $form = $this->formFactory->create(FooType::class, $foo, array());

            $this->loadFixtures(array());
        }

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPSERTS, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPSERTS, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount($isUpdate ? 1 : 0, $domain->getRepository()->findAll());

        $resource = $domain->upsert($form);
        $this->assertCount(0, $resource->getErrors());
        $this->assertCount(1, $resource->getFormErrors());
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testErrorIdentifier($isUpdate)
    {
        $this->loadFixtures(array());

        $domain = $this->createDomain();

        if ($isUpdate) {
            /* @var Foo $object */
            $object = $domain->newInstance();
            $form = $this->buildForm($object, array(
                'name' => 'Bar',
                'detail' => 'Detail',
            ));
        } else {
            $object = $this->insertResource($domain);
            $object->setDetail(null);
            $form = $this->buildForm($object, array(
                'name' => 'New Bar',
                'detail' => 'New Detail',
            ));
        }

        $resource = $domain->upsert($form);
        $this->assertTrue($resource->isValid());
    }

    /**
     * @param object $object
     * @param array  $data
     *
     * @return FormInterface
     */
    protected function buildForm($object, array $data)
    {
        $form = $this->formFactory->create(FooType::class, $object, array());
        $form->submit($data, false);

        return $form;
    }
}
