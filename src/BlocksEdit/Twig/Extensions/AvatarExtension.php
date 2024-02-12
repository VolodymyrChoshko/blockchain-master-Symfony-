<?php
namespace BlocksEdit\Twig\Extensions;

use BlocksEdit\Config\Config;
use BlocksEdit\Twig\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class AvatarExtension
 */
class AvatarExtension extends AbstractExtension
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * Constructor
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return array|TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('avatar', [$this, 'avatar'], ['is_safe' => ['html']])
        ];
    }

    /**
     * @param mixed $user
     * @param int   $size
     * @param array $attribs
     *
     * @return string
     */
    public function avatar($user, int $size = 60, array $attribs = []): string
    {
        if (!is_array($user)) {
            return '';
        }
        if (!$size) {
            $size = 60;
        }

        $className = 'avatar';
        if (isset($attribs['class'])) {
            $className .= ' ' . $attribs['class'];
            unset($attribs['class']);
        }

        if (empty($user['usr_avatar'])) {
            $initials = '';
            $words = explode(' ', $user['usr_name']);
            foreach ($words as $word) {
                if (!isset($word[0])) {
                    continue;
                }
                $initials .= strtoupper($word[0]);
            }

            return sprintf('<div class="%s" title="%s">%s</div>', $className, $user['usr_name'], $initials);
        }

        $size = "${size}x${size}";
        $className .= ' avatar-' . $size;
        list($name, $ext) = explode('.',  $user['usr_avatar']);

        return sprintf(
            '<img src="%s/%s-%s.%s" title="%s" class="%s" alt="Avatar" %s />',
            $this->config->uris['avatars'],
            $name,
            $size,
            $ext,
            $user['usr_name'],
            $className,
            $this->buildAttribs($attribs)
        );
    }

    /**
     * @param array $attribs
     *
     * @return string
     */
    protected function buildAttribs(array $attribs): string
    {
        $built = [];
        foreach($attribs as $name => $value) {
            $value = htmlspecialchars($value);
            $built[] = "$name=\"$value\"";
        }

        return join(' ', $built);
    }
}
