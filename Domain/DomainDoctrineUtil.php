<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Domain;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata as OrmClassMetadata;
use Sonatra\Component\Resource\Exception\InvalidArgumentException;

/**
 * Util for domain and doctrine.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
abstract class DomainDoctrineUtil
{
    /**
     * Get the doctrine object manager of the class.
     *
     * @param ManagerRegistry $or    The doctrine registry
     * @param string          $class The class name or doctrine shortcut class name
     *
     * @return ObjectManager|null
     */
    public static function getManager(ManagerRegistry $or, $class)
    {
        $manager = $or->getManagerForClass($class);

        if (null === $manager) {
            foreach ($or->getManagers() as $objectManager) {
                if ($objectManager->getMetadataFactory()->hasMetadataFor($class)) {
                    $manager = $objectManager;
                    break;
                }
            }
        }

        if (null !== $manager) {
            $manager = static::validateManager($class, $manager);
        }

        return $manager;
    }

    /**
     * Get the required object manager.
     *
     * @param ManagerRegistry $or    The doctrine registry
     * @param string          $class The class name
     *
     * @return ObjectManager
     *
     * @throws InvalidArgumentException When the class is not registered in doctrine
     */
    public static function getRequiredManager(ManagerRegistry $or, $class)
    {
        $manager = static::getManager($or, $class);

        return static::validateManager($class, $manager);
    }

    /**
     * Validate the object manager.
     *
     * @param string             $class   The class name
     * @param ObjectManager|null $manager The object manager
     *
     * @return ObjectManager
     *
     * @throws InvalidArgumentException When the class is not registered in doctrine
     */
    public static function validateManager($class, $manager)
    {
        /* @var ClassMetadata|OrmClassMetadata|null $meta */
        $meta = null !== $manager ? $manager->getClassMetadata($class) : null;
        $isOrmMeta = $meta instanceof OrmClassMetadata;

        if (null === $manager || ($isOrmMeta && $meta->isMappedSuperclass)) {
            throw new InvalidArgumentException(sprintf('The "%s" class is not registered in doctrine', $class));
        }

        return $manager;
    }
}
