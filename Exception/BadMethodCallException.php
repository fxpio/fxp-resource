<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Exception;

/**
 * Base BadMethodCallException for the resource bundle.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class BadMethodCallException extends \BadMethodCallException implements ExceptionInterface
{
}