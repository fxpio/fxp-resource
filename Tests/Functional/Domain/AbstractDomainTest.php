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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use PHPUnit\Framework\TestCase;
use Sonatra\Component\DefaultValue\ObjectFactory;
use Sonatra\Component\DefaultValue\ObjectFactoryInterface;
use Sonatra\Component\DefaultValue\ObjectRegistry;
use Sonatra\Component\DefaultValue\ResolvedObjectTypeFactory;
use Sonatra\Component\Resource\Domain\Domain;
use Sonatra\Component\Resource\Domain\DomainInterface;
use Sonatra\Component\Resource\ResourceInterface;
use Sonatra\Component\Resource\Tests\Fixtures\Entity\Bar;
use Sonatra\Component\Resource\Tests\Fixtures\Entity\Foo;
use Sonatra\Component\Resource\Tests\Fixtures\Listener\SoftDeletableSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Abstract class for Functional tests for Domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
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
     * @var ObjectFactoryInterface
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
        $config = Setup::createXMLMetadataConfiguration(array(
            __DIR__.'/../../Fixtures/config/doctrine',
        ), true);
        $connectionOptions = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $this->em = EntityManager::create($connectionOptions, $config);

        $this->softDeletable = new SoftDeletableSubscriber();
        $this->em->getEventManager()->addEventSubscriber($this->softDeletable);

        $this->dispatcher = new EventDispatcher();

        $resolvedTypeFactory = new ResolvedObjectTypeFactory();
        $objectRegistry = new ObjectRegistry(array(), $resolvedTypeFactory);
        $this->objectFactory = new ObjectFactory($objectRegistry, $resolvedTypeFactory);

        $this->validator = Validation::createValidatorBuilder()
            ->addXmlMapping(__DIR__.'/../../Fixtures/config/validation.xml')
            ->getValidator();

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($this->validator))
            ->getFormFactory();

        $this->translator = new Translator('en');
        $ref = new \ReflectionClass(ResourceInterface::class);
        $this->translator->addResource('xml', realpath(dirname($ref->getFileName()).'/Resources/translations/SonatraResource.en.xlf'), 'en', 'SonatraResource');
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
        $domain->setObjectManager($this->em, array('soft_deletable'));
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
        $this->loadFixtures(array());

        $objects = array();

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
        if (PHP_VERSION_ID >= 50500 && !defined('HHVM_VERSION')) {
            return '/Integrity constraint violation: (\d+) NOT NULL constraint failed: foo.detail/';
        }

        return '/Integrity constraint violation: (\d+) foo.detail may not be NULL/';
    }
}
