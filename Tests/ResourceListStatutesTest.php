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

use Fxp\Component\Resource\ResourceListStatutes;
use PHPUnit\Framework\TestCase;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class ResourceListStatutesTest extends TestCase
{
    public function testInstantiationOfClass(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\ClassNotInstantiableException::class);

        new ResourceListStatutes();
    }
}
