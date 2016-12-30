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

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Sonatra\Component\DefaultValue\ObjectFactoryInterface;
use Sonatra\Component\Resource\Exception\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Resource domain factory.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainFactory implements DomainFactoryInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $or;

    /**
     * @var EventDispatcherInterface
     */
    protected $ed;

    /**
     * @var ObjectFactoryInterface
     */
    protected $of;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var array
     */
    protected $undeleteDisableFilters;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var array
     */
    protected $resolveTargets;

    /**
     * Constructor.
     *
     * @param ManagerRegistry          $or                     The doctrine registry
     * @param EventDispatcherInterface $ed                     The event dispatcher
     * @param ObjectFactoryInterface   $of                     The default value object factory
     * @param ValidatorInterface       $validator              The validator
     * @param array                    $undeleteDisableFilters The undelete disable filters
     * @param bool                     $debug                  The debug mode
     */
    public function __construct(ManagerRegistry $or,
                                EventDispatcherInterface $ed,
                                ObjectFactoryInterface $of,
                                ValidatorInterface $validator,
                                array $undeleteDisableFilters = array(),
                                $debug = false)
    {
        $this->or = $or;
        $this->ed = $ed;
        $this->of = $of;
        $this->validator = $validator;
        $this->undeleteDisableFilters = $undeleteDisableFilters;
        $this->debug = $debug;
        $this->resolveTargets = array();
    }

    /**
     * {@inheritdoc}
     */
    public function addResolveTargets(array $resolveTargets)
    {
        $this->resolveTargets = array_merge($this->resolveTargets, $resolveTargets);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getShortNames()
    {
        $names = array();

        foreach ($this->or->getManagers() as $manager) {
            /* @var ClassMetadata $meta */
            foreach ($manager->getMetadataFactory()->getAllMetadata() as $meta) {
                $names[DomainUtil::generateShortName($meta->getName())] = $meta->getName();
            }
        }

        return $names;
    }

    /**
     * {@inheritdoc}
     */
    public function isManagedClass($class)
    {
        return null !== $this->getManager($class);
    }

    /**
     * {@inheritdoc}
     */
    public function getManagedClass($class)
    {
        return $this->getRequiredManager($class)->getClassMetadata($class)->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function create($class, $shortName = null)
    {
        return new Domain($class, $shortName);
    }

    /**
     * {@inheritdoc}
     */
    public function injectDependencies(DomainInterface $domain)
    {
        $domain->setDebug($this->debug);
        $domain->setObjectManager($this->getRequiredManager($domain->getClass()), $this->undeleteDisableFilters);
        $domain->setEventDispatcher($this->ed);
        $domain->setObjectFactory($this->of);
        $domain->setValidator($this->validator);

        return $domain;
    }

    /**
     * Get the doctrine object manager of the class.
     *
     * @param string $class The class name or doctrine shortcut class name
     *
     * @return ObjectManager
     *
     * @throws InvalidArgumentException When the class is not registered in doctrine
     */
    protected function getManager($class)
    {
        $class = $this->findClassName($class);
        $manager = $this->or->getManagerForClass($class);

        if (null === $manager) {
            foreach ($this->or->getManagers() as $objectManager) {
                if ($objectManager->getMetadataFactory()->hasMetadataFor($class)) {
                    $manager = $objectManager;
                    break;
                }
            }
        }

        return $manager;
    }

    protected function getRequiredManager($class)
    {
        $manager = $this->getManager($class);

        if (null === $manager) {
            throw new InvalidArgumentException(sprintf('The "%s" class is not registered in doctrine', $class));
        }

        return $manager;
    }

    protected function findClassName($class)
    {
        return isset($this->resolveTargets[$class])
            ? $this->resolveTargets[$class]
            : $class;
    }
}
