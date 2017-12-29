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

use Doctrine\ORM\EntityManager;
use Fxp\Component\Resource\Event\ResourceEvent;
use Fxp\Component\Resource\Exception\BadMethodCallException;
use Fxp\Component\Resource\Model\SoftDeletableInterface;
use Fxp\Component\Resource\ResourceEvents;
use Fxp\Component\Resource\ResourceInterface;
use Fxp\Component\Resource\ResourceItem;
use Fxp\Component\Resource\ResourceListInterface;
use Fxp\Component\Resource\ResourceStatutes;
use Fxp\Component\Resource\ResourceUtil;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * A resource domain.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class Domain extends BaseDomain
{
    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder($alias = 'o', $indexBy = null)
    {
        if ($this->om instanceof EntityManager) {
            return $this->getRepository()->createQueryBuilder($alias, $indexBy);
        }

        throw new BadMethodCallException('The "Domain::createQueryBuilder()" method can only be called for a domain with Doctrine ORM Entity Manager');
    }

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
            $resource = new ResourceItem($sdt);
            DomainUtil::addResourceError($resource, $this->translator->trans('domain.object_does_not_exist', array('{{ id }}' => $id), 'FxpResource'));
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
        $searchIds = DomainUtil::extractIdentifierInObjectList($identifiers, $objects);

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
                DomainUtil::addResourceError($resource, $this->translator->trans('domain.database_previous_error', array(), 'FxpResource'));
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
            try {
                $this->om->persist($object);
                $hasFlushError = $this->doAutoCommitFlushTransaction($resource, $autoCommit);
            } catch (\Exception $e) {
                $hasFlushError = DomainUtil::injectErrorMessage($this->translator, $resource, $e, $this->debug);
            }
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
                DomainUtil::addResourceError($resource, $this->translator->trans('domain.resource_type_not_undeleted', array(), 'FxpResource'));
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
            DomainUtil::addResourceError($resource, $this->translator->trans('domain.database_previous_error', array(), 'FxpResource'));
            $continue = true;
        } elseif (null !== $idError = $this->getErrorIdentifier($resource->getRealData(), static::TYPE_DELETE)) {
            $hasError = true;
            $resource->setStatus(ResourceStatutes::ERROR);
            $resource->getErrors()->add(new ConstraintViolation($idError, $idError, array(), $resource->getRealData(), null, null));
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
                    $this->doDeleteResourceAction($resource);
                }
            } else {
                if (!$object->isDeleted()) {
                    $object->setDeletedAt(new \DateTime());
                }
                $this->doDeleteResourceAction($resource);
            }
        } else {
            $this->doDeleteResourceAction($resource);
        }

        return $skipped;
    }

    /**
     * Real delete a entity in object manager.
     *
     * @param ResourceInterface $resource The resource
     */
    protected function doDeleteResourceAction(ResourceInterface $resource)
    {
        try {
            $this->om->remove($resource->getRealData());
        } catch (\Exception $e) {
            DomainUtil::injectErrorMessage($this->translator, $resource, $e, $this->debug);
        }
    }
}
