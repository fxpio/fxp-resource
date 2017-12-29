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

use Fxp\Component\Resource\Tests\Fixtures\Entity\Foo;

/**
 * Functional tests for Domain.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class DomainTest extends AbstractDomainTest
{
    /**
     * @expectedException \Fxp\Component\Resource\Exception\InvalidConfigurationException
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

        $valid = 'foo';
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
