<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Handler;

use Fxp\Component\Resource\Domain\DomainInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * A form config list for domain.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class DomainFormConfigList extends FormConfigList
{
    /**
     * @var DomainInterface
     */
    protected $domain;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var string
     */
    protected $identifier = 'id';

    /**
     * @var array
     */
    protected $defaultValueOptions = array();

    /**
     * @var bool
     */
    protected $creation = true;

    /**
     * Constructor.
     *
     * @param DomainInterface $domain    The domain resource
     * @param string          $type      The class name of form type
     * @param array           $options   The form options for create the form type
     * @param string          $method    The request method
     * @param string          $converter The data converter for request content
     */
    public function __construct(DomainInterface $domain,
                                $type,
                                array $options = array(),
                                $method = Request::METHOD_POST,
                                $converter = 'json')
    {
        parent::__construct($type, $options, $method, $converter);

        $this->domain = $domain;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Set the config of identifier.
     *
     * @param string $identifier The property name of the identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Set the default value options.
     *
     * @param array $options
     *
     * @return $this
     */
    public function setDefaultValueOptions(array $options)
    {
        $this->defaultValueOptions = $options;

        return $this;
    }

    public function setCreation($isCreation)
    {
        $this->creation = (bool) $isCreation;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function convertObjects(array &$list)
    {
        if ($this->creation) {
            $size = count($list);
            $objects = array();

            for ($i = 0; $i < $size; ++$i) {
                $objects[] = $this->domain->newInstance($this->defaultValueOptions);
            }
        } else {
            $ids = array();

            foreach ($list as &$record) {
                $ids[] = isset($record[$this->identifier]) ? $record[$this->identifier] : 0;
                unset($record[$this->identifier]);
            }

            $objects = $this->findObjects($ids);
        }

        return $objects;
    }

    /**
     * Find the objects.
     *
     * @param int[] $ids The record ids
     *
     * @return array
     */
    protected function findObjects(array $ids)
    {
        $foundObjects = $this->domain->getRepository()->findBy(array(
            $this->identifier => array_unique($ids),
        ));
        $mapFinds = array();
        $objects = array();

        foreach ($foundObjects as $foundObject) {
            $id = $this->propertyAccessor->getValue($foundObject, $this->identifier);
            $mapFinds[$id] = $foundObject;
        }

        foreach ($ids as $i => $id) {
            $objects[$i] = isset($mapFinds[$id])
                ? $mapFinds[$id]
                : $this->domain->newInstance($this->defaultValueOptions);
        }

        return $objects;
    }
}
