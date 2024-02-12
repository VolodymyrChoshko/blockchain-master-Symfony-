<?php
namespace BlocksEdit\Twig\Extensions;

use BlocksEdit\Http\SessionInterface;
use BlocksEdit\Twig\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class GrantsExtension
 */
class GrantsExtension extends AbstractExtension
{
    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * Constructor
     *
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('isGranted', [$this, 'isGranted'])
        ];
    }

    /**
     * @param string $grant
     *
     * @return bool
     */
    public function isGranted(string $grant): bool
    {
        $grants = $this->session->get('security.grants', []);

        return in_array($grant, $grants);
    }
}
