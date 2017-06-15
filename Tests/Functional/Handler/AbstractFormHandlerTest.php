<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Tests\Functional\Handler;

use PHPUnit\Framework\TestCase;
use Sonatra\Component\Resource\Converter\ConverterRegistry;
use Sonatra\Component\Resource\Converter\JsonConverter;
use Sonatra\Component\Resource\Handler\FormHandler;
use Sonatra\Component\Resource\Handler\FormHandlerInterface;
use Sonatra\Component\Resource\ResourceInterface;
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
 * @author François Pluchino <francois.pluchino@sonatra.com>
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
        $translator->addResource('xml', realpath(dirname($ref->getFileName()).'/Resources/translations/SonatraResource.en.xlf'), 'en', 'SonatraResource');
        $translator->addLoader('xml', new XliffFileLoader());

        $converterRegistry = new ConverterRegistry(array(
            new JsonConverter($translator),
        ));

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
