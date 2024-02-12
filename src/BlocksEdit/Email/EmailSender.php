<?php
namespace BlocksEdit\Email;

use BlocksEdit\Http\RouteGeneratorInterface;
use BlocksEdit\Logging\LoggerTrait;
use BlocksEdit\Twig\TwigRender;
use Entity\CreditCard;
use Entity\EmailTemplate;
use Exception;
use Service\Export\ExportService;
use Repository\EmailTemplateRepository;
use Repository\OrganizationsRepository;
use Repository\NoSendRepository;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Class EmailSender
 */
class EmailSender
{
    use LoggerTrait;

    const BILLING_TRIAL_ENDING = 'BILLING_TRIAL_ENDING';
    const BILLING_TRIAL_INTEGRATIONS_ENDING = 'BILLING_TRIAL_INTEGRATIONS_ENDING';
    const BILLING_TRIAL_ENDED = 'BILLING_TRIAL_ENDED';
    const BILLING_TRIAL_INTEGRATIONS_ENDED = 'BILLING_TRIAL_INTEGRATIONS_ENDED';
    const BILLING_CREDIT_CARD_DECLINED = 'BILLING_CREDIT_CARD_DECLINED';
    const BILLING_PLAN_PAUSED = 'BILLING_PLAN_PAUSED';
    const BILLING_NOTICE = 'BILLING_NOTICE';
    const HELP_AND_SUPPORT = 'HELP_AND_SUPPORT';
    const ORGANIZATIONS_INVITE = 'ORGANIZATIONS_INVITE';
    const PASSWORD_RESET = 'PASSWORD_RESET';
    const TEMPLATE_INVITE = 'TEMPLATE_INVITE';
    const WELCOME = 'WELCOME';
    const WHAT_YOU_CAN_DO = 'WHAT_YOU_CAN_DO';
    const WHAT_YOU_CAN_DO_EXAMPLE = 'WHAT_YOU_CAN_DO_EXAMPLE';
    const NOTIFICATION_REPLY = 'NOTIFICATION_REPLY';
    const NOTIFICATION_MENTION = 'NOTIFICATION_MENTION';

    const DISK_LOCATIONS
        = [
            self::BILLING_TRIAL_ENDING              => 'billing-trial-ending.html.twig',
            self::BILLING_TRIAL_INTEGRATIONS_ENDING => 'billing-trial-integrations-ending.html.twig',
            self::BILLING_TRIAL_ENDED               => 'billing-trial-ended.html.twig',
            self::BILLING_TRIAL_INTEGRATIONS_ENDED  => 'billing-trial-integrations-ended.html.twig',
            self::BILLING_CREDIT_CARD_DECLINED      => 'billing-credit-card-declined.html.twig',
            self::BILLING_PLAN_PAUSED               => 'billing-plan-paused.html.twig',
            self::BILLING_NOTICE                    => 'billing-notice.html.twig',
            self::HELP_AND_SUPPORT                  => 'help-and-support.html.twig',
            self::WELCOME                           => 'the-welcome-email.html.twig',
            self::WHAT_YOU_CAN_DO                   => 'what-you-can-do.html.twig',
            self::WHAT_YOU_CAN_DO_EXAMPLE           => 'what-you-can-do-example.html.twig',
            self::ORGANIZATIONS_INVITE              => 'organization-invite.html.twig',
            self::TEMPLATE_INVITE                   => 'template-invite.html.twig',
            self::PASSWORD_RESET                    => 'password-reset.html.twig',
        ];

    /**
     * @var TwigRender
     */
    protected $twig;

    /**
     * @var MailerInterface
     */
    protected $mailer;

    /**
     * @var OrganizationsRepository
     */
    protected $orgRepo;

    /**
     * @var EmailTemplateRepository
     */
    protected $templateRepo;

    /**
     * @var NoSendRepository
     */
    protected $noSendRepo;

    /**
     * @var RouteGeneratorInterface
     */
    protected $routeGenerator;

    /**
     * Constructor
     *
     * @param TwigRender              $twig
     * @param MailerInterface         $mailer
     * @param OrganizationsRepository $orgsRepo
     * @param NoSendRepository        $noSendRepo
     * @param EmailTemplateRepository $emailTemplateRepo
     * @param RouteGeneratorInterface $routeGenerator
     */
    public function __construct(
        TwigRender $twig,
        MailerInterface $mailer,
        OrganizationsRepository $orgsRepo,
        NoSendRepository $noSendRepo,
        EmailTemplateRepository $emailTemplateRepo,
        RouteGeneratorInterface $routeGenerator
    )
    {
        $this->twig           = $twig;
        $this->mailer         = $mailer;
        $this->orgRepo        = $orgsRepo;
        $this->noSendRepo     = $noSendRepo;
        $this->templateRepo   = $emailTemplateRepo;
        $this->routeGenerator = $routeGenerator;
    }

    /**
     * @param string $id
     *
     * @return string
     */
    public function getSubject(string $id): string
    {
        switch ($id) {
            case self::BILLING_TRIAL_ENDING:
                return 'Your Blocks Edit Team trial is almost over';
            case self::BILLING_TRIAL_INTEGRATIONS_ENDING:
                return 'Your Blocks Edit integration trial is almost over';
            case self::BILLING_TRIAL_ENDED:
                return 'Your Blocks Edit Team trial ended.';
            case self::BILLING_TRIAL_INTEGRATIONS_ENDED:
                return 'Your Blocks Edit integration trial ended.';
            case self::BILLING_CREDIT_CARD_DECLINED:
                return 'Blocks Edit: Credit card declined.';
            case self::BILLING_PLAN_PAUSED:
                return 'Blocks Edit: Billing plan paused.';
            case self::WELCOME:
                return 'The welcome email';
            case self::HELP_AND_SUPPORT:
                return 'Help and support';
            case self::WHAT_YOU_CAN_DO:
                return 'What you can do';
            case self::WHAT_YOU_CAN_DO_EXAMPLE:
                return 'Template showcase';
            case self::BILLING_NOTICE:
                return 'Billing and payment';
            case self::ORGANIZATIONS_INVITE:
                return 'You\'ve been added to the {{ orgName }} organization';
            case self::TEMPLATE_INVITE:
                return 'You\'ve been invited to start editing emails';
            case self::PASSWORD_RESET:
                return 'Password reset';
            default:
                return 'Blocks Edit';
        }
    }

    /**
     * @param array|string $to
     *
     * @return int
     * @throws Exception
     */
    public function sendWelcome($to): int
    {
        return $this->send($to, self::WELCOME, []);
    }

    /**
     * @param array|string    $to
     * @param string          $dueDollar
     * @param string          $dueDate
     * @param string          $dueDesc
     * @param string          $discounts
     * @param string          $urlBilling
     * @param CreditCard|null $creditCard
     *
     * @return int
     * @throws Exception
     */
    public function sendBillingNotice(
        $to,
        string $dueDollar,
        string $dueDate,
        string $dueDesc,
        string $discounts,
        string $urlBilling,
        ?CreditCard $creditCard
    ): int
    {
        return $this->send($to, self::BILLING_NOTICE, [
            'dueDollar'  => $dueDollar,
            'dueDate'    => $dueDate,
            'dueDesc'    => $dueDesc,
            'discounts'  => $discounts,
            'creditCard' => $creditCard,
            'urlBilling' => $urlBilling
        ]);
    }

    /**
     * @param array|string $to
     * @param string $from
     * @param string $urlAccept
     * @param string $orgName
     * @param string $access
     *
     * @return int
     * @throws Exception
     */
    public function sendOrganizationInvite(
        $to,
        string $from,
        string $urlAccept,
        string $orgName,
        string $access
    ): int
    {
        return $this->send($to, self::ORGANIZATIONS_INVITE, [
            'from'      => $from,
            'urlAccept' => $urlAccept,
            'orgName'   => $orgName,
            'access'    => $access
        ]);
    }

    /**
     * @param array|string $to
     * @param string $from
     * @param string $urlAccept
     * @param string $urlScreenshot
     * @param string $templateTitle
     *
     * @return int
     * @throws Exception
     */
    public function sendTemplateInvite(
        $to,
        string $from,
        string $urlAccept,
        string $urlScreenshot,
        string $templateTitle
    ): int
    {
        return $this->send($to, self::TEMPLATE_INVITE, [
            'from'          => $from,
            'urlAccept'     => $urlAccept,
            'urlScreenshot' => $urlScreenshot,
            'templateTitle' => $templateTitle
        ]);
    }

    /**
     * @param array|string $to
     * @param string $urlReset
     *
     * @return int
     * @throws Exception
     */
    public function sendPasswordReset($to, string $urlReset): int
    {
        return $this->send($to, self::PASSWORD_RESET, [
            'urlReset' => $urlReset
        ]);
    }

    /**
     * @param string|array $to
     * @param string       $urlBillingPage
     * @param string       $urlExtendTrial
     *
     * @return int
     * @throws Exception
     */
    public function sendBillingTrialEnding($to, string $urlBillingPage, string $urlExtendTrial): int
    {
        return $this->send($to, self::BILLING_TRIAL_ENDING, [
            'urlBillingPage' => $urlBillingPage,
            'urlExtendTrial' => $urlExtendTrial
        ]);
    }

    /**
     * @param string|array $to
     * @param string       $urlBillingPage
     * @param string       $urlExtendTrial
     *
     * @return int
     * @throws Exception
     */
    public function sendBillingTrialIntegrationEnding(
        $to,
        string $urlBillingPage,
        string $urlExtendTrial
    ): int
    {
        return $this->send($to, self::BILLING_TRIAL_INTEGRATIONS_ENDING, [
            'urlBillingPage' => $urlBillingPage,
            'urlExtendTrial' => $urlExtendTrial
        ]);
    }

    /**
     * @param string|array $to
     * @param string       $urlBillingPage
     *
     * @return int
     * @throws Exception
     */
    public function sendBillingTrialEnded($to, string $urlBillingPage): int
    {
        return $this->send($to, self::BILLING_TRIAL_ENDED, [
            'urlBillingPage' => $urlBillingPage
        ]);
    }

    /**
     * @param string|array $to
     * @param string       $urlBillingPage
     *
     * @return int
     * @throws Exception
     */
    public function sendBillingTrialIntegrationsEnded($to, string $urlBillingPage): int
    {
        return $this->send($to, self::BILLING_TRIAL_INTEGRATIONS_ENDED, [
            'urlBillingPage' => $urlBillingPage
        ]);
    }

    /**
     * @param string|array $to
     * @param string       $urlBillingPage
     *
     * @return int
     * @throws Exception
     */
    public function sendBillingCreditCardDeclined($to, string $urlBillingPage): int
    {
        return $this->send($to, self::BILLING_CREDIT_CARD_DECLINED, [
            'urlBillingPage' => $urlBillingPage
        ]);
    }

    /**
     * @param string|array $to
     * @param string       $urlBillingPage
     *
     * @return int
     * @throws Exception
     */
    public function sendBillingPlanPaused($to, string $urlBillingPage): int
    {
        return $this->send($to, self::BILLING_PLAN_PAUSED, [
            'urlBillingPage' => $urlBillingPage
        ]);
    }

    /**
     * @param        $to
     * @param string $user
     * @param string $comment
     * @param string $dateTime
     * @param string $emailTitle
     * @param string $emailUrl
     *
     * @return int
     * @throws Exception
     */
    public function sendNotificationReply(
        $to,
        string $user,
        string $comment,
        string $dateTime,
        string $emailTitle,
        string $emailUrl
    ): int
    {
        return $this->send($to, self::NOTIFICATION_REPLY, [
            'user'       => $user,
            'comment'    => $comment,
            'dateTime'   => $dateTime,
            'emailTitle' => $emailTitle,
            'emailUrl'   => $emailUrl
        ]);
    }

    /**
     * @param        $to
     * @param string $user
     * @param string $comment
     * @param string $dateTime
     * @param string $emailTitle
     * @param string $emailUrl
     *
     * @return int
     * @throws Exception
     */
    public function sendNotificationMention(
        $to,
        string $user,
        string $comment,
        string $dateTime,
        string $emailTitle,
        string $emailUrl
    ): int
    {
        return $this->send($to, self::NOTIFICATION_MENTION, [
            'user'       => $user,
            'comment'    => $comment,
            'dateTime'   => $dateTime,
            'emailTitle' => $emailTitle,
            'emailUrl'   => $emailUrl
        ]);
    }

    /**
     * @param string|array $to
     * @param string $id
     * @param array  $params
     *
     * @return int
     * @throws Exception
     */
    public function send($to, string $id, array $params = []): int
    {
        if (!is_array($to)) {
            $to = [$to];
        }

        $toSend = [];
        foreach($to as $t) {
            if (is_array($t) && isset($t['usr_email'])) {
                $toSend[] = $t['usr_email'];
            } else if (is_array($t) && isset($t['org_id'])) {
                $owners = $this->orgRepo->getOwners($t['org_id']);
                foreach($owners as $owner) {
                    $toSend[] = $owner['usr_email'];
                }
            } else {
                $toSend[] = $t;
            }
        }

        $noSendCheck = false;
        $template    = $this->getEmailTemplate($id);
        if (!$template) {
            $subject = $this->getSubject($id);
        } else {
            $subject     = $template->getSubject();
            $noSendCheck = $template->getNoSendCheck();
        }
        $subject = $this->renderString($subject, $params);

        foreach($toSend as $emailAddress) {
            if ($noSendCheck && !$this->noSendRepo->isSendable($emailAddress)) {
                continue;
            }
            $html = $this->generateHtml($id, $emailAddress, $params);
            if ($html) {
                $this->mailer->quickSend($toSend, $subject, $html);
            }
        }

        return 1;
    }

    /**
     * @param string        $to
     * @param EmailTemplate $template
     * @param array         $params
     *
     * @return int
     * @throws Exception
     */
    public function sendTest(string $to, EmailTemplate $template, array $params = []): int
    {
        $subject = $this->renderString($template->getSubject(), $params);
        $html    = $this->generateHtml($template->getName(), $to, $params, $template);
        if (!$html) {
            return 0;
        }

        return $this->mailer->quickSend($to, $subject, $html);
    }

    /**
     * @param string             $id
     * @param string             $emailAddress
     * @param array              $params
     * @param EmailTemplate|null $template
     *
     * @return string
     * @throws Exception
     */
    protected function generateHtml(
        string $id,
        string $emailAddress,
        array $params,
        ?EmailTemplate $template = null
    ): string
    {
        $params['urlUnsubscribe'] = $this->routeGenerator->generate('unsubscribe', [
            'base64_email' => base64_encode($emailAddress)
        ], 'absolute');

        try {
            if (!$template) {
                $template = $this->getEmailTemplate($id);
            }
            if (!$template || $template->getLocation() === EmailTemplate::LOCATION_DISK) {
                $location = 'emails/' . self::DISK_LOCATIONS[$id];
                $html     = $this->twig->render($location, $params);
            } else if ($template->getLocation() === EmailTemplate::LOCATION_BUILDER) {
                $htmlData = $this->exportService->forSendLink($template->getEmaId());
                $html     = $this->renderString($htmlData->getHtml(), $params);
            } else {
                $html = $template->getContent();
                $html = $this->renderString($html, $params);
            }

            return $html;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return '';
    }

    /**
     * @param string $html
     * @param array  $params
     *
     * @return string
     * @throws Exception
     */
    protected function renderString(string $html, array $params): string
    {
        $loader = new ArrayLoader([
            'template.html.twig' => $html
        ]);
        $twig   = new Environment($loader, [
            'debug'            => false,
            'strict_variables' => false
        ]);

        return $twig->render('template.html.twig', $params);
    }

    /**
     * @param string $name
     *
     * @return EmailTemplate|null
     * @throws Exception
     */
    protected function getEmailTemplate(string $name): ?EmailTemplate
    {
        $template = $this->templateRepo->findByName($name);
        if (!$template) {
            return null;
        }

        return $template;
    }

    /**
     * @var ExportService
     */
    protected $exportService;

    /**
     * @param ExportService $exportService
     */
    public function setExportService(ExportService $exportService)
    {
    	$this->exportService = $exportService;
    }
}
