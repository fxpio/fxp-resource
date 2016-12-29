<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Tests\Functional\Domain;

use Sonatra\Component\Resource\Domain\DomainInterface;
use Sonatra\Component\Resource\Event\ResourceEvent;
use Sonatra\Component\Resource\ResourceEvents;
use Sonatra\Component\Resource\ResourceInterface;
use Sonatra\Component\Resource\ResourceListInterface;
use Sonatra\Component\Resource\ResourceListStatutes;
use Sonatra\Component\Resource\ResourceStatutes;
use Sonatra\Component\Resource\Tests\Fixtures\Entity\Foo;
use Sonatra\Component\Resource\Tests\Fixtures\Form\FooType;
use Symfony\Component\Form\FormInterface;

/**
 * Functional tests for update methods of Domain with form resources.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainUpdateFormTest extends AbstractDomainTest
{
    public function testUpdateWithErrorValidation()
    {
        $domain = $this->createDomain();
        $foo = $this->insertResource($domain);
        $foo->setName(null);
        $form = $this->buildForm($foo, array(
            'name' => null,
            'description' => 'test',
        ));

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPDATES, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPDATES, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(1, $domain->getRepository()->findAll());

        $resource = $domain->update($form);
        $this->assertCount(0, $resource->getErrors());
        $this->assertCount(1, $resource->getFormErrors());

        $errors = $resource->getFormErrors();
        $this->assertRegExp('/This value should not be blank./', $errors[0]->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(1, $domain->getRepository()->findAll());
    }

    public function testUpdateWithErrorDatabase()
    {
        $domain = $this->createDomain();
        $foo = $this->insertResource($domain);
        $foo->setDetail(null);
        $form = $this->buildForm($foo, array(
            'description' => 'test',
        ));

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPDATES, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPDATES, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(1, $domain->getRepository()->findAll());

        $resource = $domain->update($form);
        $this->assertFalse($resource->isValid());
        $this->assertCount(1, $resource->getErrors());
        $this->assertCount(0, $resource->getFormErrors());

        $errors = $resource->getErrors();
        $this->assertRegExp($this->getIntegrityViolationMessage(), $errors[0]->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(1, $domain->getRepository()->findAll());
    }

    public function testUpdate()
    {
        $domain = $this->createDomain();
        $foo = $this->insertResource($domain);
        $foo->setDetail(null);
        $form = $this->buildForm($foo, array(
            'name' => 'New Bar',
            'detail' => 'New Detail',
        ));

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPDATES, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPDATES, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::UPDATED, $resource->getStatus());
            }
        });

        $this->assertCount(1, $domain->getRepository()->findAll());

        $resource = $domain->update($form);
        $this->assertTrue($resource->isValid());
        $this->assertCount(0, $resource->getErrors());
        $this->assertCount(0, $resource->getFormErrors());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(1, $domain->getRepository()->findAll());
    }

    public function testUpdatesWithErrorValidation()
    {
        $domain = $this->createDomain();
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

        $this->runTestUpdatesException($domain, $forms, '/This value should not be blank./', true);
    }

    public function testUpdatesWithErrorDatabase()
    {
        $domain = $this->createDomain();
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

        $this->runTestUpdatesException($domain, $forms, $this->getIntegrityViolationMessage(), false);
    }

    protected function runTestUpdatesException(DomainInterface $domain, array $objects, $errorMessage, $autoCommit = false)
    {
        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPDATES, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPDATES, function (ResourceEvent $e) use (&$postEvent, $autoCommit, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            $resources = $e->getResources();
            $this->assertCount(2, $resources);
            $this->assertSame(ResourceStatutes::ERROR, $resources[0]->getStatus());
            $this->assertSame($autoCommit ? ResourceStatutes::CANCELED
                : ResourceStatutes::ERROR, $resources[1]->getStatus());
        });

        $this->assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->updates($objects);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);
        $this->assertTrue($resources->hasErrors());

        $errors = $autoCommit
            ? $resources->get(0)->getFormErrors()
            : $resources->getErrors();
        $this->assertRegExp($errorMessage, $errors[0]->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(2, $domain->getRepository()->findAll());
        $this->assertSame($autoCommit ? ResourceListStatutes::MIXED
            : ResourceListStatutes::ERROR, $resources->getStatus());
    }

    public function testUpdates()
    {
        $this->runTestUpdates(false);
    }

    public function testUpdatesAutoCommitWithErrorValidationAndErrorDatabase()
    {
        $domain = $this->createDomain();
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

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPDATES, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPDATES, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->updates($forms, true);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);

        $this->assertTrue($resources->hasErrors());
        $errors1 = $resources->get(0)->getFormErrors();
        $this->assertRegExp('/This value should not be blank./', $errors1[0]->getMessage());
        $this->assertRegExp($this->getIntegrityViolationMessage(), $resources->get(1)->getErrors()->get(0)->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(2, $domain->getRepository()->findAll());
    }

    public function testUpsertsAutoCommitWithErrorDatabase()
    {
        $domain = $this->createDomain();

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

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPDATES, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPDATES, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->updates($forms, true);
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

        $this->assertCount(2, $domain->getRepository()->findAll());
    }

    public function testUpdatesAutoCommitWithErrorValidationAndSuccess()
    {
        $domain = $this->createDomain();
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

        $this->assertCount(2, $domain->getRepository()->findAll());
        $resources = $domain->updates($forms, true);
        $this->assertCount(2, $domain->getRepository()->findAll());

        $this->assertCount(2, $resources);
        $this->assertInstanceOf(ResourceInterface::class, $resources->get(0));
        $this->assertInstanceOf(ResourceInterface::class, $resources->get(1));

        $this->assertSame(ResourceListStatutes::MIXED, $resources->getStatus());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        $this->assertSame(ResourceStatutes::UPDATED, $resources->get(1)->getStatus());
    }

    public function testUpdatesAutoCommit()
    {
        $this->runTestUpdates(true);
    }

    public function runTestUpdates($autoCommit)
    {
        $domain = $this->createDomain();
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

        $this->assertCount(2, $domain->getRepository()->findAll());
        $resources = $domain->updates($forms, $autoCommit);
        $this->assertCount(2, $domain->getRepository()->findAll());

        $this->assertCount(2, $resources);
        $this->assertInstanceOf(ResourceInterface::class, $resources->get(0));
        $this->assertInstanceOf(ResourceInterface::class, $resources->get(1));

        $this->assertSame(ResourceListStatutes::SUCCESSFULLY, $resources->getStatus());
        $this->assertSame(ResourceStatutes::UPDATED, $resources->get(0)->getStatus());
        $this->assertTrue($resources->get(0)->isValid());
        $this->assertSame(ResourceStatutes::UPDATED, $resources->get(1)->getStatus());
        $this->assertTrue($resources->get(1)->isValid());
    }

    public function testUpdateWithMissingFormSubmission()
    {
        $domain = $this->createDomain();
        $object = $this->insertResource($domain);
        $form = $this->buildForm($object, array(
            'name' => null,
            'detail' => 'New Detail 1',
        ));

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPDATES, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPDATES, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(1, $domain->getRepository()->findAll());

        $resource = $domain->update($form);
        $this->assertCount(0, $resource->getErrors());
        $this->assertCount(1, $resource->getFormErrors());
    }

    public function testErrorIdentifier()
    {
        $domain = $this->createDomain();
        /* @var Foo $object */
        $object = $domain->newInstance();
        $form = $this->buildForm($object, array(
            'name' => 'Bar',
            'detail' => 'Detail',
        ));

        $this->loadFixtures(array());

        $resource = $domain->update($form);
        $this->assertFalse($resource->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
        $this->assertRegExp('/The resource cannot be updated because it has not an identifier/', $resource->getErrors()->get(0)->getMessage());
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
