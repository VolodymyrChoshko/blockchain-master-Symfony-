<?php
namespace BlocksEdit\Twig\Extensions;

use BlocksEdit\Form\FormBuilder;
use BlocksEdit\Twig\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class FormExtension
 */
class FormExtension extends AbstractExtension
{
    /**
     * @var FormBuilder
     */
    protected $formBuilder;

    /**
     * Constructor
     *
     * @param FormBuilder $formBuilder
     */
    public function __construct(FormBuilder $formBuilder)
    {
        $this->formBuilder = $formBuilder;
    }

    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('formWidget', [$this->formBuilder, 'widget'], ['is_safe' => ['html']])
        ];
    }
}
