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

use Sonatra\Component\Resource\Exception\InvalidArgumentException;
use Sonatra\Component\Resource\Exception\UnexpectedTypeException;

/**
 * A request content converter manager interface.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ConverterRegistry implements ConverterRegistryInterface
{
    /**
     * @var ConverterInterface[]
     */
    protected $converters = array();

    /**
     * Constructor.
     *
     * @param ConverterInterface[] $converters
     *
     * @throws UnexpectedTypeException When the converter is not an instance of "Sonatra\Component\Resource\Converter\ConverterInterface"
     */
    public function __construct(array $converters)
    {
        foreach ($converters as $converter) {
            if (!$converter instanceof ConverterInterface) {
                throw new UnexpectedTypeException($converter, 'Sonatra\Component\Resource\Converter\ConverterInterface');
            }
            $this->converters[strtolower($converter->getName())] = $converter;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (!is_string($name)) {
            throw new UnexpectedTypeException($name, 'string');
        }

        $sName = strtolower($name);

        if (isset($this->converters[$sName])) {
            return $this->converters[$sName];
        }

        throw new InvalidArgumentException(sprintf('Could not load content converter "%s"', $name));
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        return isset($this->converters[strtolower($name)]);
    }
}
