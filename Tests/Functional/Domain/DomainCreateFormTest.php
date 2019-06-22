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

use Fxp\Component\DefaultValue\Tests\Fixtures\Object\Foo;
use Fxp\Component\Resource\Domain\DomainInterface;
use Fxp\Component\Resource\Event\PostCreatesEvent;
use Fxp\Component\Resource\Event\PreCreatesEvent;
use Fxp\Component\Resource\ResourceInterface;
use Fxp\Component\Resource\ResourceListInterface;
use Fxp\Component\Resource\ResourceListStatutes;
use Fxp\Component\Resource\ResourceStatutes;
use Fxp\Component\Resource\Tests\Fixtures\Form\FooType;
use Symfony\Component\Form\FormInterface;

/**
 * Functional tests for create methods of Domain with form resources.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class DomainCreateFormTest extends AbstractDomainTest
{
    public function testCreateWithErrorValidation(): void
    {
        $domain = $this->createDomain();
        /** @var Foo $foo */
        $foo = $domain->newInstance();
        $form = $this->buildForm($foo, [
            'description' => 'test',
        ]);

        $this->loadFixtures([]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreCreatesEvent::class, function (PreCreatesEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostCreatesEvent::class, function (PostCreatesEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        static::assertCount(0, $domain->getRepository()->findAll());

        $resource = $domain->create($form);
        static::assertCount(0, $resource->getErrors());
        static::assertCount(1, $resource->getFormErrors());

        $errors = $resource->getFormErrors();
        static::assertRegExp('/This value should not be blank./', $errors[0]->getMessage());

        static::assertTrue($preEvent);
        static::assertTrue($postEvent);

        static::assertCount(0, $domain->getRepository()->findAll());
    }

    public function testCreateWithErrorDatabase(): void
    {
        $domain = $this->createDomain();
        /** @var Foo $foo */
        $foo = $domain->newInstance();
        $form = $this->buildForm($foo, [
            'name' => 'Bar',
        ]);

        $this->loadFixtures([]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreCreatesEvent::class, function (PreCreatesEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostCreatesEvent::class, function (PostCreatesEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        static::assertCount(0, $domain->getRepository()->findAll());

        $resource = $domain->create($form);
        static::assertFalse($resource->isValid());
        static::assertCount(1, $resource->getErrors());
        static::assertCount(0, $resource->getFormErrors());

        $errors = $resource->getErrors();
        static::assertRegExp($this->getIntegrityViolationMessage(), $errors[0]->getMessage());

        static::assertTrue($preEvent);
        static::assertTrue($postEvent);

        static::assertCount(0, $domain->getRepository()->findAll());
    }

    public function testCreate(): void
    {
        $domain = $this->createDomain();
        /** @var Foo $foo */
        $foo = $domain->newInstance();
        $form = $this->buildForm($foo, [
            'name' => 'Bar',
            'detail' => 'Detail',
        ]);

        $this->loadFixtures([]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreCreatesEvent::class, function (PreCreatesEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostCreatesEvent::class, function (PostCreatesEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::CREATED, $resource->getStatus());
            }
        });

        static::assertCount(0, $domain->getRepository()->findAll());

        $resource = $domain->create($form);
        static::assertTrue($resource->isValid());
        static::assertCount(0, $resource->getErrors());
        static::assertCount(0, $resource->getFormErrors());

        static::assertTrue($preEvent);
        static::assertTrue($postEvent);

        static::assertCount(1, $domain->getRepository()->findAll());
    }

    public function testCreatesWithErrorValidation(): void
    {
        $domain = $this->createDomain();
        /** @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        /** @var Foo $foo2 */
        $foo2 = $domain->newInstance();

        $form1 = $this->buildForm($foo1, [
            'description' => 'test',
        ]);
        $form2 = $this->buildForm($foo2, [
            'description' => 'test',
        ]);

        $this->runTestCreatesException($domain, [$form1, $form2], '/This value should not be blank./', true);
    }

    public function testCreatesWithErrorDatabase(): void
    {
        $domain = $this->createDomain();
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

        $this->runTestCreatesException($domain, [$form1, $form2], $this->getIntegrityViolationMessage(), false);
    }

    public function testCreates(): void
    {
        $this->runTestCreates(false);
    }

    public function testCreatesAutoCommitWithErrorValidationAndErrorDatabase(): void
    {
        $domain = $this->createDomain();
        /** @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        /** @var Foo $foo2 */
        $foo2 = $domain->newInstance();

        $form1 = $this->buildForm($foo1, []);
        $form2 = $this->buildForm($foo2, [
            'name' => 'Bar',
        ]);

        $objects = [$form1, $form2];

        $this->loadFixtures([]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreCreatesEvent::class, function (PreCreatesEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostCreatesEvent::class, function (PostCreatesEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        static::assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->creates($objects, true);
        static::assertInstanceOf(ResourceListInterface::class, $resources);

        static::assertTrue($resources->hasErrors());
        $errors1 = $resources->get(0)->getFormErrors();
        static::assertRegExp('/This value should not be blank./', $errors1[0]->getMessage());
        static::assertRegExp($this->getIntegrityViolationMessage(), $resources->get(1)->getErrors()->get(0)->getMessage());

        static::assertTrue($preEvent);
        static::assertTrue($postEvent);

        static::assertCount(0, $domain->getRepository()->findAll());
    }

    public function testCreatesAutoCommitWithErrorDatabase(): void
    {
        $domain = $this->createDomain();

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

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreCreatesEvent::class, function (PreCreatesEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostCreatesEvent::class, function (PostCreatesEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        static::assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->creates($forms, true);
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

        static::assertCount(0, $domain->getRepository()->findAll());
    }

    public function testCreatesAutoCommitWithErrorValidationAndSuccess(): void
    {
        $domain = $this->createDomain();
        /** @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        /** @var Foo $foo2 */
        $foo2 = $domain->newInstance();

        $form1 = $this->buildForm($foo1, []);
        $form2 = $this->buildForm($foo2, [
            'name' => 'Bar',
            'detail' => 'Detail',
        ]);

        $objects = [$form1, $form2];

        $this->loadFixtures([]);

        static::assertCount(0, $domain->getRepository()->findAll());
        $resources = $domain->creates($objects, true);
        static::assertCount(1, $domain->getRepository()->findAll());

        static::assertCount(2, $resources);
        static::assertInstanceOf(ResourceInterface::class, $resources->get(0));
        static::assertInstanceOf(ResourceInterface::class, $resources->get(1));

        static::assertSame(ResourceListStatutes::MIXED, $resources->getStatus());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        static::assertSame(ResourceStatutes::CREATED, $resources->get(1)->getStatus());
    }

    public function testCreatesAutoCommit(): void
    {
        $this->runTestCreates(true);
    }

    public function testCreateWithMissingFormSubmission(): void
    {
        $domain = $this->createDomain();
        /** @var Foo $foo */
        $foo = $domain->newInstance();

        $form = $this->formFactory->create(FooType::class, $foo, []);

        $this->loadFixtures([]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreCreatesEvent::class, function (PreCreatesEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostCreatesEvent::class, function (PostCreatesEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        static::assertCount(0, $domain->getRepository()->findAll());

        $resource = $domain->create($form);
        static::assertCount(0, $resource->getErrors());
        static::assertCount(1, $resource->getFormErrors());
    }

    public function testErrorIdentifier(): void
    {
        $domain = $this->createDomain();
        $foo = $this->insertResource($domain);
        $foo->setDetail(null);
        $form = $this->buildForm($foo, [
            'name' => 'New Bar',
            'detail' => 'New Detail',
        ]);

        $resource = $domain->create($form);
        static::assertFalse($resource->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resource->getStatus());
        static::assertRegExp('/The resource cannot be created because it has an identifier/', $resource->getErrors()->get(0)->getMessage());
    }

    protected function runTestCreatesException(DomainInterface $domain, array $objects, $errorMessage, $autoCommit = false): void
    {
        $this->loadFixtures([]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreCreatesEvent::class, function (PreCreatesEvent $e) use (&$preEvent, $domain): void {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostCreatesEvent::class, function (PostCreatesEvent $e) use (&$postEvent, $autoCommit, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            $resources = $e->getResources();
            $this->assertCount(2, $resources);
            $this->assertSame(ResourceStatutes::ERROR, $resources[0]->getStatus());
            $this->assertSame($autoCommit ? ResourceStatutes::CANCELED
                : ResourceStatutes::ERROR, $resources[1]->getStatus());
        });

        static::assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->creates($objects);
        static::assertInstanceOf(ResourceListInterface::class, $resources);
        static::assertTrue($resources->hasErrors());

        $errors = $autoCommit
            ? $resources->get(0)->getFormErrors()
            : $resources->getErrors();
        static::assertRegExp($errorMessage, $errors[0]->getMessage());

        static::assertTrue($preEvent);
        static::assertTrue($postEvent);

        static::assertCount(0, $domain->getRepository()->findAll());
        static::assertSame($autoCommit ? ResourceListStatutes::MIXED
            : ResourceListStatutes::ERROR, $resources->getStatus());
    }

    protected function runTestCreates($autoCommit): void
    {
        $domain = $this->createDomain();
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

        $objects = [$form1, $form2];

        $this->loadFixtures([]);

        static::assertCount(0, $domain->getRepository()->findAll());
        $resources = $domain->creates($objects, $autoCommit);
        static::assertCount(2, $domain->getRepository()->findAll());

        static::assertCount(2, $resources);
        static::assertInstanceOf(ResourceInterface::class, $resources->get(0));
        static::assertInstanceOf(ResourceInterface::class, $resources->get(1));

        static::assertSame(ResourceListStatutes::SUCCESSFULLY, $resources->getStatus());
        static::assertSame(ResourceStatutes::CREATED, $resources->get(0)->getStatus());
        static::assertTrue($resources->get(0)->isValid());
        static::assertSame(ResourceStatutes::CREATED, $resources->get(1)->getStatus());
        static::assertTrue($resources->get(1)->isValid());
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
        $form->submit($data, true);

        return $form;
    }
}
