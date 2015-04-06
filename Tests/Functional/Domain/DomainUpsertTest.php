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

use Sonatra\Bundle\ResourceBundle\Domain\DomainInterface;
use Sonatra\Bundle\ResourceBundle\Event\ResourceEvent;
use Sonatra\Bundle\ResourceBundle\ResourceEvents;
use Sonatra\Bundle\ResourceBundle\ResourceListStatutes;
use Sonatra\Bundle\ResourceBundle\ResourceStatutes;
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Entity\Foo;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Functional tests for upsert methods of Domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainUpsertTest extends AbstractDomainTest
{
    public function getUpsertType()
    {
        return array(
            array(false),
            array(true),
        );
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertWithErrorValidation($isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $foo = $this->insertResource($domain);
            $foo->setName(null);
        } else {
            $this->loadFixtures(array());
            /* @var Foo $foo */
            $foo = $domain->newInstance();
        }

        $this->runTestUpsertException($domain, $foo, '/This value should not be blank./', $isUpdate);
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertWithErrorDatabase($isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $foo = $this->insertResource($domain);
            $foo->setDetail(null);
        } else {
            $this->loadFixtures(array());
            /* @var Foo $foo */
            $foo = $domain->newInstance();
            $foo->setName('Bar');
        }

        $this->runTestUpsertException($domain, $foo, '/Database error code "(\d+)"/', $isUpdate);
    }

    protected function runTestUpsertException(DomainInterface $domain, $object, $errorMessage, $isUpdate)
    {
        $preEvent = false;
        $postEvent = false;
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPSERTS, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPSERTS, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
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

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsert($isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $foo = $this->insertResource($domain);
            $foo->setName('Foo');
        } else {
            $this->loadFixtures(array());
            /* @var Foo $foo */
            $foo = $domain->newInstance();
            $foo->setName('Bar');
            $foo->setDetail('Detail');
        }

        $preEvent = false;
        $postEvent = false;
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPSERTS, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPSERTS, function (ResourceEvent $e) use (&$postEvent, $domain, $isUpdate) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
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
    public function testUpsertsWithErrorValidation($isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            foreach ($objects as $object) {
                $object->setName(null);
            }
        } else {
            $this->loadFixtures(array());
            /* @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            /* @var Foo $foo2 */
            $foo2 = $domain->newInstance();
            $objects = array($foo1, $foo2);
        }

        $this->runTestUpsertsException($domain, $objects, '/This value should not be blank./', true, $isUpdate);
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertsWithErrorDatabase($isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            foreach ($objects as $object) {
                $object->setDetail(null);
            }
        } else {
            $this->loadFixtures(array());
            /* @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            $foo1->setName('Bar');
            /* @var Foo $foo2 */
            $foo2 = $domain->newInstance();
            $foo2->setName('Bar');
            $objects = array($foo1, $foo2);
        }

        $this->runTestUpsertsException($domain, $objects, '/Database error code "(\d+)"/', false, $isUpdate);
    }

    protected function runTestUpsertsException(DomainInterface $domain, array $objects, $errorMessage, $autoCommit = false, $isUpdate = false)
    {
        $preEvent = false;
        $postEvent = false;
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPSERTS, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPSERTS, function (ResourceEvent $e) use (&$postEvent, $autoCommit, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            $resources = $e->getResources();
            $this->assertCount(2, $resources);
            $this->assertSame(ResourceStatutes::ERROR, $resources[0]->getStatus());
            $this->assertSame($autoCommit ? ResourceStatutes::CANCELED
                : ResourceStatutes::ERROR, $resources[1]->getStatus());
        });

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());

        $resources = $domain->upserts($objects);
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceListInterface', $resources);
        $this->assertTrue($resources->hasErrors());

        /* @var ConstraintViolationListInterface $errors */
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

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpserts($isUpdate)
    {
        $this->runTestUpserts(false, $isUpdate);
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertsAutoCommitWithErrorValidationAndErrorDatabase($isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            $objects[0]->setName(null);
            $objects[1]->setDetail(null);
        } else {
            $this->loadFixtures(array());
            /* @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            /* @var Foo $foo2 */
            $foo2 = $domain->newInstance();
            $foo2->setName('Bar');

            $objects = array($foo1, $foo2);
        }

        $preEvent = false;
        $postEvent = false;
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPSERTS, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPSERTS, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());

        $resources = $domain->upserts($objects, true);
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceListInterface', $resources);

        $this->assertTrue($resources->hasErrors());
        $this->assertRegExp('/This value should not be blank./', $resources->get(0)->getErrors()->get(0)->getMessage());
        $this->assertRegExp('/Database error code "(\d+)"/', $resources->get(1)->getErrors()->get(0)->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testUpsertsAutoCommitWithErrorDatabase($isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            $objects[0]->setDetail(null);
            $objects[0]->setDescription('test 1');
            $objects[1]->setDescription('test 2');
        } else {
            $this->loadFixtures(array());
            /* @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            $foo1->setName('Bar');
            /* @var Foo $foo2 */
            $foo2 = $domain->newInstance();
            $foo2->setName('Bar');
            $foo2->setName('Detail');

            $objects = array($foo1, $foo2);
        }

        $preEvent = false;
        $postEvent = false;
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_UPSERTS, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_UPSERTS, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());

        $resources = $domain->upserts($objects, true);
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceListInterface', $resources);

        $this->assertTrue($resources->hasErrors());

        $this->assertCount(1, $resources->get(0)->getErrors());
        $this->assertCount(1, $resources->get(1)->getErrors());

        $this->assertRegExp('/Database error code "(\d+)"/', $resources->get(0)->getErrors()->get(0)->getMessage());
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
    public function testUpsertsAutoCommitWithErrorValidationAndSuccess($isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            $objects[0]->setName(null);
            $objects[1]->setDetail('New Detail 2');
        } else {
            $this->loadFixtures(array());
            /* @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            /* @var Foo $foo2 */
            $foo2 = $domain->newInstance();
            $foo2->setName('Bar');
            $foo2->setDetail('Detail');

            $objects = array($foo1, $foo2);
        }

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());
        $resources = $domain->upserts($objects, true);
        $this->assertCount($isUpdate ? 2 : 1, $domain->getRepository()->findAll());

        $this->assertCount(2, $resources);
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface', $resources->get(0));
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface', $resources->get(1));

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
    public function testUpsertsAutoCommit($isUpdate)
    {
        $this->runTestUpserts(true, $isUpdate);
    }

    public function runTestUpserts($autoCommit, $isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            $objects = $this->insertResources($domain, 2);

            foreach ($objects as $i => $object) {
                $object->setName('New Bar '.($i + 1));
                $object->setDetail('New Detail '.($i + 1));
            }
        } else {
            $this->loadFixtures(array());
            /* @var Foo $foo1 */
            $foo1 = $domain->newInstance();
            $foo1->setName('Bar 1');
            $foo1->setDetail('Detail 1');
            /* @var Foo $foo2 */
            $foo2 = $domain->newInstance();
            $foo2->setName('Bar 2');
            $foo2->setDetail('Detail 2');

            $objects = array($foo1, $foo2);
        }

        $this->assertCount($isUpdate ? 2 : 0, $domain->getRepository()->findAll());
        $resources = $domain->upserts($objects, $autoCommit);
        $this->assertCount(2, $domain->getRepository()->findAll());

        $this->assertCount(2, $resources);
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface', $resources->get(0));
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface', $resources->get(1));

        $this->assertSame(ResourceListStatutes::SUCCESSFULLY, $resources->getStatus());
        $this->assertSame($isUpdate ? ResourceStatutes::UPDATED
            : ResourceStatutes::CREATED, $resources->get(0)->getStatus());
        $this->assertSame($isUpdate ? ResourceStatutes::UPDATED
            : ResourceStatutes::CREATED, $resources->get(1)->getStatus());
    }

    public function testInvalidObjectType()
    {
        $msg = 'Expected argument of type "Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Entity\Foo", "integer" given at the position "0"';
        $this->setExpectedException('Sonatra\Bundle\ResourceBundle\Exception\UnexpectedTypeException', $msg);

        $domain = $this->createDomain();
        /* @var object $object */
        $object = 42;

        $domain->upsert($object);
    }

    /**
     * @dataProvider getUpsertType
     *
     * @param bool $isUpdate
     */
    public function testErrorIdentifier($isUpdate)
    {
        $domain = $this->createDomain();

        if ($isUpdate) {
            /* @var Foo $object */
            $object = $domain->newInstance();
            $object->setName('Bar');
            $object->setDetail('Detail');
        } else {
            $object = $this->insertResource($domain);
        }

        $resource = $domain->upsert($object);
        $this->assertTrue($resource->isValid());
    }
}
