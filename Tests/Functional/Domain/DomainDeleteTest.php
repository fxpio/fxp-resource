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
use Fxp\Component\Resource\ResourceListInterface;
use Fxp\Component\Resource\ResourceStatutes;
use Fxp\Component\Resource\Tests\Fixtures\Entity\Bar;
use Fxp\Component\Resource\Tests\Fixtures\Listener\ErrorListener;

/**
 * Functional tests for delete methods of Domain.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class DomainDeleteTest extends AbstractDomainTest
{
    protected $softClass = Bar::class;

    public function testSoftDeletableListener(): void
    {
        $this->softDeletable->disable();

        $domain = $this->createDomain($this->softClass);
        $objects = $this->insertResources($domain, 2);

        static::assertCount(2, $domain->getRepository()->findAll());

        $this->em->remove($objects[0]);
        $this->em->flush();
        static::assertCount(1, $domain->getRepository()->findAll());

        $this->softDeletable->enable();
        $objects = $domain->getRepository()->findAll();
        static::assertCount(1, $objects);

        // soft delete
        $this->em->remove($objects[0]);
        $this->em->flush();
        /** @var Bar[] $objects */
        $objects = $domain->getRepository()->findAll();
        static::assertCount(1, $objects);
        static::assertTrue($objects[0]->isDeleted());

        // hard delete
        $this->em->remove($objects[0]);
        $this->em->flush();
        static::assertCount(0, $domain->getRepository()->findAll());
    }

    public function getSoftDelete()
    {
        return [
            [false, true],
            [true,  true],
            [false, false],
            [true,  false],
        ];
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteObject($withSoftObject, $softDelete): void
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $object = $this->insertResource($domain);

        static::assertCount(1, $domain->getRepository()->findAll());

        $res = $domain->delete($object, $softDelete);

        static::assertTrue($res->isValid());
        static::assertSame(ResourceStatutes::DELETED, $res->getStatus());

        if (!$withSoftObject) {
            static::assertCount(0, $domain->getRepository()->findAll());
        } else {
            /** @var Bar[] $objects */
            $objects = $domain->getRepository()->findAll();
            static::assertCount($softDelete ? 1 : 0, $objects);
        }
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteObjects($withSoftObject, $softDelete): void
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = $this->insertResources($domain, 2);

        static::assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete);
        static::assertInstanceOf(ResourceListInterface::class, $resources);
        static::assertFalse($resources->hasErrors());

        foreach ($resources->all() as $resource) {
            static::assertTrue($resource->isValid());
            static::assertSame(ResourceStatutes::DELETED, $resource->getStatus());
        }

        if (!$withSoftObject) {
            static::assertCount(0, $domain->getRepository()->findAll());
        } elseif (!$softDelete) {
            static::assertCount(0, $domain->getRepository()->findAll());
        } else {
            /** @var Bar[] $objects */
            $objects = $domain->getRepository()->findAll();
            static::assertCount(2, $objects);

            foreach ($objects as $object) {
                static::assertTrue($object->isDeleted());
            }
        }
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteAutoCommitObjects($withSoftObject, $softDelete): void
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = $this->insertResources($domain, 2);

        static::assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete, true);
        static::assertInstanceOf(ResourceListInterface::class, $resources);
        static::assertFalse($resources->hasErrors());

        foreach ($resources->all() as $resource) {
            static::assertTrue($resource->isValid());
            static::assertSame(ResourceStatutes::DELETED, $resource->getStatus());
        }

        if (!$withSoftObject) {
            static::assertCount(0, $domain->getRepository()->findAll());
        } elseif (!$softDelete) {
            static::assertCount(0, $domain->getRepository()->findAll());
        } else {
            /** @var Bar[] $objects */
            $objects = $domain->getRepository()->findAll();
            static::assertCount(2, $objects);

            foreach ($objects as $object) {
                static::assertTrue($object->isDeleted());
            }
        }
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteNonExistentObject($withSoftObject, $softDelete): void
    {
        $this->loadFixtures([]);

        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $object = $domain->newInstance();

        static::assertCount(0, $domain->getRepository()->findAll());

        $res = $domain->delete($object, $softDelete);
        static::assertFalse($res->isValid());
        static::assertSame(ResourceStatutes::ERROR, $res->getStatus());

        static::assertCount(0, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteNonExistentObjects($withSoftObject, $softDelete): void
    {
        $this->loadFixtures([]);

        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = [$domain->newInstance(), $domain->newInstance()];

        static::assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete);
        static::assertInstanceOf(ResourceListInterface::class, $resources);
        static::assertTrue($resources->hasErrors());

        static::assertFalse($resources->get(0)->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        static::assertTrue($resources->get(1)->isValid());
        static::assertSame(ResourceStatutes::CANCELED, $resources->get(1)->getStatus());

        static::assertCount(0, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteAutoCommitNonExistentObjects($withSoftObject, $softDelete): void
    {
        $this->loadFixtures([]);

        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = [$domain->newInstance(), $domain->newInstance()];

        static::assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete, true);
        static::assertInstanceOf(ResourceListInterface::class, $resources);
        static::assertTrue($resources->hasErrors());

        foreach ($resources->all() as $resource) {
            static::assertFalse($resource->isValid());
            static::assertSame(ResourceStatutes::ERROR, $resource->getStatus());
        }

        static::assertCount(0, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteNonExistentAndExistentObjects($withSoftObject, $softDelete): void
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = $this->insertResources($domain, 1);
        array_unshift($objects, $domain->newInstance());

        static::assertCount(1, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete);
        static::assertInstanceOf(ResourceListInterface::class, $resources);
        static::assertTrue($resources->hasErrors());

        static::assertFalse($resources->get(0)->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        static::assertTrue($resources->get(1)->isValid());
        static::assertSame(ResourceStatutes::CANCELED, $resources->get(1)->getStatus());

        if (!$withSoftObject) {
            static::assertCount(1, $domain->getRepository()->findAll());
        } else {
            if (!$softDelete) {
                static::assertCount(1, $domain->getRepository()->findAll());
            } else {
                /** @var Bar[] $objects */
                $objects = $domain->getRepository()->findAll();
                static::assertCount(1, $objects);

                foreach ($objects as $object) {
                    static::assertFalse($object->isDeleted());
                }
            }
        }
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteAutoCommitNonExistentAndExistentObjects($withSoftObject, $softDelete): void
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = $this->insertResources($domain, 1);
        array_unshift($objects, $domain->newInstance());

        static::assertCount(1, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete, true);
        static::assertInstanceOf(ResourceListInterface::class, $resources);
        static::assertTrue($resources->hasErrors());

        static::assertFalse($resources->get(0)->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        static::assertTrue($resources->get(1)->isValid());
        static::assertSame(ResourceStatutes::DELETED, $resources->get(1)->getStatus());

        if (!$withSoftObject) {
            static::assertCount(0, $domain->getRepository()->findAll());
        } else {
            if (!$softDelete) {
                static::assertCount(0, $domain->getRepository()->findAll());
            } else {
                /** @var Bar[] $objects */
                $objects = $domain->getRepository()->findAll();
                static::assertCount(1, $objects);

                foreach ($objects as $object) {
                    static::assertTrue($object->isDeleted());
                }
            }
        }
    }

    public function getAutoCommits()
    {
        return [
            [false],
            [true],
        ];
    }

    /**
     * @dataProvider getAutoCommits
     *
     * @param bool $autoCommit
     */
    public function testDeleteSkipAlreadyDeletedObjects($autoCommit): void
    {
        $domain = $this->createDomain($this->softClass);
        $objects = $this->insertResources($domain, 2);

        $this->em->remove($objects[0]);
        $this->em->remove($objects[1]);
        $this->em->flush();

        static::assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, true, $autoCommit);
        foreach ($resources->all() as $resource) {
            static::assertSame(ResourceStatutes::DELETED, $resource->getStatus());
        }

        $objects = $domain->getRepository()->findAll();
        static::assertCount(2, $objects);

        $resources = $domain->deletes($objects, false, $autoCommit);
        foreach ($resources->all() as $resource) {
            static::assertSame(ResourceStatutes::DELETED, $resource->getStatus());
        }

        static::assertCount(0, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteErrorAndSuccessObjectsWithViolationException($withSoftObject, $softDelete): void
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = $this->insertResources($domain, 2);
        $errorListener = new ErrorListener('deleted', true);

        $this->em->getEventManager()->addEventListener(Events::preFlush, $errorListener);

        static::assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete);
        static::assertTrue($resources->hasErrors());
        static::assertFalse($resources->get(0)->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        static::assertSame('The entity does not deleted (violation exception)', $resources->get(0)->getErrors()->get(0)->getMessage());

        static::assertTrue($resources->get(1)->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(1)->getStatus());
        static::assertCount(0, $resources->get(1)->getErrors());
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteAutoCommitErrorAndSuccessObjects($withSoftObject, $softDelete): void
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = $this->insertResources($domain, 2);
        $errorListener = new ErrorListener('deleted');

        $this->em->getEventManager()->addEventListener(Events::preFlush, $errorListener);

        static::assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete, true);
        static::assertTrue($resources->hasErrors());
        static::assertFalse($resources->get(0)->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        static::assertSame('The entity does not deleted (exception)', $resources->get(0)->getErrors()->get(0)->getMessage());

        static::assertFalse($resources->get(1)->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(1)->getStatus());
        static::assertSame('Caused by previous internal database error', $resources->get(1)->getErrors()->get(0)->getMessage());
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteAutoCommitErrorAndSuccessObjectsWithViolationException($withSoftObject, $softDelete): void
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = $this->insertResources($domain, 2);
        $errorListener = new ErrorListener('deleted', true);

        $this->em->getEventManager()->addEventListener(Events::preFlush, $errorListener);

        static::assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete, true);
        static::assertTrue($resources->hasErrors());
        static::assertFalse($resources->get(0)->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        static::assertSame('The entity does not deleted (violation exception)', $resources->get(0)->getErrors()->get(0)->getMessage());

        static::assertFalse($resources->get(1)->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(1)->getStatus());
        static::assertSame('Caused by previous internal database error', $resources->get(1)->getErrors()->get(0)->getMessage());
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteAutoCommitErrorOnPreRemoveAndSuccessObjects($withSoftObject, $softDelete): void
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = $this->insertResources($domain, 2);
        $errorListener = new ErrorListener('deleted', false);

        $this->em->getEventManager()->addEventListener(Events::preRemove, $errorListener);

        static::assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete, true);
        static::assertTrue($resources->hasErrors());
        static::assertFalse($resources->get(0)->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        static::assertSame('The entity does not deleted (exception)', $resources->get(0)->getErrors()->get(0)->getMessage());

        static::assertFalse($resources->get(1)->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(1)->getStatus());
        static::assertSame('Caused by previous internal database error', $resources->get(1)->getErrors()->get(0)->getMessage());
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteAutoCommitErrorOnPreRemoveAndSuccessObjectsWithViolationException($withSoftObject, $softDelete): void
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = $this->insertResources($domain, 2);
        $errorListener = new ErrorListener('deleted', true);

        $this->em->getEventManager()->addEventListener(Events::preRemove, $errorListener);

        static::assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete, true);
        static::assertTrue($resources->hasErrors());
        static::assertFalse($resources->get(0)->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        static::assertSame('The entity does not deleted (violation exception)', $resources->get(0)->getErrors()->get(0)->getMessage());

        static::assertFalse($resources->get(1)->isValid());
        static::assertSame(ResourceStatutes::ERROR, $resources->get(1)->getStatus());
        static::assertSame('Caused by previous internal database error', $resources->get(1)->getErrors()->get(0)->getMessage());
    }
}
