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

use Symfony\Component\Validator\Constraints\GroupSequence;

/**
 * Validation wrapper data interface.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
interface ValidationWrapperInterface extends WrapperInterface
{
    /**
     * Returns the model data.
     *
     * @return GroupSequence[]|string[]
     */
    public function getValidationGroups(): array;
}
