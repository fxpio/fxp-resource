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
     * @throws UnexpectedTypeException When the converter is not an instance of "Fxp\Component\Resource\Converter\ConverterInterface"
     */
    public function __construct(array $converters)
    {
        foreach ($converters as $converter) {
            if (!$converter instanceof ConverterInterface) {
                throw new UnexpectedTypeException($converter, 'Fxp\Component\Resource\Converter\ConverterInterface');
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
