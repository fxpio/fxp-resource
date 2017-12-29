<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Tests;

use Fxp\Component\Resource\ResourceStatutes;
use PHPUnit\Framework\TestCase;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ResourceStatutesTest extends TestCase
{
    /**
     * @expectedException \Fxp\Component\Resource\Exception\ClassNotInstantiableException
     */
    public function testInstantiationOfClass()
    {
        new ResourceStatutes();
    }
}
