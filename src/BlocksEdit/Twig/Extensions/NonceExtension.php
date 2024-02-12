<?php
namespace BlocksEdit\Twig\Extensions;

use BlocksEdit\Html\NonceGeneratorInterface;
use BlocksEdit\Twig\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class NonceExtension
 */
class NonceExtension extends AbstractExtension
{
    /**
     * @var NonceGeneratorInterface
     */
    protected $nonceGenerator;

    /**
     * Constructor
     *
     * @param NonceGeneratorInterface $nonceGenerator
     */
    public function __construct(NonceGeneratorInterface $nonceGenerator)
    {
        $this->nonceGenerator = $nonceGenerator;
    }

    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('nonce', [$this, 'nonce'], ['is_safe' => ['html']]),
            new TwigFunction('generateNonce', [$this, 'generateNonce'])
        ];
    }

    /**
     * @param string $form
     * @param int    $expiration
     *
     * @return string
     */
    public function nonce(string $form, int $expiration = 3600): string
    {
        $nonce = $this->generateNonce($form, $expiration);

        return sprintf('<input type="hidden" name="token" value="%s" />', htmlspecialchars($nonce));
    }

    /**
     * @param string $form
     * @param int    $expiration
     *
     * @return string
     */
    public function generateNonce(string $form, int $expiration = 3600): string
    {
        return $this->nonceGenerator->generate($form, $expiration);
    }
}
