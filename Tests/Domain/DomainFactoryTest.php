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
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata as OrmClassMetadata;
use Fxp\Component\Resource\Domain\Domain;
use Fxp\Component\Resource\Domain\DomainFactory;
use Fxp\Component\Resource\Object\ObjectFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Tests case for Domain Manager.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class DomainFactoryTest extends TestCase
{
    /**
     * @var DomainFactory
     */
    protected $factory;

    /**
     * @var MockObject|ObjectManager
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
     * @var MockObject|ObjectFactoryInterface
     */
    protected $objectFactory;

    /**
     * @var MockObject|ValidatorInterface
     */
    protected $validator;

    /**
     * @var MockObject|TranslatorInterface
     */
    protected $translator;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $this->objectFactory = $this->getMockBuilder(ObjectFactoryInterface::class)->getMock();
        $this->validator = $this->getMockBuilder(ValidatorInterface::class)->getMock();
        $this->translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
        $this->objectManager = $this->getMockBuilder(ObjectManager::class)->getMock();
        $this->metaFactory = $this->getMockBuilder(ClassMetadataFactory::class)->getMock();
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)->getMock();
        $this->factory = new DomainFactory(
            $this->registry,
            $this->eventDispatcher,
            $this->objectFactory,
            $this->validator,
            $this->translator
        );

        $this->objectManager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($this->metaFactory)
        ;

        $this->registry->expects($this->any())
            ->method('getManagers')
            ->willReturn([$this->objectManager])
        ;
    }

    public function testAddResolveTargets(): void
    {
        $this->assertInstanceOf(DomainFactory::class, $this->factory->addResolveTargets(['FooInterface' => 'Foo']));
    }

    public function testIsManagedClass(): void
    {
        $res = $this->factory->isManagedClass(\stdClass::class);

        $this->assertFalse($res);
    }

    public function testIsManagedClassWithResolveTarget(): void
    {
        $this->factory->addResolveTargets([
            's' => \stdClass::class,
        ]);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn(null)
        ;

        $this->metaFactory->expects($this->once())
            ->method('hasMetadataFor')
            ->with(\stdClass::class)
            ->willReturn(true)
        ;

        $res = $this->factory->isManagedClass('s');

        $this->assertTrue($res);
    }

    public function testGetManagedClass(): void
    {
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn($this->objectManager)
        ;

        /** @var ClassMetadata|MockObject $meta */
        $meta = $this->getMockBuilder(ClassMetadata::class)->getMock();
        $meta->expects($this->any())
            ->method('getName')
            ->will($this->returnValue(\stdClass::class))
        ;

        $this->objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($meta)
        ;

        $res = $this->factory->getManagedClass(\stdClass::class);

        $this->assertSame(\stdClass::class, $res);
    }

    public function testGetManagedClassWithNonManagedClass(): void
    {
        $this->expectException(\Fxp\Component\DoctrineExtra\Exception\ObjectManagerNotFoundException::class);
        $this->expectExceptionMessage('The doctrine manager for the "stdClass" class is not found');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn(null)
        ;

        $this->metaFactory->expects($this->once())
            ->method('hasMetadataFor')
            ->with(\stdClass::class)
            ->willReturn(false)
        ;

        $this->factory->getManagedClass(\stdClass::class);
    }

    public function testGetManagedClassWithOrmMappedSuperClass(): void
    {
        $this->expectException(\Fxp\Component\DoctrineExtra\Exception\ObjectManagerNotFoundException::class);
        $this->expectExceptionMessage('The doctrine manager for the "stdClass" class is not found');

        $this->metaFactory->expects($this->atLeastOnce())
            ->method('hasMetadataFor')
            ->with(\stdClass::class)
            ->willReturn(true)
        ;

        /** @var MockObject|OrmClassMetadata $meta */
        $meta = $this->getMockBuilder(OrmClassMetadata::class)->disableOriginalConstructor()->getMock();
        $meta->isMappedSuperclass = true;

        $this->objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($meta)
        ;

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn(null)
        ;

        $this->registry->expects($this->once())
            ->method('getManagers')
            ->willReturn([$this->objectManager])
        ;

        $this->factory->getManagedClass(\stdClass::class);
    }

    public function testCreate(): void
    {
        /** @var MockObject|OrmClassMetadata $meta */
        $meta = $this->getMockBuilder(OrmClassMetadata::class)->disableOriginalConstructor()->getMock();

        $meta->expects($this->once())
            ->method('getName')
            ->willReturn(\stdClass::class)
        ;

        $this->objectManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($meta)
        ;

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn($this->objectManager)
        ;

        $domain = $this->factory->create(\stdClass::class);

        $this->assertInstanceOf(Domain::class, $domain);
        $this->assertSame(\stdClass::class, $domain->getClass());
    }
}
