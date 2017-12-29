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

/**
 * A resource domain with domain manager.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class DomainAware extends Domain implements DomainAwareInterface
{
    /**
     * @var DomainManagerInterface|null
     */
    protected $domainManager;

    /**
     * {@inheritdoc}
     */
    public function setDomainManager(DomainManagerInterface $domainManager)
    {
        $this->domainManager = $domainManager;

        return $this;
    }
}
