<?php
namespace BlocksEdit\Html;

use BlocksEdit\Twig\TwigRender;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

/**
 * Class FormBuilder
 */
class FormBuilderFactory
{
    /**
     * @param TwigRender $twigRender
     *
     * @return FormFactoryInterface
     */
    public function create(TwigRender $twigRender)
    {
        $tokenManager = new CsrfTokenManager();
        $twig         = $twigRender->getEnvironment();
        $formEngine = new TwigRendererEngine(
            ['layout/form/form_div_layout.html.twig'],
            $twig
        );
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([
            FormRenderer::class => function () use ($formEngine, $tokenManager) {
                return new FormRenderer($formEngine, $tokenManager);
            },
        ]));
        $twig->addExtension(new FormExtension());

        $validator = Validation::createValidator();

        return Forms::createFormFactoryBuilder()
            ->addExtension(new CsrfExtension($tokenManager))
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory();
    }
}
