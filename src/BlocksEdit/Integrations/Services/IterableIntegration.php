<?php
namespace BlocksEdit\Integrations\Services;

use BlocksEdit\Form\FormBuilder;
use BlocksEdit\Html\FormErrors;
use BlocksEdit\Http\Request;
use BlocksEdit\Integrations\AbstractFilesystemIntegration;
use BlocksEdit\Integrations\Exception\IntegrationException;
use BlocksEdit\Integrations\Hook;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Repository\SourcesRepository;

/**
 * Class IterableIntegration
 */
class IterableIntegration extends AbstractFilesystemIntegration
{
    /**
     * {@inheritDoc}
     */
    public function getSlug(): string
    {
        return 'iterable';
    }

    /**
     * {@inheritDoc}
     */
    public function getDisplayName(): string
    {
        return 'Iterable';
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getPrice(): int
    {
        return 9900;
    }

    /**
     * {@inheritDoc}
     */
    public function getIconURL(): string
    {
        return '/assets/images/integration-iterable.png';
    }

    /**
     * {@inheritDoc}
     */
    public function getInstructionsURL(): string
    {
        return 'https://blocksedit.com/help/integrations/iterable-setup/';
    }

    /**
     * {@inheritDoc}
     */
    public function getHomeDirectoryPlaceholder(): string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultHomeDirectory(): string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getSettingsForm(Request $request, array $values = [], ?FormErrors $errors = null): array
    {
        $form = [
            'apiKey' => [
                'type'     => 'password',
                'label'    => 'API Key',
                'required' => true
            ]
        ];

        return $this->applyFormValues($form, $values);
    }

    /**
     * {@inheritDoc}
     */
    public function getFrontendSettings(array $rules = [], array $hooks = []): array
    {
        return parent::getFrontendSettings(
            [
                self::RULE_CAN_LIST_FOLDERS     => false,
                self::RULE_CAN_RENAME_FOLDERS   => false,
                self::RULE_CAN_CREATE_FOLDERS   => false,
                self::RULE_CAN_DELETE_FOLDERS   => false,
                self::RULE_CAN_EXPORT_IMAGES    => false,
                self::RULE_CAN_LIST_FILES       => false,
                self::RULE_CAN_DELETE_FILE      => false,
                self::RULE_CAN_RENAME_FILE      => false,
                self::RULE_EXPORT_SETTINGS_SHOW => true,
            ],
            [
                self::HOOK_EMAIL_SETTINGS          => [$this, 'hookEmailSettings'],
                self::HOOK_TEMPLATE_SETTINGS_PRE   => [$this, 'hookTemplateSettingsPre'],
                self::HOOK_TEMPLATE_SETTINGS_POST  => [$this, 'hookTemplateSettingsPost'],
                self::HOOK_EMAIL_SETTINGS_POST     => [$this, 'hookEmailSettingsPost']
            ]
        );
    }

    /**
     * @param Hook $hook
     *
     * @throws GuzzleException
     */
    public function hookEmailSettings(Hook $hook)
    {
        $email    = $hook->getEmail();
        $template = $hook->getTemplate();

        $getValue = function($key) use ($email, $template) {
            $emailValue   = 0;
            $fromEmailKey = sprintf('%d.%d.%s', $this->source->getId(), $email['ema_id'], $key);
            if (isset($this->settings[$fromEmailKey])) {
                $emailValue = $this->settings[$fromEmailKey];
            } else {
                $key = sprintf('%d.%d.%s', $this->source->getId(), $template['tmp_id'], $key);
                if (isset($this->settings[$key])) {
                    $emailValue = $this->settings[$key];
                }
            }

            return [$emailValue, $fromEmailKey];
        };

        [$emailValue, $fromEmailKey] = $getValue('fromEmail');
        [$nameValue, $fromNameKey]   = $getValue('fromName');
        [$replyEmail, $replyEmailKey] = $getValue('replyEmail');
        [$messageType, $messageTypeKey] = $getValue('messageType');

        $messageTypeChoices = [];
        foreach($this->getMessageTypes() as $item) {
            $messageTypeChoices[$item['id']] = $item['name'];
        }

        $form = FormBuilder::create();
        $html = $form->widget($fromNameKey, [
            'type'     => 'text',
            'label'    => 'From Name',
            'required' => true,
            'value'    => $nameValue
        ]);
        $html .= $form->widget($fromEmailKey, [
            'type'     => 'text',
            'label'    => 'From Email (approved Iterable senders only)',
            'required' => true,
            'value'    => $emailValue
        ]);
        $html .= $form->widget($replyEmailKey, [
            'type'     => 'text',
            'label'    => 'Reply Email',
            'required' => false,
            'value'    => $replyEmail
        ]);
        $html .= $form->widget($messageTypeKey, [
            'type'     => 'choice',
            'label'    => 'Message Type',
            'required' => false,
            'choices'  => $messageTypeChoices,
            'value'    => $messageType
        ]);

        $html .= '<p>Unless it is transactional, email must contain at least one of these tokens as a link: {{unsubscribeUrl}}, {{hostedUnsubscribeUrl}}, {{unsubscribeMessageTypeUrl}}</p>';

        $hook->addResponse($html);
    }

    /**
     * @param Hook $hook
     *
     * @throws GuzzleException
     */
    public function hookTemplateSettingsPre(Hook $hook)
    {
        try {
            $template = $hook->getTemplate();
            if (!$template) {
                return;
            }

            $fromEmailKey = sprintf('%d.%d.fromEmail', $this->source->getId(), $template['tmp_id']);
            $form    = FormBuilder::create();

            $fromNameKey = sprintf('%d.%d.fromName', $this->source->getId(), $template['tmp_id']);
            $html   = $form->widget($fromNameKey, [
                'type'        => 'text',
                'required'    => false,
                'value'       => $this->settings[$fromNameKey] ?? ''
            ], [
                'placeholder' => 'From Name',
                'style' => 'width: 84%; margin-bottom: 2px;'
            ]);

            $html    .= $form->widget($fromEmailKey, [
                'type'     => 'text',
                'required' => false,
                'value'    => $this->settings[$fromEmailKey] ?? 0
            ], [
                'placeholder' => 'From Email (approved Iterable senders only)',
                'style' => 'width: 84%; margin-bottom: 2px;'
            ]);

            $replyEmailKey = sprintf('%d.%d.replyEmail', $this->source->getId(), $template['tmp_id']);
            $html   .= $form->widget($replyEmailKey, [
                'type'        => 'text',
                'required'    => false,
                'value'       => $this->settings[$replyEmailKey] ?? ''
            ], [
                'placeholder' => 'Reply Email',
                'style' => 'width: 84%; margin-bottom: 2px;'
            ]);

            $messageTypeChoices = [
                0 => 'Message Type'
            ];
            foreach($this->getMessageTypes() as $item) {
                $messageTypeChoices[$item['id']] = $item['name'];
            }

            $messageTypeKey = sprintf('%d.%d.messageType', $this->source->getId(), $template['tmp_id']);
            $html .= $form->widget($messageTypeKey, [
                'type'     => 'choice',
                'required' => false,
                'choices'  => $messageTypeChoices,
                'value'    => $this->settings[$messageTypeKey] ?? ''
            ], [
                'style' => 'width: 84%;'
            ]);

            $hook->addResponse($html);
        } catch (Exception $e) {

        }
    }

    /**
     * @param Hook $hook
     *
     * @throws Exception
     */
    public function hookEmailSettingsPost(Hook $hook)
    {
        $email = $hook->getEmail();
        if (!$email) {
            return;
        }

        $request = $hook->getRequest();
        $integrationSettings = $request->json->get('integrationSettings');
        if ($integrationSettings) {
            $fromEmailKey = sprintf('%d.%d.fromEmail', $this->source->getId(), $email['ema_id']);
            if (isset($integrationSettings[$fromEmailKey])) {
                $this->settings[$fromEmailKey] = $integrationSettings[$fromEmailKey];
            }

            $fromNameKey = sprintf('%d.%d.fromName', $this->source->getId(), $email['ema_id']);
            if (isset($integrationSettings[$fromNameKey])) {
                $this->settings[$fromNameKey] = $integrationSettings[$fromNameKey];
            }

            $replyEmailKey = sprintf('%d.%d.replyEmail', $this->source->getId(), $email['ema_id']);
            if (isset($integrationSettings[$replyEmailKey])) {
                $this->settings[$replyEmailKey] = $integrationSettings[$replyEmailKey];
            }

            $messageTypeKey = sprintf('%d.%d.messageType', $this->source->getId(), $email['ema_id']);
            if (isset($integrationSettings[$messageTypeKey])) {
                $this->settings[$messageTypeKey] = $integrationSettings[$messageTypeKey];
            }

            $sourcesRepo = $this->container->get(SourcesRepository::class);
            $sourcesRepo->updateSettings($this->source, $this->settings);
        }
    }

    /**
     * @param Hook $hook
     */
    public function hookTemplateSettingsPost(Hook $hook)
    {
        $template = $hook->getTemplate();
        if (!$template) {
            return;
        }

        $fromEmailKey = sprintf('%d.%d.fromEmail', $this->source->getId(), $template['tmp_id']);
        $request = $hook->getRequest();
        $fields  = $request->json->get('extraFields');
        if (isset($fields[$fromEmailKey])) {
            $this->settings[$fromEmailKey] = $fields[$fromEmailKey];
        }

        $fromNameKey = sprintf('%d.%d.fromName', $this->source->getId(), $template['tmp_id']);
        if (isset($fields[$fromNameKey])) {
            $this->settings[$fromNameKey] = $fields[$fromNameKey];
        }

        $replyKey = sprintf('%d.%d.replyEmail', $this->source->getId(), $template['tmp_id']);
        if (isset($fields[$replyKey])) {
            $this->settings[$replyKey] = $fields[$replyKey];
        }

        $messageTypeKey = sprintf('%d.%d.messageType', $this->source->getId(), $template['tmp_id']);
        if (isset($fields[$messageTypeKey])) {
            $this->settings[$messageTypeKey] = $fields[$messageTypeKey];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function connect(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function disconnect(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isConnected(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     * @throws Exception|GuzzleException
     */
    public function uploadFile(
        string $remoteFilename,
        string $localFilename,
        string $assetType,
        int $assetID,
        $subject = '',
        array $extra = []
    ): string {
        $fromEmail = sprintf('%d.%d.fromEmail', $this->source->getId(), $assetID);
        if (empty($extra[$fromEmail])) {
            if (isset($this->settings[$fromEmail])) {
                $extra[$fromEmail] = $this->settings[$fromEmail];
            } else {
                throw new IntegrationException('Missing from email.');
            }
        }

        $fromName = sprintf('%d.%d.fromName', $this->source->getId(), $assetID);
        if (empty($extra[$fromName])) {
            if (isset($this->settings[$fromName])) {
                $extra[$fromName] = $this->settings[$fromName];
            } else {
                throw new IntegrationException('Missing from name.');
            }
        }

        $replyEmail = sprintf('%d.%d.replyEmail', $this->source->getId(), $assetID);
        if (empty($extra[$replyEmail])) {
            if (isset($this->settings[$replyEmail])) {
                $extra[$replyEmail] = $this->settings[$replyEmail];
            }
        }

        $messageType = sprintf('%d.%d.messageType', $this->source->getId(), $assetID);
        if (empty($extra[$messageType])) {
            if (isset($this->settings[$messageType])) {
                $extra[$messageType] = $this->settings[$messageType];
            }
        }

        if (isset($extra['title'])) {
            $subject = $extra['title'];
        }
        $html = file_get_contents($localFilename);

        $body = [
            'clientTemplateId' => (string)$assetID,
            'name'             => $extra['name'] ?? 'Title',
            'fromName'         => $extra[$fromName],
            'fromEmail'        => $extra[$fromEmail],
            'replyToEmail'     => $extra[$replyEmail] ?? '',
            'subject'          => $subject,
            'html'             => $html,
        ];
        if (!empty($extra['preview'])) {
            $body['preheaderText'] = $extra['preview'];
        }
        if (!empty($extra[$messageType])) {
            $body['messageTypeId'] = (int)$extra[$messageType];
        }

        $resp = $this->doRequest('POST', '/api/templates/email/upsert', [
            RequestOptions::JSON => $body,
        ]);
        $this->logger->error(json_encode($resp));
        if (isset($resp['code']) && $resp['code'] !== 'Success') {
            throw new IntegrationException($resp['msg']);
        }

        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getDirectoryListing(string $dir): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function createDirectory(string $dir): bool
    {
        throw new Exception('Not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function deleteDirectory(string $dir): bool
    {
        throw new Exception('Not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function downloadFile(string $remoteFilename, string $localFilename): int
    {
        throw new Exception('Not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function deleteFile(string $remoteFilename): bool
    {
        throw new Exception('Not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function rename(string $remoteOldName, string $remoteNewName): bool
    {
        throw new Exception('Not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $remoteFilename): bool
    {
        throw new Exception('Not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function getFileURL(string $remoteFilename): string
    {
        throw new Exception('Not supported');
    }

    /**
     * @return array
     * @throws GuzzleException
     */
    protected function getMessageTypes(): array
    {
        $resp = $this->doRequest('GET', '/api/messageTypes');
        if (is_array($resp) && isset($resp['messageTypes'])) {
            return array_reverse($resp['messageTypes']);
        }

        return [];
    }

    /**
     * @param string $method
     * @param string $path
     * @param array  $options
     *
     * @return mixed
     * @throws GuzzleException
     */
    protected function doRequest(string $method, string $path, array $options = [])
    {
        if (!isset($options['headers'])) {
            $options['headers'] = [];
        }
        $options['headers']['Content-Type'] = 'application/json';
        $options['headers']['Accept']       = 'application/json';
        $options['headers']['Api-Key']      = $this->settings['apiKey'];
        $options['http_errors']             = false;

        $guzzle = new Client();
        $resp   = $guzzle->request($method, 'https://api.iterable.com' . $path, $options);

        return json_decode((string)$resp->getBody(), true);
    }
}
