<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Tests\Event;

use Sonatra\Component\Resource\Domain\DomainInterface;
use Sonatra\Component\Resource\Event\ResourceEvent;
use Sonatra\Component\Resource\ResourceEvents;
use Sonatra\Component\Resource\ResourceListInterface;

/**
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ResourceEventTest extends \PHPUnit_Framework_TestCase
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
