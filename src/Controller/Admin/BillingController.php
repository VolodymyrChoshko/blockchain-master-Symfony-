<?php
namespace Controller\Admin;

use BlocksEdit\Command\ArgsParser;
use BlocksEdit\Command\Console;
use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\RedirectResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use DateTime;
use Entity\BillingAdjustment;
use Entity\BillingPlan;
use Entity\BillingTransaction;
use Entity\Invoice;
use Entity\InvoiceItem;
use Exception;
use Repository\UserRepository;
use Repository\BillingAdjustmentRepository;
use Repository\BillingLogRepository;
use Repository\BillingPlanRepository;
use Repository\BillingPriceRepository;
use Repository\BillingTransactionRepository;
use Repository\CreditCardRepository;
use Repository\InvoiceItemRepository;
use Repository\InvoiceRepository;
use Repository\OrganizationsRepository;
use Stripe\Charge;
use Stripe\Refund;
use Stripe\Stripe;

/**
 * @IsGranted({"SITE_ADMIN_2FA"})
 * @Route("/admin/billing", name="admin_billing_")
 */
class BillingController extends Controller
{
    /**
     * @Route(name="index")
     *
     * @param Request                      $request
     * @param BillingPlanRepository        $planRepository
     * @param BillingTransactionRepository $billingTransactionRepository
     * @param OrganizationsRepository      $orgsRepository
     * @param UserRepository               $userRepository
     * @param CreditCardRepository         $creditCardRepository
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(
        Request $request,
        BillingPlanRepository $planRepository,
        BillingTransactionRepository $billingTransactionRepository,
        OrganizationsRepository $orgsRepository,
        UserRepository $userRepository,
        CreditCardRepository $creditCardRepository
    ): Response
    {
        $orgNames     = [];
        $orgOwners    = [];
        $billingPlans = [];
        $creditCards  = [];
        $searchOid    = $request->query->get('oid');
        $searchType   = $request->query->get('type');

        /** @var BillingPlan[] $billingPlans */
        if ($searchOid) {
            if (strpos($searchOid, '@') !== false) {
                $user = $userRepository->findByEmail($searchOid);
                if ($user) {
                    $orgs = $orgsRepository->findByUserAndAccess($user['usr_id'], 1);
                    foreach ($orgs as $org) {
                        $billingPlan = $planRepository->findByOrg($org['rba_org_id']);
                        if ($billingPlan) {
                            $billingPlans[] = $billingPlan;
                        }
                    }
                }
            } else {
                if (is_numeric($searchOid)) {
                    $billingPlans = $planRepository->findByOrg($searchOid);
                    if ($billingPlans) {
                        $billingPlans = [$billingPlans];
                    } else {
                        $billingPlans = [];
                    }
                } else {
                    $orgs = $orgsRepository->findByName($searchOid);
                    foreach ($orgs as $org) {
                        $billingPlan = $planRepository->findByOrg($org['org_id']);
                        if ($billingPlan) {
                            $billingPlans[] = $billingPlan;
                        }
                    }
                }
            }

            if ($searchType) {
                $filtered = [];
                foreach($billingPlans as $billingPlan) {
                    if ($billingPlan->getType() === $searchType) {
                        $filtered[] = $billingPlan;
                    }
                }
                $billingPlans = $filtered;
            }
        } else if ($searchType) {
            $billingPlans = $planRepository->findByType($searchType);
        } else {
            $billingPlans = $planRepository->findAll();
        }

        foreach($billingPlans as $billingPlan) {
            $oid = $billingPlan->getOrgId();
            if (!isset($orgNames[$oid])) {
                $org = $orgsRepository->findByID($oid);
                $orgNames[$oid] = $org['org_name'];
            }
            if (!isset($orgOwners[$oid])) {
                $owners = $orgsRepository->getOwners($oid);
                if ($owners) {
                    $orgOwners[$oid] = $owners[0];
                }
            }
            if (!isset($creditCards[$oid])) {
                $creditCard = $creditCardRepository->findActiveCard($oid);
                if ($creditCard) {
                    $creditCards[$oid] = $creditCard;
                }
            }
        }

        $thisMonth          = mktime(0, 0, 0, (int)date('m'), 1, (int)date('Y'));
        $thisMonth          = new DateTime('@' . $thisMonth);
        $totalAmountCents   = $billingTransactionRepository->findTotalAmountCents();
        $monthlyAmountCents = $billingTransactionRepository->findTotalAmountCents($thisMonth);

        return $this->render('admin/billing/index.html.twig', [
            'billingPlans'    => $billingPlans,
            'creditCards'     => $creditCards,
            'orgNames'        => $orgNames,
            'orgOwners'       => $orgOwners,
            'searchOid'       => $searchOid,
            'searchType'      => $searchType,
            'totalEarnings'   => number_format($totalAmountCents / 100, 2),
            'monthlyEarnings' => number_format($monthlyAmountCents / 200, 2)
        ]);
    }

    /**
     * @Route("/create", name="create")
     *
     * @param Request                 $request
     * @param BillingPlanRepository   $billingPlanRepository
     * @param OrganizationsRepository $organizationsRepository
     *
     * @return Response
     * @throws Exception
     */
    public function createAction(
        Request $request,
        BillingPlanRepository $billingPlanRepository,
        OrganizationsRepository $organizationsRepository
    ): Response
    {
        $oid = $request->query->getInt('oid');
        if (!$organizationsRepository->findByID($oid)) {
            $this->flash->error('Organization does not exist.');
            return $this->redirectToRoute('admin_billing_index');
        }

        $billingPlan = $billingPlanRepository->findByOrg($oid);
        if ($billingPlan) {
            $this->flash->error('Billing plan already exists for the organization.');
        } else {
            $billingPlan = (new BillingPlan())
                ->setOrgId($oid);
            $billingPlanRepository->upgradeToTrial($billingPlan);
            $this->flash->success('Billing plan created.');
        }

        return $this->redirectToRoute('admin_billing_plan', ['id' => $oid]);
    }

    /**
     * @Route("/settings", name="settings")
     *
     * @param Request                $request
     * @param BillingPriceRepository $billingPriceRepository
     *
     * @return Response
     * @throws Exception
     */
    public function settingsAction(
        Request $request,
        BillingPriceRepository $billingPriceRepository
    ): Response
    {
        $membershipPrices  = [];
        $integrationPrices = [];

        $prices = $billingPriceRepository->findAll();
        foreach($prices as $price) {
            if (strpos($price->getTarget(), 'membership:') === 0) {
                $membershipPrices[$price->getId()] = $price;
            } else {
                $integrationPrices[$price->getId()] = $price;
            }
        }

        if ($request->isPost()) {
            foreach($request->request->getArray('membership') as $id => $price) {
                $amountCents = $this->toAmountCents($price);
                $membershipPrices[$id]->setAmountCents($amountCents);
                $billingPriceRepository->update($membershipPrices[$id]);
            }

            foreach($request->request->getArray('integration') as $id => $price) {
                $amountCents = $this->toAmountCents($price);
                $integrationPrices[$id]->setAmountCents($amountCents);
                $billingPriceRepository->update($integrationPrices[$id]);
            }

            $this->flash->success('Prices updated.');
            return $this->redirectToRoute('admin_billing_settings');
        }

        return $this->render('admin/billing/settings.html.twig', [
            'membershipPrices'  => $membershipPrices,
            'integrationPrices' => $integrationPrices
        ]);
    }

    /**
     * @Route("/plan/{id}", name="plan")
     *
     * @param int                         $id
     * @param Request                     $request
     * @param BillingPlanRepository       $billingPlanRepository
     * @param CreditCardRepository        $creditCardRepository
     * @param OrganizationsRepository     $orgRepo
     * @param InvoiceRepository           $invoiceRepository
     * @param BillingAdjustmentRepository $billingAdjustmentRepository
     * @param BillingLogRepository        $billingLogRepository
     *
     * @return Response
     * @throws Exception
     */
    public function planAction(
        int $id,
        Request $request,
        BillingPlanRepository $billingPlanRepository,
        CreditCardRepository $creditCardRepository,
        OrganizationsRepository $orgRepo,
        InvoiceRepository $invoiceRepository,
        BillingAdjustmentRepository $billingAdjustmentRepository,
        BillingLogRepository $billingLogRepository
    ): Response
    {
        $org         = $orgRepo->findByID($id);
        $billingPlan = $billingPlanRepository->findByOrg($id);
        if (!$billingPlan) {
            $this->throwNotFound();
        }

        $totalCents = 0;
        $invoices   = $invoiceRepository->findByOrg($billingPlan->getOrgId());
        foreach($invoices as $invoice) {
            $totalCents += $invoice->getAmountCents();
        }

        $billingLogs = $billingLogRepository->findByOrg($billingPlan->getOrgId());
        $creditCard  = $creditCardRepository->findActiveCard($billingPlan->getOrgId());
        $owners      = $orgRepo->getOwners($billingPlan->getOrgId());
        $adjustments = $billingAdjustmentRepository->findByOrg($billingPlan->getOrgId());
        $nextInvoice = $invoiceRepository->generateInvoice($billingPlan, $adjustments);

        if ($request->isPost()) {
            $type                 = $request->request->get('type');
            $chargeDay            = $request->request->getInt('chargeDay');
            $chargeMonth          = $request->request->getInt('chargeMonth');
            $chargeYear           = $request->request->getInt('chargeYear');
            $reoccurringMonths    = $request->request->getInt('reoccurringMonths');
            $isTrialComplete      = $request->request->get('isTrialComplete') === 'on';
            $isPaused             = $request->request->get('isPaused') === 'on';
            $isDeclined           = $request->request->get('isDeclined') === 'on';
            $isDowngraded         = $request->request->get('isDowngraded') === 'on';
            $isTrialNoticeSent    = $request->request->get('isTrialNoticeSent') === 'on';
            $isTrialExtended      = $request->request->get('isTrialExtended') === 'on';
            $isUpcomingNoticeSent = $request->request->get('isUpcomingNoticeSent') === 'on';
            $fixedPrice           = $this->toAmountCents($request->request->get('fixedPrice'));
            $flags                = join(',', array_keys($request->request->getArray('flags')));

            if ($type !== $billingPlan->getType()) {
                $billingLogRepository->createLog(
                    $billingPlan,
                    sprintf('Admin changed billing plan to %s.', strtoupper($type))
                );
            }
            if ($chargeDay !== $billingPlan->getChargeDay()) {
                $billingLogRepository->createLog(
                    $billingPlan,
                    sprintf('Admin changed charge day to %d.', $chargeDay)
                );
            }
            if ($chargeMonth !== $billingPlan->getChargeMonth()) {
                $billingLogRepository->createLog(
                    $billingPlan,
                    sprintf('Admin changed charge month to %d.', $chargeMonth)
                );
            }
            if ($chargeYear !== $billingPlan->getChargeYear()) {
                $billingLogRepository->createLog(
                    $billingPlan,
                    sprintf('Admin changed charge year to %d.', $chargeYear)
                );
            }
            if (!$isTrialComplete && $billingPlan->isTrialComplete()) {
                $billingLogRepository->createLog(
                    $billingPlan,
                    'Admin set trial period to NOT STARTED.'
                );
            }
            if ($isTrialComplete && !$billingPlan->isTrialComplete()) {
                $billingLogRepository->createLog(
                    $billingPlan,
                    'Admin set trial period to COMPLETED.'
                );
            }

            if (!$isTrialExtended && $billingPlan->isTrialExtended()) {
                $billingLogRepository->createLog(
                    $billingPlan,
                    'Admin set trial period extended to OFF.'
                );
            }
            if ($isTrialExtended && !$billingPlan->isTrialExtended()) {
                $billingLogRepository->createLog(
                    $billingPlan,
                    'Admin set trial period extended to ON.'
                );
            }

            if (!$isTrialNoticeSent && $billingPlan->isTrialNoticeSent()) {
                $billingLogRepository->createLog(
                    $billingPlan,
                    'Admin set trial notice to NOT SENT.'
                );
            }
            if ($isTrialNoticeSent && !$billingPlan->isTrialNoticeSent()) {
                $billingLogRepository->createLog(
                    $billingPlan,
                    'Admin set trial notice to SENT.'
                );
            }
            if ($isDowngraded && !$billingPlan->isDowngraded()) {
                $billingLogRepository->createLog(
                    $billingPlan,
                    'Admin set plan to DOWNGRADED.'
                );
            }
            if ($fixedPrice !== $billingPlan->getFixedPriceCents()) {
                $billingLogRepository->createLog(
                    $billingPlan,
                    sprintf('Admin sets fixed price to $%s.', number_format($fixedPrice / 100, 2))
                );
            }
            if ($reoccurringMonths !== $billingPlan->getReoccurringMonths()) {
                $billingLogRepository->createLog(
                    $billingPlan,
                    sprintf('Admin sets billing period to %d month(s).', $reoccurringMonths)
                );
            }

            $billingPlan
                ->setType($type)
                ->setFlags($flags)
                ->setChargeDay($chargeDay)
                ->setChargeYear($chargeYear)
                ->setChargeMonth($chargeMonth)
                ->setIsDowngraded($isDowngraded)
                ->setIsTrialComplete($isTrialComplete)
                ->setIsTrialNoticeSent($isTrialNoticeSent)
                ->setIsTrialExtended($isTrialExtended)
                ->setIsUpcomingNoticeSent($isUpcomingNoticeSent)
                ->setReoccurringMonths($reoccurringMonths);

            if ($fixedPrice && $fixedPrice !== 0.0) {
                $billingPlan->setFixedPriceCents($fixedPrice);
            } else {
                $billingPlan->setFixedPriceCents(0);
            }

            if ($isPaused && !$billingPlan->isPaused()) {
                $billingPlan
                    ->setIsPaused(true)
                    ->setPauseReason('Paused by site staff.')
                    ->setDatePaused(new DateTime());
                $billingLogRepository->createLog($billingPlan, 'Admin PAUSED the billing plan.');
            } else if (!$isPaused && $billingPlan->isPaused()) {
                $billingPlan
                    ->setIsPaused(false)
                    ->setPauseReason('')
                    ->setDatePaused(null);
                $billingLogRepository->createLog(
                    $billingPlan,
                    'Admin UN-PAUSED the billing plan.'
                );
            }

            if ($isDeclined && !$billingPlan->isDeclined()) {
                $billingPlan->setDateDeclined(new DateTime());
                $billingLogRepository->createLog(
                    $billingPlan,
                    'Admin DECLINED the billing plan.'
                );
            } else if (!$isDeclined && $billingPlan->isDeclined()) {
                $billingPlan->setDateDeclined(null);
                $billingLogRepository->createLog(
                    $billingPlan,
                    'Admin UN-DECLINED the billing plan.'
                );
            }

            $billingPlanRepository->update($billingPlan);
            $this->flash->success('Billing plan updated.');

            return $this->redirectToRoute('admin_billing_plan', ['id' => $id]);
        }

        return $this->render('admin/billing/plan.html.twig', [
            'invoices'    => $invoices,
            'totalCents'  => $totalCents,
            'creditCard'  => $creditCard,
            'billingPlan' => $billingPlan,
            'billingLogs' => $billingLogs,
            'adjustments' => $adjustments,
            'nextInvoice' => $nextInvoice,
            'owners'      => $owners,
            'org'         => $org
        ]);
    }

    /**
     * @Route("/adjustment/{id}", name="adjustment")
     *
     * @param int                         $id
     * @param Request                     $request
     * @param BillingPlanRepository       $billingPlanRepository
     * @param BillingAdjustmentRepository $billingAdjustmentRepository
     * @param BillingLogRepository        $billingLogRepository
     * @param OrganizationsRepository     $orgRepo
     *
     * @return Response
     * @throws Exception
     */
    public function adjustmentAction(
        int $id,
        Request $request,
        BillingPlanRepository $billingPlanRepository,
        BillingAdjustmentRepository $billingAdjustmentRepository,
        BillingLogRepository $billingLogRepository,
        OrganizationsRepository $orgRepo
    ): Response
    {
        $org         = $orgRepo->findByID($id);
        $billingPlan = $billingPlanRepository->findByOrg($id);
        if (!$billingPlan) {
            $this->throwNotFound();
        }

        $adjustment = new BillingAdjustment();
        $aid        = $request->query->get('aid');
        if ($aid) {
            $adjustment = $billingAdjustmentRepository->findByID($aid);
            if (!$adjustment) {
                $this->throwNotFound();
            }
        }

        if ($request->isPost()) {
            $description   = $request->request->get('description');
            $amountCents   = $this->toAmountCents($request->request->get('amount'));
            $reason        = $request->request->get('reason');

            $origAmount = $adjustment->getAmountCents();
            $adjustment
                ->setOrgId($id)
                ->setDescription($description)
                ->setReason($reason)
                ->setAmountCents($amountCents)
                ->setRemainingCents($amountCents)
                ->setStatus(BillingAdjustment::STATUS_PENDING);
            if ($adjustment->getId()) {
                $billingAdjustmentRepository->update($adjustment);
                $billingLogRepository->createLog(
                    $billingPlan,
                    sprintf(
                        'Admin updated adjustment from %s to %s',
                        number_format(($origAmount / 100), 2),
                        number_format(($amountCents / 100), 2)
                    )
                );
                $this->flash->success('Adjustment updated.');
            } else {
                $billingAdjustmentRepository->insert($adjustment);
                $billingLogRepository->createLog(
                    $billingPlan,
                    sprintf('Admin created adjustment for %s', number_format(($amountCents / 100), 2))
                );
                $this->flash->success('Adjustment created.');
            }

            return $this->redirectToRoute('admin_billing_plan', ['id' => $id]);
        }

        return $this->render('admin/billing/adjustment.html.twig', [
            'billingPlan' => $billingPlan,
            'adjustment'  => $adjustment,
            'org'         => $org,
            'aid'         => $aid
        ]);
    }

    /**
     * @Route("/adjustment/{id}/remove", name="adjustment_remove")
     *
     * @param int                         $id
     * @param BillingAdjustmentRepository $billingAdjustmentRepository
     * @param BillingLogRepository        $billingLogRepository
     * @param BillingPlanRepository       $billingPlanRepository
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function adjustmentRemoveAction(
        int $id,
        BillingAdjustmentRepository $billingAdjustmentRepository,
        BillingLogRepository $billingLogRepository,
        BillingPlanRepository $billingPlanRepository
    ): RedirectResponse {
        $adjustment = $billingAdjustmentRepository->findByID($id);
        if (!$adjustment) {
            $this->throwNotFound();
        }

        $billingPlan = $billingPlanRepository->findByOrg($adjustment->getOrgId());
        $billingAdjustmentRepository->delete($adjustment);
        $billingLogRepository->createLog(
            $billingPlan,
            sprintf('Admin removed adjustment for %s', number_format(($adjustment->getAmountCents() / 100), 2))
        );
        $this->flash->success('Adjustment removed.');

        return $this->redirectToRoute('admin_billing_plan', ['id' => $adjustment->getOrgId()]);
    }

    /**
     * @Route("/invoices/{id}/create", name="invoice_create")
     *
     * @param int                          $id
     * @param Request                      $request
     * @param InvoiceRepository            $invoiceRepository
     * @param InvoiceItemRepository        $invoiceItemRepository
     * @param BillingPlanRepository        $billingPlanRepository
     * @param BillingTransactionRepository $billingTransactionRepository
     * @param OrganizationsRepository      $organizationsRepository
     * @param CreditCardRepository         $creditCardRepository
     *
     * @return Response
     * @throws Exception
     */
    public function invoiceCreateAction(
        int $id,
        Request $request,
        InvoiceRepository $invoiceRepository,
        InvoiceItemRepository $invoiceItemRepository,
        BillingPlanRepository $billingPlanRepository,
        BillingTransactionRepository $billingTransactionRepository,
        OrganizationsRepository $organizationsRepository,
        CreditCardRepository $creditCardRepository
    ): Response
    {
        $org = $organizationsRepository->findByID($id);
        if (!$org) {
            $this->throwNotFound();
        }

        $invoice = (new Invoice())
            ->setOrgId($id)
            ->setDatePeriodStart(new DateTime())
            ->setDatePeriodEnd(new DateTime())
            ->setId($invoiceRepository->getMaxID() + 1);

        if ($request->isPost()) {
            if ($request->request->get('action') === 'create') {
                $description  = $request->request->get('description');
                $created      = $request->request->getArray('created');
                $start        = $request->request->getArray('start');
                $end          = $request->request->getArray('end');
                $descriptions = $request->request->getArray('descriptions');
                $amounts      = $request->request->getArray('amounts');
                $notes        = $request->request->get('notes');

                $created = mktime(0, 0, 0, $created['month'], $created['day'], $created['year']);
                $start   = mktime(0, 0, 0, $start['month'], $start['day'], $start['year']);
                $end     = mktime(0, 0, 0, $end['month'], $end['day'], $end['year']);

                $invoice
                    ->setDescription($description)
                    ->setNotes($notes)
                    ->setDateCreated(new DateTime('@' . $created))
                    ->setDatePeriodStart(new DateTime('@' . $start))
                    ->setDatePeriodEnd(new DateTime('@' . $end))
                    ->setStatus(Invoice::STATUS_PAID);

                $due   = 0;
                $items = [];
                foreach($descriptions as $i => $description) {
                    if ($description) {
                        $amount  = $this->toAmountCents($amounts[$i]);
                        $due     += $amount;
                        $items[] = (new InvoiceItem())
                            ->setDescription($description)
                            ->setAmountCents($amount)
                            ->setType($amount > 0 ? InvoiceItem::TYPE_CHARGE : InvoiceItem::TYPE_DISCOUNT);
                    }
                }
                $invoice->setItems($items);
                $invoice->setAmountCents($due);

                $creditCard = $creditCardRepository->findActiveCard($invoice->getOrgId());

                return $this->render('admin/billing/invoice-preview.html.twig', [
                    'org'        => $org,
                    'invoice'    => $invoice,
                    'encoded'    => base64_encode(serialize($invoice)),
                    'creditCard' => $creditCard
                ]);
            } else {
                /** @var Invoice $invoice */
                $encoded     = $request->request->get('encoded');
                $invoice     = unserialize(base64_decode($encoded));
                $chargeChard = $request->request->get('chargeCard') === 'on';
                $invoice->setId(0);

                $creditCard = null;
                if ($chargeChard) {
                    $creditCard = $creditCardRepository->findActiveCard($invoice->getOrgId());
                    if (!$creditCard) {
                        $this->flash->error('Organization does not have a credit card.');

                        return $this->render('admin/billing/invoice-preview.html.twig', [
                            'org'     => $org,
                            'invoice' => $invoice,
                            'encoded' => base64_encode(serialize($invoice))
                        ]);
                    }

                    Stripe::setApiKey($this->config->stripe['secret']);
                    $charge = Charge::create(
                        [
                            'customer' => $creditCard->getStripeId(),
                            'amount'   => $invoice->getAmountCents(),
                            'currency' => 'usd'
                        ]
                    );

                    $billingTransaction = (new BillingTransaction())
                        ->setOrgId($invoice->getOrgId())
                        ->setCreditCardId($creditCard->getId())
                        ->setAmountCents($invoice->getAmountCents())
                        ->setTransactionId($charge->id);
                    $billingTransaction->setCreditCardId($creditCard->getId());
                    $billingTransactionRepository->insert($billingTransaction);
                    $invoice->setBillingTransactionId($billingTransaction->getId());
                } else {
                    $invoice->setBillingTransactionId(null);
                }

                $invoiceRepository->insert($invoice);
                foreach($invoice->getItems() as $invoiceItem) {
                    $invoiceItem->setInvoiceId($invoice->getId());
                    $invoiceItemRepository->insert($invoiceItem);
                }

                $billingPlan = $billingPlanRepository->findByOrg($invoice->getOrgId());
                $file        = $invoiceRepository->generateInvoicePDF($invoice, $billingPlan, $creditCard);
                $fileUrl     = $invoiceRepository->uploadInvoicePDF($invoice, $file);
                $invoice->setFileUrl($fileUrl);
                $invoiceRepository->update($invoice);
                $this->flash->success('Invoice created.');

                return $this->redirectToRoute('admin_billing_plan', ['id' => $invoice->getOrgId()]);
            }
        }

        return $this->render('admin/billing/invoice-create.html.twig', [
            'org'     => $org,
            'invoice' => $invoice
        ]);
    }

    /**
     * @Route("/invoices/{id}/preview", name="invoice_preview_iframe")
     *
     * @param Request                 $request
     * @param OrganizationsRepository $organizationsRepository
     *
     * @return Response
     * @throws Exception
     */
    public function invoicePreviewIframeAction(
        Request $request,
        OrganizationsRepository $organizationsRepository
    ): Response
    {
        /** @var Invoice $invoice */
        /** @phpstan-ignore-next-line */
        $encoded = $request->query->get('encoded');
        $invoice = unserialize(base64_decode($encoded));
        $org     = $organizationsRepository->findByID($invoice->getOrgId());

        return $this->render('billing/blocks/invoice.html.twig', [
            'org'          => $org,
            'invoice'      => $invoice,
            'invoiceItems' => $invoice->getItems(),
            'creditCard'   => null
        ]);
    }

    /**
     * @Route("/invoices/{id}", name="invoice_details")
     *
     * @param int                          $id
     * @param InvoiceRepository            $invoiceRepository
     * @param InvoiceItemRepository        $invoiceItemRepository
     * @param BillingTransactionRepository $billingTransactionRepository
     * @param CreditCardRepository         $creditCardRepository
     *
     * @return Response
     * @throws Exception
     */
    public function invoiceDetailsAction(
        int $id,
        InvoiceRepository $invoiceRepository,
        InvoiceItemRepository $invoiceItemRepository,
        BillingTransactionRepository $billingTransactionRepository,
        CreditCardRepository $creditCardRepository
    ): Response
    {
        $invoice = $invoiceRepository->findByID($id);
        if (!$invoice) {
            $this->throwNotFound();
        }

        $items = $invoiceItemRepository->findByInvoice($id);
        $invoice->setItems($items);

        $creditCard         = null;
        $billingTransaction = null;
        if ($invoice->getBillingTransactionId()) {
            $billingTransaction = $billingTransactionRepository->findByID($invoice->getBillingTransactionId());
            if ($billingTransaction->getCreditCardId()) {
                $creditCard = $creditCardRepository->findByID($billingTransaction->getCreditCardId());
            }
        }

        return $this->render('admin/billing/invoice-details.html.twig', [
            'invoice'            => $invoice,
            'creditCard'         => $creditCard,
            'billingTransaction' => $billingTransaction
        ]);
    }

    /**
     * @Route("/card/{id}/remove", name="card_remove")
     *
     * @param int                     $id
     * @param OrganizationsRepository $orgRepo
     * @param CreditCardRepository    $creditCardRepository
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function removeCardAction(
        int $id,
        OrganizationsRepository $orgRepo,
        CreditCardRepository $creditCardRepository
    ): RedirectResponse
    {
        $org        = $orgRepo->findByID($id);
        $creditCard = $creditCardRepository->findActiveCard($org['org_id']);
        if ($creditCard) {
            $creditCardRepository->delete($creditCard);
            $this->flash->success('Credit card removed.');
        } else {
            $this->flash->error('Card not found');
        }

        return $this->redirectToRoute('admin_billing_plan', ['id' => $id]);
    }

    /**
     * @Route("/transactions/{id}/refund", name="transactions_refund")
     *
     * @param int                          $id
     * @param InvoiceRepository            $invoiceRepository
     * @param BillingTransactionRepository $billingTransactionRepository
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function refundTransactionAction(
        int $id,
        InvoiceRepository $invoiceRepository,
        BillingTransactionRepository $billingTransactionRepository
    ): RedirectResponse {
        $invoice = $invoiceRepository->findByID($id);
        if (!$invoice || !$invoice->getBillingTransactionId()) {
            $this->throwNotFound();
        }
        $transaction = $billingTransactionRepository->findByID($invoice->getBillingTransactionId());
        if (!$transaction) {
            $this->throwNotFound();
        }

        Stripe::setApiKey($this->config->stripe['secret']);
        Refund::create([
            'charge' => $transaction->getTransactionId()
        ]);

        $invoiceRepository->delete($invoice);
        $billingTransactionRepository->delete($transaction);
        $this->flash->success('Transaction refunded.');

        return $this->redirectToRoute('admin_billing_index');
    }

    /**
     * @Route("/crons", name="crons")
     *
     * @throws Exception
     */
    public function cronsAction(): JsonResponse
    {
        $parser = new ArgsParser();
        $runner = new Console($this->container);
        $runner->run($parser->parse(['bin/console', 'billing:payments:exec']));

        return $this->json('ok');
    }

    /**
     * @param mixed $value
     *
     * @return int
     */
    protected function toAmountCents($value): int
    {
        $fixedPrice = str_replace(',', '', $value);

        return (int)((float)$fixedPrice * 100);
    }
}
