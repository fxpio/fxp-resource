<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Doctrine Soft Deletable Filter.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class SoftDeletableFilter extends SQLFilter
{
    /**
     * @var EntityManager|null
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
        $addCondSql = "({$addCondSql} OR {$targetTableAlias}.{$column} > {$now})";

        return $addCondSql;
    }

    /**
     * @return EntityManager|mixed|null
     */
    protected function getEntityManager()
    {
        if (null === $this->entityManager) {
            $ref = new \ReflectionProperty('Doctrine\ORM\Query\Filter\SQLFilter', 'em');
            $ref->setAccessible(true);
            $this->entityManager = $ref->getValue($this);
        }

        return $this->entityManager;
    }
}
