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

use Fxp\Component\Resource\Exception\InvalidArgumentException;

/**
 * Domain manager interface.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
interface DomainManagerInterface
{
    /**
     * Check if the class is managed.
     *
     * @param string $class The class name or short name
     *
     * @return bool
     */
    public function has($class);

    /**
     * Add a resource domain.
     *
     * @param DomainInterface $domain The resource domain
     *
     * @throws InvalidArgumentException When the domain class already exist
     * @throws InvalidArgumentException When the domain short name already exist
     */
    public function add(DomainInterface $domain);

    /**
     * Remove a resource domain.
     *
     * @param string $class The class name or short name
     */
    public function remove($class);

    /**
     * Get all resource domains.
     *
     * @return DomainInterface[]
     */
    public function all();

    /**
     * Get the short names.
     *
     * @return string[]
     */
    public function getShortNames();

    /**
     * Get a resource domain.
     *
     * @param string $class The class name or short name
     *
     * @return DomainInterface
     *
     * @throws InvalidArgumentException When the class of resource domain is not managed
     */
    public function get($class);
}
