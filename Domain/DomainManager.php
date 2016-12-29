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

use Sonatra\Component\Resource\Exception\InvalidArgumentException;

/**
 * Domain manager.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainManager implements DomainManagerInterface
{
    /**
     * @var DomainInterface[]
     */
    protected $domains;

    /**
     * @var array
     */
    protected $shortNames;

    /**
     * @var array
     */
    protected $resolveTargets;

    /**
     * @var DomainFactoryInterface
     */
    protected $factory;

    /**
     * @var array
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @param DomainInterface[]      $domains The resource domains
     * @param DomainFactoryInterface $factory The domain factory
     */
    public function __construct(array $domains, DomainFactoryInterface $factory)
    {
        $this->domains = array();
        $this->shortNames = array();
        $this->resolveTargets = array();
        $this->factory = $factory;
        $this->cache = array();

        foreach ($domains as $domain) {
            $this->add($domain);
        }
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
    public function has($class)
    {
        $class = $this->findClassName($class);

        return isset($this->domains[$this->findClassName($class)])
            || $this->factory->isManagedClass($class);
    }

    /**
     * {@inheritdoc}
     */
    public function add(DomainInterface $domain)
    {
        if (isset($this->domains[$domain->getClass()])) {
            throw new InvalidArgumentException(sprintf('The resource domain for the class "%s" already exist', $domain->getClass()));
        }

        if (isset($this->shortNames[$domain->getShortName()])) {
            throw new InvalidArgumentException(sprintf('The resource domain for the short name "%s" already exist', $domain->getShortName()));
        }

        $this->domains[$domain->getClass()] = $domain;
        $this->shortNames[$domain->getShortName()] = $domain->getClass();
    }

    /**
     * {@inheritdoc}
     */
    public function remove($class)
    {
        $class = $this->findClassName($class);

        if (isset($this->domains[$class])) {
            unset($this->shortNames[$this->domains[$class]->getShortName()]);
            unset($this->domains[$class]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->domains;
    }

    /**
     * {@inheritdoc}
     */
    public function getShortNames()
    {
        return $this->shortNames;
    }

    /**
     * {@inheritdoc}
     */
    public function get($class)
    {
        $class = $this->findClassName($class);

        if (isset($this->cache[$class])) {
            return $this->domains[$this->cache[$class]];
        }

        $getClass = $class;
        $class = $this->factory->getManagedClass($class);
        $this->cache[$getClass] = $class;

        if (!isset($this->domains[$class])) {
            $this->add($this->factory->create($class));
        }

        return $this->domains[$class];
    }

    /**
     * Find the real class name of short name or doctrine resolve target.
     *
     * @param string $class The short name or class name or the doctrine resolve target
     *
     * @return string The real class of short name
     */
    protected function findClassName($class)
    {
        $class = isset($this->shortNames[$class])
            ? $this->shortNames[$class]
            : $class;

        return isset($this->resolveTargets[$class])
            ? $this->resolveTargets[$class]
            : $class;
    }
}
