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

use Fxp\Component\Resource\Domain\DomainInterface;
use Fxp\Component\Resource\ResourceListInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\Event;

/**
 * The resource event.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ResourceEvent extends Event
{
    /**
     * @var DomainInterface
     */
    private $domain;

    /**
     * @var ResourceListInterface
     */
    private $resources;

    /**
     * Constructor.
     *
     * @param DomainInterface       $domain    The resource domain for this resources
     * @param ResourceListInterface $resources The list of resource instances
     */
    public function __construct(DomainInterface $domain, ResourceListInterface $resources)
    {
        $this->domain = $domain;
        $this->resources = $resources;
    }

    /**
     * Get the resource domain for this resources.
     *
     * @return DomainInterface
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Get the list of resource instances.
     *
     * @return ResourceListInterface
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * Build the name of resource event.
     *
     * @param string $name      The name of event
     * @param string $shortName The short name of resource
     *
     * @return string
     */
    public static function build($name, $shortName)
    {
        return static::formatEventPrefix($shortName).$name;
    }

    /**
     * Format the prefix of event.
     *
     * @param string $shortName The short name of resource
     *
     * @return string
     */
    public static function formatEventPrefix($shortName)
    {
        $name = Container::underscore($shortName);

        return str_replace(array('\\', '/', ' '), '_', $name);
    }
}
