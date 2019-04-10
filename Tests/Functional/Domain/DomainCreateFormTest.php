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

use Fxp\Component\DefaultValue\Tests\Fixtures\Object\Foo;
use Fxp\Component\Resource\Domain\DomainInterface;
use Fxp\Component\Resource\Event\PostCreatesEvent;
use Fxp\Component\Resource\Event\PreCreatesEvent;
use Fxp\Component\Resource\ResourceInterface;
use Fxp\Component\Resource\ResourceListInterface;
use Fxp\Component\Resource\ResourceListStatutes;
use Fxp\Component\Resource\ResourceStatutes;
use Fxp\Component\Resource\Tests\Fixtures\Form\FooType;
use Symfony\Component\Form\FormInterface;

/**
 * Functional tests for create methods of Domain with form resources.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class DomainCreateFormTest extends AbstractDomainTest
{
    public function testCreateWithErrorValidation()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo */
        $foo = $domain->newInstance();
        $form = $this->buildForm($foo, [
            'description' => 'test',
        ]);

        $this->loadFixtures([]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreCreatesEvent::class, function (PreCreatesEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostCreatesEvent::class, function (PostCreatesEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
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
        $form = $this->buildForm($foo, [
            'name' => 'Bar',
        ]);

        $this->loadFixtures([]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreCreatesEvent::class, function (PreCreatesEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostCreatesEvent::class, function (PostCreatesEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
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
        $this->assertRegExp($this->getIntegrityViolationMessage(), $errors[0]->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(0, $domain->getRepository()->findAll());
    }

    public function testCreate()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo */
        $foo = $domain->newInstance();
        $form = $this->buildForm($foo, [
            'name' => 'Bar',
            'detail' => 'Detail',
        ]);

        $this->loadFixtures([]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreCreatesEvent::class, function (PreCreatesEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostCreatesEvent::class, function (PostCreatesEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
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

        $form1 = $this->buildForm($foo1, [
            'description' => 'test',
        ]);
        $form2 = $this->buildForm($foo2, [
            'description' => 'test',
        ]);

        $this->runTestCreatesException($domain, [$form1, $form2], '/This value should not be blank./', true);
    }

    public function testCreatesWithErrorDatabase()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();

        $form1 = $this->buildForm($foo1, [
            'name' => 'Bar',
        ]);
        $form2 = $this->buildForm($foo2, [
            'name' => 'Bar',
        ]);

        $this->runTestCreatesException($domain, [$form1, $form2], $this->getIntegrityViolationMessage(), false);
    }

    protected function runTestCreatesException(DomainInterface $domain, array $objects, $errorMessage, $autoCommit = false)
    {
        $this->loadFixtures([]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreCreatesEvent::class, function (PreCreatesEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostCreatesEvent::class, function (PostCreatesEvent $e) use (&$postEvent, $autoCommit, $domain) {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
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

        $form1 = $this->buildForm($foo1, []);
        $form2 = $this->buildForm($foo2, [
            'name' => 'Bar',
        ]);

        $objects = [$form1, $form2];

        $this->loadFixtures([]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreCreatesEvent::class, function (PreCreatesEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostCreatesEvent::class, function (PostCreatesEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->creates($objects, true);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);

        $this->assertTrue($resources->hasErrors());
        $errors1 = $resources->get(0)->getFormErrors();
        $this->assertRegExp('/This value should not be blank./', $errors1[0]->getMessage());
        $this->assertRegExp($this->getIntegrityViolationMessage(), $resources->get(1)->getErrors()->get(0)->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(0, $domain->getRepository()->findAll());
    }

    public function testCreatesAutoCommitWithErrorDatabase()
    {
        $domain = $this->createDomain();

        $this->loadFixtures([]);
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();

        $form1 = $this->buildForm($foo1, [
            'name' => 'Bar',
        ]);
        $form2 = $this->buildForm($foo2, [
            'name' => 'Bar',
        ]);

        $forms = [$form1, $form2];

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreCreatesEvent::class, function (PreCreatesEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostCreatesEvent::class, function (PostCreatesEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->creates($forms, true);
        $this->assertInstanceOf(ResourceListInterface::class, $resources);

        $this->assertTrue($resources->hasErrors());
        $this->assertCount(0, $resources->get(0)->getFormErrors());
        $this->assertCount(0, $resources->get(1)->getFormErrors());

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

        $form1 = $this->buildForm($foo1, []);
        $form2 = $this->buildForm($foo2, [
            'name' => 'Bar',
            'detail' => 'Detail',
        ]);

        $objects = [$form1, $form2];

        $this->loadFixtures([]);

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

    public function testCreatesAutoCommit()
    {
        $this->runTestCreates(true);
    }

    protected function runTestCreates($autoCommit)
    {
        $domain = $this->createDomain();
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();

        $form1 = $this->buildForm($foo1, [
            'name' => 'Bar 1',
            'detail' => 'Detail 1',
        ]);
        $form2 = $this->buildForm($foo2, [
            'name' => 'Bar 2',
            'detail' => 'Detail 2',
        ]);

        $objects = [$form1, $form2];

        $this->loadFixtures([]);

        $this->assertCount(0, $domain->getRepository()->findAll());
        $resources = $domain->creates($objects, $autoCommit);
        $this->assertCount(2, $domain->getRepository()->findAll());

        $this->assertCount(2, $resources);
        $this->assertInstanceOf(ResourceInterface::class, $resources->get(0));
        $this->assertInstanceOf(ResourceInterface::class, $resources->get(1));

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

        $form = $this->formFactory->create(FooType::class, $foo, []);

        $this->loadFixtures([]);

        $preEvent = false;
        $postEvent = false;

        $this->dispatcher->addListener(PreCreatesEvent::class, function (PreCreatesEvent $e) use (&$preEvent, $domain) {
            $preEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $this->dispatcher->addListener(PostCreatesEvent::class, function (PostCreatesEvent $e) use (&$postEvent, $domain) {
            $postEvent = true;
            $this->assertSame($domain->getClass(), $e->getClass());
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resource = $domain->create($form);
        $this->assertCount(0, $resource->getErrors());
        $this->assertCount(1, $resource->getFormErrors());
    }

    public function testErrorIdentifier()
    {
        $domain = $this->createDomain();
        $foo = $this->insertResource($domain);
        $foo->setDetail(null);
        $form = $this->buildForm($foo, [
            'name' => 'New Bar',
            'detail' => 'New Detail',
        ]);

        $resource = $domain->create($form);
        $this->assertFalse($resource->isValid());
        $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
        $this->assertRegExp('/The resource cannot be created because it has an identifier/', $resource->getErrors()->get(0)->getMessage());
    }

    /**
     * @param object $object
     * @param array  $data
     *
     * @return FormInterface
     */
    protected function buildForm($object, array $data)
    {
        $form = $this->formFactory->create(FooType::class, $object, []);
        $form->submit($data, true);

        return $form;
    }
}
