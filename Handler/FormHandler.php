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

use Fxp\Component\Resource\Converter\ConverterRegistryInterface;
use Fxp\Component\Resource\Exception\InvalidArgumentException;
use Fxp\Component\Resource\Exception\InvalidResourceException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A form handler.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
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
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var null|int
     */
    protected $defaultLimit;

    /**
     * Constructor.
     *
     * @param ConverterRegistryInterface $converterRegistry The converter registry
     * @param FormFactoryInterface       $formFactory       The form factory
     * @param RequestStack               $requestStack      The request stack
     * @param TranslatorInterface        $translator        The translator
     * @param null|int                   $defaultLimit      The limit of max data rows
     *
     * @throws InvalidArgumentException When the current request is request stack is empty
     */
    public function __construct(
        ConverterRegistryInterface $converterRegistry,
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        $defaultLimit = null
    ) {
        $this->converterRegistry = $converterRegistry;
        $this->formFactory = $formFactory;
        $this->request = $requestStack->getCurrentRequest();
        $this->translator = $translator;
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
        $forms = $this->process($config, [$object]);

        return $forms[0];
    }

    /**
     * {@inheritdoc}
     */
    public function processForms(FormConfigListInterface $config, array $objects = [])
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
     * Get the data list and objects.
     *
     * @param FormConfigInterface $config  The form config
     * @param array[]|object[]    $objects The list of object instance
     *
     * @return array
     */
    protected function getDataListObjects(FormConfigInterface $config, array $objects)
    {
        $limit = $this->getLimit($config instanceof FormConfigListInterface ? $config->getLimit() : null);
        $dataList = $this->getDataList($config);

        if (null !== $limit && \count($dataList) > $limit) {
            $msg = $this->translator->trans('form_handler.size_exceeds', [
                '{{ limit }}' => $limit,
            ], 'FxpResource');

            throw new InvalidResourceException(sprintf($msg, $limit));
        }

        if (0 === \count($objects) && $config instanceof FormConfigListInterface) {
            $objects = $config->convertObjects($dataList);
        }

        $dataList = array_values($dataList);
        $objects = array_values($objects);

        return [$dataList, $objects];
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
            try {
                $dataList = $config->findList($dataList);
            } catch (InvalidResourceException $e) {
                throw new InvalidResourceException($this->translator->trans('form_handler.results_field_required', [], 'FxpResource'));
            }
        } else {
            $dataList = [$dataList];
        }

        return $dataList;
    }

    /**
     * Get the limit.
     *
     * @param null|int $limit The limit
     *
     * @return null|int Returns null for unlimited row or a integer greater than 1
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
     * @param null|int $limit The limit
     *
     * @return null|int
     */
    protected function validateLimit($limit)
    {
        return null === $limit
            ? null
            : max(1, $limit);
    }

    /**
     * Create the list of form for the object instances.
     *
     * @param FormConfigInterface $config  The form config
     * @param array[]|object[]    $objects The list of object instance
     *
     * @throws InvalidResourceException When the size if request data and the object instances is different
     *
     * @return FormInterface[]
     */
    private function process(FormConfigInterface $config, array $objects)
    {
        list($dataList, $objects) = $this->getDataListObjects($config, $objects);
        $forms = [];

        if (\count($objects) !== \count($dataList)) {
            $msg = $this->translator->trans('form_handler.different_size_request_list', [
                '{{ requestSize }}' => \count($dataList),
                '{{ objectSize }}' => \count($objects),
            ], 'FxpResource');

            throw new InvalidResourceException($msg);
        }

        foreach ($objects as $i => $object) {
            $form = $this->formFactory->create($config->getType(), $object, $config->getOptions());

            $form->submit($dataList[$i], $config->getSubmitClearMissing());
            $forms[] = $form;
        }

        return $forms;
    }
}
