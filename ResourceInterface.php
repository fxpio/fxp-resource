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

use Sonatra\Component\Resource\Exception\InvalidArgumentException;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Resource interface.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
interface ResourceInterface
{
    /**
     * Set status.
     *
     * @param string $status The status defined in ResourceStatutes class
     */
    public function setStatus($status);

    /**
     * Get the status of action by the resource domain.
     *
     * @return string
     */
    public function getStatus();

    /**
     * Get the data instance of this resource.
     *
     * @return object|FormInterface
     */
    public function getData();

    /**
     * Get the real resource data.
     *
     * @return object
     */
    public function getRealData();

    /**
     * Get the list of errors.
     *
     * @return ConstraintViolationListInterface
     */
    public function getErrors();

    /**
     * Get the form errors.
     *
     * @return FormErrorIterator
     *
     * @throws InvalidArgumentException When the data is not a form
     */
    public function getFormErrors();

    /**
     * Check if the resource is a resource for a form.
     *
     * @return bool
     */
    public function isForm();

    /**
     * Check if the resource has errors.
     *
     * @return bool
     */
    public function isValid();
}
