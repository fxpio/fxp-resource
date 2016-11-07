<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Tests\Domain;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Sonatra\Component\DefaultValue\ObjectFactoryInterface;
use Sonatra\Component\Resource\Domain\Domain;

/**
 * Tests case for Domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainTest extends \PHPUnit_Framework_TestCase
{
    public function getShortNames()
    {
        return array(
            array(null,              'stdClass'),
            array('CustomShortName', 'CustomShortName'),
        );
    }

    /**
     * @dataProvider getShortNames
     *
     * @param string|null $shortName      The short name of domain
     * @param string      $validShortName The valid short name of domain
     */
    public function testShortName($shortName, $validShortName)
    {
        $domain = new Domain(\stdClass::class, $shortName);

        $this->assertSame($validShortName, $domain->getShortName());
    }

    public function testCreateQueryBuilder()
    {
        $domain = new Domain(\stdClass::class);
        $om = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        /* @var EntityManager $om */
        $domain->setObjectManager($om);
        $qb = $domain->createQueryBuilder('f');

        $this->assertSame($om, $domain->getObjectManager());
        $this->assertInstanceOf('Doctrine\ORM\QueryBuilder', $qb);
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\BadMethodCallException
     * @expectedExceptionMessage The "Domain::createQueryBuilder()" method can only be called for a domain with Doctrine ORM Entity Manager
     */
    public function testCreateQueryBuilderInvalidObjectManager()
    {
        $domain = new Domain(\stdClass::class);
        $domain->createQueryBuilder();
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\InvalidConfigurationException
     * @expectedExceptionMessageRegExp /The "([\w\\]+)" class is not managed by doctrine object manager/
     */
    public function testInvalidObjectManager()
    {
        $domain = new Domain(\stdClass::class);
        /* @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $om */
        $om = $this->getMockBuilder(ObjectManager::class)->getMock();
        $om->expects($this->once())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willThrowException(new MappingException());

        $domain->setObjectManager($om);
    }

    public function testGetRepository()
    {
        $domain = new Domain(\stdClass::class);
        /* @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $om */
        $om = $this->getMockBuilder(ObjectManager::class)->getMock();

        $domain->setObjectManager($om);

        $mockRepo = $this->getMockBuilder(ObjectRepository::class)->getMock();

        $om->expects($this->once())
            ->method('getRepository')
            ->with(\stdClass::class)
            ->will($this->returnValue($mockRepo));

        $repo = $domain->getRepository();

        $this->assertSame($mockRepo, $repo);
    }

    public function testGetEventPrefix()
    {
        $domain = new Domain(\stdClass::class);

        $this->assertSame('std_class', $domain->getEventPrefix());
    }

    public function testNewInstance()
    {
        $domain = new Domain(\stdClass::class);

        /* @var ObjectFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $of */
        $of = $this->getMockBuilder(ObjectFactoryInterface::class)->getMock();

        $domain->setObjectFactory($of);

        $instance = new \stdClass();

        $of->expects($this->once())
            ->method('create')
            ->with(\stdClass::class, null, array())
            ->will($this->returnValue($instance));

        $val = $domain->newInstance();

        $this->assertSame($instance, $val);
    }
}
