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

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sonatra\Component\DefaultValue\ObjectFactoryInterface;
use Sonatra\Component\Resource\Event\ResourceEvent;
use Sonatra\Component\Resource\ResourceInterface;
use Sonatra\Component\Resource\ResourceList;
use Sonatra\Component\Resource\Exception\InvalidConfigurationException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * A abstract class for resource domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
abstract class AbstractDomain implements DomainInterface
{
    const TYPE_CREATE = 0;
    const TYPE_UPDATE = 1;
    const TYPE_UPSERT = 2;
    const TYPE_DELETE = 3;
    const TYPE_UNDELETE = 4;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $shortName;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var EventDispatcherInterface
     */
    protected $ed;

    /**
     * @var ObjectFactoryInterface
     */
    protected $of;

    /**
     * @var ValidatorInterface;
     */
    protected $validator;

    /**
     * @var string
     */
    protected $eventPrefix;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var array
     */
    protected $disableFilters;

    /**
     * Constructor.
     *
     * @param string      $class     The class name
     * @param string|null $shortName The short name
     */
    public function __construct($class, $shortName = null)
    {
        $this->class = $class;
        $this->shortName = $shortName;
        $this->eventPrefix = ResourceEvent::formatEventPrefix($class);
        $this->debug = false;
        $this->disableFilters = array();
    }

    /**
     * {@inheritdoc}
     */
    public function setDebug($debug)
    {
        $this->debug = (bool) $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function setObjectManager(ObjectManager $om, $disableFilters = array())
    {
        $this->om = $om;

        try {
            $this->class = $om->getClassMetadata($this->class)->getName();
            $this->eventPrefix = ResourceEvent::formatEventPrefix($this->class);
        } catch (MappingException $e) {
            $msg = sprintf('The "%s" class is not managed by doctrine object manager', $this->getClass());
            throw new InvalidConfigurationException($msg, 0, $e);
        }

        if ($om instanceof EntityManagerInterface) {
            $this->disableFilters = $disableFilters;
            $this->connection = $om->getConnection();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectManager()
    {
        return $this->om;
    }

    /**
     * {@inheritdoc}
     */
    public function setEventDispatcher(EventDispatcherInterface $ed)
    {
        $this->ed = $ed;
    }

    /**
     * {@inheritdoc}
     */
    public function setObjectFactory(ObjectFactoryInterface $of)
    {
        $this->of = $of;
    }

    /**
     * {@inheritdoc}
     */
    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function getShortName()
    {
        if (null === $this->shortName) {
            $this->shortName = DomainUtil::generateShortName($this->class);
        }

        return $this->shortName;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
        return $this->om->getRepository($this->getClass());
    }

    /**
     * {@inheritdoc}
     */
    public function getClassMetadata()
    {
        return $this->om->getClassMetadata($this->getClass());
    }

    /**
     * {@inheritdoc}
     */
    public function getEventPrefix()
    {
        return $this->eventPrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function getEventName($name)
    {
        return $this->getEventPrefix().$name;
    }

    /**
     * {@inheritdoc}
     */
    public function newInstance(array $options = array())
    {
        return $this->of->create($this->getClass(), null, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function create($resource)
    {
        return DomainUtil::oneAction($this->creates(array($resource), true));
    }

    /**
     * {@inheritdoc}
     */
    public function creates(array $resources, $autoCommit = false)
    {
        return $this->persist($resources, $autoCommit, Domain::TYPE_CREATE);
    }

    /**
     * {@inheritdoc}
     */
    public function update($resource)
    {
        return DomainUtil::oneAction($this->updates(array($resource), true));
    }

    /**
     * {@inheritdoc}
     */
    public function updates(array $resources, $autoCommit = false)
    {
        return $this->persist($resources, $autoCommit, Domain::TYPE_UPDATE);
    }

    /**
     * {@inheritdoc}
     */
    public function upsert($resource)
    {
        return DomainUtil::oneAction($this->upserts(array($resource), true));
    }

    /**
     * {@inheritdoc}
     */
    public function upserts(array $resources, $autoCommit = false)
    {
        return $this->persist($resources, $autoCommit, Domain::TYPE_UPSERT);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($resource, $soft = true)
    {
        return DomainUtil::oneAction($this->deletes(array($resource), $soft, true));
    }

    /**
     * {@inheritdoc}
     */
    public function undelete($identifier)
    {
        return DomainUtil::oneAction($this->undeletes(array($identifier), true));
    }

    /**
     * Dispatch the event.
     *
     * @param string        $name  The event name
     * @param ResourceEvent $event The event
     *
     * @return ResourceEvent
     */
    protected function dispatchEvent($name, ResourceEvent $event)
    {
        return $this->ed->dispatch($this->getEventName($name), $event);
    }

    /**
     * Disable the doctrine filters.
     *
     * @return array The previous values of filters
     */
    protected function disableFilters()
    {
        $previous = array();

        if ($this->om instanceof EntityManager) {
            $oFilters = $this->om->getFilters();

            foreach ($this->disableFilters as $filterName) {
                if ($oFilters->has($filterName)) {
                    $previous[$filterName] = $oFilters->isEnabled($filterName);
                    $oFilters->disable($filterName);
                }
            }
        }

        return $previous;
    }

    /**
     * Enable the doctrine filters.
     *
     * @param array $previousValues the previous values of filters
     */
    protected function enableFilters(array $previousValues = array())
    {
        if ($this->om instanceof EntityManager) {
            $oFilters = $this->om->getFilters();

            foreach ($this->disableFilters as $filterName) {
                if ($oFilters->has($filterName) && !$oFilters->isEnabled($filterName)
                        && isset($previousValues[$filterName]) && $previousValues[$filterName]) {
                    $oFilters->enable($filterName);
                }
            }
        }
    }

    /**
     * Persist the resources.
     *
     * Warning: It's recommended to limit the number of resources.
     *
     * @param FormInterface[]|object[] $resources      The list of object resource instance
     * @param bool                     $autoCommit     Commit transaction for each resource or all
     *                                                 (continue the action even if there is an error on a resource)
     * @param int                      $type           The type of persist action
     * @param ResourceInterface[]      $errorResources The error resources
     *
     * @return ResourceList
     */
    abstract protected function persist(array $resources, $autoCommit, $type, array $errorResources = array());
}
