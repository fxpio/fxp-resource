<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Handler;

/**
 * A form config interface.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
interface FormConfigListInterface extends FormConfigInterface
{
    /**
     * Set the limit of the size list.
     *
     * @param int|null $limit The limit
     *
     * @return self
     */
    public function setLimit($limit);

    /**
     * Get the limit of the size list.
     *
     * @return int|null
     */
    public function getLimit();

    /**
     * Set the transactional mode.
     *
     * @param bool $transactional Check if the domain use the transactional mode
     *
     * @return self
     */
    public function setTransactional($transactional);

    /**
     * Check if the domain use the transactional mode.
     *
     * @return bool
     */
    public function isTransactional();

    /**
     * Find the record list in form data.
     *
     * @param array $data The form data
     *
     * @return array
     */
    public function findList(array $data);

    /**
     * Convert the list of objects, and clean the list.
     *
     * @param array[] $list The list of record data
     *
     * @return object[]
     */
    public function convertObjects(array &$list);
}
