<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Tests\Functional\Domain;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Sonatra\Bundle\ResourceBundle\ResourceStatutes;
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Entity\Bar;
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Listener\ErrorListener;
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Listener\SoftDeletableSubscriber;

/**
 * Functional tests for delete methods of Domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainDeleteTest extends AbstractDomainTest
{
    protected $softClass = 'Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Entity\Bar';

    public function testSoftDeletableListener()
    {
        $this->assertTrue($this->getContainer()->has('doctrine.orm.subscriber.soft_deletable'));
        /* @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /* @var SoftDeletableSubscriber $subscriber */
        $subscriber = $this->getContainer()->get('doctrine.orm.subscriber.soft_deletable');
        $subscriber->disable();

        $domain = $this->createDomain($this->softClass);
        $objects = $this->insertResources($domain, 2);

        $this->assertCount(2, $domain->getRepository()->findAll());

        $em->remove($objects[0]);
        $em->flush();
        $this->assertCount(1, $domain->getRepository()->findAll());

        $subscriber->enable();
        $objects = $domain->getRepository()->findAll();
        $this->assertCount(1, $objects);

        // soft delete
        $em->remove($objects[0]);
        $em->flush();
        /* @var Bar[] $objects */
        $objects = $domain->getRepository()->findAll();
        $this->assertCount(1, $objects);
        $this->assertTrue($objects[0]->isDeleted());

        // hard delete
        $em->remove($objects[0]);
        $em->flush();
        $this->assertCount(0, $domain->getRepository()->findAll());
    }

    public function getSoftDelete()
    {
        return array(
            array(false, true),
            array(true,  true),
            array(false, false),
            array(true,  false),
        );
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
            $this->assertCount(1, $objects);
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
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceListInterface', $resources);
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
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceListInterface', $resources);
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
        $this->loadFixtures(array());

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
        $this->loadFixtures(array());

        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = array($domain->newInstance(), $domain->newInstance());

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete);
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceListInterface', $resources);
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
        $this->loadFixtures(array());

        $domain = $withSoftObject ? $this->createDomain($this->softClass) : $this->createDomain();
        $objects = array($domain->newInstance(), $domain->newInstance());

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->deletes($objects, $softDelete, true);
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceListInterface', $resources);
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
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceListInterface', $resources);
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
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceListInterface', $resources);
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
        return array(
            array(false),
            array(true),
        );
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

        /* @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $em->remove($objects[0]);
        $em->remove($objects[1]);
        $em->flush();

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

        /* @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $em->getEventManager()->addEventListener(Events::preFlush, $errorListener);

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

        /* @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $em->getEventManager()->addEventListener(Events::preFlush, $errorListener);

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

        /* @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $em->getEventManager()->addEventListener(Events::preFlush, $errorListener);

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

        /* @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $em->getEventManager()->addEventListener(Events::preRemove, $errorListener);

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

        /* @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $em->getEventManager()->addEventListener(Events::preRemove, $errorListener);

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
