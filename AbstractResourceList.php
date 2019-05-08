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
     * @var null|ConstraintViolationListInterface|FormErrorIterator[]
     */
    protected $childrenErrors;

    /**
     * Constructor.
     *
     * @param ResourceInterface[]              $resources The list of resource
     * @param ConstraintViolationListInterface $errors    The list of errors
     */
    public function __construct(
        array $resources = [],
        ?ConstraintViolationListInterface $errors = null
    ) {
        $this->resources = [];
        $this->errors = $errors ?? new ConstraintViolationList();

        foreach ($resources as $resource) {
            $this->add($resource);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(): string
    {
        if (null === $this->status) {
            $this->refreshStatus();
        }

        return $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * {@inheritdoc}
     */
    public function add(ResourceInterface $resource): void
    {
        $this->reset();
        $this->resources[] = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function addAll(ResourceListInterface $otherList): void
    {
        $this->reset();

        foreach ($otherList as $resource) {
            $this->resources[] = $resource;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->resources;
    }

    /**
     * {@inheritdoc}
     */
    public function get($offset): ResourceInterface
    {
        if (!isset($this->resources[$offset])) {
            throw new OutOfBoundsException(sprintf('The offset "%s" does not exist.', $offset));
        }

        return $this->resources[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function has(int $offset): bool
    {
        return isset($this->resources[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function set(int $offset, ResourceInterface $resource): void
    {
        $this->reset();
        $this->resources[$offset] = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(int $offset): void
    {
        $this->reset();
        unset($this->resources[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->resources);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): ResourceInterface
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $resource): void
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
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }

    /**
     * Reset the value of status and children errors.
     */
    protected function reset(): void
    {
        $this->status = null;
        $this->childrenErrors = null;
    }

    /**
     * Refresh the status of this list.
     */
    abstract protected function refreshStatus(): void;
}
