<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Tests\Fixtures\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Doctrine Soft Deletable Filter.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class SoftDeletableFilter extends SQLFilter
{
    /**
     * @var null|EntityManager
     */
    protected $entityManager;

    /**
     * {@inheritdoc}
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $conn = $this->getEntityManager()->getConnection();
        $platform = $conn->getDatabasePlatform();
        $column = $targetEntity->getColumnName('deletedAt');
        $addCondSql = $platform->getIsNullExpression($targetTableAlias.'.'.$column);

        $now = $conn->quote(date('Y-m-d H:i:s')); // should use UTC in database and PHP
        return "({$addCondSql} OR {$targetTableAlias}.{$column} > {$now})";
    }

    /**
     * @throws
     *
     * @return null|EntityManager|mixed
     */
    protected function getEntityManager()
    {
        if (null === $this->entityManager) {
            $ref = new \ReflectionProperty(SQLFilter::class, 'em');
            $ref->setAccessible(true);
            $this->entityManager = $ref->getValue($this);
        }

        return $this->entityManager;
    }
}
