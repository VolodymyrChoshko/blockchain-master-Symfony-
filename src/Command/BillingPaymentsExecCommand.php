<?php
namespace Command;

use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use BlocksEdit\Command\InputInterface;
use BlocksEdit\Command\OutputInterface;
use BlocksEdit\Email\EmailSender;
use BlocksEdit\Email\MailerInterface;
use BlocksEdit\Http\RouteGeneratorInterface;
use BlocksEdit\Twig\TwigRender;
use BlocksEdit\Util\Tokens;
use BlocksEdit\Util\TokensTrait;
use DateTime;
use Entity\BillingPlan;
use Entity\BillingTransaction;
use Entity\CreditCard;
use Entity\Invoice;
use Exception;
use Repository\OrganizationAccessRepository;
use Repository\UserRepository;
use Repository\BillingAdjustmentRepository;
use Repository\BillingLogRepository;
use Repository\BillingPlanRepository;
use Repository\BillingTransactionRepository;
use Repository\CreditCardRepository;
use Repository\InvoiceItemRepository;
use Repository\InvoiceRepository;
use Repository\OrganizationsRepository;
use Repository\SourcesRepository;
use RuntimeException;
use Stripe\Charge;
use Stripe\Error\Card;
use Stripe\Refund;
use Stripe\Stripe;
use PDO;
use Throwable;

/**
 * Class BillingPaymentsExecCommand
 */
class BillingPaymentsExecCommand extends Command
{
    use TokensTrait;

    static $name = 'billing:payments:exec';

    const TRIAL_ENDING_SOON_DAYS = 5;
    const DECLINED_GRACE_DAYS = 5;
    const PAYMENT_UPCOMING_DAYS = 5;

    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var BillingPlanRepository
     */
    protected $billingPlanRepo;

    /**
     * @var BillingTransactionRepository
     */
    protected $billingTransactionRepo;

    /**
     * @var BillingLogRepository
     */
    protected $billingLogRepo;

    /**
     * @var BillingAdjustmentRepository
     */
    protected $billingAdjustmentRepo;

    /**
     * @var CreditCardRepository
     */
    protected $creditCardRepo;

    /**
     * @var InvoiceRepository
     */
    protected $invoiceRepo;

    /**
     * @var InvoiceItemRepository
     */
    protected $invoiceItemRepo;

    /**
     * @var SourcesRepository
     */
    protected $sourcesRepo;

    /**
     * @var OrganizationsRepository
     */
    protected $orgRepo;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var OrganizationAccessRepository
     */
    protected $organizationAccessRepository;

    /**
     * @var TwigRender
     */
    protected $twig;

    /**
     * @var EmailSender
     */
    protected $emailSender;

    /**
     * @var MailerInterface
     */
    protected $mailer;

    /**
     * @var RouteGeneratorInterface
     */
    protected $routeGenerator;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Constructor
     *
     * @param PDO                          $pdo
     * @param TwigRender                   $twig
     * @param MailerInterface              $mailer
     * @param EmailSender                  $emailSender
     * @param RouteGeneratorInterface      $routeGenerator
     * @param OrganizationsRepository      $organizationsRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     * @param CreditCardRepository         $creditCardRepo
     * @param BillingPlanRepository        $billingPlanRepo
     * @param BillingTransactionRepository $billingTransactionRepo
     * @param BillingAdjustmentRepository  $billingAdjustmentRepo
     * @param BillingLogRepository         $billingLogRepo
     * @param SourcesRepository            $sourcesRepository
     * @param InvoiceRepository            $invoiceRepo
     * @param InvoiceItemRepository        $invoiceItemRepo
     * @param UserRepository               $userRepository
     */
    public function __construct(
        PDO $pdo,
        TwigRender $twig,
        MailerInterface $mailer,
        EmailSender $emailSender,
        RouteGeneratorInterface $routeGenerator,
        OrganizationsRepository $organizationsRepository,
        OrganizationAccessRepository $organizationAccessRepository,
        CreditCardRepository $creditCardRepo,
        BillingPlanRepository $billingPlanRepo,
        BillingTransactionRepository $billingTransactionRepo,
        BillingAdjustmentRepository $billingAdjustmentRepo,
        BillingLogRepository $billingLogRepo,
        SourcesRepository $sourcesRepository,
        InvoiceRepository $invoiceRepo,
        InvoiceItemRepository $invoiceItemRepo,
        UserRepository $userRepository
    )
    {
        $this->pdo = $pdo;
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->emailSender = $emailSender;
        $this->routeGenerator = $routeGenerator;
        $this->orgRepo = $organizationsRepository;
        $this->creditCardRepo = $creditCardRepo;
        $this->billingPlanRepo = $billingPlanRepo;
        $this->billingTransactionRepo = $billingTransactionRepo;
        $this->billingAdjustmentRepo = $billingAdjustmentRepo;
        $this->organizationAccessRepository = $organizationAccessRepository;
        $this->billingLogRepo = $billingLogRepo;
        $this->sourcesRepo = $sourcesRepository;
        $this->invoiceRepo = $invoiceRepo;
        $this->invoiceItemRepo = $invoiceItemRepo;
        $this->userRepository = $userRepository;
    }

    /**
     * {@inheritDoc}
     */
    public static function getHelp(): string
    {
        return 'Process billing payments.';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Args $args, OutputInterface $output, InputInterface $input)
    {
        $this->output = $output;

        // Charge credit cards for plans due today.
        $billingPlans = $this->billingPlanRepo->findDueNow();
        $this->logger->notice('Billing Cron: found ' . count($billingPlans) . ' billing plans due today.');
        foreach($billingPlans as $billingPlan) {
            if (!$billingPlan->isPaused() && !$billingPlan->isDeclined()) {
                try {
                    if ($billingPlan->isTrial() || $billingPlan->isTrialIntegration()) {
                        $this->processTrialEnded($billingPlan);
                    }
                    $this->processChargeCard($billingPlan);
                } catch (Exception $e) {
                    $this->createBillingLog($billingPlan, $e->getMessage());
                }
            }
        }

        // Trials that are ending in 5 days.
        $billingPlans = $this->billingPlanRepo->findTrialEndingSoon(self::TRIAL_ENDING_SOON_DAYS);
        $this->logger->notice('Billing Cron: found ' . count($billingPlans) . ' trial plans ending soon.');
        foreach($billingPlans as $billingPlan) {
            try {
                $this->processTrialEndingSoon($billingPlan);
            } catch (Exception $e) {
                $this->createBillingLog($billingPlan, $e->getMessage());
            }
        }

        // Plans that were declined 5 days ago.
        $billingPlans = $this->billingPlanRepo->findDeclinedDaysAgo(self::DECLINED_GRACE_DAYS);
        $this->logger->notice('Billing Cron: found ' . count($billingPlans) . ' declined plans moving to paused.');
        foreach($billingPlans as $billingPlan) {
            try {
                $this->processDeclinedToPaused($billingPlan);
            } catch (Exception $e) {
                $this->createBillingLog($billingPlan, $e->getMessage());
            }
        }

        // Plans with due dates coming up in 5 days.
        $billingPlans = $this->billingPlanRepo->findDueInDays(self::PAYMENT_UPCOMING_DAYS);
        $this->logger->notice('Billing Cron: found ' . count($billingPlans) . ' plans with upcoming payments.');
        foreach($billingPlans as $billingPlan) {
            try {
                $this->processUpcomingPayment($billingPlan);
            } catch (Exception $e) {
                $this->createBillingLog($billingPlan, $e->getMessage());
            }
        }
    }

    /**
     * @param BillingPlan $billingPlan
     *
     * @throws Exception
     */
    protected function processTrialEndingSoon(BillingPlan $billingPlan)
    {
        $this->createBillingLog($billingPlan, 'Warning org owners that trial ends soon.');

        $urlBillingPage = $this->generateUrl('billing', $billingPlan);
        if ($billingPlan->isTrialExtended()) {
            $urlExtendTrial = '';
        } else {
            $token = $this->tokens->generateToken(
                $billingPlan->getOrgId(),
                Tokens::TOKEN_EXTEND_TRIAL
            );
            $urlExtendTrial = $this->generateUrl('billing_extend', $billingPlan);
            $urlExtendTrial .= "?t=$token";
        }

        if ($billingPlan->isTrialIntegration()) {
            $this->emailSender->sendBillingTrialIntegrationEnding(
                $this->getOrgEmailAddress($billingPlan),
                $urlBillingPage,
                $urlExtendTrial
            );
        } else {
            $this->emailSender->sendBillingTrialEnding(
                $this->getOrgEmailAddress($billingPlan),
                $urlBillingPage,
                $urlExtendTrial
            );
        }

        $billingPlan->setIsTrialNoticeSent(true);
        $this->billingPlanRepo->update($billingPlan);
    }

    /**
     * @param BillingPlan $billingPlan
     *
     * @throws Exception
     */
    protected function processTrialEnded(BillingPlan $billingPlan)
    {
        $this->createBillingLog($billingPlan, 'Ended trial period and set plan to SOLO');

        $billingPlan
            ->setType(BillingPlan::TYPE_TEAM)
            ->setIsTrialComplete(true);
        $this->billingPlanRepo->update($billingPlan);

        if ($billingPlan->isTrialIntegration()) {
            $this->emailSender->sendBillingTrialIntegrationsEnded(
                $this->getOrgEmailAddress($billingPlan),
                $this->generateUrl('billing', $billingPlan)
            );
        } else {
            $this->emailSender->sendBillingTrialEnded(
                $this->getOrgEmailAddress($billingPlan),
                $this->generateUrl('billing', $billingPlan)
            );
        }
    }

    /**
     * @param BillingPlan $billingPlan
     *
     * @throws Exception
     */
    protected function processChargeCard(BillingPlan $billingPlan)
    {
        $this->createBillingLog(
            $billingPlan,
            sprintf(
                'Processing customer billing plan due on month %d and day %d.',
                $billingPlan->getChargeMonth(),
                $billingPlan->getChargeDay()
            )
        );

        $adjustments = $this->billingAdjustmentRepo->findUnAppliedByOrg($billingPlan->getOrgId());
        $invoice  = $this->invoiceRepo->generateInvoice($billingPlan, $adjustments);
        $chargeId = '';

        $creditCard = $this->creditCardRepo->findActiveCard($billingPlan->getOrgId());
        if ($invoice->getAmountCents() > 0 && !$creditCard) {
            $billingPlan->setDateDeclined(new DateTime());
            $this->billingPlanRepo->update($billingPlan);
            $this->createBillingLog($billingPlan, 'Set plan to DECLINED because card not found.');
            $this->output->errorLine(
                sprintf('Org %d does not have a credit card on file.', $billingPlan->getOrgId())
            );
            return;
        }

        try {
            $this->creditCardRepo->beginTransaction();

            if ($invoice->getAmountCents() > 0) {
                Stripe::setApiKey($this->config->stripe['secret']);
                $charge = Charge::create(
                    [
                        'customer' => $creditCard->getStripeId(),
                        'amount'   => $invoice->getAmountCents(),
                        'currency' => 'usd'
                    ]
                );
                if (empty($charge->id)) {
                    throw new Exception('Stripe did not return a charge ID');
                }
                $chargeId = $charge->id;
            } else {
                $chargeId = 'x';
            }

            $this->createBillingLog(
                $billingPlan,
                sprintf('Charged card amount $%s, Stripe charge ID: %s', $invoice->getAmountCents() / 100, $chargeId)
            );

            $billingTransaction = (new BillingTransaction())
                ->setOrgId($billingPlan->getOrgId())
                ->setAmountCents($invoice->getAmountCents())
                ->setCreditCardId($creditCard->getId())
                ->setTransactionId($chargeId);
            $this->billingTransactionRepo->insert($billingTransaction);

            $invoice->setBillingTransactionId($billingTransaction->getId());
            $this->invoiceRepo->insert($invoice);
            foreach ($invoice->getItems() as $invoiceItem) {
                $invoiceItem->setInvoiceId($invoice->getId());
                $this->invoiceItemRepo->insert($invoiceItem);
            }
            $this->uploadInvoice($invoice, $billingPlan, $creditCard);

            foreach($adjustments as $adjustment) {
                $this->billingAdjustmentRepo->update($adjustment);
            }

            $billingPlan
                ->setChargeMonth($this->getNextMonth($billingPlan))
                ->setChargeYear($this->getNextYear($billingPlan))
                ->setIsPaused(false)
                ->setPauseReason('')
                ->setDateDeclined(null)
                ->setDatePaused(null)
                ->setIsUpcomingNoticeSent(false);
            $this->billingPlanRepo->update($billingPlan);

            $this->creditCardRepo->commit();
            $this->output->writeLine('Finished.');
        } catch (Card $e) {
            $this->creditCardRepo->rollBack();
            $this->onCardError($billingPlan, $e);
        } catch (Throwable $e) {
            $this->creditCardRepo->rollBack();
            $this->alert($e);

            if ($chargeId) {
                try {
                    $this->createBillingLog(
                        $billingPlan,
                        'Refunding because of system error. ' . $e->getMessage()
                    );
                    Refund::create([
                        'charge' => $chargeId
                    ]);
                } catch (Throwable $e) {
                    $this->alert($e);
                }
            }
        }
    }

    /**
     * @param BillingPlan $billingPlan
     *
     * @throws Exception
     */
    protected function processDeclinedToPaused(BillingPlan $billingPlan)
    {
        $billingPlan->setIsPaused(true);
        $this->billingPlanRepo->update($billingPlan);
        $this->emailSender->sendBillingPlanPaused(
            $this->getOrgEmailAddress($billingPlan),
            $this->generateUrl('billing', $billingPlan)
        );
        $this->createBillingLog($billingPlan, 'Set plan from DECLINED to PAUSED.');
    }

    /**
     * @param BillingPlan $billingPlan
     *
     * @throws Exception
     */
    protected function processUpcomingPayment(BillingPlan $billingPlan)
    {
        if ($billingPlan->isUpcomingNoticeSent()) {
            return;
        }

        $creditCard  = $this->creditCardRepo->findActiveCard($billingPlan->getOrgId());
        $adjustments = $this->billingAdjustmentRepo->findUnAppliedByOrg($billingPlan->getOrgId());
        $nextInvoice = $this->invoiceRepo->generateInvoice($billingPlan, $adjustments);

        $this->emailSender->sendBillingNotice(
            $this->getOrgEmailAddress($billingPlan),
            number_format($nextInvoice->getAmountCents() / 100, 2),
            $billingPlan->getNextBillingDate()->format('F j, Y'),
            $nextInvoice->getDescription(),
            '',
            $this->generateUrl('billing', $billingPlan),
            $creditCard
        );

        $billingPlan->setIsUpcomingNoticeSent(true);
        $this->billingPlanRepo->update($billingPlan);
        $this->createBillingLog($billingPlan, 'Sent upcoming bill email.');
    }

    /**
     * @param Invoice       $invoice
     * @param BillingPlan   $billingPlan
     * @param CreditCard    $creditCard
     *
     * @throws Throwable
     */
    protected function uploadInvoice(
        Invoice $invoice,
        BillingPlan $billingPlan,
        CreditCard $creditCard
    )
    {
        $this->output->writeLine('Uploading invoice...');

        $temp = $this->invoiceRepo->generateInvoicePDF($invoice, $billingPlan, $creditCard);
        $url  = $this->invoiceRepo->uploadInvoicePDF($invoice, $temp);
        $invoice->setFileUrl($url);
        $this->invoiceRepo->update($invoice);
        @unlink($temp);
    }

    /**
     * @param BillingPlan $billingPlan
     *
     * @return mixed
     * @throws Exception
     */
    protected function getOrgEmailAddress(BillingPlan $billingPlan)
    {
        $ownerId = $this->organizationAccessRepository->findInitialOwnerID($billingPlan->getOrgId());
        if (!$ownerId) {
            throw new RuntimeException('Count not determine owner of org ' . $billingPlan->getOrgId());
        }
        $owner = $this->userRepository->findByID($ownerId);
        if (!$owner) {
            throw new RuntimeException('Count not determine owner of org ' . $billingPlan->getOrgId());
        }

        return $owner['usr_email'];
    }

    /**
     * @param BillingPlan $billingPlan
     * @param Card        $e
     *
     * @throws Exception
     */
    protected function onCardError(BillingPlan $billingPlan, Card $e)
    {
        if ($billingPlan->getDateDeclined()) {
            $this->createBillingLog($billingPlan, 'Set plan to PAUSED because card declined.');

            $billingPlan
                ->setIsPaused(true)
                ->setPauseReason($e->getMessage())
                ->setDatePaused(new DateTime());
            $this->emailSender->sendBillingPlanPaused(
                $this->orgRepo->getOwners($billingPlan->getOrgId()),
                $this->generateUrl('billing', $billingPlan)
            );
        } else {
            $this->createBillingLog($billingPlan, 'Set plan to DECLINED because card declined.');

            $billingPlan
                ->setIsPaused(false)
                ->setPauseReason($e->getMessage())
                ->setDateDeclined(new DateTime());
            $this->emailSender->sendBillingCreditCardDeclined(
                $this->orgRepo->getOwners($billingPlan->getOrgId()),
                $this->generateUrl('billing', $billingPlan)
            );
        }

        $this->billingPlanRepo->update($billingPlan);
    }

    /**
     * @param BillingPlan $billingPlan
     *
     * @return int
     */
    protected function getNextMonth(BillingPlan $billingPlan): int
    {
        $months = $billingPlan->getReoccurringMonths();
        $nextMonth = new DateTime("$months month");

        return (int)$nextMonth->format('n');
    }

    /**
     * @param BillingPlan $billingPlan
     *
     * @return int
     */
    protected function getNextYear(BillingPlan $billingPlan): int
    {
        $months = $billingPlan->getReoccurringMonths();
        $nextMonth = new DateTime("$months month");

        return (int)$nextMonth->format('Y');
    }

    /**
     * @param BillingPlan $billingPlan
     * @param string      $message
     *
     * @throws Exception
     */
    protected function createBillingLog(BillingPlan $billingPlan, string $message)
    {
        $this->billingLogRepo->createLog($billingPlan, 'Billing Cron: ' . $message . ': ' . ((string)gethostname()));
        $this->output->writeLine($message);
    }

    /**
     * @param Throwable $e
     */
    protected function alert(Throwable $e)
    {
        $this->output->errorLine($e->getMessage());
        $this->logger->alert($e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * @param string      $name
     * @param BillingPlan $billingPlan
     *
     * @return mixed
     * @throws Exception
     */
    protected function generateUrl(string $name, BillingPlan $billingPlan)
    {
        return $this->routeGenerator->generate(
            $name,
            [],
            'absolute',
            $billingPlan->getOrgId()
        );
    }
}
