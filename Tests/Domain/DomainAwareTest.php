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

use PHPUnit\Framework\TestCase;
use Sonatra\Component\Resource\Domain\DomainAware;
use Sonatra\Component\Resource\Domain\DomainManagerInterface;

/**
 * Tests case for Domain aware.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
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
