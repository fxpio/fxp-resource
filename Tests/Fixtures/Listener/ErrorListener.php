<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Tests\Fixtures\Listener;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Fxp\Component\Resource\Exception\ConstraintViolationException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Doctrine ORM error listener.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ErrorListener
{
    /**
     * @var string
     */
    protected $action;

    /**
     * @var bool
     */
    protected $useConstraint;

    public function __construct($action, $useConstraint = false)
    {
        $this->action = (string) $action;
        $this->useConstraint = $useConstraint;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->doException($args->getObject());
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $this->doException($args->getObject());
    }

    public function preFlush(PreFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->doException($entity);
        }
    }

    /**
     * @param object $entity The entity
     *
     * @throws \Exception When the entity does not deleted
     */
    public function doException($entity)
    {
        if ($this->useConstraint) {
            $message = 'The entity does not '.$this->action.' (violation exception)';
            $violation = new ConstraintViolation($message, $message, array(), $entity, null, null);
            $list = new ConstraintViolationList(array($violation));

            throw new ConstraintViolationException($list);
        }

        throw new \Exception('The entity does not '.$this->action.' (exception)');
    }
}
