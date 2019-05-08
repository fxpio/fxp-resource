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

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Fxp\Component\Resource\Model\SoftDeletableInterface;

/**
 * Doctrine ORM soft deletable subscriber.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class SoftDeletableSubscriber implements EventSubscriber
{
    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * Enable the soft deletable.
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable the soft deletable.
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
        ];
    }

    /**
     * If it's a SoftDeletable object, update the "deletedAt" field
     * and skip the removal of the object.
     *
     * @param OnFlushEventArgs $args
     *
     * @throws
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        if (!$this->enabled) {
            return;
        }

        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityDeletions() as $object) {
            if ($object instanceof SoftDeletableInterface) {
                $oldValue = $object->getDeletedAt();

                if ($oldValue instanceof \Datetime) {
                    continue; // want to hard delete
                }

                $date = new \DateTime();
                $object->setDeletedAt($date);

                $em->persist($object);
                $uow->propertyChanged($object, 'deletedAt', $oldValue, $date);
                $uow->scheduleExtraUpdate($object, [
                    'deletedAt' => [$date, $date],
                ]);
            }
        }
    }
}
