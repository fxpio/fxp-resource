<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource;

use Fxp\Component\Resource\Exception\OutOfBoundsException;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Abstract Resource list.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractResourceList implements \IteratorAggregate, ResourceListInterface
{
    /**
     * @var string
     */
    protected $status;

    /**
     * @var ResourceInterface[]
     */
    protected $resources;

    /**
     * @var ConstraintViolationListInterface
     */
    protected $errors;

    /**
     * @var ConstraintViolationListInterface|FormErrorIterator[]|null
     */
    protected $childrenErrors;

    /**
     * Constructor.
     *
     * @param ResourceInterface[]              $resources The list of resource
     * @param ConstraintViolationListInterface $errors    The list of errors
     */
    public function __construct(array $resources = [],
                                ConstraintViolationListInterface $errors = null)
    {
        $this->resources = [];
        $this->errors = null !== $errors ? $errors : new ConstraintViolationList();

        foreach ($resources as $resource) {
            $this->add($resource);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        if (null === $this->status) {
            $this->refreshStatus();
        }

        return $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * {@inheritdoc}
     */
    public function add(ResourceInterface $resource)
    {
        $this->reset();
        $this->resources[] = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function addAll(ResourceListInterface $otherList)
    {
        $this->reset();

        foreach ($otherList as $resource) {
            $this->resources[] = $resource;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->resources;
    }

    /**
     * {@inheritdoc}
     */
    public function get($offset)
    {
        if (!isset($this->resources[$offset])) {
            throw new OutOfBoundsException(sprintf('The offset "%s" does not exist.', $offset));
        }

        return $this->resources[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function has($offset)
    {
        return isset($this->resources[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function set($offset, ResourceInterface $resource)
    {
        $this->reset();
        $this->resources[$offset] = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($offset)
    {
        $this->reset();
        unset($this->resources[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->resources);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $resource)
    {
        if (null === $offset) {
            $this->add($resource);
        } else {
            $this->set($offset, $resource);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * Reset the value of status and children errors.
     */
    protected function reset()
    {
        $this->status = null;
        $this->childrenErrors = null;
    }

    /**
     * Refresh the status of this list.
     */
    abstract protected function refreshStatus();
}
