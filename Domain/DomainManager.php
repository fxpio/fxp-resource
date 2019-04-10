<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Domain;

/**
 * Domain manager.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class DomainManager implements DomainManagerInterface
{
    /**
     * @var DomainInterface[]
     */
    protected $domains = [];

    /**
     * @var DomainFactoryInterface
     */
    protected $factory;

    /**
     * Constructor.
     *
     * @param DomainFactoryInterface $factory The domain factory
     */
    public function __construct(DomainFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function has($class)
    {
        return isset($this->domains[$class])
            || $this->factory->isManagedClass($class);
    }

    /**
     * {@inheritdoc}
     */
    public function get($class)
    {
        $class = $this->factory->getManagedClass($class);

        if (!isset($this->domains[$class])) {
            $this->domains[$class] = $this->factory->create($class);
        }

        return $this->domains[$class];
    }
}
