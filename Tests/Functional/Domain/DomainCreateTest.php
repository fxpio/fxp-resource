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

use Doctrine\ORM\Events;
use Fxp\Component\Resource\Domain\DomainInterface;
use Fxp\Component\Resource\Event\PostCreatesEvent;
use Fxp\Component\Resource\Event\PreCreatesEvent;
use Fxp\Component\Resource\ResourceInterface;
use Fxp\Component\Resource\ResourceListInterface;
use Fxp\Component\Resource\ResourceListStatutes;
use Fxp\Component\Resource\ResourceStatutes;
use Fxp\Component\Resource\Tests\Fixtures\Entity\Foo;
use Fxp\Component\Resource\Tests\Fixtures\Listener\ErrorListener;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Functional tests for create methods of Domain.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class DomainCreateTest extends AbstractDomainTest
{
    public function testCreateWithErrorValidation(): void
    {
        $domain = $this->createDomain();
        /** @var Foo $foo */
        $foo = $domain->newInstance();

        $this->runTestCreateException($domain, $foo, '/This value should not be blank./');
    }

    public function testCreateWithErrorDatabase(): void
    {
        $domain = $this->createDomain();
        /** @var Foo $foo */
        $foo = $domain->newInstance();
        $foo->setName('Bar');

        $this->runTestCreateException($domain, $foo, $this->getIntegrityViolationMessage());
    }

    public function testCreate(): void
    {
        $domain = $this->createDomain();
        /** @var Foo $foo */
        $foo = $domain->newInstance();
        $foo->setName('Bar');
        $foo->setDetail('Detail');

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

        $resource = $domain->create($foo);
        static::assertCount(0, $resource->getErrors());

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

        $this->runTestCreatesException($domain, [$foo1, $foo2], '/This value should not be blank./', true);
    }

    public function testCreatesWithErrorDatabase(): void
    {
        $domain = $this->createDomain();
        /** @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        $foo1->setName('Bar');
        /** @var Foo $foo2 */
        $foo2 = $domain->newInstance();
        $foo2->setName('Bar');

        $this->runTestCreatesException($domain, [$foo1, $foo2], $this->getIntegrityViolationMessage(), false);
    }

    public function testCreates(): void
    {
        $this->runTestCreates(false);
    }

    public function testCreatesAutoCommit(): void
    {
        $this->runTestCreates(true);
    }

    public function testCreatesAutoCommitWithErrorValidationAndErrorDatabase(): void
    {
        $domain = $this->createDomain();
        /** @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        /** @var Foo $foo2 */
        $foo2 = $domain->newInstance();
        $foo2->setName('Bar');

        $objects = [$foo1, $foo2];

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
        static::assertRegExp('/This value should not be blank./', $resources->get(0)->getErrors()->get(0)->getMessage());
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
        $foo1->setName('Bar');
        /** @var Foo $foo2 */
        $foo2 = $domain->newInstance();
        $foo2->setName('Bar');
        $foo2->setName('Detail');

        $objects = [$foo1, $foo2];

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
        $foo2->setName('Bar');
        $foo2->setDetail('Detail');

        $objects = [$foo1, $foo2];

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

    public function testInvalidObjectType(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "Fxp\\Component\\Resource\\Tests\\Fixtures\\Entity\\Foo", "integer" given at the position "0"');

        $domain = $this->createDomain();
        /** @var object $object */
        $object = 42;

        $domain->create($object);
    }

    public function testErrorIdentifier(): void
    {
        $domain = $this->createDomain();
        $object = $this->insertResource($domain);

        $resource = $domain->create($object);
        static::assertFalse($resource->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resource->getStatus());
        static::assertRegExp('/The resource cannot be created because it has an identifier/', $resource->getErrors()->get(0)->getMessage());
    }

    public function testCreateAutoCommitErrorOnPrePersistAndSuccessObjectsWithViolationException(): void
    {
        $domain = $this->createDomain();
        /** @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        $foo1->setName('Bar 1');
        $foo1->setDetail('Detail 1');
        /** @var Foo $foo2 */
        $foo2 = $domain->newInstance();
        $foo2->setName('Bar 2');
        $foo2->setDetail('Detail 2');

        $objects = [$foo1, $foo2];
        $errorListener = new ErrorListener('created', true);

        $this->loadFixtures([]);

        $this->em->getEventManager()->addEventListener(Events::prePersist, $errorListener);

        static::assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->creates($objects, true);
        static::assertTrue($resources->hasErrors());
        static::assertFalse($resources->get(0)->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        static::assertSame('The entity does not created (violation exception)', $resources->get(0)->getErrors()->get(0)->getMessage());

        static::assertFalse($resources->get(1)->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(1)->getStatus());
        static::assertSame('Caused by previous internal database error', $resources->get(1)->getErrors()->get(0)->getMessage());
    }

    public function testCreateAutoCommitErrorOnPrePersistAndSuccessObjects(): void
    {
        $domain = $this->createDomain();
        /** @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        $foo1->setName('Bar 1');
        $foo1->setDetail('Detail 1');
        /** @var Foo $foo2 */
        $foo2 = $domain->newInstance();
        $foo2->setName('Bar 2');
        $foo2->setDetail('Detail 2');

        $objects = [$foo1, $foo2];
        $errorListener = new ErrorListener('created', false);

        $this->loadFixtures([]);

        $this->em->getEventManager()->addEventListener(Events::prePersist, $errorListener);

        static::assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->creates($objects, true);
        static::assertTrue($resources->hasErrors());
        static::assertFalse($resources->get(0)->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        static::assertSame('The entity does not created (exception)', $resources->get(0)->getErrors()->get(0)->getMessage());

        static::assertFalse($resources->get(1)->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(1)->getStatus());
        static::assertSame('Caused by previous internal database error', $resources->get(1)->getErrors()->get(0)->getMessage());
    }

    protected function runTestCreateException(DomainInterface $domain, $object, $errorMessage): void
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
        $this->dispatcher->addListener(PostCreatesEvent::class, function (PostCreatesEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        static::assertCount(0, $domain->getRepository()->findAll());

        $resource = $domain->create($object);
        static::assertCount(1, $resource->getErrors());
        static::assertRegExp($errorMessage, $resource->getErrors()->get(0)->getMessage());

        static::assertTrue($preEvent);
        static::assertTrue($postEvent);

        static::assertCount(0, $domain->getRepository()->findAll());
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

        /** @var ConstraintViolationListInterface $errors */
        $errors = $autoCommit
            ? $resources->get(0)->getErrors()
            : $resources->getErrors();
        static::assertCount(1, $errors);
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
        $foo1->setName('Bar 1');
        $foo1->setDetail('Detail 1');
        /** @var Foo $foo2 */
        $foo2 = $domain->newInstance();
        $foo2->setName('Bar 2');
        $foo2->setDetail('Detail 2');

        $objects = [$foo1, $foo2];

        $this->loadFixtures([]);

        static::assertCount(0, $domain->getRepository()->findAll());
        $resources = $domain->creates($objects, $autoCommit);
        static::assertCount(2, $domain->getRepository()->findAll());

        static::assertCount(2, $resources);
        static::assertInstanceOf(ResourceInterface::class, $resources->get(0));
        static::assertInstanceOf(ResourceInterface::class, $resources->get(1));

        static::assertSame(ResourceListStatutes::SUCCESSFULLY, $resources->getStatus());
        static::assertSame(ResourceStatutes::CREATED, $resources->get(0)->getStatus());
        static::assertSame(ResourceStatutes::CREATED, $resources->get(1)->getStatus());
    }
}
