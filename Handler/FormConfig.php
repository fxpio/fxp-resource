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
     * @var callable[]
     */
    protected $builderHandlers = [];

    /**
     * Constructor.
     *
     * @param string $type      The class name of form type
     * @param array  $options   The form options for create the form type
     * @param string $method    The request method
     * @param string $converter The data converter for request content
     */
    public function __construct(
        string $type,
        array $options = [],
        string $method = Request::METHOD_POST,
        string $converter = 'json'
    ) {
        $this->setType($type);
        $this->setMethod($method);
        $this->setOptions($options);
        $this->setConverter($converter);
    }

    /**
     * {@inheritdoc}
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
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
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function setMethod(string $method): void
    {
        $this->method = $method;
        $this->options['method'] = $method;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function setSubmitClearMissing(?bool $clearMissing): void
    {
        $this->clearMissing = $clearMissing;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubmitClearMissing(): bool
    {
        if (null === $this->clearMissing) {
            return Request::METHOD_PATCH !== $this->method;
        }

        return (bool) $this->clearMissing;
    }

    /**
     * {@inheritdoc}
     */
    public function getConverter(): string
    {
        return $this->converter;
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(string $converter): void
    {
        $this->converter = $converter;
    }

    /**
     * {@inheritdoc}
     */
    public function getBuilderHandlers(): array
    {
        return $this->builderHandlers;
    }

    /**
     * {@inheritdoc}
     */
    public function addBuilderHandler(?callable $builderHandler): self
    {
        $this->builderHandlers[] = $builderHandler;

        return $this;
    }
}
