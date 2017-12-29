<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Converter;

use Fxp\Component\Resource\Exception\InvalidArgumentException;
use Fxp\Component\Resource\Exception\UnexpectedTypeException;

/**
 * A request content converter manager interface.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
interface ConverterRegistryInterface
{
    /**
     * Returns a converter by name.
     *
     * This methods registers the converter.
     *
     * @param string $name The name of the converter
     *
     * @return ConverterInterface The converter
     *
     * @throws UnexpectedTypeException  If the parameter is not a string
     * @throws InvalidArgumentException If the converter can not be retrieved
     */
    public function get($name);

    /**
     * Returns whether the given converter is supported.
     *
     * @param string $name The name of the converter
     *
     * @return bool Whether the type is supported
     */
    public function has($name);
}
