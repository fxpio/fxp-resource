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

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Fxp\Component\Resource\ResourceInterface;
use Fxp\Component\Resource\ResourceListInterface;
use Symfony\Component\Form\FormInterface;

/**
 * A resource domain interface.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
interface DomainInterface
{
    /**
     * Get the doctrine object registry.
     *
     * @return ObjectManager
     */
    public function getObjectManager();

    /**
     * Get the class name for this resource domain.
     *
     * @return string
     */
    public function getClass();

    /**
     * Get the doctrine repository for this resource domain.
     *
     * @return ObjectRepository|EntityRepository
     */
    public function getRepository();

    /**
     * Create the query builder for this domain.
     *
     * @param string      $alias   The alias of class in query
     * @param string|null $indexBy The index for the from
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias = 'o', $indexBy = null);

    /**
     * Generate a new resource instance with default values.
     *
     * @param array $options The options of fxp default value factory
     *
     * @return object
     */
    public function newInstance(array $options = []);

    /**
     * Create a resource.
     *
     * @param object|FormInterface $resource The object resource instance of defined class name
     *
     * @return ResourceInterface
     */
    public function create($resource);

    /**
     * Create resources.
     *
     * Warning: It's recommended to limit the number of resources.
     *
     * @param object[]|FormInterface[] $resources  The list of object resource instance
     * @param bool                     $autoCommit Commit transaction for each resource or all
     *                                             (continue the action even if there is an error on a resource)
     *
     * @return ResourceListInterface
     */
    public function creates(array $resources, $autoCommit = false);

    /**
     * Update a resource.
     *
     * @param object|FormInterface $resource The object resource
     *
     * @return ResourceInterface
     */
    public function update($resource);

    /**
     * Update resources.
     *
     * Warning: It's recommended to limit the number of resources.
     *
     * @param object[]|FormInterface[] $resources  The list of object resource instance
     * @param bool                     $autoCommit Commit transaction for each resource or all
     *                                             (continue the action even if there is an error on a resource)
     *
     * @return ResourceListInterface
     */
    public function updates(array $resources, $autoCommit = false);

    /**
     * Update or insert a resource.
     *
     * @param object|FormInterface $resource The object resource
     *
     * @return ResourceInterface
     */
    public function upsert($resource);

    /**
     * Update or insert resources.
     *
     * Warning: It's recommended to limit the number of resources.
     *
     * @param object[]|FormInterface[] $resources  The list of object resource instance
     * @param bool                     $autoCommit Commit transaction for each resource or all
     *                                             (continue the action even if there is an error on a resource)
     *
     * @return ResourceListInterface
     */
    public function upserts(array $resources, $autoCommit = false);

    /**
     * Delete a resource.
     *
     * @param object $resource The object resource
     * @param bool   $soft     Check if the delete must be hard or soft for the objects compatibles
     *
     * @return ResourceInterface
     */
    public function delete($resource, $soft = true);

    /**
     * Delete resources.
     *
     * Warning: It's recommended to limit the number of resources.
     *
     * @param object[] $resources  The list of object resource instance
     * @param bool     $soft       Check if the delete must be hard or soft for the objects compatibles
     * @param bool     $autoCommit Commit transaction for each resource or all
     *                             (continue the action even if there is an error on a resource)
     *
     * @return ResourceListInterface
     */
    public function deletes(array $resources, $soft = true, $autoCommit = false);

    /**
     * Undelete a resource.
     *
     * @param object|int|string $identifier The object or object identifier
     *
     * @return ResourceInterface
     */
    public function undelete($identifier);

    /**
     * Undelete resources.
     *
     * Warning: It's recommended to limit the number of resources.
     *
     * @param object[]|int[]|string[] $identifiers The list of objects or object identifiers
     * @param bool                    $autoCommit  Commit transaction for each resource or all
     *                                             (continue the action even if there is an error on a resource)
     *
     * @return ResourceListInterface
     */
    public function undeletes(array $identifiers, $autoCommit = false);
}
