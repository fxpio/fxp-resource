<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Exception;

/**
 * Base ClassNotInstantiableException for the resource bundle.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ClassNotInstantiableException extends RuntimeException
{
    /**
     * Constructor.
     *
     * @param string $classname The class name
     */
    public function __construct($classname)
    {
        parent::__construct(sprintf('The "%s" class cannot be instantiated', $classname));
    }
}
