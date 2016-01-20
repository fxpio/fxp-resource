<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Handler;

use Sonatra\Bundle\ResourceBundle\Converter\ConverterRegistryInterface;
use Sonatra\Bundle\ResourceBundle\Exception\InvalidArgumentException;
use Sonatra\Bundle\ResourceBundle\Exception\InvalidResourceException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A form handler.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class FormHandler implements FormHandlerInterface
{
    /**
     * @var ConverterRegistryInterface
     */
    protected $converterRegistry;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var int|null
     */
    protected $defaultLimit;

    /**
     * Constructor.
     *
     * @param ConverterRegistryInterface $converterRegistry The converter registry
     * @param FormFactoryInterface       $formFactory       The form factory
     * @param RequestStack               $requestStack      The request stack
     * @param int|null                   $defaultLimit      The limit of max data rows
     *
     * @throws InvalidArgumentException When the current request is request stack is empty
     */
    public function __construct(ConverterRegistryInterface $converterRegistry,
                                FormFactoryInterface $formFactory, RequestStack $requestStack, $defaultLimit = null)
    {
        $this->converterRegistry = $converterRegistry;
        $this->formFactory = $formFactory;
        $this->request = $requestStack->getCurrentRequest();
        $this->defaultLimit = $this->validateLimit($defaultLimit);

        if (null === $this->request) {
            throw new InvalidArgumentException('The current request is required in request stack');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processForm(FormConfigInterface $config, $object)
    {
        $forms = $this->process($config, array($object));

        return $forms[0];
    }

    /**
     * {@inheritdoc}
     */
    public function processForms(FormConfigListInterface $config, array $objects = array())
    {
        return $this->process($config, $objects);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultLimit()
    {
        return $this->defaultLimit;
    }

    /**
     * Create the list of form for the object instances.
     *
     * @param FormConfigInterface $config  The form config
     * @param object[]|array[]    $objects The list of object instance
     *
     * @return FormInterface[]
     *
     * @throws InvalidResourceException When the size if request data and the object instances is different
     */
    private function process(FormConfigInterface $config, array $objects)
    {
        $limit = $this->getLimit($config instanceof FormConfigListInterface ? $config->getLimit() : null);
        $forms = array();
        $dataList = $this->getDataList($config);

        if (null !== $limit && count($dataList) > $limit) {
            $msg = 'The list of resource sent exceeds the permitted limit (%s)';
            throw new InvalidResourceException(sprintf($msg, $limit));
        }

        if (0 === count($objects) && $config instanceof FormConfigListInterface) {
            $objects = $config->convertObjects($dataList);
        }

        $objects = array_values($objects);
        $dataList = array_values($dataList);

        if (count($objects) !== count($dataList)) {
            $msg = 'The size of the request data list (%s) is different that the object instance list (%s)';
            throw new InvalidResourceException(sprintf($msg, count($dataList), count($objects)));
        }

        foreach ($objects as $i => $object) {
            $form = $this->formFactory->create($config->getType(), $object, $config->getOptions());
            $form->submit($dataList[$i], $config->getSubmitClearMissing());
            $forms[] = $form;
        }

        return $forms;
    }

    /**
     * Get the form data list.
     *
     * @param FormConfigInterface $config The form config
     *
     * @return array
     */
    protected function getDataList(FormConfigInterface $config)
    {
        $converter = $this->converterRegistry->get($config->getConverter());
        $dataList = $converter->convert((string) $this->request->getContent());

        if ($config instanceof FormConfigListInterface) {
            $dataList = $config->findList($dataList);
        } else {
            $dataList = array($dataList);
        }

        return $dataList;
    }

    /**
     * Get the limit.
     *
     * @param int|null $limit The limit
     *
     * @return int|null Returns null for unlimited row or a integer greater than 1
     */
    protected function getLimit($limit = null)
    {
        if (null === $limit) {
            $limit = $this->getDefaultLimit();
        }

        return $this->validateLimit($limit);
    }

    /**
     * Validate the limit with a integer greater than 1.
     *
     * @param int|null $limit The limit
     *
     * @return int|null
     */
    protected function validateLimit($limit)
    {
        return null === $limit
            ? null
            : max(1, $limit);
    }
}
