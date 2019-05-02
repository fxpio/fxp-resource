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
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Functional tests for upsert methods of Domain.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class DomainUpsertTest extends AbstractDomainTest
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

        $this->runTestUpsertException($domain, $foo, '/This value should not be blank./', $isUpdate);
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

        $this->runTestUpsertException($domain, $foo, $this->getIntegrityViolationMessage(), $isUpdate);
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
            $foo->setName('Foo');
        } else {
            $this->loadFixtures([]);
            /** @var Foo $foo */
            $foo = $domain->newInstance();
            $foo->setName('Bar');
            $foo->setDetail('Detail');
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
        $this->dispatcher->addListener(PostUpsertsEvent::class, function (PostUpsertsEvent $e) use (&$postEvent, $domain, $isUpdate): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame($isUpdate ? ResourceStatutes::UPDATED
                    : ResourceStatutes::CREATED, $resource->getStatus());
            }
        });

        $this->assertCount($isUpdate ? 1 : 0, $domain->getRepository()->findAll());

        $resource = $domain->upsert($foo);
        $this->assertCount(0, $resource->getErrors());
        $this->assertSame($isUpdate ? 'Foo' : 'Bar', $resource->getData()->getName());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(1, $domain->getRepository()->findAll());
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

            foreach ($objects as $object) {
                $object->setName(null);
            }
        } else {
            $this->loadFixtures([]);
            /** @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            /** @var Foo $foo2 */
            $foo2 = $domain->newInstance();
            $objects = [$foo1, $foo2];
        }

        $this->runTestUpsertsException($domain, $objects, '/This value should not be blank./', true, $isUpdate);
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

            foreach ($objects as $object) {
                $object->setDetail(null);
            }
        } else {
            $this->loadFixtures([]);
            /** @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            $foo1->setName('Bar');
            /** @var Foo $foo2 */
            $foo2 = $domain->newInstance();
            $foo2->setName('Bar');
            $objects = [$foo1, $foo2];
        }

        $this->runTestUpsertsException($domain, $objects, $this->getIntegrityViolationMessage(), false, $isUpdate);
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

            $objects[0]->setName(null);
            $objects[1]->setDetail(null);
        } else {
            $this->loadFixtures([]);
            /** @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            /** @var Foo $foo2 */
            $foo2 = $domain->newInstance();
            $foo2->setName('Bar');

            $objects = [$foo1, $foo2];
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

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());

        $resources = $domain->upserts($objects, true);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);

        $this->assertTrue($resources->hasErrors());
        $this->assertRegExp('/This value should not be blank./', $resources->get(0)->getErrors()->get(0)->getMessage());
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
    public function testUpsertsAutoCommitWithErrorDatabase($isUpdate): void
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            $objects[0]->setDetail(null);
            $objects[0]->setDescription('test 1');
            $objects[1]->setDescription('test 2');
        } else {
            $this->loadFixtures([]);
            /** @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            $foo1->setName('Bar');
            /** @var Foo $foo2 */
            $foo2 = $domain->newInstance();
            $foo2->setName('Bar');
            $foo2->setName('Detail');

            $objects = [$foo1, $foo2];
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

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());

        $resources = $domain->upserts($objects, true);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);

        $this->assertTrue($resources->hasErrors());

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
    public function testUpsertsAutoCommitWithErrorValidationAndSuccess($isUpdate): void
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            $objects[0]->setName(null);
            $objects[1]->setDetail('New Detail 2');
        } else {
            $this->loadFixtures([]);
            /** @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            /** @var Foo $foo2 */
            $foo2 = $domain->newInstance();
            $foo2->setName('Bar');
            $foo2->setDetail('Detail');

            $objects = [$foo1, $foo2];
        }

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());
        $resources = $domain->upserts($objects, true);
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
    public function testUpsertsAutoCommit($isUpdate): void
    {
        $this->runTestUpserts(true, $isUpdate);
    }

    public function runTestUpserts($autoCommit, $isUpdate): void
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            foreach ($objects as $i => $object) {
                $object->setName('New Bar '.($i + 1));
                $object->setDetail('New Detail '.($i + 1));
            }
        } else {
            $this->loadFixtures([]);
            /** @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            $foo1->setName('Bar 1');
            $foo1->setDetail('Detail 1');
            /** @var Foo $foo2 */
            $foo2 = $domain->newInstance();
            $foo2->setName('Bar 2');
            $foo2->setDetail('Detail 2');

            $objects = [$foo1, $foo2];
        }

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());
        $resources = $domain->upserts($objects, $autoCommit);
        $this->assertCount(2, $domain->getRepository()->findAll());

        $this->assertCount(2, $resources);
        $this->assertInstanceOf(ResourceInterface::class, $resources->get(0));
        $this->assertInstanceOf(ResourceInterface::class, $resources->get(1));

        $this->assertSame(ResourceListStatutes::SUCCESSFULLY, $resources->getStatus());
        $this->assertSame($isUpdate ? ResourceStatutes::UPDATED
            : ResourceStatutes::CREATED, $resources->get(0)->getStatus());
        $this->assertSame($isUpdate ? ResourceStatutes::UPDATED
            : ResourceStatutes::CREATED, $resources->get(1)->getStatus());
    }

    public function testInvalidObjectType(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "Fxp\\Component\\Resource\\Tests\\Fixtures\\Entity\\Foo", "integer" given at the position "0"');

        $domain = $this->createDomain();
        /** @var object $object */
        $object = 42;

        $domain->upsert($object);
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
            $object->setName('Bar');
            $object->setDetail('Detail');
        } else {
            $object = $this->insertResource($domain);
        }

        $resource = $domain->upsert($object);
        $this->assertTrue($resource->isValid());
    }

    protected function runTestUpsertException(DomainInterface $domain, $object, $errorMessage, $isUpdate): void
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
        $this->dispatcher->addListener(PostUpsertsEvent::class, function (PostUpsertsEvent $e) use (&$postEvent, $domain): void {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount($isUpdate ? 1 : 0, $domain->getRepository()->findAll());

        $resource = $domain->upsert($object);
        $this->assertCount(1, $resource->getErrors());
        $this->assertRegExp($errorMessage, $resource->getErrors()->get(0)->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount($isUpdate ? 1 : 0, $domain->getRepository()->findAll());
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

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());

        $resources = $domain->upserts($objects);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);
        $this->assertTrue($resources->hasErrors());

        /** @var ConstraintViolationListInterface $errors */
        $errors = $autoCommit
            ? $resources->get(0)->getErrors()
            : $resources->getErrors();
        $this->assertCount(1, $errors);
        $this->assertRegExp($errorMessage, $errors[0]->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());
        $this->assertSame($autoCommit ? ResourceListStatutes::MIXED
            : ResourceListStatutes::ERROR, $resources->getStatus());
    }
}
