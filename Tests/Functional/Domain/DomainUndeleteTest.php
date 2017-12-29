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

use Doctrine\ORM\EntityManager;
use Fxp\Component\Resource\ResourceListStatutes;
use Fxp\Component\Resource\ResourceStatutes;
use Fxp\Component\Resource\Tests\Fixtures\Entity\Bar;
use Fxp\Component\Resource\Tests\Fixtures\Entity\Foo;
use Fxp\Component\Resource\Tests\Fixtures\Filter\SoftDeletableFilter;

/**
 * Functional tests for undelete methods of Domain.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class DomainUndeleteTest extends AbstractDomainTest
{
    protected $softClass = Bar::class;

    public function getAutoCommits()
    {
        return [
            [false],
            [true],
        ];
    }

    public function getResourceTypes()
    {
        return [
            ['object'],
            ['identifier'],
        ];
    }

    public function getAutoCommitsAndResourceTypes()
    {
        return [
            [false, 'object'],
            [false, 'identifier'],
            [true,  'object'],
            [true,  'identifier'],
        ];
    }

    /**
     * @dataProvider getResourceTypes
     *
     * @param string $resourceType
     */
    public function testUndeleteObject($resourceType)
    {
        $this->configureEntityManager();

        $domain = $this->createDomain($this->softClass);
        /* @var Bar $object */
        $object = $this->insertResource($domain);

        $this->assertCount(1, $domain->getRepository()->findAll());

        $em = $this->getEntityManager();
        $em->remove($object);
        $em->flush();

        $this->assertTrue($object->isDeleted());
        $this->assertCount(0, $domain->getRepository()->findAll());

        $em->getFilters()->disable('soft_deletable');
        $this->assertCount(1, $domain->getRepository()->findAll());
        $em->getFilters()->enable('soft_deletable');

        $em->clear();

        if ('object' === $resourceType) {
            $res = $domain->undelete($object);
        } else {
            $res = $domain->undelete(1);
        }

        $this->assertInstanceOf($domain->getClass(), $res->getRealData());
        $this->assertSame(ResourceStatutes::UNDELETED, $res->getStatus());
        $this->assertTrue($res->isValid());
    }

    /**
     * @dataProvider getAutoCommitsAndResourceTypes
     *
     * @param bool   $autoCommit
     * @param string $resourceType
     */
    public function testUndeleteObjects($autoCommit, $resourceType)
    {
        $this->configureEntityManager();

        $domain = $this->createDomain($this->softClass);
        /* @var Bar[] $objects */
        $objects = $this->insertResources($domain, 2);

        $this->assertCount(2, $domain->getRepository()->findAll());

        $em = $this->getEntityManager();
        $em->remove($objects[0]);
        $em->remove($objects[1]);
        $em->flush();

        $this->assertTrue($objects[0]->isDeleted());
        $this->assertTrue($objects[1]->isDeleted());
        $this->assertCount(0, $domain->getRepository()->findAll());

        $em->getFilters()->disable('soft_deletable');
        $this->assertCount(2, $domain->getRepository()->findAll());
        $em->getFilters()->enable('soft_deletable');

        $em->clear();

        if ('object' === $resourceType) {
            $res = $domain->undeletes([$objects[0], $objects[1]], $autoCommit);
        } else {
            $res = $domain->undeletes([1, 2], $autoCommit);
        }

        $this->assertFalse($res->hasErrors());
        $this->assertSame(ResourceListStatutes::SUCCESSFULLY, $res->getStatus());

        $this->assertInstanceOf($domain->getClass(), $res->get(0)->getRealData());
        $this->assertSame(ResourceStatutes::UNDELETED, $res->get(0)->getStatus());
        $this->assertTrue($res->get(0)->isValid());
        $this->assertInstanceOf($domain->getClass(), $res->get(1)->getRealData());
        $this->assertSame(ResourceStatutes::UNDELETED, $res->get(1)->getStatus());
        $this->assertTrue($res->get(1)->isValid());
    }

    /**
     * @dataProvider getResourceTypes
     *
     * @param string $resourceType
     */
    public function testUndeleteNonExistentObject($resourceType)
    {
        $this->configureEntityManager();
        $this->loadFixtures([]);

        $domain = $this->createDomain($this->softClass);
        /* @var Bar $object */
        $object = $domain->newInstance();

        $val = 'object' === $resourceType
            ? $object
            : 1;

        $res = $domain->undelete($val);
        $this->assertFalse($res->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $res->getStatus());
        $this->assertCount(2, $res->getErrors());

        if ('object' === $resourceType) {
            $this->assertSame('This value should not be blank.', $res->getErrors()->get(0)->getMessage());
            $this->assertSame('The resource cannot be undeleted because it has not an identifier', $res->getErrors()->get(1)->getMessage());
        } else {
            $this->assertSame('The object with the identifier "1" does not exist', $res->getErrors()->get(0)->getMessage());
            $this->assertSame('The resource type can not be undeleted', $res->getErrors()->get(1)->getMessage());
        }
    }

    /**
     * @dataProvider getAutoCommitsAndResourceTypes
     *
     * @param bool   $autoCommit
     * @param string $resourceType
     */
    public function testUndeleteNonExistentObjects($autoCommit, $resourceType)
    {
        $this->configureEntityManager();
        $this->loadFixtures([]);

        $domain = $this->createDomain($this->softClass);
        /* @var Bar $object */
        $objects = [$domain->newInstance(), $domain->newInstance()];

        $val = 'object' === $resourceType
            ? $objects
            : [1, 2];

        $res = $domain->undeletes($val, $autoCommit);

        $this->assertTrue($res->hasErrors());
        $this->assertSame(ResourceStatutes::ERROR, $res->get(0)->getStatus());
        $this->assertSame($autoCommit ? ResourceStatutes::ERROR
            : ResourceStatutes::CANCELED, $res->get(1)->getStatus());

        $this->assertCount(2, $res->get(0)->getErrors());

        if ('object' === $resourceType) {
            $this->assertSame('This value should not be blank.', $res->get(0)->getErrors()->get(0)->getMessage());
            $this->assertSame('The resource cannot be undeleted because it has not an identifier', $res->get(0)->getErrors()->get(1)->getMessage());

            if ($autoCommit) {
                $this->assertCount(2, $res->get(1)->getErrors());
                $this->assertSame('This value should not be blank.', $res->get(1)->getErrors()->get(0)->getMessage());
                $this->assertSame('The resource cannot be undeleted because it has not an identifier', $res->get(1)->getErrors()->get(1)->getMessage());
            } else {
                $this->assertCount(0, $res->get(1)->getErrors());
            }
        } else {
            $this->assertSame('The object with the identifier "1" does not exist', $res->get(0)->getErrors()->get(0)->getMessage());
            $this->assertSame('The resource type can not be undeleted', $res->get(0)->getErrors()->get(1)->getMessage());

            if ($autoCommit) {
                $this->assertCount(2, $res->get(1)->getErrors());
                $this->assertSame('The object with the identifier "2" does not exist', $res->get(1)->getErrors()->get(0)->getMessage());
                $this->assertSame('The resource type can not be undeleted', $res->get(1)->getErrors()->get(1)->getMessage());
            } else {
                $this->assertCount(1, $res->get(1)->getErrors());
            }
        }
    }

    /**
     * @dataProvider getAutoCommits
     *
     * @param bool $autoCommit
     */
    public function testUndeleteMixedIdentifiers($autoCommit)
    {
        $this->configureEntityManager();

        $successStatus = $autoCommit ? ResourceStatutes::UNDELETED : ResourceStatutes::CANCELED;
        $domain = $this->createDomain($this->softClass);
        /* @var Bar[] $objects */
        $objects = $this->insertResources($domain, 4);

        $this->assertCount(4, $domain->getRepository()->findAll());

        $em = $this->getEntityManager();
        $em->remove($objects[0]);
        $em->remove($objects[1]);
        $em->flush();

        $this->assertTrue($objects[0]->isDeleted());
        $this->assertTrue($objects[1]->isDeleted());
        $this->assertCount(2, $domain->getRepository()->findAll());

        $em->getFilters()->disable('soft_deletable');
        $this->assertCount(4, $domain->getRepository()->findAll());
        $em->getFilters()->enable('soft_deletable');

        $em->clear();

        $res = $domain->undeletes([0, $objects[0], 2], $autoCommit);
        $this->assertTrue($res->hasErrors());
        $this->assertSame(ResourceListStatutes::MIXED, $res->getStatus());

        $this->assertInstanceOf($domain->getClass(), $res->get(0)->getRealData());
        $this->assertSame($successStatus, $res->get(0)->getStatus());
        $this->assertTrue($res->get(0)->isValid());
        $this->assertInstanceOf($domain->getClass(), $res->get(1)->getRealData());
        $this->assertSame($successStatus, $res->get(1)->getStatus());
        $this->assertTrue($res->get(1)->isValid());
        $this->assertInstanceOf('stdClass', $res->get(2)->getRealData());
        $this->assertSame(ResourceStatutes::ERROR, $res->get(2)->getStatus());
        $this->assertFalse($res->get(2)->isValid());
    }

    public function testUndeleteAutoCommitNonExistentAndExistentObjects()
    {
        //TODO
        $this->assertNull(null);
    }

    public function testDeleteAutoCommitErrorAndSuccessObjects()
    {
        //TODO
        $this->assertNull(null);
    }

    /**
     * @dataProvider getResourceTypes
     *
     * @param string $resourceType
     */
    public function testUndeleteNonSoftDeletableObject($resourceType)
    {
        $this->loadFixtures([]);

        $domain = $this->createDomain();
        /* @var Foo $object */
        $object = $domain->newInstance();

        $val = 'object' === $resourceType
            ? $object
            : 1;

        $this->assertCount(0, $domain->getRepository()->findAll());

        $res = $domain->undelete($val);
        $this->assertFalse($res->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $res->getStatus());

        if ('object' === $resourceType) {
            $this->assertCount(1, $res->getErrors());
            $this->assertSame('The resource type can not be undeleted', $res->getErrors()->get(0)->getMessage());
        } else {
            $this->assertCount(2, $res->getErrors());
            $this->assertSame('The object with the identifier "1" does not exist', $res->getErrors()->get(0)->getMessage());
            $this->assertSame('The resource type can not be undeleted', $res->getErrors()->get(1)->getMessage());
        }
    }

    /**
     * @dataProvider getAutoCommitsAndResourceTypes
     *
     * @param bool   $autoCommit
     * @param string $resourceType
     */
    public function testUndeleteNonSoftDeletableObjects($autoCommit, $resourceType)
    {
        $this->loadFixtures([]);

        $domain = $this->createDomain();
        /* @var Foo[] $objects */
        $objects = [$domain->newInstance(), $domain->newInstance()];

        $val = 'object' === $resourceType
            ? $objects
            : [1, 2];

        $this->assertCount(0, $domain->getRepository()->findAll());

        $res = $domain->undeletes($val, $autoCommit);
        $this->assertTrue($res->hasErrors());
        $this->assertSame($autoCommit ? ResourceListStatutes::ERROR
            : ResourceListStatutes::MIXED, $res->getStatus());
        $this->assertSame(ResourceStatutes::ERROR, $res->get(0)->getStatus());
        $this->assertSame($autoCommit ? ResourceStatutes::ERROR
            : ResourceStatutes::CANCELED, $res->get(1)->getStatus());

        if ('object' === $resourceType) {
            $this->assertCount(1, $res->get(0)->getErrors());
            $this->assertSame('The resource type can not be undeleted', $res->get(0)->getErrors()->get(0)->getMessage());

            if ($autoCommit) {
                $this->assertCount(1, $res->get(1)->getErrors());
                $this->assertSame('The resource type can not be undeleted', $res->get(1)->getErrors()->get(0)->getMessage());
            } else {
                $this->assertCount(0, $res->get(1)->getErrors());
            }
        } else {
            $this->assertCount(2, $res->get(0)->getErrors());
            $this->assertSame('The object with the identifier "1" does not exist', $res->get(0)->getErrors()->get(0)->getMessage());
            $this->assertSame('The resource type can not be undeleted', $res->get(0)->getErrors()->get(1)->getMessage());

            if ($autoCommit) {
                $this->assertCount(2, $res->get(1)->getErrors());
                $this->assertSame('The object with the identifier "2" does not exist', $res->get(1)->getErrors()->get(0)->getMessage());
                $this->assertSame('The resource type can not be undeleted', $res->get(1)->getErrors()->get(1)->getMessage());
            } else {
                $this->assertCount(1, $res->get(1)->getErrors());
                $this->assertSame('The object with the identifier "2" does not exist', $res->get(1)->getErrors()->get(0)->getMessage());
            }
        }
    }

    protected function configureEntityManager()
    {
        $this->em->getConfiguration()
            ->addFilter('soft_deletable', SoftDeletableFilter::class);
        $this->em->getFilters()->enable('soft_deletable');
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->em;
    }
}
