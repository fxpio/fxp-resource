<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Domain;

use Sonatra\Bundle\ResourceBundle\Event\ResourceEvent;
use Sonatra\Bundle\ResourceBundle\Model\SoftDeletableInterface;
use Sonatra\Bundle\ResourceBundle\Resource\Resource;
use Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface;
use Sonatra\Bundle\ResourceBundle\Resource\ResourceListInterface;
use Sonatra\Bundle\ResourceBundle\Resource\ResourceUtil;
use Sonatra\Bundle\ResourceBundle\ResourceEvents;
use Sonatra\Bundle\ResourceBundle\ResourceStatutes;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * A resource domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class Domain extends BaseDomain
{
    /**
     * {@inheritdoc}
     */
    public function deletes(array $resources, $soft = true, $autoCommit = false)
    {
        $list = ResourceUtil::convertObjectsToResourceList(array_values($resources), $this->getClass(), false);

        $this->dispatchEvent(ResourceEvents::PRE_DELETES, new ResourceEvent($this, $list));
        $this->beginTransaction($autoCommit);
        $hasError = $this->doDeleteList($list, $autoCommit, $soft);
        $this->doFlushFinalTransaction($list, $autoCommit, $hasError);

        $this->dispatchEvent(ResourceEvents::POST_DELETES, new ResourceEvent($this, $list));

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function undeletes(array $identifiers, $autoCommit = false)
    {
        list($objects, $missingIds) = $this->convertIdentifierToObjects($identifiers);
        $errorResources = array();

        foreach ($missingIds as $id) {
            $sdt = new \stdClass();
            $sdt->{DomainUtil::getIdentifierName($this->om, $this->getClass())} = $id;
            $resource = new Resource($sdt);
            DomainUtil::addResourceError($resource, sprintf('The object with the identifier "%s" does not exist', $id));
            $errorResources[] = $resource;
        }

        return $this->persist($objects, $autoCommit, static::TYPE_UNDELETE, $errorResources);
    }

    /**
     * Convert the list containing the identifier and/or object, to the list of objects.
     *
     * @param array $identifiers The list containing identifier or object
     *
     * @return array The list of objects and the list of identifiers that have no object
     */
    protected function convertIdentifierToObjects(array $identifiers)
    {
        $idName = DomainUtil::getIdentifierName($this->om, $this->getClass());
        $objects = array();
        $missingIds = array();
        $searchIds = $this->extractIdentifierInObjectList($identifiers, $objects);

        if (count($searchIds) > 0) {
            $previousFilters = $this->disableFilters();
            $searchObjects = $this->getRepository()->findBy(array($idName => $searchIds));
            $this->enableFilters($previousFilters);
            $objects = array_merge($objects, $searchObjects);

            if (count($searchIds) !== count($searchObjects)) {
                $missingIds = $searchIds;

                foreach ($objects as $object) {
                    $pos = array_search(DomainUtil::getIdentifier($this->om, $object), $missingIds);

                    if (false !== $pos) {
                        array_splice($missingIds, $pos, 1);
                    }
                }
            }
        }

        return array($objects, $missingIds);
    }

    /**
     * Extract the identifier that are not a object.
     *
     * @param array $identifiers The list containing identifier or object
     * @param array $objects     The real objects (by reference)
     *
     * @return array The identifiers that are not a object
     */
    protected function extractIdentifierInObjectList(array $identifiers, array &$objects)
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
     * {@inheritdoc}
     */
    protected function persist(array $resources, $autoCommit, $type, array $errorResources = array())
    {
        list($preEvent, $postEvent) = DomainUtil::getEventNames($type);
        $list = ResourceUtil::convertObjectsToResourceList(array_values($resources), $this->getClass());

        foreach ($errorResources as $errorResource) {
            $list->add($errorResource);
        }

        $this->dispatchEvent($preEvent, new ResourceEvent($this, $list));
        $this->beginTransaction($autoCommit);
        $hasError = $this->doPersistList($list, $autoCommit, $type);
        $this->doFlushFinalTransaction($list, $autoCommit, $hasError);

        $this->dispatchEvent($postEvent, new ResourceEvent($this, $list));

        return $list;
    }

    /**
     * Do persist the resources.
     *
     * @param ResourceListInterface $resources  The list of object resource instance
     * @param bool                  $autoCommit Commit transaction for each resource or all
     *                                          (continue the action even if there is an error on a resource)
     * @param int                   $type       The type of persist action
     *
     * @return bool Check if there is an error in list
     */
    protected function doPersistList(ResourceListInterface $resources, $autoCommit, $type)
    {
        $hasError = false;
        $hasFlushError = false;

        foreach ($resources as $i => $resource) {
            if (!$autoCommit && $hasError) {
                $resource->setStatus(ResourceStatutes::CANCELED);
                continue;
            } elseif ($autoCommit && $hasFlushError && $hasError) {
                DomainUtil::addResourceError($resource, 'Caused by previous internal database error');
                continue;
            }

            list($successStatus, $hasFlushError) = $this->doPersistResource($resource, $autoCommit, $type);
            $hasError = $this->finalizeResourceStatus($resource, $successStatus, $hasError);
        }

        return $hasError;
    }

    /**
     * Do persist a resource.
     *
     * @param ResourceInterface $resource   The resource
     * @param bool              $autoCommit Commit transaction for each resource or all
     *                                      (continue the action even if there is an error on a resource)
     * @param int               $type       The type of persist action
     *
     * @return array The successStatus and hasFlushError value
     */
    protected function doPersistResource(ResourceInterface $resource, $autoCommit, $type)
    {
        $object = $resource->getRealData();
        $this->validateUndeleteResource($resource, $type);
        $this->validateResource($resource, $type);
        $successStatus = $this->getSuccessStatus($type, $object);
        $hasFlushError = false;

        if ($resource->isValid()) {
            $this->om->persist($object);
            $hasFlushError = $this->doAutoCommitFlushTransaction($resource, $autoCommit);
        }

        return array($successStatus, $hasFlushError);
    }

    /**
     * Validate the resource only when type is undelete.
     *
     * @param ResourceInterface $resource The resource
     * @param int               $type     The type of persist action
     */
    protected function validateUndeleteResource(ResourceInterface $resource, $type)
    {
        if (static::TYPE_UNDELETE === $type) {
            $object = $resource->getRealData();

            if ($object instanceof SoftDeletableInterface) {
                $object->setDeletedAt(null);
            } else {
                DomainUtil::addResourceError($resource, 'The resource type can not be undeleted');
            }
        }
    }

    /**
     * Do delete the resources.
     *
     * @param ResourceListInterface $resources  The list of object resource instance
     * @param bool                  $autoCommit Commit transaction for each resource or all
     *                                          (continue the action even if there is an error on a resource)
     * @param bool                  $soft       The soft deletable
     *
     * @return bool Check if there is an error in list
     */
    protected function doDeleteList(ResourceListInterface $resources, $autoCommit, $soft = true)
    {
        $hasError = false;
        $hasFlushError = false;

        foreach ($resources as $i => $resource) {
            list($continue, $hasError) = $this->prepareDeleteResource($resource, $autoCommit, $hasError, $hasFlushError);

            if (!$continue) {
                $skipped = $this->doDeleteResource($resource, $soft);
                $hasFlushError = $this->doAutoCommitFlushTransaction($resource, $autoCommit, $skipped);
                $hasError = $this->finalizeResourceStatus($resource, ResourceStatutes::DELETED, $hasError);
            }
        }

        return $hasError;
    }

    /**
     * Prepare the deletion of resource.
     *
     * @param ResourceInterface $resource      The resource
     * @param bool              $autoCommit    Commit transaction for each resource or all
     *                                         (continue the action even if there is an error on a resource)
     * @param bool              $hasError      Check if there is an previous error
     * @param bool              $hasFlushError Check if there is an previous flush error
     *
     * @return array The check if the delete action must be continued and check if there is an error
     */
    protected function prepareDeleteResource(ResourceInterface $resource, $autoCommit, $hasError, $hasFlushError)
    {
        $continue = false;

        if (!$autoCommit && $hasError) {
            $resource->setStatus(ResourceStatutes::CANCELED);
            $continue = true;
        } elseif ($autoCommit && $hasFlushError && $hasError) {
            DomainUtil::addResourceError($resource, 'Caused by previous internal database error');
            $continue = true;
        } elseif (null !== $idError = $this->getErrorIdentifier($resource->getRealData(), static::TYPE_DELETE)) {
            $hasError = true;
            $resource->setStatus(ResourceStatutes::ERROR);
            $resource->getErrors()->add(new ConstraintViolation($idError, $idError, array(), null, null, null));
            $continue = true;
        }

        return array($continue, $hasError);
    }

    /**
     * Do delete a resource.
     *
     * @param ResourceInterface $resource The resource
     * @param bool              $soft     The soft deletable
     *
     * @return bool Check if the resource is skipped or deleted
     */
    protected function doDeleteResource(ResourceInterface $resource, $soft)
    {
        $skipped = false;
        $object = $resource->getRealData();

        if ($object instanceof SoftDeletableInterface) {
            if ($soft) {
                if ($object->isDeleted()) {
                    $skipped = true;
                } else {
                    $this->om->remove($object);
                }
            } else {
                if (!$object->isDeleted()) {
                    $object->setDeletedAt(new \DateTime());
                }
                $this->om->remove($object);
            }
        } else {
            $this->om->remove($object);
        }

        return $skipped;
    }
}
