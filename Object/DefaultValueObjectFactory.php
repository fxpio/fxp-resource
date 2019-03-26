<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Object;

use Fxp\Component\DefaultValue\ObjectFactoryInterface as DefaultValueObjectFactoryInterface;

/**
 * A object factory with Fxp Default Value.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class DefaultValueObjectFactory implements ObjectFactoryInterface
{
    /**
     * @var DefaultValueObjectFactoryInterface
     */
    private $of;

    /**
     * Constructor.
     *
     * @param DefaultValueObjectFactoryInterface $of The default value object factory
     */
    public function __construct(DefaultValueObjectFactoryInterface $of)
    {
        $this->of = $of;
    }

    /**
     * {@inheritdoc}
     */
    public function create($classname, array $options = [])
    {
        return $this->of->create($classname, null, $options);
    }
}
