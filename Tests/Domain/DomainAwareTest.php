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

use Fxp\Component\Resource\Domain\DomainAware;
use Fxp\Component\Resource\Domain\DomainManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests case for Domain aware.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class DomainAwareTest extends TestCase
{
    public function testSetDomainManager()
    {
        /* @var DomainManagerInterface $dm */
        $dm = $this->getMockBuilder(DomainManagerInterface::class)->getMock();
        $domain = new DomainAware(\stdClass::class);

        $this->assertInstanceOf(DomainAware::class, $domain->setDomainManager($dm));
    }
}
