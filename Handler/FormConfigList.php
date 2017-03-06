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

use Sonatra\Component\Resource\Exception\InvalidResourceException;

/**
 * A form config list.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
abstract class FormConfigList extends FormConfig implements FormConfigListInterface
{
    /**
     * @var int|null
     */
    protected $limit;

    /**
     * @var bool
     */
    protected $transactional = true;

    /**
     * {@inheritdoc}
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * {@inheritdoc}
     */
    public function setTransactional($transactional)
    {
        $this->transactional = (bool) $transactional;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isTransactional()
    {
        return $this->transactional;
    }

    /**
     * {@inheritdoc}
     */
    public function findList(array $data)
    {
        if (!isset($data['records'])) {
            throw new InvalidResourceException('The records field is required');
        }

        if (array_key_exists('transaction', $data)) {
            $this->setTransactional($data['transaction']);
        }

        return $data['records'];
    }
}
