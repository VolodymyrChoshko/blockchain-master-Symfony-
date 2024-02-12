<?php
namespace BlocksEdit\Integrations\Services;

use BlocksEdit\Html\Flasher;
use BlocksEdit\Html\FormErrors;
use BlocksEdit\Http\ContentDispositionResponse;
use BlocksEdit\Http\Exception\BadRequestException;
use BlocksEdit\Http\Exception\UnauthorizedException;
use BlocksEdit\Http\RedirectResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use BlocksEdit\Http\RouteGenerator;
use BlocksEdit\Integrations\AbstractIntegration;
use BlocksEdit\Integrations\IntegrationInterface;
use BlocksEdit\Integrations\LoginIntegrationInterface;
use BlocksEdit\Integrations\RoutableIntegrationInterface;
use BlocksEdit\View\View;
use Exception;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\IdPMetadataParser;
use OneLogin\Saml2\Utils;
use OneLogin\Saml2\ValidationError;
use Repository\OrganizationAccessRepository;
use Repository\UserRepository;
use Service\AuthService;
use Repository\SourcesRepository;
use RuntimeException;

/**
 * Class SingleSignOnIntegration
 */
class SingleSignOnIntegration
    extends AbstractIntegration
    implements RoutableIntegrationInterface, LoginIntegrationInterface
{
    /**
     * @var array
     */
    protected static $idps = [
        'okta',
        'oneLogin',
        'custom'
    ];

    /**
     * @var array
     */
    protected $sourceSettings;

    /**
     * {@inheritDoc}
     */
    public function getSlug(): string
    {
        return 'sso';
    }

    /**
     * {@inheritDoc}
     */
    public function getRestrictions(): array
    {
        return [IntegrationInterface::ONE_PER_ORG];
    }

    /**
     * {@inheritDoc}
     */
    public function getDisplayName(): string
    {
        return 'Single Sign-On';
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
        return 19900;
    }

    /**
     * @return string
     */
    public function getIconURL(): string
    {
        return '/assets/images/integration-sso.png';
    }

    /**
     * {@inheritDoc}
     */
    public function getInstructionsURL(): string
    {
        return 'https://blocksedit.com/help/integrations/single-sign-on-setup/';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultSettings(): array
    {
        return [
            'idp' => 'okta'
        ];
    }

    /**
     * {@inheritDoc}
     * @throws Error
     * @throws Exception
     */
    public function getSettingsForm(Request $request, array $values = [], ?FormErrors $errors = null)
    {
        $auth            = $this->getAuth($request);
        $sp              = $auth->getSettings()->getSPData();
        $routerGenerator = $this->container->get(RouteGenerator::class);

        $domain       = $request->getDomainForOrg($this->oid);
        $metadata_url = $routerGenerator->generate('integration_sso_metadata', [], $domain);
        $acs_url      = $routerGenerator->generate('integration_sso_acs', [], $domain);
        $cert_url     = $routerGenerator->generate('integration_sso_cert', [], $domain);

        $form = [
            'idp' => [
                'type'  => 'choice',
                'label' => 'Identity Provider',
                'choices' => [
                    'okta'     => 'Okta',
                    'oneLogin' => 'OneLogin',
                    'custom'   => 'Custom'
                ]
            ],
            'sp_metadata_url' => [
                'type'     => 'text',
                'label'    => 'Metadata URL',
                'value'    => $metadata_url
            ],
            'sp_cert_url' => [
                'type'     => 'text',
                'label'    => 'Certificate URL',
                'value'    => $cert_url
            ],
            'sp_acs' => [
                'type'     => 'text',
                'label'    => 'Single sign on URL',
                'value'    => $acs_url
            ],
            'sp_entity_id' => [
                'type'     => 'text',
                'label'    => 'Entity ID',
                'value'    => $sp['entityId']
            ],
            'idp_metadata_url_custom' => [
                'type'     => 'text',
                'label'    => 'Metadata URL'
            ],
            'idp_metadata_file_custom' => [
                'type'  => 'file',
                'label' => 'Metadata File'
            ],
            'idp_metadata_url_oneLogin' => [
                'type'     => 'text',
                'label'    => 'Issuer URL'
            ],
            'idp_metadata_file_oneLogin' => [
                'type'  => 'file',
                'label' => 'SAML Metadata'
            ],
            'idp_metadata_url_okta' => [
                'type'     => 'text',
                'label'    => 'Identity Provider metadata'
            ],
            'idp_metadata_file_okta' => [
                'type'  => 'file',
                'label' => 'SAML Metadata'
            ],
            'idp_acs' => [
                'type'     => 'text',
                'label'    => 'Single sign on URL',
            ],
            'idp_entity_id' => [
                'type'     => 'text',
                'label'    => 'Entity ID'
            ],
            'idp_metadata_cert' => [
                'type'  => 'file',
                'label' => 'X.509 Certificate'
            ],
        ];

        $form = $this->applyFormValues($form, $values);
        $view = new View(__DIR__ . '/SingleSignOn/form.phtml', [
            'oid'         => $this->oid,
            'form'        => $form,
            'errors'      => $errors,
            'formValues'  => $values,
            'source'      => $this->source,
            'integration' => $this
        ]);

        return $view->render();
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function preSaveSettings(array $settings)
    {
        $errors = new FormErrors();
        $parser = new IdPMetadataParser();

        if (empty($settings['idp'])) {
            throw new BadRequestException();
        }
        $idp = $settings['idp'];
        if (!in_array($idp, self::$idps)) {
            throw new BadRequestException();
        }

        $urlKey  = "idp_metadata_url_${idp}";
        $fileKey = "idp_metadata_file_${idp}";
        if (!empty($settings[$urlKey])) {
            $metadata = $parser->parseRemoteXML($settings[$urlKey]);
        } else if (!empty($settings[$fileKey])) {
            $metadata = $parser->parseXML($settings[$fileKey]);
        } else {
            if (empty($settings['idp_entity_id'])) {
                $errors->add('idp_entity_id', 'Missing required value.');
            }
            if (empty($settings['idp_acs'])) {
                $errors->add('idp_acs', 'Missing required value.');
            }
            if (empty($settings['idp_metadata_cert'])) {
                $errors->add('idp_metadata_cert', 'Missing required value.');
            }

            $metadata = [
                'idp' => [
                    'entityId' => $settings['idp_entity_id'],
                    'singleSignOnService' => [
                        'url'     => $settings['idp_acs'],
                        'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
                    ],
                    'x509cert' => $settings['idp_metadata_cert']
                ]
            ];
        }

        if ($errors->hasErrors()) {
            return $errors;
        }

        return array_merge($settings, [
            'metadata' => json_encode($metadata)
        ]);
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function getLoginButtonLabel(): string
    {
        return $this->source->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getLoginPath(Request $request): string
    {
        try {
            $routerGenerator = $this->container->get(RouteGenerator::class);
            $domain          = $request->getDomainForOrg($this->oid);

            return $routerGenerator->generate(
                'integration_sso_login',
                [],
                $domain
            );
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * @return array
     */
    public function getRoutes(): array
    {
        return [
            'integration_sso_login' => [
                'match'   => '/sso/login',
                'method'  => 'loginAction',
                'methods' => ['GET']
            ],
            'integration_sso_acs' => [
                'match'   => '/sso',
                'method'  => 'acsAction',
                'methods' => ['POST']
            ],
            'integration_sso_metadata' => [
                'match'  => '/sso/metadata.xml',
                'method' => 'metadataAction'
            ],
            'integration_sso_sls' => [
                'match'   => '/sso/sls',
                'method'  => 'slsAction',
                'methods' => ['POST']
            ],
            'integration_sso_cert' => [
                'match'  => '/sso/cert',
                'method' => 'certAction'
            ]
        ];
    }

    /**
     * @param Request $request
     *
     * @throws Error
     * @throws UnauthorizedException
     */
    public function loginAction(Request $request)
    {
        $oid = $request->oid;
        $this->verifyOrg($oid);

        $auth = $this->getAuth($request);
        $auth->login(
            $request->getDomainForOrg($oid)
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Error
     * @throws Exception
     */
    public function metadataAction(Request $request)
    {
        $oid = $request->oid;
        $this->verifyOrg($oid);

        $auth     = $this->getAuth($request);
        $settings = $auth->getSettings();
        $metadata = $settings->getSPMetadata();
        $errors   = $settings->validateMetadata($metadata);

        if (empty($errors)) {
            if ($request->query->has('download')) {
                return new ContentDispositionResponse($metadata, 'text/xml', 'metadata.xml', 200, [], false, true);
            }
            return new Response($metadata, 200, ['Content-Type' => 'text/xml']);
        } else {
            throw new Error(
                'Invalid SP metadata: ' . implode(', ', $errors),
                Error::METADATA_SP_INVALID
            );
        }
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     * @throws BadRequestException
     * @throws Error
     * @throws UnauthorizedException
     * @throws ValidationError
     * @throws Exception
     */
    public function acsAction(Request $request): RedirectResponse
    {
        $oid = $request->oid;
        $this->logger->error('ACS ' . json_encode([
                'oid' => $oid,
                'post' => $_POST,
                'server' => $_SERVER,
            ]));
        $this->verifyOrg($oid);

        $auth = $this->getAuth($request);
        $auth->processResponse();

        $errors = $auth->getErrors();
        if (!empty($errors)) {
            $this->logger->error('Errors ' . json_encode($errors));
            $flasher = $this->container->get(Flasher::class);
            $flasher->error('Error reported from identify provider.');
            return new RedirectResponse('/login');
        }
        if (!$auth->isAuthenticated()) {
            $this->logger->error('Not authenticated');
            $flasher = $this->container->get(Flasher::class);
            $flasher->error('Not authenticated.');
            return new RedirectResponse('/login');
        }

        $orgAccessRepo = $this->container->get(OrganizationAccessRepository::class);
        $aid           = $orgAccessRepo->findInitialOwnerID($oid);
        if (!$aid) {
            $this->logger->error('Invalid login credentials.');
            $flasher = $this->container->get(Flasher::class);
            $flasher->error('Invalid login credentials.');
            return new RedirectResponse('/login');
        }

        $authRepo = $this->container->get(AuthService::class);
        $user     = $this->container->get(UserRepository::class)->findAccountUserByEmail($aid, $auth->getNameId());
        if (!$user) {
            $user = $this->provisionUser($aid, $oid, $auth);
        }

        $authRepo->loginSSO($request, $user);

        if (!empty($_POST['RelayState']) && Utils::getSelfURL() !== $_POST['RelayState']) {
            $this->logger->error('RelayState ' . $_POST['RelayState']);
            return new RedirectResponse($_POST['RelayState']);
        }

        return new RedirectResponse(
            $request->getDomainForOrg($oid)
        );
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     * @throws BadRequestException
     * @throws Error
     * @throws UnauthorizedException
     */
    public function slsAction(Request $request): RedirectResponse
    {
        $oid = $request->oid;
        $this->verifyOrg($oid);

        $auth = $this->getAuth($request);
        $auth->processSLO();
        $errors = $auth->getErrors();
        if (!empty($errors)) {
            throw new BadRequestException(implode(', ', $errors));
        }

        $request->session->destroy();
        $request->removeCookie('remember');

        return new RedirectResponse('/');
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Error
     * @throws UnauthorizedException
     */
    public function certAction(Request $request)
    {
        $oid = $request->oid;
        $this->verifyOrg($oid);

        $auth = $this->getAuth($request);
        $data = $auth->getSettings()->getSPData();

        return new ContentDispositionResponse(
            $data['x509cert'],
            'application/x-pem-file',
            'blocksedit.pem',
            200,
            [],
            false,
            true
        );
    }

    /**
     * @param Request $request
     *
     * @return Auth
     * @throws Error
     * @throws Exception
     */
    protected function getAuth(Request $request): Auth
    {
        $oid      = $request->oid;
        $domain   = $request->getDomainForOrg($oid);
        $settings = require(__DIR__ . '/SingleSignOn/settings.php');

        $settings['debug'] = $this->config->env === 'dev';
        $settings['sp']['assertionConsumerService']['url'] = sprintf('%s/sso', $domain);
        $settings['sp']['singleLogoutService']['url']      = sprintf('%s/sso/sls', $domain);
        $settings['sp']['x509cert']                        = file_get_contents(sprintf('%scerts/saml.crt', $this->config->dirs['config']));
        $settings['sp']['privateKey']                      = file_get_contents(sprintf('%scerts/saml.pem', $this->config->dirs['config']));

        if (!$this->source) {
            throw new RuntimeException('Integration source not set.');
        }
        $sourceSettings = $this->getSourceSettings();
        if (!empty($sourceSettings['metadata'])) {
            $metadata        = json_decode($sourceSettings['metadata'], true);
            $settings['idp'] = $metadata['idp'];
        } else {
            unset($settings['security']);
        }

        Utils::setProxyVars(true);

        return new Auth($settings);
    }

    /**
     * @return int
     */
    protected function getUserID(): int
    {
        return $this->container->get(AuthService::class)->getLoginId();
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getSourceSettings(): array
    {
        if (!$this->sourceSettings) {
            if (!$this->source) {
                throw new RuntimeException('Integration source not set.');
            }
            $this->sourceSettings = $this->container->get(SourcesRepository::class)
                ->findSettings($this->source);
        }

        return $this->sourceSettings;
    }

    /**
     * @param int $oid
     *
     * @return bool
     * @throws UnauthorizedException
     */
    protected function verifyOrg(int $oid): bool
    {
        try {
            $sources = $this->container->get(SourcesRepository::class)
                ->findByIntegrationAndOrg(SingleSignOnIntegration::class, $oid);
        } catch (Exception $e) {
            throw new UnauthorizedException('Single sign-on is not enabled for this account.');
        }
        if (!$sources || $sources[0]->getOrgId() !== $oid) {
            throw new UnauthorizedException('Single sign-on is not enabled for this account.');
        }

        $this->setSource($sources[0]);

        return true;
    }

    /**
     * @param int  $aid
     * @param int  $oid
     * @param Auth $auth
     *
     * @return bool|array
     * @throws BadRequestException
     * @throws Exception
     */
    protected function provisionUser(int $aid, int $oid, Auth $auth)
    {
        $email      = $auth->getNameId();
        $firstName  = $auth->getAttribute('firstName');
        $lastName   = $auth->getAttribute('lastName');
        $attributes = $auth->getAttributes();

        if (isset($attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/firstName'])) {
            $firstName = $attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/firstName'];
        }
        if (isset($attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/lastName'])) {
            $lastName = $attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/lastName'];
        }

        if (is_array($firstName)) {
            $firstName = $firstName[0];
        }
        if (is_array($lastName)) {
            $lastName = $lastName[0];
        }
        if (empty($email)) {
            throw new BadRequestException('Missing email attributes.');
        }
        if (empty($firstName)) {
            throw new BadRequestException('Missing firstName attributes.');
        }
        if (empty($lastName)) {
            throw new BadRequestException('Missing lastName attributes.');
        }

        $this->logger->error('Provisioning user ' . json_encode([
                'aid'       => $aid,
                'oid'       => $oid,
                'email'     => $email,
                'firstName' => $firstName,
                'lastName'  => $lastName,
                'server'    => $_SERVER,
            ]
        ));

        return $this->container->get(UserRepository::class)
            ->provisionUser($aid, $oid, $email, $firstName, $lastName);
    }
}
