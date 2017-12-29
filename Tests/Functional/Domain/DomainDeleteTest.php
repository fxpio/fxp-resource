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
 */
class DomainDeleteTest extends AbstractDomainTest
{
    protected $softClass = Bar::class;

    public function testSoftDeletableListener()
    {
        $this->softDeletable->disable();

        $domain = $this->createDomain($this->softClass);
        $objects = $this->insertResources($domain, 2);

        $this->assertCount(2, $domain->getRepository()->findAll());

        $this->em->remove($objects[0]);
        $this->em->flush();
        $this->assertCount(1, $domain->getRepository()->findAll());

        $this->softDeletable->enable();
        $objects = $domain->getRepository()->findAll();
        $this->assertCount(1, $objects);

        // soft delete
        $this->em->remove($objects[0]);
        $this->em->flush();
        /* @var Bar[] $objects */
        $objects = $domain->getRepository()->findAll();
        $this->assertCount(1, $objects);
        $this->assertTrue($objects[0]->isDeleted());

        // hard delete
        $this->em->remove($objects[0]);
        $this->em->flush();
        $this->assertCount(0, $domain->getRepository()->findAll());
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
    public function testDeleteObject($withSoftObject, $softDelete)
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $object = $this->insertResource($domain);

        $this->assertCount(1, $domain->getRepository()->findAll());

        $res = $domain->delete($object, $softDelete);

        $this->assertTrue($res->isValid());
        $this->assertSame(ResourceStatutes::DELETED, $res->getStatus());

        if (!$withSoftObject) {
            $this->assertCount(0, $domain->getRepository()->findAll());
        } else {
            /* @var Bar[] $objects */
            $objects = $domain->getRepository()->findAll();
            $this->assertCount($softDelete ? 1 : 0, $objects);
        }
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteObjects($withSoftObject, $softDelete)
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = $this->insertResources($domain, 2);

        $this->assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);
        $this->assertFalse($resources->hasErrors());

        foreach ($resources->all() as $resource) {
            $this->assertTrue($resource->isValid());
            $this->assertSame(ResourceStatutes::DELETED, $resource->getStatus());
        }

        if (!$withSoftObject) {
            $this->assertCount(0, $domain->getRepository()->findAll());
        } elseif (!$softDelete) {
            $this->assertCount(0, $domain->getRepository()->findAll());
        } else {
            /* @var Bar[] $objects */
            $objects = $domain->getRepository()->findAll();
            $this->assertCount(2, $objects);

            foreach ($objects as $object) {
                $this->assertTrue($object->isDeleted());
            }
        }
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteAutoCommitObjects($withSoftObject, $softDelete)
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = $this->insertResources($domain, 2);

        $this->assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete, true);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);
        $this->assertFalse($resources->hasErrors());

        foreach ($resources->all() as $resource) {
            $this->assertTrue($resource->isValid());
            $this->assertSame(ResourceStatutes::DELETED, $resource->getStatus());
        }

        if (!$withSoftObject) {
            $this->assertCount(0, $domain->getRepository()->findAll());
        } elseif (!$softDelete) {
            $this->assertCount(0, $domain->getRepository()->findAll());
        } else {
            /* @var Bar[] $objects */
            $objects = $domain->getRepository()->findAll();
            $this->assertCount(2, $objects);

            foreach ($objects as $object) {
                $this->assertTrue($object->isDeleted());
            }
        }
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteNonExistentObject($withSoftObject, $softDelete)
    {
        $this->loadFixtures([]);

        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $object = $domain->newInstance();

        $this->assertCount(0, $domain->getRepository()->findAll());

        $res = $domain->delete($object, $softDelete);
        $this->assertFalse($res->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $res->getStatus());

        $this->assertCount(0, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteNonExistentObjects($withSoftObject, $softDelete)
    {
        $this->loadFixtures([]);

        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = [$domain->newInstance(), $domain->newInstance()];

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);
        $this->assertTrue($resources->hasErrors());

        $this->assertFalse($resources->get(0)->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        $this->assertTrue($resources->get(1)->isValid());
        $this->assertSame(ResourceStatutes::CANCELED, $resources->get(1)->getStatus());

        $this->assertCount(0, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteAutoCommitNonExistentObjects($withSoftObject, $softDelete)
    {
        $this->loadFixtures([]);

        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = [$domain->newInstance(), $domain->newInstance()];

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete, true);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);
        $this->assertTrue($resources->hasErrors());

        foreach ($resources->all() as $resource) {
            $this->assertFalse($resource->isValid());
            $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
        }

        $this->assertCount(0, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteNonExistentAndExistentObjects($withSoftObject, $softDelete)
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = $this->insertResources($domain, 1);
        array_unshift($objects, $domain->newInstance());

        $this->assertCount(1, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);
        $this->assertTrue($resources->hasErrors());

        $this->assertFalse($resources->get(0)->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        $this->assertTrue($resources->get(1)->isValid());
        $this->assertSame(ResourceStatutes::CANCELED, $resources->get(1)->getStatus());

        if (!$withSoftObject) {
            $this->assertCount(1, $domain->getRepository()->findAll());
        } else {
            if (!$softDelete) {
                $this->assertCount(1, $domain->getRepository()->findAll());
            } else {
                /* @var Bar[] $objects */
                $objects = $domain->getRepository()->findAll();
                $this->assertCount(1, $objects);

                foreach ($objects as $object) {
                    $this->assertFalse($object->isDeleted());
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
    public function testDeleteAutoCommitNonExistentAndExistentObjects($withSoftObject, $softDelete)
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = $this->insertResources($domain, 1);
        array_unshift($objects, $domain->newInstance());

        $this->assertCount(1, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete, true);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);
        $this->assertTrue($resources->hasErrors());

        $this->assertFalse($resources->get(0)->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        $this->assertTrue($resources->get(1)->isValid());
        $this->assertSame(ResourceStatutes::DELETED, $resources->get(1)->getStatus());

        if (!$withSoftObject) {
            $this->assertCount(0, $domain->getRepository()->findAll());
        } else {
            if (!$softDelete) {
                $this->assertCount(0, $domain->getRepository()->findAll());
            } else {
                /* @var Bar[] $objects */
                $objects = $domain->getRepository()->findAll();
                $this->assertCount(1, $objects);

                foreach ($objects as $object) {
                    $this->assertTrue($object->isDeleted());
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
    public function testDeleteSkipAlreadyDeletedObjects($autoCommit)
    {
        $domain = $this->createDomain($this->softClass);
        $objects = $this->insertResources($domain, 2);

        $this->em->remove($objects[0]);
        $this->em->remove($objects[1]);
        $this->em->flush();

        $this->assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, true, $autoCommit);
        foreach ($resources->all() as $resource) {
            $this->assertSame(ResourceStatutes::DELETED, $resource->getStatus());
        }

        $objects = $domain->getRepository()->findAll();
        $this->assertCount(2, $objects);

        $resources = $domain->deletes($objects, false, $autoCommit);
        foreach ($resources->all() as $resource) {
            $this->assertSame(ResourceStatutes::DELETED, $resource->getStatus());
        }

        $this->assertCount(0, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteErrorAndSuccessObjectsWithViolationException($withSoftObject, $softDelete)
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = $this->insertResources($domain, 2);
        $errorListener = new ErrorListener('deleted', true);

        $this->em->getEventManager()->addEventListener(Events::preFlush, $errorListener);

        $this->assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete);
        $this->assertTrue($resources->hasErrors());
        $this->assertFalse($resources->get(0)->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        $this->assertSame('The entity does not deleted (violation exception)', $resources->get(0)->getErrors()->get(0)->getMessage());

        $this->assertTrue($resources->get(1)->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(1)->getStatus());
        $this->assertCount(0, $resources->get(1)->getErrors());
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteAutoCommitErrorAndSuccessObjects($withSoftObject, $softDelete)
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = $this->insertResources($domain, 2);
        $errorListener = new ErrorListener('deleted');

        $this->em->getEventManager()->addEventListener(Events::preFlush, $errorListener);

        $this->assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete, true);
        $this->assertTrue($resources->hasErrors());
        $this->assertFalse($resources->get(0)->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        $this->assertSame('The entity does not deleted (exception)', $resources->get(0)->getErrors()->get(0)->getMessage());

        $this->assertFalse($resources->get(1)->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(1)->getStatus());
        $this->assertSame('Caused by previous internal database error', $resources->get(1)->getErrors()->get(0)->getMessage());
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteAutoCommitErrorAndSuccessObjectsWithViolationException($withSoftObject, $softDelete)
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = $this->insertResources($domain, 2);
        $errorListener = new ErrorListener('deleted', true);

        $this->em->getEventManager()->addEventListener(Events::preFlush, $errorListener);

        $this->assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete, true);
        $this->assertTrue($resources->hasErrors());
        $this->assertFalse($resources->get(0)->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        $this->assertSame('The entity does not deleted (violation exception)', $resources->get(0)->getErrors()->get(0)->getMessage());

        $this->assertFalse($resources->get(1)->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(1)->getStatus());
        $this->assertSame('Caused by previous internal database error', $resources->get(1)->getErrors()->get(0)->getMessage());
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteAutoCommitErrorOnPreRemoveAndSuccessObjects($withSoftObject, $softDelete)
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = $this->insertResources($domain, 2);
        $errorListener = new ErrorListener('deleted', false);

        $this->em->getEventManager()->addEventListener(Events::preRemove, $errorListener);

        $this->assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete, true);
        $this->assertTrue($resources->hasErrors());
        $this->assertFalse($resources->get(0)->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        $this->assertSame('The entity does not deleted (exception)', $resources->get(0)->getErrors()->get(0)->getMessage());

        $this->assertFalse($resources->get(1)->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(1)->getStatus());
        $this->assertSame('Caused by previous internal database error', $resources->get(1)->getErrors()->get(0)->getMessage());
    }

    /**
     * @dataProvider getSoftDelete
     *
     * @param bool $withSoftObject
     * @param bool $softDelete
     */
    public function testDeleteAutoCommitErrorOnPreRemoveAndSuccessObjectsWithViolationException($withSoftObject, $softDelete)
    {
        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = $this->insertResources($domain, 2);
        $errorListener = new ErrorListener('deleted', true);

        $this->em->getEventManager()->addEventListener(Events::preRemove, $errorListener);

        $this->assertCount(2, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete, true);
        $this->assertTrue($resources->hasErrors());
        $this->assertFalse($resources->get(0)->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        $this->assertSame('The entity does not deleted (violation exception)', $resources->get(0)->getErrors()->get(0)->getMessage());

        $this->assertFalse($resources->get(1)->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(1)->getStatus());
        $this->assertSame('Caused by previous internal database error', $resources->get(1)->getErrors()->get(0)->getMessage());
    }
}
