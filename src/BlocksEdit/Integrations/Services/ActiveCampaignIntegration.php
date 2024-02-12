<?php
namespace BlocksEdit\Integrations\Services;

use BlocksEdit\Form\FormBuilder;
use BlocksEdit\Html\FormErrors;
use BlocksEdit\Http\Request;
use BlocksEdit\Integrations\AbstractFilesystemIntegration;
use BlocksEdit\Integrations\Filesystem\FileInfo;
use BlocksEdit\Integrations\Hook;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Repository\SourcesRepository;
use RuntimeException;
use Soundasleep\Html2Text;
use Soundasleep\Html2TextException;

/**
 * Class ActiveCampaignIntegration
 *
 * @see https://www.activecampaign.com/api/overview.php
 */
class ActiveCampaignIntegration extends AbstractFilesystemIntegration
{
    /**
     * {@inheritDoc}
     */
    public function getSlug(): string
    {
        return 'activecampaign';
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
    public function getDisplayName(): string
    {
        return 'ActiveCampaign';
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
    public function getIconURL(): string
    {
        return '/assets/images/integration-activecampaign.png';
    }

    /**
     * {@inheritDoc}
     */
    public function getInstructionsURL(): string
    {
        return 'https://blocksedit.com/help/integrations/activecampaign-setup/';
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
                self::HOOK_EMAIL_SETTINGS         => [$this, 'hookEmailSettings'],
                self::HOOK_EMAIL_SETTINGS_POST    => [$this, 'hookEmailSettingsPost'],
                self::HOOK_TEMPLATE_SETTINGS_PRE  => [$this, 'hookTemplateSettingsPre'],
                self::HOOK_TEMPLATE_SETTINGS_POST => [$this, 'hookTemplateSettingsPost']
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function formatRemoteFilename($email): string
    {
        if (is_array($email)) {
            return $email['ema_title'] ?? 'Blocks Edit Template';
        }

        return $email;
    }

    /**
     * {@inheritDoc}
     */
    public function getSettingsForm(Request $request, array $values = [], ?FormErrors $errors = null): array
    {
        $form = [
            'uri' => [
                'type'     => 'text',
                'label'    => 'API URL',
                'required' => true
            ],
            'token' => [
                'type'     => 'password',
                'label'    => 'API Key',
                'required' => true
            ]
        ];

        return $this->applyFormValues($form, $values);
    }

    /**
     * @param Hook $hook
     */
    public function hookEmailSettings(Hook $hook)
    {
        $choices = [];
        foreach($this->getList() as $item) {
            $choices[$item['id']] = $item['name'];
        }

        $email    = $hook->getEmail();
        $template = $hook->getTemplate();

        $emailValue   = 0;
        $emailListKey = sprintf('%d.%d.listID', $this->source->getId(), $email['ema_id']);
        if (isset($this->settings[$emailListKey])) {
            $emailValue = $this->settings[$emailListKey];
        } else {
            $listKey = sprintf('%d.%d.listID', $this->source->getId(), $template['tmp_id']);
            if (isset($this->settings[$listKey])) {
                $emailValue = $this->settings[$listKey];
            }
        }

        $nameValue   = '';
        $nameListKey = sprintf('%d.%d.name', $this->source->getId(), $email['ema_id']);
        if (isset($this->settings[$nameListKey])) {
            $nameValue = $this->settings[$nameListKey];
        } else {
            $listKey = sprintf('%d.%d.name', $this->source->getId(), $template['tmp_id']);
            if (isset($this->settings[$listKey])) {
                $nameValue = $this->settings[$listKey];
            }
        }

        $fromValue   = '';
        $fromListKey = sprintf('%d.%d.from', $this->source->getId(), $email['ema_id']);
        if (isset($this->settings[$fromListKey])) {
            $fromValue = $this->settings[$fromListKey];
        } else {
            $listKey = sprintf('%d.%d.from', $this->source->getId(), $template['tmp_id']);
            if (isset($this->settings[$listKey])) {
                $fromValue = $this->settings[$listKey];
            }
        }

        $form = FormBuilder::create();
        $html = $form->widget($emailListKey, [
            'type'     => 'choice',
            'label'    => 'Campaign List',
            'required' => true,
            'choices'  => $choices,
            'value'    => $emailValue
        ]);
        $html .= $form->widget($nameListKey, [
            'type'     => 'text',
            'label'    => 'From Name',
            'required' => true,
            'value'    => $nameValue
        ]);
        $html .= $form->widget($fromListKey, [
            'type'     => 'text',
            'label'    => 'From Email',
            'required' => true,
            'value'    => $fromValue
        ]);
        $hook->addResponse($html);
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
            $emailListKey = sprintf('%d.%d.listID', $this->source->getId(), $email['ema_id']);
            if (isset($integrationSettings[$emailListKey])) {
                $this->settings[$emailListKey] = $integrationSettings[$emailListKey];
            }

            $nameListKey = sprintf('%d.%d.name', $this->source->getId(), $email['ema_id']);
            if (isset($integrationSettings[$nameListKey])) {
                $this->settings[$nameListKey] = $integrationSettings[$nameListKey];
            }

            $fromListKey = sprintf('%d.%d.from', $this->source->getId(), $email['ema_id']);
            if (isset($integrationSettings[$fromListKey])) {
                $this->settings[$fromListKey] = $integrationSettings[$fromListKey];
            }

            $sourcesRepo = $this->container->get(SourcesRepository::class);
            $sourcesRepo->updateSettings($this->source, $this->settings);
        }
    }

    /**
     * @param Hook $hook
     */
    public function hookTemplateSettingsPre(Hook $hook)
    {
        try {
            $choices = [
                '0' => 'Default list'
            ];
            $list = $this->getList();
            if (!$list) {
                return;
            }
            foreach($list as $item) {
                $choices[$item['id']] = $item['name'];
            }

            $template = $hook->getTemplate();
            if (!$template) {
                return;
            }

            $listKey = sprintf('%d.%d.listID', $this->source->getId(), $template['tmp_id']);
            $form    = FormBuilder::create();
            $html    = $form->widget($listKey, [
                'type'     => 'choice',
                'required' => false,
                'choices'  => $choices,
                'value'    => $this->settings[$listKey] ?? 0
            ], [
                'style' => 'width: 84%; margin-bottom: 2px;'
            ]);

            $nameKey = sprintf('%d.%d.name', $this->source->getId(), $template['tmp_id']);
            $html   .= $form->widget($nameKey, [
                'type'        => 'text',
                'required'    => false,
                'value'       => $this->settings[$nameKey] ?? ''
            ], [
                'placeholder' => 'From Name',
                'style' => 'width: 84%; margin-bottom: 2px;'
            ]);

            $fromKey = sprintf('%d.%d.from', $this->source->getId(), $template['tmp_id']);
            $html   .= $form->widget($fromKey, [
                'type'        => 'text',
                'required'    => false,
                'value'       => $this->settings[$fromKey] ?? ''
            ], [
                'placeholder' => 'From Email',
                'style' => 'width: 84%;'
            ]);
            $hook->addResponse($html);
        } catch (Exception $e) {

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

        $listKey = sprintf('%d.%d.listID', $this->source->getId(), $template['tmp_id']);
        $request = $hook->getRequest();
        $fields  = $request->json->get('extraFields');
        if (isset($fields[$listKey])) {
            $this->settings[$listKey] = $fields[$listKey];
        }

        $nameKey = sprintf('%d.%d.name', $this->source->getId(), $template['tmp_id']);
        if (isset($fields[$nameKey])) {
            $this->settings[$nameKey] = $fields[$nameKey];
        }

        $fromKey = sprintf('%d.%d.from', $this->source->getId(), $template['tmp_id']);
        if (isset($fields[$fromKey])) {
            $this->settings[$fromKey] = $fields[$fromKey];
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
     * {@inheritDoc}
     */
    public function getDirectoryListing($dir): array
    {
        // http://www.enviado.com.mx/api/example.php?call=message_view
        // $resp = $this->doRequest('GET', '/api/3/campaigns');
        $resp = $this->doV1Request('GET', '/admin/api.php?api_action=message_list&ids=all');

        $files = [];
        foreach($resp['messages'] as $message) {
            if (!empty($message['name']) && $message['ed_instanceid'] === '0') {
                $files[] = new FileInfo(
                    '-' . $message['id'] . '-' . $message['name'] . '.html',
                    false,
                    0,
                    (new DateTime($message['cdate']))->getTimestamp(),
                    false
                );
            }
        }

        return $files;
    }

    /**
     * {@inheritDoc}
     */
    public function createDirectory($dir): bool
    {
        throw new Exception('Not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function deleteDirectory($dir): bool
    {
        throw new Exception('Not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function uploadFile(
        $remoteFilename,
        $localFilename,
        string $assetType,
        int $assetID,
        $subject = '',
        array $extra = []
    ): string
    {
        $emailListKey = sprintf('%d.%d.listID', $this->source->getId(), $assetID);
        if (empty($extra[$emailListKey])) {
            if (isset($this->settings[$emailListKey])) {
                $extra[$emailListKey] = $this->settings[$emailListKey];
            } else {
                throw new Exception('Missing listID.');
            }
        }

        $nameListKey = sprintf('%d.%d.name', $this->source->getId(), $assetID);
        if (empty($extra[$nameListKey])) {
            if (isset($this->settings[$nameListKey])) {
                $extra[$nameListKey] = $this->settings[$nameListKey];
            } else {
                throw new Exception('Missing name.');
            }
        }

        $fromListKey = sprintf('%d.%d.from', $this->source->getId(), $assetID);
        if (empty($extra[$fromListKey])) {
            if (isset($this->settings[$fromListKey])) {
                $extra[$fromListKey] = $this->settings[$fromListKey];
            } else {
                throw new Exception('Missing from.');
            }
        }

        if (isset($extra['title'])) {
            $subject = $extra['title'];
        }
        $html = file_get_contents($localFilename);

        $messageID = 0;
        $key       = sprintf('ActiveCampaign:message:%d', $assetID);
        $existingMessageID = $this->cache->get($key);
        if ($existingMessageID) {
            if ($this->viewMessage($existingMessageID)) {
                $messageID = $existingMessageID;
            }
        }

        if ($messageID) {
            $this->updateMessage(
                $messageID,
                $html,
                $remoteFilename,
                $extra[$emailListKey],
                $extra[$nameListKey],
                $extra[$fromListKey],
                $subject
            );
        } else {
            $messageID = $this->createMessage(
                $html,
                $remoteFilename,
                $extra[$emailListKey],
                $extra[$nameListKey],
                $extra[$fromListKey],
                $subject
            );
            $this->cache->set($key, $messageID);

            $campaign = [
                'type'       => 'single',
                'name'       => $remoteFilename,
                'sdate'      => date('Y-m-d H:i:s'),
                'status'     => 0,
                'public'     => 0,
                'p'          => [
                    $extra[$emailListKey] => $extra[$emailListKey]
                ],
                'm'          => [
                    $messageID => 100
                ]
            ];

            $resp = $this->doV1Request('POST', '/admin/api.php?api_action=campaign_create', [
                'form_params' => $campaign
            ]);
            if (!$resp || $resp['result_code'] != 1) {
                throw new Exception('Unable to save campaign.');
            }
        }

        return '';
    }

    /**
     * @param string $html
     * @param string $name
     * @param int    $listID
     * @param string $fromName
     * @param string $from
     * @param string $subject
     *
     * @return int
     * @throws GuzzleException
     * @throws Html2TextException
     */
    protected function createMessage(
        string $html,
        string $name,
        int $listID,
        string $fromName,
        string $from,
        string $subject
    ): int
    {
        $message = [
            'message' => [
                'fromname'     => $fromName,
                'fromemail'    => $from,
                'reply2'       => $from,
                'name'         => $name,
                'subject'      => $subject,
                "p[{$listID}]" => $listID,
                'text'         => Html2Text::convert($html, true),
                'html'         => $html,
            ]
        ];

        $resp = $this->doRequest('POST', '/api/3/messages', [
            RequestOptions::JSON => $message
        ]);
        if (empty($resp['message']) || empty($resp['message']['id'])) {
            throw new RuntimeException('Unable to create message.');
        }

        return (int)$resp['message']['id'];
    }

    /**
     * @param int    $messageID
     * @param string $html
     * @param string $name
     * @param int    $listID
     * @param string $fromName
     * @param string $from
     * @param string $subject
     *
     * @return int
     * @throws GuzzleException
     * @throws Html2TextException
     */
    protected function updateMessage(
        int $messageID,
        string $html,
        string $name,
        int $listID,
        string $fromName,
        string $from,
        string $subject
    ): int
    {
        $message = [
            'message' => [
                'fromname'    => $fromName,
                'fromemail'    => $from,
                'reply2'       => $from,
                'name'         => $name,
                'subject'      => $subject,
                "p[{$listID}]" => $listID,
                'text'         => Html2Text::convert($html, true),
                'html'         => $html,
            ]
        ];

        $resp = $this->doRequest('PUT', "/api/3/messages/${messageID}", [
            RequestOptions::JSON => $message
        ]);
        if (empty($resp['message']) || empty($resp['message']['id'])) {
            throw new RuntimeException('Unable to create message.');
        }

        return (int)$resp['message']['id'];
    }

    /**
     * @param int $messageID
     *
     * @return array
     * @throws GuzzleException
     */
    protected function viewMessage(int $messageID): array
    {
        $resp = $this->doRequest('GET', "/api/3/messages/${messageID}");
        if (empty($resp) || empty($resp['message'])) {
            return [];
        }

        return $resp['message'];
    }

    /**
     * @return array
     */
    protected function getList(): array
    {
        $resp = $this->doV1Request('GET', '/admin/api.php?api_action=list_list&ids=all');
        if ($resp['result_code'] != 1) {
            return [];
        }
        unset($resp['result_code']);
        unset($resp['result_message']);
        unset($resp['result_output']);

        return $resp;
    }

    /**
     * {@inheritDoc}
     */
    public function downloadFile($remoteFilename, $localFilename): int
    {
        throw new Exception('Not supported');
        /*$resp = $this->doRequest('GET', '/api/3/messages');
        if (!empty($resp['messages'])) {
            foreach($resp['messages'] as $message) {
                $foundName = '/' . '-' . $message['id'] . '-' . $message['name'] . '.html';
                if ($foundName === $remoteFilename) {
                    return file_put_contents($localFilename, $message['html']);
                }
            }
        }

        return 0;*/
    }

    /**
     * {@inheritDoc}
     */
    public function deleteFile($remoteFilename): bool
    {
        throw new Exception('Not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function rename($remoteOldName, $remoteNewName): bool
    {
        throw new Exception('Not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function exists($remoteFilename): bool
    {
        throw new Exception('Not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function getFileURL($remoteFilename): string
    {
        return '';
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
        $options['headers']['Api-Token']    = $this->settings['token'];

        $guzzle = new Client();
        $resp   = $guzzle->request($method, $this->settings['uri'] . $path, $options);

        return json_decode((string)$resp->getBody(), true);
    }

    protected function doV1Request(string $method, string $path, array $options = [])
    {
        if (!isset($options['headers'])) {
            $options['headers'] = [];
        }
        // $options['headers']['Content-Type'] = 'application/json';

        $guzzle = new Client();
        $resp   = $guzzle->request(
            $method,
            $this->settings['uri'] . $path . '&api_key=' . $this->settings['token'] . '&api_output=json',
            $options
        );

        return json_decode((string)$resp->getBody(), true);
    }
}
