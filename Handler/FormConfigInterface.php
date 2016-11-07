<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Handler;

use Sonatra\Component\Resource\Exception\InvalidArgumentException;

/**
 * A form config interface.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
interface FormConfigInterface
{
    /**
     * Set the form type.
     *
     * @param string $type The class name of form type
     *
     * @throws InvalidArgumentException When the type is not a string of class name
     */
    public function setType($type);

    /**
     * Get the class name of form type.
     *
     * @return string
     */
    public function getType();

    /**
     * Set the form options.
     *
     * @param array $options The form options
     */
    public function setOptions(array $options);

    /**
     * Get the form options.
     *
     * @return array
     */
    public function getOptions();

    /**
     * Set the request method.
     *
     * @param string $method The request method
     */
    public function setMethod($method);

    /**
     * Get the request method.
     *
     * @return string
     */
    public function getMethod();

    /**
     * Set the submit clear missing option.
     *
     * @param bool|null $clearMissing The submit clear missing (use null for choose automatically the best method)
     */
    public function setSubmitClearMissing($clearMissing);

    /**
     * Get the submit clear missing option.
     *
     * @return bool
     */
    public function getSubmitClearMissing();

    /**
     * Set the data converter for the request content.
     *
     * @param string $converter The name of data converter
     */
    public function setConverter($converter);

    /**
     * Get the data converter for the request content.
     *
     * @return string
     */
    public function getConverter();
}
