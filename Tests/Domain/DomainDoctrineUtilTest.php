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

use Doctrine\Common\Persistence\ManagerRegistry;
use Fxp\Component\Resource\Domain\DomainDoctrineUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests case for Domain doctrine util.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class DomainDoctrineUtilTest extends TestCase
{
    public function testGetManagerWithInvalidClass()
    {
        /* @var ManagerRegistry|MockObject $registry */
        $registry = $this->getMockBuilder(ManagerRegistry::class)->getMock();

        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->willThrowException(new \ReflectionException('INVALID CLASS'));

        $registry->expects($this->once())
            ->method('getManagers')
            ->willReturn([]);

        $this->assertNull(DomainDoctrineUtil::getManager($registry, 'InvalidClass'));
    }
}
