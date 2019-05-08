<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Object;

/**
 * A object factory interface.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
interface ObjectFactoryInterface
{
    /**
     * Create the object.
     *
     * @param string $classname The classname
     * @param array  $options   The options
     *
     * @return object
     */
    public function create(string $classname, array $options = []);
}
