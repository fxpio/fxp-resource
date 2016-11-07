<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Handler;

use Symfony\Component\Form\FormInterface;

/**
 * A form handler interface.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
interface FormHandlerInterface
{
    /**
     * Process form for one object instance (create and submit form).
     *
     * @param FormConfigInterface $config The form config
     * @param object|array        $object The object instance
     *
     * @return FormInterface
     */
    public function processForm(FormConfigInterface $config, $object);

    /**
     * Process form for one object instance (create and submit form).
     *
     * @param FormConfigListInterface $config  The form config
     * @param object[]|array[]        $objects The list of object instance
     *
     * @return FormInterface[]
     */
    public function processForms(FormConfigListInterface $config, array $objects = array());

    /**
     * Get the default limit. If the value is null, then there is not limit of quantity of rows.
     *
     * @return int|null
     */
    public function getDefaultLimit();
}
