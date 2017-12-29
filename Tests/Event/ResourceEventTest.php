<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Tests\Event;

use Fxp\Component\Resource\Domain\DomainInterface;
use Fxp\Component\Resource\Event\ResourceEvent;
use Fxp\Component\Resource\ResourceEvents;
use Fxp\Component\Resource\ResourceListInterface;
use PHPUnit\Framework\TestCase;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ResourceEventTest extends TestCase
{
    public function testGetter()
    {
        /* @var DomainInterface $domain */
        $domain = $this->getMockBuilder(DomainInterface::class)->getMock();
        /* @var ResourceListInterface $list */
        $list = $this->getMockBuilder(ResourceListInterface::class)->getMock();

        $event = new ResourceEvent($domain, $list);

        $this->assertSame($domain, $event->getDomain());
        $this->assertSame($list, $event->getResources());
    }

    public function testBuild()
    {
        $this->assertSame('std_class.domain.pre_creates', ResourceEvent::build(ResourceEvents::PRE_CREATES, \stdClass::class));
    }

    public function testBuildShortName()
    {
        $this->assertSame('foo_bar.domain.pre_creates', ResourceEvent::build(ResourceEvents::PRE_CREATES, 'Foo Bar'));
    }
}
