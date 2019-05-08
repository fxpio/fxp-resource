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
 * A resource domain factory interface.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
interface DomainFactoryInterface
{
    /**
     * Add the resolve targets.
     *
     * @param array $resolveTargets The resolve targets
     *
     * @return DomainFactoryInterface
     */
    public function addResolveTargets(array $resolveTargets): DomainFactoryInterface;

    /**
     * Check if the class is managed by doctrine.
     *
     * @param string $class The class name
     *
     * @return bool
     */
    public function isManagedClass(string $class): bool;

    /**
     * Get the managed class name defined in doctrine.
     *
     * @param string $class
     *
     * @throws InvalidArgumentException When the class is not registered in doctrine
     *
     * @return string
     */
    public function getManagedClass(string $class): string;

    /**
     * Create a resource domain.
     *
     * @param string $class The class name
     *
     * @throws InvalidArgumentException When the class is not registered in doctrine
     *
     * @return DomainInterface
     */
    public function create(string $class): DomainInterface;
}
