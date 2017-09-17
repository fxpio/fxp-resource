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

use Doctrine\Common\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Sonatra\Component\Resource\Domain\DomainDoctrineUtil;

/**
 * Tests case for Domain doctrine util.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainDoctrineUtilTest extends TestCase
{
    public function testGetManagerWithInvalidClass()
    {
        /* @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder(ManagerRegistry::class)->getMock();

        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->willThrowException(new \ReflectionException('INVALID CLASS'));

        $registry->expects($this->once())
            ->method('getManagers')
            ->willReturn(array());

        $this->assertNull(DomainDoctrineUtil::getManager($registry, 'InvalidClass'));
    }
}
