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
use Fxp\Component\Resource\Event\ResourceEvent;
use Fxp\Component\Resource\ResourceEvents;
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
 */
class DomainCreateTest extends AbstractDomainTest
{
    public function testCreateWithErrorValidation()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo */
        $foo = $domain->newInstance();

        $this->runTestCreateException($domain, $foo, '/This value should not be blank./');
    }

    public function testCreateWithErrorDatabase()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo */
        $foo = $domain->newInstance();
        $foo->setName('Bar');

        $this->runTestCreateException($domain, $foo, $this->getIntegrityViolationMessage());
    }

    protected function runTestCreateException(DomainInterface $domain, $object, $errorMessage)
    {
        $this->loadFixtures(array());

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_CREATES, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_CREATES, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resource = $domain->create($object);
        $this->assertCount(1, $resource->getErrors());
        $this->assertRegExp($errorMessage, $resource->getErrors()->get(0)->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(0, $domain->getRepository()->findAll());
    }

    public function testCreate()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo */
        $foo = $domain->newInstance();
        $foo->setName('Bar');
        $foo->setDetail('Detail');

        $this->loadFixtures(array());

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_CREATES, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_CREATES, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::CREATED, $resource->getStatus());
            }
        });

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resource = $domain->create($foo);
        $this->assertCount(0, $resource->getErrors());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(1, $domain->getRepository()->findAll());
    }

    public function testCreatesWithErrorValidation()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();

        $this->runTestCreatesException($domain, array($foo1, $foo2), '/This value should not be blank./', true);
    }

    public function testCreatesWithErrorDatabase()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        $foo1->setName('Bar');
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();
        $foo2->setName('Bar');

        $this->runTestCreatesException($domain, array($foo1, $foo2), $this->getIntegrityViolationMessage(), false);
    }

    protected function runTestCreatesException(DomainInterface $domain, array $objects, $errorMessage, $autoCommit = false)
    {
        $this->loadFixtures(array());

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_CREATES, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_CREATES, function (ResourceEvent $e) use (&$postEvent, $autoCommit, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            $resources = $e->getResources();
            $this->assertCount(2, $resources);
            $this->assertSame(ResourceStatutes::ERROR, $resources[0]->getStatus());
            $this->assertSame($autoCommit ? ResourceStatutes::CANCELED
                : ResourceStatutes::ERROR, $resources[1]->getStatus());
        });

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->creates($objects);
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

        $this->assertCount(0, $domain->getRepository()->findAll());
        $this->assertSame($autoCommit ? ResourceListStatutes::MIXED
            : ResourceListStatutes::ERROR, $resources->getStatus());
    }

    public function testCreates()
    {
        $this->runTestCreates(false);
    }

    public function testCreatesAutoCommit()
    {
        $this->runTestCreates(true);
    }

    protected function runTestCreates($autoCommit)
    {
        $domain = $this->createDomain();
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        $foo1->setName('Bar 1');
        $foo1->setDetail('Detail 1');
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();
        $foo2->setName('Bar 2');
        $foo2->setDetail('Detail 2');

        $objects = array($foo1, $foo2);

        $this->loadFixtures(array());

        $this->assertCount(0, $domain->getRepository()->findAll());
        $resources = $domain->creates($objects, $autoCommit);
        $this->assertCount(2, $domain->getRepository()->findAll());

        $this->assertCount(2, $resources);
        $this->assertInstanceOf(ResourceInterface::class, $resources->get(0));
        $this->assertInstanceOf(ResourceInterface::class, $resources->get(1));

        $this->assertSame(ResourceListStatutes::SUCCESSFULLY, $resources->getStatus());
        $this->assertSame(ResourceStatutes::CREATED, $resources->get(0)->getStatus());
        $this->assertSame(ResourceStatutes::CREATED, $resources->get(1)->getStatus());
    }

    public function testCreatesAutoCommitWithErrorValidationAndErrorDatabase()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();
        $foo2->setName('Bar');

        $objects = array($foo1, $foo2);

        $this->loadFixtures(array());

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_CREATES, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_CREATES, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->creates($objects, true);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);

        $this->assertTrue($resources->hasErrors());
        $this->assertRegExp('/This value should not be blank./', $resources->get(0)->getErrors()->get(0)->getMessage());
        $this->assertRegExp($this->getIntegrityViolationMessage(), $resources->get(1)->getErrors()->get(0)->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(0, $domain->getRepository()->findAll());
    }

    public function testCreatesAutoCommitWithErrorDatabase()
    {
        $domain = $this->createDomain();

        $this->loadFixtures(array());
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        $foo1->setName('Bar');
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();
        $foo2->setName('Bar');
        $foo2->setName('Detail');

        $objects = array($foo1, $foo2);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_CREATES, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_CREATES, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->creates($objects, true);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);

        $this->assertTrue($resources->hasErrors());

        $this->assertCount(1, $resources->get(0)->getErrors());
        $this->assertCount(1, $resources->get(1)->getErrors());

        $this->assertRegExp($this->getIntegrityViolationMessage(), $resources->get(0)->getErrors()->get(0)->getMessage());
        $this->assertRegExp('/Caused by previous internal database error/', $resources->get(1)->getErrors()->get(0)->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(0, $domain->getRepository()->findAll());
    }

    public function testCreatesAutoCommitWithErrorValidationAndSuccess()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();
        $foo2->setName('Bar');
        $foo2->setDetail('Detail');

        $objects = array($foo1, $foo2);

        $this->loadFixtures(array());

        $this->assertCount(0, $domain->getRepository()->findAll());
        $resources = $domain->creates($objects, true);
        $this->assertCount(1, $domain->getRepository()->findAll());

        $this->assertCount(2, $resources);
        $this->assertInstanceOf(ResourceInterface::class, $resources->get(0));
        $this->assertInstanceOf(ResourceInterface::class, $resources->get(1));

        $this->assertSame(ResourceListStatutes::MIXED, $resources->getStatus());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        $this->assertSame(ResourceStatutes::CREATED, $resources->get(1)->getStatus());
    }

    /**
     * @expectedException \Fxp\Component\Resource\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Fxp\Component\Resource\Tests\Fixtures\Entity\Foo", "integer" given at the position "0"
     */
    public function testInvalidObjectType()
    {
        $domain = $this->createDomain();
        /* @var object $object */
        $object = 42;

        $domain->create($object);
    }

    public function testErrorIdentifier()
    {
        $domain = $this->createDomain();
        $object = $this->insertResource($domain);

        $resource = $domain->create($object);
        $this->assertFalse($resource->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
        $this->assertRegExp('/The resource cannot be created because it has an identifier/', $resource->getErrors()->get(0)->getMessage());
    }

    public function testCreateAutoCommitErrorOnPrePersistAndSuccessObjectsWithViolationException()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        $foo1->setName('Bar 1');
        $foo1->setDetail('Detail 1');
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();
        $foo2->setName('Bar 2');
        $foo2->setDetail('Detail 2');

        $objects = array($foo1, $foo2);
        $errorListener = new ErrorListener('created', true);

        $this->loadFixtures(array());

        $this->em->getEventManager()->addEventListener(Events::prePersist, $errorListener);

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->creates($objects, true);
        $this->assertTrue($resources->hasErrors());
        $this->assertFalse($resources->get(0)->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        $this->assertSame('The entity does not created (violation exception)', $resources->get(0)->getErrors()->get(0)->getMessage());

        $this->assertFalse($resources->get(1)->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(1)->getStatus());
        $this->assertSame('Caused by previous internal database error', $resources->get(1)->getErrors()->get(0)->getMessage());
    }

    public function testCreateAutoCommitErrorOnPrePersistAndSuccessObjects()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        $foo1->setName('Bar 1');
        $foo1->setDetail('Detail 1');
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();
        $foo2->setName('Bar 2');
        $foo2->setDetail('Detail 2');

        $objects = array($foo1, $foo2);
        $errorListener = new ErrorListener('created', false);

        $this->loadFixtures(array());

        $this->em->getEventManager()->addEventListener(Events::prePersist, $errorListener);

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->creates($objects, true);
        $this->assertTrue($resources->hasErrors());
        $this->assertFalse($resources->get(0)->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        $this->assertSame('The entity does not created (exception)', $resources->get(0)->getErrors()->get(0)->getMessage());

        $this->assertFalse($resources->get(1)->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(1)->getStatus());
        $this->assertSame('Caused by previous internal database error', $resources->get(1)->getErrors()->get(0)->getMessage());
    }
}
