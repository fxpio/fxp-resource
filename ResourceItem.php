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

use Fxp\Component\Resource\Exception\InvalidArgumentException;
use Fxp\Component\Resource\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Action resource for domain.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ResourceItem implements ResourceInterface
{
    /**
     * @var string
     */
    protected $status;

    /**
     * @var object
     */
    protected $data;

    /**
     * @var ConstraintViolationListInterface
     */
    protected $errors;

    /**
     * Constructor.
     *
     * @param FormInterface|object             $data   The data instance or form with data instance
     * @param ConstraintViolationListInterface $errors The list of errors
     */
    public function __construct($data, ?ConstraintViolationListInterface $errors = null)
    {
        $this->status = ResourceStatutes::PENDING;
        $this->data = $data;
        $this->errors = $errors ?? new ConstraintViolationList();

        $this->validateData($data);
    }

    /**
     * {@inheritdoc}
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getRealData()
    {
        return $this->data instanceof FormInterface
            ? $this->data->getData()
            : $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors(): ConstraintViolationListInterface
    {
        return $this->errors;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormErrors(): FormErrorIterator
    {
        if ($this->data instanceof FormInterface) {
            return $this->data->getErrors(true);
        }

        throw new InvalidArgumentException('The data of resource is not a form instance, used the "getErrors()" method');
    }

    /**
     * {@inheritdoc}
     */
    public function isForm(): bool
    {
        return $this->getData() instanceof FormInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(): bool
    {
        $formSuccess = $this->isForm()
            ? 0 === $this->getFormErrors()->count()
            : true;

        return 0 === $this->getErrors()->count() && $formSuccess;
    }

    /**
     * Validate the data.
     *
     * @param mixed $data
     *
     * @throws UnexpectedTypeException When the data or form data is not an instance of object
     */
    protected function validateData($data): void
    {
        if ($data instanceof FormInterface) {
            $data = $data->getData();
        }

        if (!\is_object($data)) {
            throw new UnexpectedTypeException($data, 'object');
        }
    }
}
