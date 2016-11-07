<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource;

use Sonatra\Component\Resource\Exception\ClassNotInstantiableException;

/**
 * The action statutes for the list of resource domains.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
final class ResourceListStatutes
{
    /**
     * The ResourceStatutes::PENDING status is used when none of the resources is
     * executed.
     *
     * This status is used in Sonatra\Component\Resource\Event\ResourceEvent
     * and Sonatra\Component\Resource\Domain\DomainInterface instances.
     */
    const PENDING = 'pending';

    /**
     * The ResourceStatutes::SUCCESSFULLY status is used when the all resources in the
     * list has been executed successfully.
     *
     * This status is used in Sonatra\Component\Resource\Event\ResourceEvent
     * and Sonatra\Component\Resource\Domain\DomainInterface instances.
     */
    const SUCCESSFULLY = 'successfully';

    /**
     * The ResourceStatutes::MIXED status is used when several different statuses are
     * in the list.
     *
     * This status is used in Sonatra\Component\Resource\Event\ResourceEvent
     * and Sonatra\Component\Resource\Domain\DomainInterface instances.
     */
    const MIXED = 'mixed';

    /**
     * The ResourceStatutes::CANCEL status is used when the all resources in the
     * list has canceled.
     *
     * This status is used in Sonatra\Component\Resource\Event\ResourceEvent
     * and Sonatra\Component\Resource\Domain\DomainInterface instances.
     */
    const CANCEL = 'cancel';

    /**
     * The ResourceStatutes::ERROR status is used when the all resources in the
     * list has errors.
     *
     * This status is used in Sonatra\Component\Resource\Event\ResourceEvent
     * and Sonatra\Component\Resource\Domain\DomainInterface instances.
     */
    const ERROR = 'error';

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
