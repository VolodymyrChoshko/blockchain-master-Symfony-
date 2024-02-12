<?php
namespace BlocksEdit\Integrations\Services\Klaviyo;

use Klaviyo\Exception\KlaviyoException;
use Klaviyo\Exception\KlaviyoResourceNotFoundException;

/**
 * Class Templates
 */
class Templates extends Api
{
    const TEMPLATE_NAME = 'name';
    const TEMPLATE_HTML = 'html';

    /**
     * @return array
     * @throws KlaviyoException
     */
    public function getTemplates(): array
    {
        $resp = $this->v1Request('email-templates');
        if (isset($resp['data'])) {
            return (array)$resp['data'];
        }

        return [];
    }

    /**
     * @param string $templateName
     * @param string $html
     *
     * @return array
     * @throws KlaviyoException
     */
    public function createTemplate(string $templateName, string $html): array
    {
        $params = $this->createRequestBody([
            self::TEMPLATE_NAME => $templateName,
            self::TEMPLATE_HTML => $html
        ]);

        return $this->v1Request('email-templates', $params, self::HTTP_POST);
    }

    /**
     * @param string $id
     * @param string $templateName
     * @param string $html
     *
     * @return array
     * @throws KlaviyoException
     * @throws KlaviyoResourceNotFoundException
     */
    public function updateTemplate(string $id, string $templateName, string $html): array
    {
        $params = $this->createRequestBody([
            self::TEMPLATE_NAME => $templateName,
            self::TEMPLATE_HTML => $html
        ]);

        return $this->v1Request('email-template/' . $id, $params, self::HTTP_PUT);
    }
}
