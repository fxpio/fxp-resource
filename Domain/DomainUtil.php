<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Domain;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Exception\DriverException;
use Sonatra\Component\Resource\Exception\ConstraintViolationException;
use Sonatra\Component\Resource\ResourceEvents;
use Sonatra\Component\Resource\ResourceInterface;
use Sonatra\Component\Resource\ResourceListInterface;
use Sonatra\Component\Resource\ResourceStatutes;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Util for domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
abstract class DomainUtil
{
    /**
     * Format pdo driver exception.
     *
     * @param DriverException $exception The exception
     * @param bool            $debug     The debug mode
     *
     * @return string
     */
    public static function extractDriverExceptionMessage(DriverException $exception, $debug = false)
    {
        $message = 'Database error';

        if ($debug && null !== $exception->getPrevious()) {
            $prevMessage = static::getFirstException($exception)->getMessage();
            $pos = strpos($prevMessage, ':');

            if ($pos > 0 && 0 === strpos($prevMessage, 'SQLSTATE[')) {
                $message .= ': '.trim(substr($prevMessage, $pos + 1));
            }
        }

        return $message;
    }

    /**
     * Get the value of resource identifier.
     *
     * @param ObjectManager $om     The doctrine object manager
     * @param object        $object The resource object
     *
     * @return int|string|null
     */
    public static function getIdentifier(ObjectManager $om, $object)
    {
        $propertyAccess = PropertyAccess::createPropertyAccessor();
        $meta = $om->getClassMetadata(ClassUtils::getClass($object));
        $ids = $meta->getIdentifier();
        $value = null;

        foreach ($ids as $id) {
            $idVal = $propertyAccess->getValue($object, $id);

            if (null !== $idVal) {
                $value = $idVal;
                break;
            }
        }

        return $value;
    }

    /**
     * Get the name of identifier.
     *
     * @param ObjectManager $om        The doctrine object manager
     * @param string        $className The class name
     *
     * @return string
     */
    public static function getIdentifierName(ObjectManager $om, $className)
    {
        $meta = $om->getClassMetadata($className);
        $ids = $meta->getIdentifier();

        return implode('', $ids);
    }

    /**
     * Get the event names of persist action.
     *
     * @param int $type The type of persist
     *
     * @return array The list of pre event name and post event name
     */
    public static function getEventNames($type)
    {
        $names = array(ResourceEvents::PRE_UPSERTS, ResourceEvents::POST_UPSERTS);

        if (Domain::TYPE_CREATE === $type) {
            $names = array(ResourceEvents::PRE_CREATES, ResourceEvents::POST_CREATES);
        } elseif (Domain::TYPE_UPDATE === $type) {
            $names = array(ResourceEvents::PRE_UPDATES, ResourceEvents::POST_UPDATES);
        } elseif (Domain::TYPE_DELETE === $type) {
            $names = array(ResourceEvents::PRE_DELETES, ResourceEvents::POST_DELETES);
        } elseif (Domain::TYPE_UNDELETE === $type) {
            $names = array(ResourceEvents::PRE_UNDELETES, ResourceEvents::POST_UNDELETES);
        }

        return $names;
    }

    /**
     * Add the error in resource.
     *
     * @param ResourceInterface $resource The resource
     * @param string            $message  The error message
     */
    public static function addResourceError(ResourceInterface $resource, $message)
    {
        $resource->setStatus(ResourceStatutes::ERROR);
        $resource->getErrors()->add(new ConstraintViolation($message, $message, array(), $resource->getRealData(), null, null));
    }

    /**
     * Extract the identifier that are not a object.
     *
     * @param array $identifiers The list containing identifier or object
     * @param array $objects     The real objects (by reference)
     *
     * @return array The identifiers that are not a object
     */
    public static function extractIdentifierInObjectList(array $identifiers, array &$objects)
    {
        $searchIds = array();

        foreach ($identifiers as $identifier) {
            if (is_object($identifier)) {
                $objects[] = $identifier;
                continue;
            }
            $searchIds[] = $identifier;
        }

        return $searchIds;
    }

    /**
     * Generate the short name of domain with the class name.
     *
     * @param string $class
     *
     * @return string
     */
    public static function generateShortName($class)
    {
        $pos = strrpos($class, '\\');
        $pos = false !== $pos ? $pos + 1 : 0;
        $name = substr($class, $pos);

        if (false !== $pos = strrpos($name, 'Interface')) {
            $name = substr($name, 0, $pos);
        }

        return $name;
    }

    /**
     * Inject the exception message in resource error list.
     *
     * @param ResourceInterface $resource The resource
     * @param \Exception        $e        The exception on persist action
     *
     * @return bool
     */
    public static function injectErrorMessage(ResourceInterface $resource, \Exception $e)
    {
        if ($e instanceof ConstraintViolationException) {
            $resource->setStatus(ResourceStatutes::ERROR);
            $resource->getErrors()->addAll($e->getConstraintViolations());
        } else {
            static::addResourceError($resource, $e->getMessage());
        }

        return true;
    }

    /**
     * Inject the list errors in the first resource, and return the this first resource.
     *
     * @param ResourceListInterface $resources The resource list
     *
     * @return ResourceInterface The first resource
     */
    public static function oneAction(ResourceListInterface $resources)
    {
        $resources->get(0)->getErrors()->addAll($resources->getErrors());

        return $resources->get(0);
    }

    /**
     * Move the flush errors in each resource if the root object is present in constraint violation.
     *
     * @param ResourceListInterface            $resources The list of resources
     * @param ConstraintViolationListInterface $errors    The list of flush errors
     */
    public static function moveFlushErrorsInResource(ResourceListInterface $resources, ConstraintViolationListInterface $errors)
    {
        if ($errors->count() > 0) {
            $maps = static::getMapErrors($errors);

            foreach ($resources->all() as $resource) {
                $resource->setStatus(ResourceStatutes::ERROR);
                $hash = spl_object_hash($resource->getRealData());
                if (isset($maps[$hash])) {
                    $resource->getErrors()->add($maps[$hash]);
                    unset($maps[$hash]);
                }
            }

            foreach ($maps as $error) {
                $resources->getErrors()->add($error);
            }
        }
    }

    /**
     * Cancel all resource in list that have an successfully status.
     *
     * @param ResourceListInterface $resources The list of resources
     */
    public static function cancelAllSuccessResources(ResourceListInterface $resources)
    {
        foreach ($resources->all() as $resource) {
            if (ResourceStatutes::ERROR !== $resource->getStatus()) {
                $resource->setStatus(ResourceStatutes::CANCELED);
            }
        }
    }

    /**
     * Get the map of object hash and constraint violation list.
     *
     * @param ConstraintViolationListInterface $errors
     *
     * @return array The map of object hash and constraint violation list
     */
    protected static function getMapErrors(ConstraintViolationListInterface $errors)
    {
        $maps = array();
        $size = $errors->count();

        for ($i = 0; $i < $size; ++$i) {
            $root = $errors->get($i)->getRoot();

            if (is_object($root)) {
                $maps[spl_object_hash($errors->get($i)->getRoot())] = $errors->get($i);
            } else {
                $maps[] = $errors->get($i);
            }
        }

        return $maps;
    }

    /**
     * Get the initial exception.
     *
     * @param \Exception $exception
     *
     * @return \Exception
     */
    protected static function getFirstException(\Exception $exception)
    {
        if (null !== $exception->getPrevious()) {
            return static::getFirstException($exception->getPrevious());
        }

        return $exception;
    }
}
