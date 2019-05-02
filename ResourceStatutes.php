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
 * The action statutes for the resource domains.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
final class ResourceStatutes
{
    /**
     * The ResourceStatutes::PENDING status is used when an error is thrown on a
     * previous resource.
     *
     * This status is used in Fxp\Component\Resource\Event\ResourceEvent
     * and Fxp\Component\Resource\Domain\DomainInterface instances.
     */
    public const PENDING = 'pending';

    /**
     * The ResourceStatutes::ERROR status is used when the action of the resource
     * domain wasn't completed successfully.
     *
     * This status is used in Fxp\Component\Resource\Event\ResourceEvent
     * and Fxp\Component\Resource\Domain\DomainInterface instances.
     */
    public const ERROR = 'error';

    /**
     * The ResourceStatutes::CANCELED status is used when the action of the resource
     * domain is canceled because of an error in the list (only for complete list of
     * transactions).
     *
     * This status is used in Fxp\Component\Resource\Event\ResourceEvent
     * and Fxp\Component\Resource\Domain\DomainInterface instances.
     */
    public const CANCELED = 'canceled';

    /**
     * The ResourceStatutes::CREATED status is used when the action of domain has
     * created the resource successfully.
     *
     * This status is used in Fxp\Component\Resource\Event\ResourceEvent
     * and Fxp\Component\Resource\Domain\DomainInterface instances.
     */
    public const CREATED = 'created';

    /**
     * The ResourceStatutes::UPDATED status is used when the action of domain has
     * updated the resource successfully.
     *
     * This status is used in Fxp\Component\Resource\Event\ResourceEvent
     * and Fxp\Component\Resource\Domain\DomainInterface instances.
     */
    public const UPDATED = 'updated';

    /**
     * The ResourceStatutes::DELETED status is used when the action of domain has
     * deleted the resource successfully.
     *
     * This status is used in Fxp\Component\Resource\Event\ResourceEvent
     * and Fxp\Component\Resource\Domain\DomainInterface instances.
     */
    public const DELETED = 'deleted';

    /**
     * The ResourceStatutes::UNDELETED status is used when the action of domain has
     * undeleted the resource successfully.
     *
     * This status is used in Fxp\Component\Resource\Event\ResourceEvent
     * and Fxp\Component\Resource\Domain\DomainInterface instances.
     */
    public const UNDELETED = 'undeleted';

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
