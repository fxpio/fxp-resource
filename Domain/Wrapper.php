<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Domain;

/**
 * Wrapper data.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class Wrapper implements WrapperInterface
{
    /**
     * @var mixed
     */
    protected $data;

    /**
     * Constructor.
     *
     * @param mixed $data The wrapped data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->data;
    }
}
