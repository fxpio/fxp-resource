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
 */
class DomainFactoryTest extends TestCase
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

    protected function setUp()
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
            ->willReturn($this->metaFactory);

        $this->registry->expects($this->any())
            ->method('getManagers')
            ->willReturn([$this->objectManager]);
    }

    public function testAddResolveTargets()
    {
        $this->assertInstanceOf(DomainFactory::class, $this->factory->addResolveTargets(['FooInterface' => 'Foo']));
    }

    public function testIsManagedClass()
    {
        $res = $this->factory->isManagedClass(\stdClass::class);

        $this->assertFalse($res);
    }

    public function testIsManagedClassWithResolveTarget()
    {
        $this->factory->addResolveTargets([
            's' => \stdClass::class,
        ]);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn(null);

        $this->metaFactory->expects($this->once())
            ->method('hasMetadataFor')
            ->with(\stdClass::class)
            ->willReturn(true);

        $res = $this->factory->isManagedClass('s');

        $this->assertTrue($res);
    }

    public function testGetManagedClass()
    {
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn($this->objectManager);

        /* @var ClassMetadata|MockObject $meta */
        $meta = $this->getMockBuilder(ClassMetadata::class)->getMock();
        $meta->expects($this->any())
            ->method('getName')
            ->will($this->returnValue(\stdClass::class));

        $this->objectManager->expects($this->atLeast(2))
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($meta);

        $res = $this->factory->getManagedClass(\stdClass::class);

        $this->assertSame(\stdClass::class, $res);
    }

    /**
     * @expectedException \Fxp\Component\Resource\Exception\InvalidArgumentException
     * @expectedExceptionMessage The "stdClass" class is not registered in doctrine
     */
    public function testGetManagedClassWithNonManagedClass()
    {
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn(null);

        $this->metaFactory->expects($this->once())
            ->method('hasMetadataFor')
            ->with(\stdClass::class)
            ->willReturn(false);

        $this->factory->getManagedClass(\stdClass::class);
    }

    /**
     * @expectedException \Fxp\Component\Resource\Exception\InvalidArgumentException
     * @expectedExceptionMessage The "stdClass" class is not registered in doctrine
     */
    public function testGetManagedClassWithOrmMappedSuperClass()
    {
        /* @var OrmClassMetadata|MockObject $meta */
        $meta = $this->getMockBuilder(OrmClassMetadata::class)->disableOriginalConstructor()->getMock();
        $meta->isMappedSuperclass = true;

        $this->objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($meta);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn($this->objectManager);

        $this->factory->getManagedClass(\stdClass::class);
    }

    public function testCreate()
    {
        /* @var OrmClassMetadata|MockObject $meta */
        $meta = $this->getMockBuilder(OrmClassMetadata::class)->disableOriginalConstructor()->getMock();

        $meta->expects($this->once())
            ->method('getName')
            ->willReturn(\stdClass::class);

        $this->objectManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($meta);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn($this->objectManager);

        $domain = $this->factory->create(\stdClass::class);

        $this->assertInstanceOf(Domain::class, $domain);
        $this->assertSame(\stdClass::class, $domain->getClass());
    }
}
