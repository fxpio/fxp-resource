<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Domain;

use Sonatra\Component\Resource\Exception\InvalidArgumentException;

/**
 * A resource domain factory interface.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
interface DomainFactoryInterface
{
    /**
     * Add the resolve targets.
     *
     * @param array $resolveTargets The resolve targets
     *
     * @return self
     */
    public function addResolveTargets(array $resolveTargets);

    /**
     * Get the map of short names and class names.
     *
     * @return array
     */
    public function getShortNames();

    /**
     * Check if the class is managed by doctrine.
     *
     * @param string $class The class name
     *
     * @return bool
     */
    public function isManagedClass($class);

    /**
     * Get the managed class name defined in doctrine.
     *
     * @param string $class
     *
     * @return string
     *
     * @throws InvalidArgumentException When the class is not registered in doctrine
     */
    public function getManagedClass($class);

    /**
     * Create a resource domain.
     *
     * @param string      $class     The class name
     * @param string|null $shortName The short name
     *
     * @return DomainInterface
     *
     * @throws InvalidArgumentException When the class is not registered in doctrine
     */
    public function create($class, $shortName = null);

    /**
     * * Inject the dependencies of domain.
     *
     * @param DomainInterface $domain The resource domain
     *
     * @return DomainInterface
     */
    public function injectDependencies(DomainInterface $domain);
}
