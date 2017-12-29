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

/**
 * A form config list for closure converter.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ClosureFormConfigList extends FormConfigList
{
    /**
     * @var \Closure|null
     */
    protected $objectConverter;

    /**
     * {@inheritdoc}
     */
    public function setObjectConverter(\Closure $converter)
    {
        $this->objectConverter = $converter;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function convertObjects(array &$list)
    {
        if ($this->objectConverter instanceof \Closure) {
            $converter = $this->objectConverter;

            return $converter($list);
        }

        return [];
    }
}
