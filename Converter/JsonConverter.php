<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Converter;

use Sonatra\Component\Resource\Exception\InvalidJsonConverterException;

/**
 * A request content converter interface.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class JsonConverter implements ConverterInterface
{
    const NAME = 'json';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($content)
    {
        $content = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidJsonConverterException();
        }

        return is_array($content) ? $content : array();
    }
}
