<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Tests\Fixtures\Exception;

use Doctrine\DBAL\Driver\DriverException;

/**
 * Mock driver exception.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
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
