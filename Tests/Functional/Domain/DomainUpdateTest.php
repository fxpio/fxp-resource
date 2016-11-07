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
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Functional tests for update methods of Domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainUpdateTest extends AbstractDomainTest
{
    public function testUpdateWithErrorValidation()
    {
        $domain = $this->createDomain();
        $foo = $this->insertResource($domain);
        $foo->setName(null);

        $this->runTestUpdateException($domain, $foo, '/This value should not be blank./');
    }

    public function testUpdateWithErrorDatabase()
    {
        $domain = $this->createDomain();
        $foo = $this->insertResource($domain);
        $foo->setDetail(null);

        $this->runTestUpdateException($domain, $foo, $this->getIntegrityViolationMessage());
    }

    protected function runTestUpdateException(DomainInterface $domain, $object, $errorMessage)
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
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPDATES, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(1, $domain->getRepository()->findAll());

        $resource = $domain->update($object);
        $this->assertCount(1, $resource->getErrors());
        $this->assertRegExp($errorMessage, $resource->getErrors()->get(0)->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(1, $domain->getRepository()->findAll());
    }

    public function testUpdate()
    {
        $domain = $this->createDomain();
        $foo = $this->insertResource($domain);
        $foo->setName('Foo');

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

        $resource = $domain->update($foo);
        $this->assertCount(0, $resource->getErrors());
        $this->assertSame('Foo', $resource->getData()->getName());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(1, $domain->getRepository()->findAll());
    }

    public function testUpdatesWithErrorValidation()
    {
        $domain = $this->createDomain();
        $objects = $this->insertResources($domain, 2);

        foreach ($objects as $object) {
            $object->setName(null);
        }

        $this->runTestUpdatesException($domain, $objects, '/This value should not be blank./', true);
    }

    public function testUpdatesWithErrorDatabase()
    {
        $domain = $this->createDomain();
        $objects = $this->insertResources($domain, 2);

        foreach ($objects as $object) {
            $object->setDetail(null);
        }

        $this->runTestUpdatesException($domain, $objects, $this->getIntegrityViolationMessage(), false);
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

        /* @var ConstraintViolationListInterface $errors */
        $errors = $autoCommit
            ? $resources->get(0)->getErrors()
            : $resources->getErrors();
        $this->assertCount(1, $errors);
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

        $objects[0]->setName(null);
        $objects[1]->setDetail(null);

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

        $resources = $domain->updates($objects, true);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);

        $this->assertTrue($resources->hasErrors());
        $this->assertRegExp('/This value should not be blank./', $resources->get(0)->getErrors()->get(0)->getMessage());
        $this->assertRegExp($this->getIntegrityViolationMessage(), $resources->get(1)->getErrors()->get(0)->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(2, $domain->getRepository()->findAll());
    }

    public function testUpsertsAutoCommitWithErrorDatabase()
    {
        $domain = $this->createDomain();

        $objects = $this->insertResources($domain, 2);

        $objects[0]->setDetail(null);
        $objects[0]->setDescription('test 1');
        $objects[1]->setDescription('test 2');

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

        $resources = $domain->updates($objects, true);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);

        $this->assertTrue($resources->hasErrors());

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

        $objects[0]->setName(null);
        $objects[1]->setDetail('New Detail 2');

        $this->assertCount(2, $domain->getRepository()->findAll());
        $resources = $domain->updates($objects, true);
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

        foreach ($objects as $i => $object) {
            $object->setName('New Bar '.($i + 1));
            $object->setDetail('New Detail '.($i + 1));
        }

        $this->assertCount(2, $domain->getRepository()->findAll());
        $resources = $domain->updates($objects, $autoCommit);
        $this->assertCount(2, $domain->getRepository()->findAll());

        $this->assertCount(2, $resources);
        $this->assertInstanceOf(ResourceInterface::class, $resources->get(0));
        $this->assertInstanceOf(ResourceInterface::class, $resources->get(1));

        $this->assertSame(ResourceListStatutes::SUCCESSFULLY, $resources->getStatus());
        $this->assertSame(ResourceStatutes::UPDATED, $resources->get(0)->getStatus());
        $this->assertSame(ResourceStatutes::UPDATED, $resources->get(1)->getStatus());
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Sonatra\Component\Resource\Tests\Fixtures\Entity\Foo", "integer" given at the position "0"
     */
    public function testInvalidObjectType()
    {
        $domain = $this->createDomain();
        /* @var object $object */
        $object = 42;

        $domain->update($object);
    }

    public function testErrorIdentifier()
    {
        $domain = $this->createDomain();
        /* @var Foo $object */
        $object = $domain->newInstance();
        $object->setName('Bar');
        $object->setDetail('Detail');

        $this->loadFixtures(array());

        $resource = $domain->update($object);
        $this->assertFalse($resource->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
        $this->assertRegExp('/The resource cannot be updated because it has not an identifier/', $resource->getErrors()->get(0)->getMessage());
    }
}
