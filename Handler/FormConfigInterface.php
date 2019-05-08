<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Handler;

use Fxp\Component\Resource\Exception\InvalidArgumentException;

/**
 * A form config interface.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
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
    public function setType(string $type): void;

    /**
     * Get the class name of form type.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Set the form options.
     *
     * @param array $options The form options
     */
    public function setOptions(array $options): void;

    /**
     * Get the form options.
     *
     * @return array
     */
    public function getOptions(): array;

    /**
     * Set the request method.
     *
     * @param string $method The request method
     */
    public function setMethod(string $method): void;

    /**
     * Get the request method.
     *
     * @return string
     */
    public function getMethod(): string;

    /**
     * Set the submit clear missing option.
     *
     * @param null|bool $clearMissing The submit clear missing (use null for choose automatically the best method)
     */
    public function setSubmitClearMissing(?bool $clearMissing);

    /**
     * Get the submit clear missing option.
     *
     * @return bool
     */
    public function getSubmitClearMissing(): bool;

    /**
     * Set the data converter for the request content.
     *
     * @param string $converter The name of data converter
     */
    public function setConverter(string $converter): void;

    /**
     * Get the data converter for the request content.
     *
     * @return string
     */
    public function getConverter(): string;
}
