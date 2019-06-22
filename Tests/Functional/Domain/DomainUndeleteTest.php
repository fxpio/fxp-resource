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
 *
 * @internal
 */
final class DomainUndeleteTest extends AbstractDomainTest
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
    public function testUndeleteObject($resourceType): void
    {
        $this->configureEntityManager();

        $domain = $this->createDomain($this->softClass);
        /** @var Bar $object */
        $object = $this->insertResource($domain);

        static::assertCount(1, $domain->getRepository()->findAll());

        $em = $this->getEntityManager();
        $em->remove($object);
        $em->flush();

        static::assertTrue($object->isDeleted());
        static::assertCount(0, $domain->getRepository()->findAll());

        $em->getFilters()->disable('soft_deletable');
        static::assertCount(1, $domain->getRepository()->findAll());
        $em->getFilters()->enable('soft_deletable');

        $em->clear();

        if ('object' === $resourceType) {
            $res = $domain->undelete($object);
        } else {
            $res = $domain->undelete(1);
        }

        static::assertInstanceOf($domain->getClass(), $res->getRealData());
        static::assertSame(ResourceStatutes::UNDELETED, $res->getStatus());
        static::assertTrue($res->isValid());
    }

    /**
     * @dataProvider getAutoCommitsAndResourceTypes
     *
     * @param bool   $autoCommit
     * @param string $resourceType
     */
    public function testUndeleteObjects($autoCommit, $resourceType): void
    {
        $this->configureEntityManager();

        $domain = $this->createDomain($this->softClass);
        /** @var Bar[] $objects */
        $objects = $this->insertResources($domain, 2);

        static::assertCount(2, $domain->getRepository()->findAll());

        $em = $this->getEntityManager();
        $em->remove($objects[0]);
        $em->remove($objects[1]);
        $em->flush();

        static::assertTrue($objects[0]->isDeleted());
        static::assertTrue($objects[1]->isDeleted());
        static::assertCount(0, $domain->getRepository()->findAll());

        $em->getFilters()->disable('soft_deletable');
        static::assertCount(2, $domain->getRepository()->findAll());
        $em->getFilters()->enable('soft_deletable');

        $em->clear();

        if ('object' === $resourceType) {
            $res = $domain->undeletes([$objects[0], $objects[1]], $autoCommit);
        } else {
            $res = $domain->undeletes([1, 2], $autoCommit);
        }

        static::assertFalse($res->hasErrors());
        static::assertSame(ResourceListStatutes::SUCCESSFULLY, $res->getStatus());

        static::assertInstanceOf($domain->getClass(), $res->get(0)->getRealData());
        static::assertSame(ResourceStatutes::UNDELETED, $res->get(0)->getStatus());
        static::assertTrue($res->get(0)->isValid());
        static::assertInstanceOf($domain->getClass(), $res->get(1)->getRealData());
        static::assertSame(ResourceStatutes::UNDELETED, $res->get(1)->getStatus());
        static::assertTrue($res->get(1)->isValid());
    }

    /**
     * @dataProvider getResourceTypes
     *
     * @param string $resourceType
     */
    public function testUndeleteNonExistentObject($resourceType): void
    {
        $this->configureEntityManager();
        $this->loadFixtures([]);

        $domain = $this->createDomain($this->softClass);
        /** @var Bar $object */
        $object = $domain->newInstance();

        $val = 'object' === $resourceType
            ? $object
            : 1;

        $res = $domain->undelete($val);
        static::assertFalse($res->isValid());
        static::assertSame(ResourceStatutes::ERROR, $res->getStatus());
        static::assertCount(2, $res->getErrors());

        if ('object' === $resourceType) {
            static::assertSame('This value should not be blank.', $res->getErrors()->get(0)->getMessage());
            static::assertSame('The resource cannot be undeleted because it has not an identifier', $res->getErrors()->get(1)->getMessage());
        } else {
            static::assertSame('The object with the identifier "1" does not exist', $res->getErrors()->get(0)->getMessage());
            static::assertSame('The resource type can not be undeleted', $res->getErrors()->get(1)->getMessage());
        }
    }

    /**
     * @dataProvider getAutoCommitsAndResourceTypes
     *
     * @param bool   $autoCommit
     * @param string $resourceType
     */
    public function testUndeleteNonExistentObjects($autoCommit, $resourceType): void
    {
        $this->configureEntityManager();
        $this->loadFixtures([]);

        $domain = $this->createDomain($this->softClass);
        /** @var Bar $object */
        $objects = [$domain->newInstance(), $domain->newInstance()];

        $val = 'object' === $resourceType
            ? $objects
            : [1, 2];

        $res = $domain->undeletes($val, $autoCommit);

        static::assertTrue($res->hasErrors());
        static::assertSame(ResourceStatutes::ERROR, $res->get(0)->getStatus());
        static::assertSame($autoCommit ? ResourceStatutes::ERROR
            : ResourceStatutes::CANCELED, $res->get(1)->getStatus());

        static::assertCount(2, $res->get(0)->getErrors());

        if ('object' === $resourceType) {
            static::assertSame('This value should not be blank.', $res->get(0)->getErrors()->get(0)->getMessage());
            static::assertSame('The resource cannot be undeleted because it has not an identifier', $res->get(0)->getErrors()->get(1)->getMessage());

            if ($autoCommit) {
                static::assertCount(2, $res->get(1)->getErrors());
                static::assertSame('This value should not be blank.', $res->get(1)->getErrors()->get(0)->getMessage());
                static::assertSame('The resource cannot be undeleted because it has not an identifier', $res->get(1)->getErrors()->get(1)->getMessage());
            } else {
                static::assertCount(0, $res->get(1)->getErrors());
            }
        } else {
            static::assertSame('The object with the identifier "1" does not exist', $res->get(0)->getErrors()->get(0)->getMessage());
            static::assertSame('The resource type can not be undeleted', $res->get(0)->getErrors()->get(1)->getMessage());

            if ($autoCommit) {
                static::assertCount(2, $res->get(1)->getErrors());
                static::assertSame('The object with the identifier "2" does not exist', $res->get(1)->getErrors()->get(0)->getMessage());
                static::assertSame('The resource type can not be undeleted', $res->get(1)->getErrors()->get(1)->getMessage());
            } else {
                static::assertCount(1, $res->get(1)->getErrors());
            }
        }
    }

    /**
     * @dataProvider getAutoCommits
     *
     * @param bool $autoCommit
     */
    public function testUndeleteMixedIdentifiers($autoCommit): void
    {
        $this->configureEntityManager();

        $successStatus = $autoCommit ? ResourceStatutes::UNDELETED : ResourceStatutes::CANCELED;
        $domain = $this->createDomain($this->softClass);
        /** @var Bar[] $objects */
        $objects = $this->insertResources($domain, 4);

        static::assertCount(4, $domain->getRepository()->findAll());

        $em = $this->getEntityManager();
        $em->remove($objects[0]);
        $em->remove($objects[1]);
        $em->flush();

        static::assertTrue($objects[0]->isDeleted());
        static::assertTrue($objects[1]->isDeleted());
        static::assertCount(2, $domain->getRepository()->findAll());

        $em->getFilters()->disable('soft_deletable');
        static::assertCount(4, $domain->getRepository()->findAll());
        $em->getFilters()->enable('soft_deletable');

        $em->clear();

        $res = $domain->undeletes([0, $objects[0], 2], $autoCommit);
        static::assertTrue($res->hasErrors());
        static::assertSame(ResourceListStatutes::MIXED, $res->getStatus());

        static::assertInstanceOf($domain->getClass(), $res->get(0)->getRealData());
        static::assertSame($successStatus, $res->get(0)->getStatus());
        static::assertTrue($res->get(0)->isValid());
        static::assertInstanceOf($domain->getClass(), $res->get(1)->getRealData());
        static::assertSame($successStatus, $res->get(1)->getStatus());
        static::assertTrue($res->get(1)->isValid());
        static::assertInstanceOf('stdClass', $res->get(2)->getRealData());
        static::assertSame(ResourceStatutes::ERROR, $res->get(2)->getStatus());
        static::assertFalse($res->get(2)->isValid());
    }

    public function testUndeleteAutoCommitNonExistentAndExistentObjects(): void
    {
        //TODO
        static::assertNull(null);
    }

    public function testDeleteAutoCommitErrorAndSuccessObjects(): void
    {
        //TODO
        static::assertNull(null);
    }

    /**
     * @dataProvider getResourceTypes
     *
     * @param string $resourceType
     */
    public function testUndeleteNonSoftDeletableObject($resourceType): void
    {
        $this->loadFixtures([]);

        $domain = $this->createDomain();
        /** @var Foo $object */
        $object = $domain->newInstance();

        $val = 'object' === $resourceType
            ? $object
            : 1;

        static::assertCount(0, $domain->getRepository()->findAll());

        $res = $domain->undelete($val);
        static::assertFalse($res->isValid());
        static::assertSame(ResourceStatutes::ERROR, $res->getStatus());

        if ('object' === $resourceType) {
            static::assertCount(1, $res->getErrors());
            static::assertSame('The resource type can not be undeleted', $res->getErrors()->get(0)->getMessage());
        } else {
            static::assertCount(2, $res->getErrors());
            static::assertSame('The object with the identifier "1" does not exist', $res->getErrors()->get(0)->getMessage());
            static::assertSame('The resource type can not be undeleted', $res->getErrors()->get(1)->getMessage());
        }
    }

    /**
     * @dataProvider getAutoCommitsAndResourceTypes
     *
     * @param bool   $autoCommit
     * @param string $resourceType
     */
    public function testUndeleteNonSoftDeletableObjects($autoCommit, $resourceType): void
    {
        $this->loadFixtures([]);

        $domain = $this->createDomain();
        /** @var Foo[] $objects */
        $objects = [$domain->newInstance(), $domain->newInstance()];

        $val = 'object' === $resourceType
            ? $objects
            : [1, 2];

        static::assertCount(0, $domain->getRepository()->findAll());

        $res = $domain->undeletes($val, $autoCommit);
        static::assertTrue($res->hasErrors());
        static::assertSame($autoCommit ? ResourceListStatutes::ERROR
            : ResourceListStatutes::MIXED, $res->getStatus());
        static::assertSame(ResourceStatutes::ERROR, $res->get(0)->getStatus());
        static::assertSame($autoCommit ? ResourceStatutes::ERROR
            : ResourceStatutes::CANCELED, $res->get(1)->getStatus());

        if ('object' === $resourceType) {
            static::assertCount(1, $res->get(0)->getErrors());
            static::assertSame('The resource type can not be undeleted', $res->get(0)->getErrors()->get(0)->getMessage());

            if ($autoCommit) {
                static::assertCount(1, $res->get(1)->getErrors());
                static::assertSame('The resource type can not be undeleted', $res->get(1)->getErrors()->get(0)->getMessage());
            } else {
                static::assertCount(0, $res->get(1)->getErrors());
            }
        } else {
            static::assertCount(2, $res->get(0)->getErrors());
            static::assertSame('The object with the identifier "1" does not exist', $res->get(0)->getErrors()->get(0)->getMessage());
            static::assertSame('The resource type can not be undeleted', $res->get(0)->getErrors()->get(1)->getMessage());

            if ($autoCommit) {
                static::assertCount(2, $res->get(1)->getErrors());
                static::assertSame('The object with the identifier "2" does not exist', $res->get(1)->getErrors()->get(0)->getMessage());
                static::assertSame('The resource type can not be undeleted', $res->get(1)->getErrors()->get(1)->getMessage());
            } else {
                static::assertCount(1, $res->get(1)->getErrors());
                static::assertSame('The object with the identifier "2" does not exist', $res->get(1)->getErrors()->get(0)->getMessage());
            }
        }
    }

    protected function configureEntityManager(): void
    {
        $this->em->getConfiguration()
            ->addFilter('soft_deletable', SoftDeletableFilter::class)
        ;
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
