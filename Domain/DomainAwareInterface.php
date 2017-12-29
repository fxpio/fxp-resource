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
 * A resource domain interface with domain manger.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
interface DomainAwareInterface extends DomainInterface
{
    /**
     * Set the domain manager.
     *
     * @param DomainManagerInterface $domainManager The domain manager
     *
     * @return self
     */
    public function setDomainManager(DomainManagerInterface $domainManager);
}
