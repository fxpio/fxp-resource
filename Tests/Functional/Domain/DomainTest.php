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

use Sonatra\Component\Resource\Tests\Fixtures\Entity\Foo;

/**
 * Functional tests for Domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainTest extends AbstractDomainTest
{
    /**
     * @expectedException \Sonatra\Component\Resource\Exception\InvalidConfigurationException
     * @expectedExceptionMessageRegExp /The "([\w\\\/]+)" class is not managed by doctrine object manager/
     */
    public function testMappingException()
    {
        $class = 'DateTime';

        $this->createDomain($class);
    }

    public function testGetRepository()
    {
        $domain = $this->createDomain();

        $this->assertInstanceOf('Doctrine\Common\Persistence\ObjectRepository', $domain->getRepository());
    }

    public function testGetEventPrefix()
    {
        $domain = $this->createDomain();

        $valid = 'sonatra_component_resource_tests_fixtures_entity_foo';
        $this->assertSame($valid, $domain->getEventPrefix());
    }

    public function testNewInstance()
    {
        $domain = $this->createDomain(Foo::class);
        $resource1 = $domain->newInstance();
        $resource2 = $this->objectFactory->create(Foo::class);

        $this->assertEquals($resource2, $resource1);
    }
}
