<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Converter;

use Sonatra\Component\Resource\Exception\InvalidJsonConverterException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * A request content converter interface.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class JsonConverter implements ConverterInterface
{
    const NAME = 'json';

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
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($content)
    {
        $content = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidJsonConverterException($this->translator->trans('converter.json.invalid_body', array(), 'SonatraResource'));
        }

        return is_array($content) ? $content : array();
    }
}
