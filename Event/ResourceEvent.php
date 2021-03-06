<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Event;

use Fxp\Component\Resource\ResourceListInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * The resource event.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class ResourceEvent extends Event
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var ResourceListInterface
     */
    private $resources;

    /**
     * Constructor.
     *
     * @param string                $class     The class name of resources
     * @param ResourceListInterface $resources The list of resource instances
     */
    public function __construct(string $class, ResourceListInterface $resources)
    {
        $this->class = $class;
        $this->resources = $resources;
    }

    /**
     * Get the class name of resources.
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Get the list of resource instances.
     *
     * @return ResourceListInterface
     */
    public function getResources(): ResourceListInterface
    {
        return $this->resources;
    }

    /**
     * Check if the the event resource is the specified class.
     *
     * @param string $class The class name
     *
     * @return bool
     */
    public function is(string $class): bool
    {
        return is_a($this->class, $class, true) || \in_array($class, class_implements($class), true);
    }
}
