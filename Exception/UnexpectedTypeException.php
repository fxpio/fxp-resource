<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Exception;

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
     * @param mixed    $value        The value given
     * @param string   $expectedType The expected type
     * @param int|null $position     The position in list
     */
    public function __construct($value, $expectedType, $position = null)
    {
        $msg = sprintf('Expected argument of type "%s", "%s" given', $expectedType, is_object($value) ? get_class((object) $value) : gettype($value));

        if (is_int($position)) {
            $msg .= sprintf(' at the position "%s"', $position);
        }

        parent::__construct($msg);
    }
}
