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
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\QueryBuilder;
use Sonatra\Component\DefaultValue\ObjectFactoryInterface;
use Sonatra\Component\Resource\Exception\InvalidConfigurationException;
use Sonatra\Component\Resource\ResourceInterface;
use Sonatra\Component\Resource\ResourceListInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * A resource domain interface.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
interface DomainInterface
{
    /**
     * Set the debug mode.
     *
     * @param bool $debug The debug mode
     */
    public function setDebug($debug);

    /**
     * Set the doctrine object registry.
     *
     * @param ObjectManager $om             The object manager
     * @param array         $disableFilters The list of doctrine filters must be disabled for undelete resources
     *
     * @throws InvalidConfigurationException When this resource domain class is not managed by doctrine
     */
    public function setObjectManager(ObjectManager $om, $disableFilters = array());

    /**
     * Get the doctrine object registry.
     *
     * @return ObjectManager
     */
    public function getObjectManager();

    /**
     * Set the event dispatcher.
     *
     * @param EventDispatcherInterface $ed
     */
    public function setEventDispatcher(EventDispatcherInterface $ed);

    /**
     * Set the default value object factory.
     *
     * @param ObjectFactoryInterface $of
     */
    public function setObjectFactory(ObjectFactoryInterface $of);

    /**
     * Set the validator.
     *
     * @param ValidatorInterface $validator The validator
     */
    public function setValidator(ValidatorInterface $validator);

    /**
     * Get the class name for this resource domain.
     *
     * @return string
     */
    public function getClass();

    /**
     * Get the short name of this resource domain.
     *
     * @return string
     */
    public function getShortName();

    /**
     * Get the doctrine repository for this resource domain.
     *
     * @return ObjectRepository
     */
    public function getRepository();

    /**
     * Create the query builder for this domain.
     *
     * @param string $alias The alias of class in query
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias = 'o');

    /**
     * Get the prefix event of this domain.
     *
     * @return string
     */
    public function getEventPrefix();

    /**
     * Generate a new resource instance with default values.
     *
     * @param array $options The options of sonatra default value factory
     *
     * @return object
     */
    public function newInstance(array $options = array());

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
