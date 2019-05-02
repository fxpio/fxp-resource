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
use Symfony\Component\HttpFoundation\Request;

/**
 * A form config.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class FormConfig implements FormConfigInterface
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var null|bool
     */
    protected $clearMissing;

    /**
     * @var string
     */
    protected $converter;

    /**
     * Constructor.
     *
     * @param string $type      The class name of form type
     * @param array  $options   The form options for create the form type
     * @param string $method    The request method
     * @param string $converter The data converter for request content
     */
    public function __construct(
        $type,
        array $options = [],
        $method = Request::METHOD_POST,
        $converter = 'json'
    ) {
        $this->setType($type);
        $this->setOptions($options);
        $this->setMethod($method);
        $this->setConverter($converter);
    }

    /**
     * {@inheritdoc}
     */
    public function setType($type): void
    {
        if (!\is_string($type)) {
            throw new InvalidArgumentException('The form type of domain form config must be an string of class name of form type');
        }

        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options): void
    {
        if (isset($options['method'])) {
            $this->setMethod($options['method']);
        }

        $this->options = array_merge($options, ['method' => $this->getMethod()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function setMethod($method): void
    {
        $this->method = $method;
        $this->options['method'] = $method;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function setSubmitClearMissing($clearMissing): void
    {
        $this->clearMissing = $clearMissing;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubmitClearMissing()
    {
        if (null === $this->clearMissing) {
            return Request::METHOD_PATCH !== $this->method;
        }

        return $this->clearMissing;
    }

    /**
     * {@inheritdoc}
     */
    public function getConverter()
    {
        return $this->converter;
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter($converter): void
    {
        $this->converter = $converter;
    }
}
