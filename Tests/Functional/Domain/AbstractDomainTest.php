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
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Fxp\Component\DefaultValue\ObjectFactory;
use Fxp\Component\DefaultValue\ObjectRegistry;
use Fxp\Component\DefaultValue\ResolvedObjectTypeFactory;
use Fxp\Component\Resource\Domain\Domain;
use Fxp\Component\Resource\Domain\DomainInterface;
use Fxp\Component\Resource\Object\DefaultValueObjectFactory;
use Fxp\Component\Resource\ResourceInterface;
use Fxp\Component\Resource\Tests\Fixtures\Entity\Bar;
use Fxp\Component\Resource\Tests\Fixtures\Entity\Foo;
use Fxp\Component\Resource\Tests\Fixtures\Listener\SoftDeletableSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract class for Functional tests for Domain.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractDomainTest extends TestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var DefaultValueObjectFactory
     */
    protected $objectFactory;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var SoftDeletableSubscriber
     */
    protected $softDeletable;

    protected function setUp()
    {
        $config = Setup::createXMLMetadataConfiguration([
            __DIR__.'/../../Fixtures/config/doctrine',
        ], true);
        $connectionOptions = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $this->em = EntityManager::create($connectionOptions, $config);

        $this->softDeletable = new SoftDeletableSubscriber();
        $this->em->getEventManager()->addEventSubscriber($this->softDeletable);

        $this->dispatcher = new EventDispatcher();

        $resolvedTypeFactory = new ResolvedObjectTypeFactory();
        $objectRegistry = new ObjectRegistry([], $resolvedTypeFactory);
        $dvof = new ObjectFactory($objectRegistry, $resolvedTypeFactory);
        $this->objectFactory = new DefaultValueObjectFactory($dvof);

        $this->validator = Validation::createValidatorBuilder()
            ->addXmlMapping(__DIR__.'/../../Fixtures/config/validation.xml')
            ->getValidator();

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($this->validator))
            ->getFormFactory();

        $this->translator = new Translator('en');
        $ref = new \ReflectionClass(ResourceInterface::class);
        $this->translator->addResource('xml', realpath(\dirname($ref->getFileName()).'/Resources/translations/FxpResource.en.xlf'), 'en', 'FxpResource');
        $this->translator->addLoader('xml', new XliffFileLoader());
    }

    protected function tearDown()
    {
        $tool = new SchemaTool($this->em);
        $tool->dropDatabase();
    }

    /**
     * Reset database and load the fixtures.
     *
     * @param array $fixtures The fixtures
     *
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    protected function loadFixtures(array $fixtures)
    {
        $tool = new SchemaTool($this->em);
        $tool->dropDatabase();
        $this->em->getConnection()->getSchemaManager()->createDatabase($this->em->getConnection()->getDatabase());
        $tool->createSchema($this->em->getMetadataFactory()->getAllMetadata());
    }

    /**
     * Create resource domain.
     *
     * @param string $class
     *
     * @return Domain
     */
    protected function createDomain($class = Foo::class)
    {
        $domain = new Domain($class);
        $domain->setDebug(true);
        $domain->setObjectManager($this->em, ['soft_deletable']);
        $domain->setEventDispatcher($this->dispatcher);
        $domain->setObjectFactory($this->objectFactory);
        $domain->setValidator($this->validator);
        $domain->setTranslator($this->translator);

        return $domain;
    }

    /**
     * Insert object in database.
     *
     * @param DomainInterface $domain
     *
     * @return Foo
     */
    protected function insertResource(DomainInterface $domain)
    {
        return current($this->insertResources($domain, 1));
    }

    /**
     * Insert objects in database.
     *
     * @param DomainInterface $domain
     * @param int             $size
     *
     * @return Foo[]|Bar[]
     */
    protected function insertResources(DomainInterface $domain, $size)
    {
        $this->loadFixtures([]);

        $objects = [];

        for ($i = 0; $i < $size; ++$i) {
            /* @var Foo|Bar $object */
            $object = $domain->newInstance();
            $object->setName('Bar '.($i + 1));
            $object->setDetail('Detail '.($i + 1));
            $this->em->persist($object);
            $objects[] = $object;
        }

        $this->em->flush();

        return $objects;
    }

    protected function getIntegrityViolationMessage()
    {
        if (\PHP_VERSION_ID >= 50500 && !\defined('HHVM_VERSION')) {
            return '/Integrity constraint violation: (\d+) NOT NULL constraint failed: foo.detail/';
        }

        return '/Integrity constraint violation: (\d+) foo.detail may not be NULL/';
    }
}
