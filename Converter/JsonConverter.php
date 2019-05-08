<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Converter;

use Fxp\Component\Resource\Exception\InvalidJsonConverterException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A request content converter interface.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class JsonConverter implements ConverterInterface
{
    public const NAME = 'json';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param TranslatorInterface $translator The translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function convert(string $content): array
    {
        $content = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidJsonConverterException($this->translator->trans('converter.json.invalid_body', [], 'FxpResource'));
        }

        return \is_array($content) ? $content : [];
    }
}
