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
 * Wrapper data interface.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
interface WrapperInterface
{
    /**
     * Returns the wrapped data.
     *
     * @return mixed
     */
    public function getData();
}
