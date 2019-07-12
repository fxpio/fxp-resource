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
 * Validation wrapper data.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ValidationWrapper extends Wrapper implements ValidationWrapperInterface
{
    /**
     * @var string[]|string[][]|GroupSequence[]
     */
    protected $validationGroups;

    /**
     * Constructor.
     *
     * @param mixed                               $data             The wrapped data
     * @param string[]|string[][]|GroupSequence[] $validationGroups The validation groups
     */
    public function __construct($data, array $validationGroups)
    {
        parent::__construct($data);

        $this->validationGroups = $validationGroups;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationGroups(): array
    {
        return $this->validationGroups;
    }
}
