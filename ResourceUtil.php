<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource;

use Fxp\Component\Resource\Exception\InvalidResourceException;
use Fxp\Component\Resource\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;

/**
 * Util for resource.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class ResourceUtil
{
    /**
     * Convert the object data of resource to resource list.
     *
     * @param object[]|FormInterface[] $objects      The resource object instance or form of object instance
     * @param string                   $requireClass The require class name
     * @param bool                     $allowForm    Check if the form is allowed
     *
     * @return ResourceList
     *
     * @throws InvalidResourceException When the instance object in the list is not an instance of the required class
     */
    public static function convertObjectsToResourceList(array $objects, $requireClass, $allowForm = true)
    {
        $list = new ResourceList();

        foreach ($objects as $i => $object) {
            static::validateObjectResource($object, $requireClass, $i, $allowForm);
            $list->add(new ResourceItem((object) $object));
        }

        return $list;
    }

    /**
     * Validate the resource object.
     *
     * @param mixed|FormInterface $object       The resource object or form of resource object
     * @param string              $requireClass The required class
     * @param int                 $i            The position of the object in the list
     * @param bool                $allowForm    Check if the form is allowed
     *
     * @throws UnexpectedTypeException  When the object parameter is not an object or a form instance
     * @throws InvalidResourceException When the object in form is not an object
     * @throws InvalidResourceException When the object instance is not an instance of the required class
     */
    public static function validateObjectResource($object, $requireClass, $i, $allowForm = true)
    {
        if ($allowForm && $object instanceof FormInterface) {
            $object = $object->getData();
        }

        if (!is_object($object) || !$object instanceof $requireClass) {
            throw new UnexpectedTypeException($object, $requireClass, $i);
        }
    }
}
