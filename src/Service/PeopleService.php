<?php
namespace Service;

use BlocksEdit\Cache\CacheInterface;
use BlocksEdit\Email\EmailSender;
use BlocksEdit\Email\MailerInterface;
use BlocksEdit\Http\RouteGeneratorInterface;
use BlocksEdit\Integrations\Services\SingleSignOnIntegration;
use BlocksEdit\IO\Paths;
use BlocksEdit\IO\PathsTrait;
use BlocksEdit\Util\Media;
use BlocksEdit\Util\Tokens;
use BlocksEdit\System\Required;
use Entity\Invitation;
use Entity\OrganizationAccess;
use Entity\User;
use Exception;
use Gumlet\ImageResize;
use Repository\AccessRepository;
use Repository\EmailRepository;
use Repository\Exception\CreateException;
use Repository\InvitationsRepository;
use Repository\OrganizationAccessRepository;
use Repository\OrganizationsRepository;
use Repository\SourcesRepository;
use Repository\TemplateHistoryRepository;
use Repository\TemplatesRepository;
use Repository\TokensRepository;
use Repository\UserRepository;
use Tag\TemplateTag;

/**
 * Class PeopleService
 */
class PeopleService
{
    use PathsTrait;

    /**
     * @param int   $uid
     * @param int   $tid
     * @param int   $oid
     * @param array $user
     *
     * @return bool
     * @throws CreateException
     * @throws Exception
     */
    public function add(int $uid, int $tid, int $oid, array $user): bool
    {
        if(!$this->templatesRepository->hasAccess($uid, $tid)) {
            throw new CreateException('Not allowed to invite people to this template!');
        }
        if (!empty($user['uid'])) {
            $this->accessRepository->addAccess($user['uid'], $tid, 1);
            return true;
        }
        if (empty($user['usr_name'])) {
            throw new CreateException('A name needs to be included.');
        }
        if (empty($user['usr_email'])) {
            throw new CreateException('An email address needs to be used.');
        } elseif (!filter_var($user['usr_email'], FILTER_VALIDATE_EMAIL)) {
            throw new CreateException('The email address is not valid.');
        }

        $oldUser         = $this->userRepository->findByEmail($user['usr_email']);
        $fromUser        = $this->userRepository->findByID($uid);
        $template        = $this->templatesRepository->findByID($tid);
        $templateHistory = $this->templateHistoryRepository->findByTemplateVersion($tid, $template['tmp_version']);
        $from            = $fromUser['usr_name'];

        $screenshotFound = false;
        $screenshotLg    = $this->paths->dirTemplateScreenshot($tid, Paths::SCREENSHOT);
        $screenshotSm    = $this->paths->dirTemplateScreenshot($tid, 'screenshot-360px.jpg');
        $screenshotUri   = $this->paths->urlTemplateScreenshot($tid, 'screenshot-360px.jpg');
        if ($templateHistory->getThumb360()) {
            $screenshotUri = $templateHistory->getThumb360();
            $screenshotFound = true;
        }
        if (!$screenshotFound && !file_exists($screenshotSm) && file_exists($screenshotLg)) {
            $resizer = new ImageResize($screenshotLg);
            $resizer->crop(360, 360, true, ImageResize::CROPTOP);
            $resizer->save($screenshotSm, null, Media::JPEG_QUALITY);
        }

        if (!$oldUser) {
            $invite = $this->invitationsRepository->findByEmailAndTemplate($user['usr_email'], $tid);
            if ($invite) {
                $this->invitationsRepository->delete($invite);
            }

            $invite = (new Invitation())
                ->setInviterId($uid)
                ->setName($user['usr_name'])
                ->setEmail($user['usr_email'])
                ->setJob('')
                ->setOrg('')
                ->setTmpId($tid)
                ->setOrgId($oid)
                ->setIsAccepted(false)
                ->setType('template');
            $this->invitationsRepository->insert($invite);

            $this->inviteGuest(
                $from,
                $user,
                $template['tmp_title'],
                $tid,
                $oid,
                $invite->getToken(),
                $screenshotUri
            );
        } else {
            if (empty($oldUser['usr_job']) && !empty($user['usr_job'])) {
                $this->userRepository->updateSingle($oldUser['usr_id'], 'usr_job', $user['usr_job']);
            }
            if (empty($oldUser['usr_organization']) && !empty($user['usr_organization'])) {
                $this->userRepository->updateSingle($oldUser['usr_id'], 'usr_organization', $user['usr_organization']);
            }

            $this->accessRepository->addAccess($oldUser['usr_id'], $tid, 1);
            $token = $this->tokensRepository->addForUser($oldUser['usr_id'], Tokens::TOKEN_INVITE);

            $this->inviteRegistered($from, $user, $template['tmp_title'], $tid, $oid, $token, $screenshotUri);
            $rbacUserID = $oldUser['usr_id'];
        }

        if (!empty($rbacUserID)) {
            if (!empty($template['tmp_org_id'])) {
                $oid = $template['tmp_org_id'];
            }
            if (!$oid) {
                $rbac = $this->organizationAccessRepository->findFirstByUserAndAccess($uid, 1);
                if (!empty($rbac['rba_org_id'])) {
                    $oid = $rbac['rba_org_id'];
                }
            }
            if (!empty($oid)) {
                $user = $this->userRepository->findByID($rbacUserID, true);
                $org  = $this->organizationsRepository->findByID($oid, true);
                $orgAccess = (new OrganizationAccess())
                    ->setUser($user)
                    ->setOrganization($org)
                    ->setAccess(OrganizationAccess::EDITOR);
                $this->organizationAccessRepository->insert($orgAccess);
            }
        }

        $this->cache->deleteByTag(new TemplateTag($tid));

        return true;
    }

    /**
     * @param string $from
     * @param array  $user
     * @param string $templateTitle
     * @param int    $tid
     * @param int    $oid
     * @param string $token
     * @param string $screenshotUrl
     *
     * @throws Exception
     */
    public function inviteRegistered(
        string $from,
        array $user,
        string $templateTitle,
        int $tid,
        int $oid,
        string $token,
        string $screenshotUrl
    )
    {
        if ($this->hasIdentityProviders($oid)) {
            $acceptUrl = $this->routeGenerator->generate('invite', [
                    'tid'   => $tid,
                    'token' => $token
                ], 'absolute', $oid) . '?tid=' . $tid;
        } else {
            $acceptUrl = $this->routeGenerator->generate('invite', [
                'tid'   => $tid,
                'token' => $token
            ], 'absolute') . '?tid=' . $tid;
        }

        $this->emailTemplateSender->sendTemplateInvite(
            $user['usr_email'],
            $from,
            $acceptUrl,
            $screenshotUrl,
            $templateTitle
        );
    }

    /**
     * @param string $from
     * @param array  $user
     * @param string $templateTitle
     * @param int    $tid
     * @param int    $oid
     * @param string $token
     * @param string $urlScreenshot
     *
     * @throws Exception
     */
    public function inviteGuest(
        string $from,
        array $user,
        string $templateTitle,
        int $tid,
        int $oid,
        string $token,
        string $urlScreenshot
    )
    {
        if ($this->hasIdentityProviders($oid)) {
            $urlAccept = $this->routeGenerator->generate('invite', [
                    'tid'   => $tid,
                    'token' => $token
                ], 'absolute', $oid) . '?tid=' . $tid;
        } else {
            $urlAccept = $this->routeGenerator->generate('invite', [
                'tid'   => $tid,
                'token' => $token
            ], 'absolute') . '?tid=' . $tid;
        }

        $this->emailTemplateSender->sendTemplateInvite(
            $user['usr_email'],
            $from,
            $urlAccept,
            $urlScreenshot,
            $templateTitle
        );
    }

    /**
     * @param int    $tid
     * @param int    $uid
     * @param string $token
     *
     * @return Invitation
     * @throws Exception
     */
    public function acceptInvite(int $tid, int $uid, string $token): ?Invitation
    {
        $user   = $this->userRepository->findByID($uid, true);
        $invite = $this->invitationsRepository->findByToken($token);
        if ($invite && !$invite->getIsAccepted()) {
            $acceptInvite = function(Invitation $invite, int $tid, User $user) use ($uid) {
                $invite
                    ->setAcceptedId($uid)
                    ->setIsAccepted(1);
                $this->invitationsRepository->update($invite);

                $this->accessRepository->addAccess($uid, $tid, 1);
                $template = $this->templatesRepository->findByID($tid);
                if (!empty($template['tmp_org_id'])) {
                    $org = $this->organizationsRepository->findByID($template['tmp_org_id'], true);
                    $orgAccess = (new OrganizationAccess())
                        ->setUser($user)
                        ->setOrganization($org)
                        ->setAccess(OrganizationAccess::EDITOR);
                    $this->organizationAccessRepository->insert($orgAccess);
                }
            };

            $acceptInvite($invite, $tid, $user);

            if ($invite->getTmpId()) {
                $template = $this->templatesRepository->findByID($invite->getTmpId());
                if ($template) {
                    $additionalInvites = $this->invitationsRepository->findAllByEmailAndOrganization(
                        $invite->getEmail(),
                        $template['tmp_org_id']
                    );
                    foreach($additionalInvites as $additionalInvite) {
                        if ($additionalInvite->getId() !== $invite->getId() && !$additionalInvite->getIsAccepted()
                        ) {
                            $acceptInvite($additionalInvite, $additionalInvite->getTmpId(), $user);
                        }
                    }
                }
            }
        } else {
            $uid = $this->tokensRepository->findUserIDFromToken($token, Tokens::TOKEN_INVITE);
            if ($uid > 0) {
                $this->accessRepository->updateResponded($tid, $uid);
                $this->tokensRepository->deleteByUser($uid, $token);
            }
        }

        return $invite;
    }

    /**
     * @param int $oid
     *
     * @return bool
     * @throws Exception
     */
    protected function hasIdentityProviders(int $oid): bool
    {
        $sources = $this->sourcesRepository
            ->findByIntegrationAndOrg(SingleSignOnIntegration::class, $oid);

        return count($sources) > 0;
    }

    /**
     * @var OrganizationsRepository
     */
    protected $organizationsRepository;

    /**
     * @var TemplatesRepository
     */
    protected $templatesRepository;

    /**
     * @var InvitationsRepository
     */
    protected $invitationsRepository;

    /**
     * @var SourcesRepository
     */
    protected $sourcesRepository;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var EmailRepository
     */
    protected $emailRepository;

    /**
     * @var MailerInterface
     */
    protected $mailer;

    /**
     * @var RouteGeneratorInterface
     */
    protected $routeGenerator;

    /**
     * @var EmailSender
     */
    protected $emailTemplateSender;

    /**
     * @var AccessRepository
     */
    protected $accessRepository;

    /**
     * @var TemplateHistoryRepository
     */
    protected $templateHistoryRepository;

    /**
     * @var TokensRepository
     */
    protected $tokensRepository;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @Required()
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache)
    {
    	$this->cache = $cache;
    }

    /**
     * @Required()
     * @param TokensRepository $tokensRepository
     */
    public function setTokensRepository(TokensRepository $tokensRepository)
    {
        $this->tokensRepository = $tokensRepository;
    }

    /**
     * @Required()
     * @param TemplateHistoryRepository $templateHistoryRepository
     */
    public function setTemplateHistoryRepository(TemplateHistoryRepository $templateHistoryRepository)
    {
        $this->templateHistoryRepository = $templateHistoryRepository;
    }

    /**
     * @Required()
     * @param AccessRepository $accessRepository
     */
    public function setAccessRepository(AccessRepository $accessRepository)
    {
        $this->accessRepository = $accessRepository;
    }

    /**
     * @Required()
     * @param EmailSender $emailSender
     */
    public function setEmailSender(EmailSender $emailSender)
    {
        $this->emailTemplateSender = $emailSender;
    }

    /**
     * @Required()
     * @param RouteGeneratorInterface $routeGenerator
     */
    public function setRouteGenerator(RouteGeneratorInterface $routeGenerator)
    {
        $this->routeGenerator = $routeGenerator;
    }

    /**
     * @Required()
     * @param MailerInterface $mailer
     */
    public function setMailerInterface(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @Required()
     * @param UserRepository $userRepository
     */
    public function setUserRepository(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @Required()
     * @param SourcesRepository $sourcesRepository
     */
    public function setSourcesRepository(SourcesRepository $sourcesRepository)
    {
        $this->sourcesRepository = $sourcesRepository;
    }

    /**
     * @Required()
     * @param InvitationsRepository $invitationsRepository
     */
    public function setInvitationsRepository(InvitationsRepository $invitationsRepository)
    {
        $this->invitationsRepository = $invitationsRepository;
    }

    /**
     * @Required()
     * @param EmailRepository $emailRepository
     */
    public function setEmailRepository(EmailRepository $emailRepository)
    {
        $this->emailRepository = $emailRepository;
    }

    /**
     * @Required()
     * @param TemplatesRepository $templatesRepository
     */
    public function setTemplatesRepository(TemplatesRepository $templatesRepository)
    {
        $this->templatesRepository = $templatesRepository;
    }

    /**
     * @Required()
     * @param OrganizationsRepository $organizationsRepository
     */
    public function setOrganizationsRepository(OrganizationsRepository $organizationsRepository)
    {
        $this->organizationsRepository = $organizationsRepository;
    }

    /**
     * @var OrganizationAccessRepository
     */
    protected $organizationAccessRepository;

    /**
     * @Required()
     * @param OrganizationAccessRepository $organizationAccessRepository
     */
    public function setOrganizationAccessRepository(OrganizationAccessRepository $organizationAccessRepository)
    {
    	$this->organizationAccessRepository = $organizationAccessRepository;
    }
}
