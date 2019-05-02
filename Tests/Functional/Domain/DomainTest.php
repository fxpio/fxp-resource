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
 *
 * @internal
 */
final class DomainTest extends AbstractDomainTest
{
    public function testMappingException(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessageRegExp('/The "([\\w\\\\\\/]+)" class is not managed by doctrine object manager/');

        $class = 'DateTime';

        $this->createDomain($class);
    }

    public function testGetRepository(): void
    {
        $domain = $this->createDomain();

        $this->assertInstanceOf('Doctrine\Common\Persistence\ObjectRepository', $domain->getRepository());
    }

    public function testNewInstance(): void
    {
        $domain = $this->createDomain(Foo::class);
        $resource1 = $domain->newInstance();
        $resource2 = $this->objectFactory->create(Foo::class);

        $this->assertEquals($resource2, $resource1);
    }
}
