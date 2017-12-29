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

use Fxp\Component\Resource\Exception\InvalidConverterException;

/**
 * A request content converter interface.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
interface ConverterInterface
{
    /**
     * Get the name of the conversion.
     *
     * @return string
     */
    public function getName();

    /**
     * Convert the string content to array.
     *
     * @param string $content
     *
     * @return array
     *
     * @throws InvalidConverterException When the data can not be converted
     */
    public function convert($content);
}
