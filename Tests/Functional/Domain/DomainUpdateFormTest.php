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
use Fxp\Component\Resource\Event\PostUpdatesEvent;
use Fxp\Component\Resource\Event\PreUpdatesEvent;
use Fxp\Component\Resource\ResourceInterface;
use Fxp\Component\Resource\ResourceListInterface;
use Fxp\Component\Resource\ResourceListStatutes;
use Fxp\Component\Resource\ResourceStatutes;
use Fxp\Component\Resource\Tests\Fixtures\Entity\Foo;
use Fxp\Component\Resource\Tests\Fixtures\Form\FooType;
use Symfony\Component\Form\FormInterface;

/**
 * Functional tests for update methods of Domain with form resources.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class DomainUpdateFormTest extends AbstractDomainTest
{
    public function testUpdateWithErrorValidation(): void
    {
        $domain = $this->createDomain();
        $foo = $this->insertResource($domain);
        $foo->setName(null);
        $form = $this->buildForm($foo, [
            'name' => null,
            'description' => 'test',
        ]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreUpdatesEvent::class, function (PreUpdatesEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostUpdatesEvent::class, function (PostUpdatesEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
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

    public function testUpdateWithErrorDatabase(): void
    {
        $domain = $this->createDomain();
        $foo = $this->insertResource($domain);
        $foo->setDetail(null);
        $form = $this->buildForm($foo, [
            'description' => 'test',
        ]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreUpdatesEvent::class, function (PreUpdatesEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostUpdatesEvent::class, function (PostUpdatesEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
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

    public function testUpdate(): void
    {
        $domain = $this->createDomain();
        $foo = $this->insertResource($domain);
        $foo->setDetail(null);
        $form = $this->buildForm($foo, [
            'name' => 'New Bar',
            'detail' => 'New Detail',
        ]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreUpdatesEvent::class, function (PreUpdatesEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostUpdatesEvent::class, function (PostUpdatesEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
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

    public function testUpdatesWithErrorValidation(): void
    {
        $domain = $this->createDomain();
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

        $this->runTestUpdatesException($domain, $forms, '/This value should not be blank./', true);
    }

    public function testUpdatesWithErrorDatabase(): void
    {
        $domain = $this->createDomain();
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

        $this->runTestUpdatesException($domain, $forms, $this->getIntegrityViolationMessage(), false);
    }

    public function testUpdates(): void
    {
        $this->runTestUpdates(false);
    }

    public function testUpdatesAutoCommitWithErrorValidationAndErrorDatabase(): void
    {
        $domain = $this->createDomain();
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

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreUpdatesEvent::class, function (PreUpdatesEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostUpdatesEvent::class, function (PostUpdatesEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
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

    public function testUpsertsAutoCommitWithErrorDatabase(): void
    {
        $domain = $this->createDomain();

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

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreUpdatesEvent::class, function (PreUpdatesEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostUpdatesEvent::class, function (PostUpdatesEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
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

    public function testUpdatesAutoCommitWithErrorValidationAndSuccess(): void
    {
        $domain = $this->createDomain();
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

    public function testUpdatesAutoCommit(): void
    {
        $this->runTestUpdates(true);
    }

    public function runTestUpdates($autoCommit): void
    {
        $domain = $this->createDomain();
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

    public function testUpdateWithMissingFormSubmission(): void
    {
        $domain = $this->createDomain();
        $object = $this->insertResource($domain);
        $form = $this->buildForm($object, [
            'name' => null,
            'detail' => 'New Detail 1',
        ]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreUpdatesEvent::class, function (PreUpdatesEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostUpdatesEvent::class, function (PostUpdatesEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(1, $domain->getRepository()->findAll());

        $resource = $domain->update($form);
        $this->assertCount(0, $resource->getErrors());
        $this->assertCount(1, $resource->getFormErrors());
    }

    public function testErrorIdentifier(): void
    {
        $domain = $this->createDomain();
        /** @var Foo $object */
        $object = $domain->newInstance();
        $form = $this->buildForm($object, [
            'name' => 'Bar',
            'detail' => 'Detail',
        ]);

        $this->loadFixtures([]);

        $resource = $domain->update($form);
        $this->assertFalse($resource->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
        $this->assertRegExp('/The resource cannot be updated because it has not an identifier/', $resource->getErrors()->get(0)->getMessage());
    }

    protected function runTestUpdatesException(DomainInterface $domain, array $objects, $errorMessage, $autoCommit = false): void
    {
        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreUpdatesEvent::class, function (PreUpdatesEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostUpdatesEvent::class, function (PostUpdatesEvent $e) use (&$postEvent, $autoCommit, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
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
