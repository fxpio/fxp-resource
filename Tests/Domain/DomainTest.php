<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Tests\Domain;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Fxp\Component\Resource\Domain\Domain;
use Fxp\Component\Resource\Domain\DomainFactory;
use Fxp\Component\Resource\Object\ObjectFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Tests case for Domain.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class DomainTest extends TestCase
{
    /**
     * @var DomainFactory
     */
    protected $factory;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var ClassMetadataFactory|MockObject
     */
    protected $metaFactory;

    /**
     * @var ManagerRegistry|MockObject
     */
    protected $registry;

    /**
     * @var EventDispatcherInterface|MockObject
     */
    protected $eventDispatcher;

    /**
     * @var ObjectFactoryInterface|MockObject
     */
    protected $objectFactory;

    /**
     * @var ValidatorInterface|MockObject
     */
    protected $validator;

    /**
     * @var TranslatorInterface|MockObject
     */
    protected $translator;

    /**
     * @var Domain
     */
    protected $domain;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $this->objectFactory = $this->getMockBuilder(ObjectFactoryInterface::class)->getMock();
        $this->validator = $this->getMockBuilder(ValidatorInterface::class)->getMock();
        $this->translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
        $this->objectManager = $this->createMockObjectManager();
        $this->metaFactory = $this->getMockBuilder(ClassMetadataFactory::class)->getMock();
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)->getMock();

        $this->domain = new Domain(\stdClass::class,
            $this->objectManager,
            $this->objectFactory,
            $this->eventDispatcher,
            $this->validator,
            $this->translator
        );
    }

    protected function tearDown()
    {
        $this->eventDispatcher = null;
        $this->objectFactory = null;
        $this->validator = null;
        $this->translator = null;
        $this->objectManager = null;
        $this->metaFactory = null;
        $this->registry = null;
        $this->domain = null;
    }

    public function testCreateQueryBuilder()
    {
        $mockRepo = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $qbMock = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $mockRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qbMock);
        $this->objectManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($mockRepo);

        $qb = $this->domain->createQueryBuilder('f');

        $this->assertSame($this->objectManager, $this->domain->getObjectManager());
        $this->assertSame($qbMock, $qb);
    }

    /**
     * @expectedException \Fxp\Component\Resource\Exception\BadMethodCallException
     * @expectedExceptionMessage The "Domain::createQueryBuilder()" method can only be called for a domain with Doctrine ORM Entity Manager
     */
    public function testCreateQueryBuilderInvalidObjectManager()
    {
        $this->objectManager = $this->createMockObjectManager(ObjectManager::class);

        $this->domain = new Domain(\stdClass::class,
            $this->objectManager,
            $this->objectFactory,
            $this->eventDispatcher,
            $this->validator,
            $this->translator
        );

        $this->domain->createQueryBuilder();
    }

    /**
     * @expectedException \Fxp\Component\Resource\Exception\InvalidConfigurationException
     * @expectedExceptionMessageRegExp /The "([\w\\]+)" class is not managed by doctrine object manager/
     */
    public function testInvalidObjectManager()
    {
        $objectManager = $this->createMockObjectManager(ObjectManager::class);
        $objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willThrowException(new MappingException());

        new Domain(\stdClass::class,
            $objectManager,
            $this->objectFactory,
            $this->eventDispatcher,
            $this->validator,
            $this->translator
        );
    }

    public function testGetRepository()
    {
        $mockRepo = $this->getMockBuilder(ObjectRepository::class)->getMock();

        $this->objectManager->expects($this->once())
            ->method('getRepository')
            ->with(\stdClass::class)
            ->willReturn($mockRepo);

        $repo = $this->domain->getRepository();

        $this->assertSame($mockRepo, $repo);
    }

    public function testGetClassMetadata()
    {
        $mockMeta = $this->getMockBuilder(ClassMetadata::class)->getMock();
        $mockMeta->expects($this->once())
            ->method('getName')
            ->willReturn(\stdClass::class);

        $objectManager = $this->createMockObjectManager(ObjectManager::class, $mockMeta);
        $domain = new Domain(\stdClass::class,
            $objectManager,
            $this->objectFactory,
            $this->eventDispatcher,
            $this->validator,
            $this->translator
        );

        $meta = $domain->getClassMetadata();

        $this->assertSame($mockMeta, $meta);
    }

    public function testNewInstance()
    {
        $instance = new \stdClass();

        $this->objectFactory->expects($this->once())
            ->method('create')
            ->with(\stdClass::class, [])
            ->willReturn($instance);

        $val = $this->domain->newInstance();

        $this->assertSame($instance, $val);
    }

    /**
     * Create the mock object manager?
     *
     * @param string                        $class The class name of object manager
     * @param ClassMetadata|MockObject|null $meta  The class metadata
     *
     * @return ObjectManager|MockObject
     */
    protected function createMockObjectManager($class = EntityManagerInterface::class, $meta = null)
    {
        $objectManager = $this->getMockBuilder($class)->getMock();

        if (null === $meta) {
            $meta = $this->getMockBuilder(ClassMetadata::class)->getMock();
            $meta->expects($this->any())
                ->method('getName')
                ->willReturn(\stdClass::class);
        }

        $objectManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($meta);

        return $objectManager;
    }
}
