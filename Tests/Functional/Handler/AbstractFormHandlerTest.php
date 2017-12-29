<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Tests\Functional\Handler;

use Fxp\Component\Resource\Converter\ConverterRegistry;
use Fxp\Component\Resource\Converter\JsonConverter;
use Fxp\Component\Resource\Handler\FormHandler;
use Fxp\Component\Resource\Handler\FormHandlerInterface;
use Fxp\Component\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validation;

/**
 * Abstract class for Functional tests for Form Handler.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractFormHandlerTest extends TestCase
{
    /**
     * Create form handler.
     *
     * @param Request|null $request The request for request stack
     * @param int|null     $limit   The limit
     *
     * @return FormHandlerInterface
     */
    protected function createFormHandler(Request $request = null, $limit = null)
    {
        $translator = new Translator('en');
        $ref = new \ReflectionClass(ResourceInterface::class);
        $translator->addResource('xml', realpath(dirname($ref->getFileName()).'/Resources/translations/FxpResource.en.xlf'), 'en', 'FxpResource');
        $translator->addLoader('xml', new XliffFileLoader());

        $converterRegistry = new ConverterRegistry([
            new JsonConverter($translator),
        ]);

        $validator = Validation::createValidatorBuilder()
            ->addXmlMapping(__DIR__.'/../../Fixtures/config/validation.xml')
            ->getValidator();

        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory();

        $requestStack = new RequestStack();

        if (null !== $request) {
            $requestStack->push($request);
        }

        return new FormHandler($converterRegistry, $formFactory, $requestStack, $translator, $limit);
    }
}
