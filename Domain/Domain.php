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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Fxp\Component\Resource\Event\PostDeletesEvent;
use Fxp\Component\Resource\Event\PreDeletesEvent;
use Fxp\Component\Resource\Exception\BadMethodCallException;
use Fxp\Component\Resource\Model\SoftDeletableInterface;
use Fxp\Component\Resource\ResourceInterface;
use Fxp\Component\Resource\ResourceItem;
use Fxp\Component\Resource\ResourceList;
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
    public function createQueryBuilder($alias = 'o', ?string $indexBy = null): QueryBuilder
    {
        if ($this->om instanceof EntityManagerInterface) {
            /** @var EntityRepository $repo */
            $repo = $this->getRepository();

            return $repo->createQueryBuilder($alias, $indexBy);
        }

        throw new BadMethodCallException('The "Domain::createQueryBuilder()" method can only be called for a domain with Doctrine ORM Entity Manager');
    }

    /**
     * {@inheritdoc}
     */
    public function deletes(array $resources, bool $soft = true, bool $autoCommit = false): ResourceListInterface
    {
        $list = ResourceUtil::convertObjectsToResourceList(array_values($resources), $this->getClass(), false);

        $this->dispatchEvent(new PreDeletesEvent($this->getClass(), $list));
        $this->beginTransaction($autoCommit);
        $hasError = $this->doDeleteList($list, $autoCommit, $soft);
        $this->doFlushFinalTransaction($list, $autoCommit, $hasError);

        $this->dispatchEvent(new PostDeletesEvent($this->getClass(), $list));

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function undeletes(array $identifiers, bool $autoCommit = false): ResourceListInterface
    {
        list($objects, $missingIds) = $this->convertIdentifierToObjects($identifiers);
        $errorResources = [];

        foreach ($missingIds as $id) {
            $sdt = new \stdClass();
            $sdt->{DomainUtil::getIdentifierName($this->om, $this->getClass())} = $id;
            $resource = new ResourceItem($sdt);
            DomainUtil::addResourceError($resource, $this->translator->trans('domain.object_does_not_exist', ['{{ id }}' => $id], 'FxpResource'));
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
    protected function convertIdentifierToObjects(array $identifiers): array
    {
        $idName = DomainUtil::getIdentifierName($this->om, $this->getClass());
        $objects = [];
        $missingIds = [];
        $searchIds = DomainUtil::extractIdentifierInObjectList($identifiers, $objects);

        if (\count($searchIds) > 0) {
            $previousFilters = $this->disableFilters();
            $searchObjects = $this->getRepository()->findBy([$idName => $searchIds]);
            $this->enableFilters($previousFilters);
            $objects = array_merge($objects, $searchObjects);

            if (\count($searchIds) !== \count($searchObjects)) {
                $missingIds = $searchIds;

                foreach ($objects as $object) {
                    $pos = array_search(DomainUtil::getIdentifier($this->om, $object), $missingIds, true);

                    if (false !== $pos) {
                        array_splice($missingIds, $pos, 1);
                    }
                }
            }
        }

        return [$objects, $missingIds];
    }

    /**
     * {@inheritdoc}
     */
    protected function persist(array $resources, bool $autoCommit, int $type, array $errorResources = []): ResourceList
    {
        list($preEventClass, $postEventClass) = DomainUtil::getEventClasses($type);
        $list = ResourceUtil::convertObjectsToResourceList(array_values($resources), $this->getClass());

        foreach ($errorResources as $errorResource) {
            $list->add($errorResource);
        }

        $this->dispatchEvent(new $preEventClass($this->getClass(), $list));
        $this->beginTransaction($autoCommit);
        $hasError = $this->doPersistList($list, $autoCommit, $type);
        $this->doFlushFinalTransaction($list, $autoCommit, $hasError);

        $this->dispatchEvent(new $postEventClass($this->getClass(), $list));

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
    protected function doPersistList(ResourceListInterface $resources, bool $autoCommit, int $type): bool
    {
        $hasError = false;
        $hasFlushError = false;

        foreach ($resources as $i => $resource) {
            if (!$autoCommit && $hasError) {
                $resource->setStatus(ResourceStatutes::CANCELED);
            } elseif ($autoCommit && $hasFlushError && $hasError) {
                DomainUtil::addResourceError($resource, $this->translator->trans('domain.database_previous_error', [], 'FxpResource'));
            } else {
                list($successStatus, $hasFlushError) = $this->doPersistResource($resource, $autoCommit, $type);
                $hasError = $this->finalizeResourceStatus($resource, $successStatus, $hasError);
            }
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
    protected function doPersistResource(ResourceInterface $resource, bool $autoCommit, int $type): array
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

        return [$successStatus, $hasFlushError];
    }

    /**
     * Validate the resource only when type is undelete.
     *
     * @param ResourceInterface $resource The resource
     * @param int               $type     The type of persist action
     */
    protected function validateUndeleteResource(ResourceInterface $resource, $type): void
    {
        if (static::TYPE_UNDELETE === $type) {
            $object = $resource->getRealData();

            if ($object instanceof SoftDeletableInterface) {
                $object->setDeletedAt();
            } else {
                DomainUtil::addResourceError($resource, $this->translator->trans('domain.resource_type_not_undeleted', [], 'FxpResource'));
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
    protected function doDeleteList(ResourceListInterface $resources, bool $autoCommit, bool $soft = true): bool
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
    protected function prepareDeleteResource(ResourceInterface $resource, bool $autoCommit, bool $hasError, bool $hasFlushError): array
    {
        $continue = false;

        if (!$autoCommit && $hasError) {
            $resource->setStatus(ResourceStatutes::CANCELED);
            $continue = true;
        } elseif ($autoCommit && $hasFlushError && $hasError) {
            DomainUtil::addResourceError($resource, $this->translator->trans('domain.database_previous_error', [], 'FxpResource'));
            $continue = true;
        } elseif (null !== $idError = $this->getErrorIdentifier($resource->getRealData(), static::TYPE_DELETE)) {
            $hasError = true;
            $resource->setStatus(ResourceStatutes::ERROR);
            $resource->getErrors()->add(new ConstraintViolation($idError, $idError, [], $resource->getRealData(), null, null));
            $continue = true;
        }

        return [$continue, $hasError];
    }

    /**
     * Do delete a resource.
     *
     * @param ResourceInterface $resource The resource
     * @param bool              $soft     The soft deletable
     *
     * @throws
     *
     * @return bool Check if the resource is skipped or deleted
     */
    protected function doDeleteResource(ResourceInterface $resource, bool $soft): bool
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
    protected function doDeleteResourceAction(ResourceInterface $resource): void
    {
        try {
            $this->om->remove($resource->getRealData());
        } catch (\Exception $e) {
            DomainUtil::injectErrorMessage($this->translator, $resource, $e, $this->debug);
        }
    }
}
