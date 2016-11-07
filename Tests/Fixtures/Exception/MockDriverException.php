<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Tests\Fixtures\Exception;

use Doctrine\DBAL\Driver\DriverException;

/**
 * Mock driver exception.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class MockDriverException extends \Exception implements DriverException
{
    /**
     * {@inheritdoc}
     */
    public function getErrorCode()
    {
        return 4224;
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLState()
    {
        null;
    }
}
