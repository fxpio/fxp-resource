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

use Fxp\Component\Resource\Exception\InvalidArgumentException;

/**
 * Domain manager.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
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
     * @var DomainFactoryInterface
     */
    protected $factory;

    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * Constructor.
     *
     * @param DomainInterface[]      $domains The resource domains
     * @param DomainFactoryInterface $factory The domain factory
     */
    public function __construct(array $domains, DomainFactoryInterface $factory)
    {
        $this->domains = [];
        $this->shortNames = $factory->getShortNames();
        $this->factory = $factory;

        foreach ($domains as $domain) {
            $this->add($domain);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has($class)
    {
        $class = $this->findClassName($class, false);

        return isset($this->domains[$class])
            || $this->factory->isManagedClass($class);
    }

    /**
     * {@inheritdoc}
     */
    public function add(DomainInterface $domain)
    {
        $this->factory->injectDependencies($domain);
        $class = $domain->getClass();

        if ($domain instanceof DomainAwareInterface) {
            $domain->setDomainManager($this);
        }

        if (isset($this->domains[$class])) {
            throw new InvalidArgumentException(sprintf('The resource domain for the class "%s" already exist', $class));
        }

        $this->domains[$class] = $domain;
        $this->shortNames[$domain->getShortName()] = $class;
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
        $this->init();

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

        if (!isset($this->domains[$class])) {
            $this->add($this->factory->create($class));
        }

        return $this->domains[$class];
    }

    /**
     * Find the real class name of short name or doctrine resolve target.
     *
     * @param string $class    The short name or class name or the doctrine resolve target
     * @param bool   $required Check if the class name must be managed by doctrine
     *
     * @return string The real class of short name
     */
    protected function findClassName($class, $required = true)
    {
        $class = isset($this->shortNames[$class])
            ? $this->shortNames[$class]
            : $class;

        return $required
            ? $this->factory->getManagedClass($class)
            : $class;
    }

    /**
     * Initialize all resource domains.
     */
    private function init()
    {
        if (!$this->initialized) {
            $this->initialized = true;

            foreach ($this->shortNames as $class) {
                if (!isset($this->domains[$class])) {
                    $this->add($this->factory->create($class));
                }
            }
        }
    }
}
