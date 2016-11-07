<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Model;

/**
 * A soft deletable interface.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
interface SoftDeletableInterface
{
    /**
     * Set deleted at.
     *
     * @param \Datetime|null $deletedAt
     *
     * @return $this
     */
    public function setDeletedAt(\DateTime $deletedAt = null);

    /**
     * Get deleted at.
     *
     * @return \DateTime|null
     */
    public function getDeletedAt();

    /**
     * Check if the resource is deleted.
     *
     * @return bool
     */
    public function isDeleted();
}
