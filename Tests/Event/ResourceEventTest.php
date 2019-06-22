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

use Fxp\Component\Resource\Event\PreCreatesEvent;
use Fxp\Component\Resource\ResourceListInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class ResourceEventTest extends TestCase
{
    public function testGetter(): void
    {
        /** @var ResourceListInterface $list */
        $list = $this->getMockBuilder(ResourceListInterface::class)->getMock();

        $event = new PreCreatesEvent(\stdClass::class, $list);

        static::assertSame(\stdClass::class, $event->getClass());
        static::assertSame($list, $event->getResources());
        static::assertTrue($event->is(\stdClass::class));
        static::assertFalse($event->is(MockObject::class));
    }
}
