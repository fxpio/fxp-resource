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

use Fxp\Component\Resource\Domain\Wrapper;
use PHPUnit\Framework\TestCase;

/**
 * Tests case for Domain.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class WrapperTest extends TestCase
{
    public function testGetData(): void
    {
        $data = new \stdClass();
        $wrapper = new Wrapper($data);

        static::assertSame($data, $wrapper->getData());
    }
}
