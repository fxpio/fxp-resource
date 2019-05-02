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
     * @param string $class The class name or the Doctrine resolved target
     *
     * @return bool
     */
    public function has($class);

    /**
     * Get a resource domain.
     *
     * @param string $class The class name
     *
     * @throws InvalidArgumentException When the class of resource domain is not managed
     *
     * @return DomainInterface
     */
    public function get($class);
}
