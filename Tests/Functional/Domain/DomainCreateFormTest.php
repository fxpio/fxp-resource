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
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Form\FooType;
use Symfony\Component\Form\FormInterface;

/**
 * Functional tests for create methods of Domain with form resources.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainCreateFormTest extends AbstractDomainTest
{
    public function testCreateWithErrorValidation()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo */
        $foo = $domain->newInstance();
        $form = $this->buildForm($foo, array(
            'description' => 'test',
        ));

        $this->loadFixtures(array());

        $preEvent = false;
        $postEvent = false;
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_CREATES, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_CREATES, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resource = $domain->create($form);
        $this->assertCount(0, $resource->getErrors());
        $this->assertCount(1, $resource->getFormErrors());

        $errors = $resource->getFormErrors();
        $this->assertRegExp('/This value should not be blank./', $errors[0]->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(0, $domain->getRepository()->findAll());
    }

    public function testCreateWithErrorDatabase()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo */
        $foo = $domain->newInstance();
        $form = $this->buildForm($foo, array(
            'name' => 'Bar',
        ));

        $this->loadFixtures(array());

        $preEvent = false;
        $postEvent = false;
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_CREATES, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_CREATES, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resource = $domain->create($form);
        $this->assertFalse($resource->isValid());
        $this->assertCount(1, $resource->getErrors());
        $this->assertCount(0, $resource->getFormErrors());

        $errors = $resource->getErrors();
        $this->assertRegExp('/Database error code "(\d+)"/', $errors[0]->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(0, $domain->getRepository()->findAll());
    }

    public function testCreate()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo */
        $foo = $domain->newInstance();
        $form = $this->buildForm($foo, array(
            'name' => 'Bar',
            'detail' => 'Detail',
        ));

        $this->loadFixtures(array());

        $preEvent = false;
        $postEvent = false;
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_CREATES, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_CREATES, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::CREATED, $resource->getStatus());
            }
        });

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resource = $domain->create($form);
        $this->assertTrue($resource->isValid());
        $this->assertCount(0, $resource->getErrors());
        $this->assertCount(0, $resource->getFormErrors());

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

        $form1 = $this->buildForm($foo1, array(
            'description' => 'test',
        ));
        $form2 = $this->buildForm($foo2, array(
            'description' => 'test',
        ));

        $this->runTestCreatesException($domain, array($form1, $form2), '/This value should not be blank./', true);
    }

    public function testCreatesWithErrorDatabase()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();

        $form1 = $this->buildForm($foo1, array(
            'name' => 'Bar',
        ));
        $form2 = $this->buildForm($foo2, array(
            'name' => 'Bar',
        ));

        $this->runTestCreatesException($domain, array($form1, $form2), '/Database error code "(\d+)"/', false);
    }

    protected function runTestCreatesException(DomainInterface $domain, array $objects, $errorMessage, $autoCommit = false)
    {
        $this->loadFixtures(array());

        $preEvent = false;
        $postEvent = false;
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_CREATES, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_CREATES, function (ResourceEvent $e) use (&$postEvent, $autoCommit, $domain) {
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
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceListInterface', $resources);
        $this->assertTrue($resources->hasErrors());

        $errors = $autoCommit
            ? $resources->get(0)->getFormErrors()
            : $resources->getErrors();
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

    public function testCreatesAutoCommitWithErrorValidationAndErrorDatabase()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();

        $form1 = $this->buildForm($foo1, array());
        $form2 = $this->buildForm($foo2, array(
            'name' => 'Bar',
        ));

        $objects = array($form1, $form2);

        $this->loadFixtures(array());

        $preEvent = false;
        $postEvent = false;
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_CREATES, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_CREATES, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->creates($objects, true);
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceListInterface', $resources);

        $this->assertTrue($resources->hasErrors());
        $errors1 = $resources->get(0)->getFormErrors();
        $this->assertRegExp('/This value should not be blank./', $errors1[0]->getMessage());
        $this->assertRegExp('/Database error code "(\d+)"/', $resources->get(1)->getErrors()->get(0)->getMessage());

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

        $form1 = $this->buildForm($foo1, array());
        $form2 = $this->buildForm($foo2, array(
            'name' => 'Bar',
            'detail' => 'Detail',
        ));

        $objects = array($form1, $form2);

        $this->loadFixtures(array());

        $this->assertCount(0, $domain->getRepository()->findAll());
        $resources = $domain->creates($objects, true);
        $this->assertCount(1, $domain->getRepository()->findAll());

        $this->assertCount(2, $resources);
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface', $resources->get(0));
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface', $resources->get(1));

        $this->assertSame(ResourceListStatutes::MIXED, $resources->getStatus());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        $this->assertSame(ResourceStatutes::CREATED, $resources->get(1)->getStatus());
    }

    public function testCreatesAutoCommit()
    {
        $this->runTestCreates(true);
    }

    public function runTestCreates($autoCommit)
    {
        $domain = $this->createDomain();
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();

        $form1 = $this->buildForm($foo1, array(
            'name' => 'Bar 1',
            'detail' => 'Detail 1',
        ));
        $form2 = $this->buildForm($foo2, array(
            'name' => 'Bar 2',
            'detail' => 'Detail 2',
        ));

        $objects = array($form1, $form2);

        $this->loadFixtures(array());

        $this->assertCount(0, $domain->getRepository()->findAll());
        $resources = $domain->creates($objects, $autoCommit);
        $this->assertCount(2, $domain->getRepository()->findAll());

        $this->assertCount(2, $resources);
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface', $resources->get(0));
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface', $resources->get(1));

        $this->assertSame(ResourceListStatutes::SUCCESSFULLY, $resources->getStatus());
        $this->assertSame(ResourceStatutes::CREATED, $resources->get(0)->getStatus());
        $this->assertTrue($resources->get(0)->isValid());
        $this->assertSame(ResourceStatutes::CREATED, $resources->get(1)->getStatus());
        $this->assertTrue($resources->get(1)->isValid());
    }

    public function testCreateWithMissingFormSubmission()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo */
        $foo = $domain->newInstance();

        /* @var FormInterface $form */
        $form = $this->getContainer()->get('form.factory')->create(new FooType(), $foo, array());

        $this->loadFixtures(array());

        $preEvent = false;
        $postEvent = false;
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_CREATES, function (ResourceEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_CREATES, function (ResourceEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain, $e->getDomain());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resource = $domain->create($form);
        $this->assertCount(0, $resource->getErrors());
        $this->assertCount(1, $resource->getFormErrors());
    }

    /**
     * @param object $object
     * @param array  $data
     *
     * @return FormInterface
     */
    protected function buildForm($object, array $data)
    {
        /* @var FormInterface $form */
        $form = $this->getContainer()->get('form.factory')->create(new FooType(), $object, array());
        $form->submit($data, true);

        return $form;
    }
}
