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
 * Base UnexpectedTypeException for the resource bundle.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class UnexpectedTypeException extends InvalidArgumentException
{
    /**
     * Constructor.
     *
     * @param mixed  $value        The value given
     * @param string $expectedType The expected type
     */
    public function __construct($value, $expectedType)
    {
        parent::__construct(sprintf('Expected argument of type "%s", "%s" given', $expectedType, is_object($value) ? get_class((object) $value) : gettype($value)));
    }
}
