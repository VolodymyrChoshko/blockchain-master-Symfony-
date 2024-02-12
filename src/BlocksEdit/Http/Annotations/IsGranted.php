<?php
namespace BlocksEdit\Http\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target({"METHOD", "CLASS"})
 */
class IsGranted
{
    const GRANT_ANY            = 'ANY';
    const GRANT_USER           = 'USER';
    const GRANT_ORG_OWNER      = 'ORG_OWNER';
    const GRANT_ORG_ADMIN      = 'ORG_ADMIN';
    const GRANT_ORG_EDITOR     = 'ORG_EDITOR';
    const GRANT_SITE_ADMIN     = 'SITE_ADMIN';
    const GRANT_SITE_ADMIN_2FA = 'SITE_ADMIN_2FA';
    const KIND_TEMPLATE        = 'template';
    const KIND_EMAIL           = 'email';
    const KIND_PREVIEW         = 'preview';

    /**
     * @Required
     * @var array
     */
    public $value;

    /**
     * @var string
     */
    public $param = 'id';

    /**
     * @var string
     */
    public $token = 'token';

    /**
     * @return array
     */
    public function getValue(): array
    {
        $values = [];
        $grants = [
            self::GRANT_SITE_ADMIN_2FA,
            self::GRANT_SITE_ADMIN,
            self::GRANT_ANY,
            self::GRANT_USER,
            self::GRANT_ORG_OWNER,
            self::GRANT_ORG_ADMIN,
            self::GRANT_ORG_EDITOR
        ];
        foreach($this->value as $value) {
            if (in_array($value, $grants)) {
                $values[] = $value;
            } else {
                $values[] = [$this->value, $this->param, $this->token];
            }
        }

        return $values;
    }
}
