<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Tests;

use PHPUnit\Framework\TestCase;
use Sonatra\Component\Resource\ResourceEvents;

/**
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ResourceEventsTest extends TestCase
{
    /**
     * @expectedException \Sonatra\Component\Resource\Exception\ClassNotInstantiableException
     */
    public function testInstantiationOfClass()
    {
        new ResourceEvents();
    }
}
