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

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Sonatra\Bundle\DefaultValueBundle\DefaultValue\ObjectFactoryInterface;
use Sonatra\Bundle\ResourceBundle\Exception\InvalidConfigurationException;
use Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * A resource domain interface.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
interface DomainInterface
{
    /**
     * Set the doctrine object registry.
     *
     * @param ObjectManager $om
     *
     * @throws InvalidConfigurationException When this resource domain class is not managed by doctrine.
     */
    public function setObjectManager(ObjectManager $om);

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
     * Get the doctrine repository for this resource domain.
     *
     * @return ObjectRepository
     */
    public function getRepository();

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
     * @param object $resource The object resource instance of defined class name
     *
     * @return ResourceInterface
     */
    public function create($resource);

    /**
     * Create resources.
     *
     * @param object[] $resources The list of object resource instance
     *
     * @return ResourceInterface[]
     */
    public function creates(array $resources);

    /**
     * Update a resource.
     *
     * @param object $resource The object resource
     *
     * @return ResourceInterface
     */
    public function update($resource);

    /**
     * Update resources.
     *
     * @param object[] $resources The list of object resource instance
     *
     * @return ResourceInterface[]
     */
    public function updates(array $resources);

    /**
     * Update or insert a resource.
     *
     * @param object $resource The object resource
     *
     * @return ResourceInterface
     */
    public function upsert($resource);

    /**
     * Update or insert resources.
     *
     * @param object[] $resources The list of object resource instance
     *
     * @return ResourceInterface[]
     */
    public function upserts(array $resources);

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
     * @param object[] $resources The list of object resource instance
     * @param bool     $soft      Check if the delete must be hard or soft for the objects compatibles
     *
     * @return ResourceInterface[]
     */
    public function deletes(array $resources, $soft = true);

    /**
     * Undelete a resource.
     *
     * @param int|string $identifier The object identifier
     *
     * @return ResourceInterface
     */
    public function undelete($identifier);

    /**
     * Undelete resources.
     *
     * @param object[] $identifiers The list of object identifier
     *
     * @return ResourceInterface[]
     */
    public function undeletes(array $identifiers);
}
