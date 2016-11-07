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

use Sonatra\Component\Resource\ResourceListStatutes;

/**
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ResourceListStatutesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Sonatra\Component\Resource\Exception\ClassNotInstantiableException
     */
    public function testInstantiationOfClass()
    {
        new ResourceListStatutes();
    }
}
