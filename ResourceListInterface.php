<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Resource list interface.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
interface ResourceListInterface extends \Traversable, \Countable, \ArrayAccess
{
    /**
     * Get the status of action by the resource domain.
     *
     * @return string
     */
    public function getStatus();

    /**
     * Get the resource instance.
     *
     * @return ResourceInterface[]
     */
    public function getResources();

    /**
     * Add a resource.
     *
     * @param ResourceInterface $resource The resource
     */
    public function add(ResourceInterface $resource);

    /**
     * Add resources.
     *
     * @param ResourceListInterface $otherList The other resources
     */
    public function addAll(ResourceListInterface $otherList);

    /**
     * Get all resources.
     *
     * @return ResourceInterface[]
     */
    public function all();

    /**
     * Get a resource.
     *
     * @param int $offset The offset
     *
     * @return ResourceInterface
     *
     * @throws \OutOfBoundsException When the offset does not exist
     */
    public function get($offset);

    /**
     * Check if the resource exist.
     *
     * @param int $offset The offset
     *
     * @return bool
     */
    public function has($offset);

    /**
     * Set a resource.
     *
     * @param int               $offset   The offset
     * @param ResourceInterface $resource The resource
     */
    public function set($offset, ResourceInterface $resource);

    /**
     * Remove a resource.
     *
     * @param int $offset The offset
     */
    public function remove($offset);

    /**
     * Get the errors defined for this list (not include the children error).
     *
     * @return ConstraintViolationListInterface
     */
    public function getErrors();

    /**
     * Check if there is an error on resource list or children.
     *
     * @return bool
     */
    public function hasErrors();
}
