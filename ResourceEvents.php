<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource;

use Fxp\Component\Resource\Exception\ClassNotInstantiableException;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
final class ResourceEvents
{
    /**
     * The ResourceEvents::PRE_CREATES event is dispatched at the beginning of the
     * DomainInterface::create() and DomainInterface::creates() method.
     *
     * This event is always prefixed by DomainInterface::getEventPrefix() for each
     * resource domain.
     *
     * This event is mostly here for reading or editing the list of resource instances
     * before the persistence in doctrine. However, it's best to use directly the
     * listeners or subscribers of doctrine.
     *
     * @Event("Fxp\Component\Resource\Event\ResourceEvent")
     */
    const PRE_CREATES = '.domain.pre_creates';

    /**
     * The ResourceEvents::POST_CREATES event is dispatched at the end of the
     * DomainInterface::create() and DomainInterface::creates() method.
     *
     * This event is always prefixed by DomainInterface::getEventPrefix() for each
     * resource domain.
     *
     * This event is mostly here for reading the list of resource instances after
     * the persistence and the flush in doctrine.
     *
     * @Event("Fxp\Component\Resource\Event\ResourceEvent")
     */
    const POST_CREATES = '.domain.post_creates';

    /**
     * The ResourceEvents::PRE_UPDATES event is dispatched at the beginning of the
     * DomainInterface::update() and DomainInterface::updates() method.
     *
     * This event is always prefixed by DomainInterface::getEventPrefix() for each
     * resource domain.
     *
     * This event is mostly here for reading or editing the list of resource instances
     * before the persistence in doctrine. However, it's best to use directly the
     * listeners or subscribers of doctrine.
     *
     * @Event("Fxp\Component\Resource\Event\ResourceEvent")
     */
    const PRE_UPDATES = '.domain.pre_updates';

    /**
     * The ResourceEvents::POST_UPDATES event is dispatched at the end of the
     * DomainInterface::update() and DomainInterface::updates() method.
     *
     * This event is always prefixed by DomainInterface::getEventPrefix() for each
     * resource domain.
     *
     * This event is mostly here for reading the list of resource instances after
     * the persistence and the flush in doctrine.
     *
     * @Event("Fxp\Component\Resource\Event\ResourceEvent")
     */
    const POST_UPDATES = '.domain.post_updates';

    /**
     * The ResourceEvents::PRE_UPSERTS event is dispatched at the beginning of the
     * DomainInterface::upsert() and DomainInterface::upserts() method.
     *
     * This event is always prefixed by DomainInterface::getEventPrefix() for each
     * resource domain.
     *
     * This event is mostly here for reading or editing the list of resource instances
     * before the persistence in doctrine. However, it's best to use directly the
     * listeners or subscribers of doctrine.
     *
     * @Event("Fxp\Component\Resource\Event\ResourceEvent")
     */
    const PRE_UPSERTS = '.domain.pre_upserts';

    /**
     * The ResourceEvents::POST_UPSERTS event is dispatched at the end of the
     * DomainInterface::upsert() and DomainInterface::upserts() method.
     *
     * This event is always prefixed by DomainInterface::getEventPrefix() for each
     * resource domain.
     *
     * This event is mostly here for reading the list of resource instances after
     * the persistence and the flush in doctrine.
     *
     * @Event("Fxp\Component\Resource\Event\ResourceEvent")
     */
    const POST_UPSERTS = '.domain.post_upserts';

    /**
     * The ResourceEvents::PRE_DELETES event is dispatched at the beginning of the
     * DomainInterface::delete() and DomainInterface::deletes() method.
     *
     * This event is always prefixed by DomainInterface::getEventPrefix() for each
     * resource domain.
     *
     * This event is mostly here for reading or editing the list of resource instances
     * before the persistence in doctrine. However, it's best to use directly the
     * listeners or subscribers of doctrine.
     *
     * @Event("Fxp\Component\Resource\Event\ResourceEvent")
     */
    const PRE_DELETES = '.domain.pre_deletes';

    /**
     * The ResourceEvents::POST_DELETES event is dispatched at the end of the
     * DomainInterface::delete() and DomainInterface::deletes() method.
     *
     * This event is always prefixed by DomainInterface::getEventPrefix() for each
     * resource domain.
     *
     * This event is mostly here for reading the list of resource instances after
     * the persistence and the flush in doctrine.
     *
     * @Event("Fxp\Component\Resource\Event\ResourceEvent")
     */
    const POST_DELETES = '.domain.post_deletes';

    /**
     * The ResourceEvents::PRE_UNDELETES event is dispatched at the beginning of the
     * DomainInterface::undelete() and DomainInterface::undeletes() method.
     *
     * This event is always prefixed by DomainInterface::getEventPrefix() for each
     * resource domain.
     *
     * This event is mostly here for reading or editing the list of resource instances
     * before the persistence in doctrine. However, it's best to use directly the
     * listeners or subscribers of doctrine.
     *
     * @Event("Fxp\Component\Resource\Event\ResourceEvent")
     */
    const PRE_UNDELETES = '.domain.pre_undeletes';

    /**
     * The ResourceEvents::POST_UNDELETES event is dispatched at the end of the
     * DomainInterface::undelete() and DomainInterface::undeletes() method.
     *
     * This event is always prefixed by DomainInterface::getEventPrefix() for each
     * resource domain.
     *
     * This event is mostly here for reading the list of resource instances after
     * the persistence and the flush in doctrine.
     *
     * @Event("Fxp\Component\Resource\Event\ResourceEvent")
     */
    const POST_UNDELETES = '.domain.post_undeletes';

    /**
     * Constructor.
     *
     * @throws ClassNotInstantiableException
     */
    public function __construct()
    {
        throw new ClassNotInstantiableException(__CLASS__);
    }
}
