<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\DependencyInjection\Compiler;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sonatra\Bundle\ResourceBundle\Domain\DomainUtil;
use Sonatra\Bundle\ResourceBundle\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sonatra_resource.domain_manager')
                || !$container->hasDefinition('doctrine')) {
            return;
        }

        $classes = $this->getObjectClasses($container);
        $managers = $this->findCustomDomains($container, $classes);
        $managers = $this->generateDefaultDomains($container, $managers, $classes);

        $container->getDefinition('sonatra_resource.domain_manager')->replaceArgument(0, $managers);
    }

    /**
     * Get classes managed by doctrine.
     *
     * @param ContainerBuilder $container The container service
     *
     * @return array
     */
    private function getObjectClasses(ContainerBuilder $container)
    {
        $classes = array();
        $registry = $container->get('doctrine');
        $dManagers = $registry->getManagers();

        /* @var ObjectManager $manager */
        foreach ($dManagers as $name => $manager) {
            $metadatas = $manager->getMetadataFactory()->getAllMetadata();

            /* @var ClassMetadataInfo $meta */
            foreach ($metadatas as $meta) {
                if (!$meta->isMappedSuperclass) {
                    $classes[] = $meta->getName();
                }
            }
        }

        return $classes;
    }

    /**
     * Find the custom domains.
     *
     * @param ContainerBuilder $container The container service
     * @param array            $classes   The classes managed by doctrine
     *
     * @return array The manager definitions
     */
    private function findCustomDomains(ContainerBuilder $container, array &$classes)
    {
        $managers = array();
        $registry = $container->get('doctrine');

        foreach ($container->findTaggedServiceIds('sonatra_resource.domain') as $serviceId => $tag) {
            $sClass = $container->get($serviceId)->getClass();
            $pos = array_search($sClass, $classes, true);

            if (null === $registry->getManagerForClass($sClass)) {
                throw new InvalidConfigurationException(sprintf('The "%s" class is not managed by doctrine object manager', $sClass));
            }

            if (false !== $pos) {
                unset($classes[$pos]);
            }

            $managers[] = new Reference($serviceId);
        }

        return $managers;
    }

    /**
     * Generate and add the default domains in the existing manager list.
     *
     * @param ContainerBuilder $container The container service
     * @param array            $managers  The list of definition domain manager
     * @param array            $classes   The list class managed by doctrine but without custom domain
     *
     * @return array
     */
    private function generateDefaultDomains(ContainerBuilder $container, array $managers, array $classes)
    {
        foreach ($classes as $class) {
            $id = 'sonatra_resource.domain.'.Container::underscore(DomainUtil::generateShortName($class));
            $def = new Definition('Sonatra\Bundle\ResourceBundle\Domain\Domain', array($class));
            $container->setDefinition($id, $def);
            $managers[] = new Reference($id);
        }

        return $managers;
    }
}
