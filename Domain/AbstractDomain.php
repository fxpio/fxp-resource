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

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Fxp\Component\DoctrineExtensions\Util\SqlFilterUtil;
use Fxp\Component\Resource\Event\ResourceEvent;
use Fxp\Component\Resource\Exception\InvalidConfigurationException;
use Fxp\Component\Resource\Object\ObjectFactoryInterface;
use Fxp\Component\Resource\ResourceInterface;
use Fxp\Component\Resource\ResourceList;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A abstract class for resource domain.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractDomain implements DomainInterface
{
    public const TYPE_CREATE = 0;
    public const TYPE_UPDATE = 1;
    public const TYPE_UPSERT = 2;
    public const TYPE_DELETE = 3;
    public const TYPE_UNDELETE = 4;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var ObjectFactoryInterface
     */
    protected $of;

    /**
     * @var EventDispatcherInterface
     */
    protected $ed;

    /**
     * @var ValidatorInterface;
     */
    protected $validator;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

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
     * @param string                   $class          The class name
     * @param ObjectManager            $om             The object manager
     * @param ObjectFactoryInterface   $of             The object factory
     * @param EventDispatcherInterface $ed             The event dispatcher
     * @param ValidatorInterface       $validator      The validator
     * @param TranslatorInterface      $translator     The translator
     * @param array                    $disableFilters The list of doctrine filters must be disabled for undelete resources
     * @param bool                     $debug          The debug mode
     */
    public function __construct(
        $class,
        ObjectManager $om,
        ObjectFactoryInterface $of,
        EventDispatcherInterface $ed,
        ValidatorInterface $validator,
        TranslatorInterface $translator,
        $disableFilters = [],
        $debug = false
    ) {
        $this->om = $om;
        $this->of = $of;
        $this->ed = $ed;
        $this->validator = $validator;
        $this->translator = $translator;
        $this->disableFilters = [];
        $this->debug = (bool) $debug;

        try {
            $this->class = $om->getClassMetadata($class)->getName();
        } catch (MappingException $e) {
            $msg = sprintf('The "%s" class is not managed by doctrine object manager', $class);

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
    public function getClass()
    {
        return $this->class;
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
    public function newInstance(array $options = [])
    {
        return $this->of->create($this->getClass(), $options);
    }

    /**
     * {@inheritdoc}
     */
    public function create($resource)
    {
        return DomainUtil::oneAction($this->creates([$resource], true));
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
        return DomainUtil::oneAction($this->updates([$resource], true));
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
        return DomainUtil::oneAction($this->upserts([$resource], true));
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
        return DomainUtil::oneAction($this->deletes([$resource], $soft, true));
    }

    /**
     * {@inheritdoc}
     */
    public function undelete($identifier)
    {
        return DomainUtil::oneAction($this->undeletes([$identifier], true));
    }

    /**
     * Dispatch the event.
     *
     * @param ResourceEvent $event The event
     *
     * @return ResourceEvent
     */
    protected function dispatchEvent(ResourceEvent $event)
    {
        $this->ed->dispatch(\get_class($event), $event);

        return $event;
    }

    /**
     * Disable the doctrine filters.
     *
     * @return array The previous values of filters
     */
    protected function disableFilters()
    {
        $previous = SqlFilterUtil::findFilters($this->om, $this->disableFilters);
        SqlFilterUtil::disableFilters($this->om, $previous);

        return $previous;
    }

    /**
     * Enable the doctrine filters.
     *
     * @param array $previousValues the previous values of filters
     */
    protected function enableFilters(array $previousValues = []): void
    {
        SqlFilterUtil::enableFilters($this->om, $previousValues);
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
    abstract protected function persist(array $resources, $autoCommit, $type, array $errorResources = []);
}
