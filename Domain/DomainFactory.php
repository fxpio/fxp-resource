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

use Doctrine\Common\Persistence\ManagerRegistry;
use Fxp\Component\Resource\Object\ObjectFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Resource domain factory.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
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
     * @var TranslatorInterface
     */
    protected $translator;

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
     * @param TranslatorInterface      $translator             The translator
     * @param array                    $undeleteDisableFilters The undelete disable filters
     * @param bool                     $debug                  The debug mode
     */
    public function __construct(ManagerRegistry $or,
                                EventDispatcherInterface $ed,
                                ObjectFactoryInterface $of,
                                ValidatorInterface $validator,
                                TranslatorInterface $translator,
                                array $undeleteDisableFilters = [],
                                $debug = false)
    {
        $this->or = $or;
        $this->ed = $ed;
        $this->of = $of;
        $this->validator = $validator;
        $this->translator = $translator;
        $this->undeleteDisableFilters = $undeleteDisableFilters;
        $this->debug = $debug;
        $this->resolveTargets = [];
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
    public function isManagedClass($class)
    {
        return null !== DomainDoctrineUtil::getManager($this->or, $this->findClassName($class));
    }

    /**
     * {@inheritdoc}
     */
    public function getManagedClass($class)
    {
        return DomainDoctrineUtil::getRequiredManager($this->or, $this->findClassName($class))
            ->getClassMetadata($class)->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function create($class)
    {
        return new Domain($class,
            DomainDoctrineUtil::getRequiredManager($this->or, $this->findClassName($class)),
            $this->of,
            $this->ed,
            $this->validator,
            $this->translator,
            $this->undeleteDisableFilters,
            $this->debug);
    }

    /**
     * Find the class name by the the class name or the Doctrine resolved target.
     *
     * @param string $class The class name
     *
     * @return string
     */
    protected function findClassName($class)
    {
        return $this->resolveTargets[$class] ?? $class;
    }
}
