<?php
namespace Controller\Account;

use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\RedirectResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use BlocksEdit\System\Serializer;
use BlocksEdit\Util\Tokens;
use BlocksEdit\Util\TokensTrait;
use Entity\CreditCard;
use Entity\User;
use Exception;
use Repository\BillingAdjustmentRepository;
use Repository\BillingPlanRepository;
use Repository\BillingTransactionRepository;
use Repository\CreditCardRepository;
use Repository\InvoiceItemRepository;
use Repository\InvoiceRepository;
use Repository\OrganizationsRepository;
use Stripe\Customer;
use Stripe\Stripe;

/**
 * @IsGranted({"USER"})
 */
class BillingController extends Controller
{
    use TokensTrait;

    /**
     * @Route("/billing", name="billing")
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(Request $request): Response
    {
        return $this->renderFrontend($request);
    }

    /**
     * @Route("/api/v1/billing", name="api_v1_billing")
     *
     * @param int                         $oid
     * @param User                        $user
     * @param Serializer                  $serializer
     * @param CreditCardRepository        $creditCardRepository
     * @param InvoiceRepository           $invoiceRepository
     * @param BillingAdjustmentRepository $billingAdjustmentRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function loadAction(
        int $oid,
        User $user,
        Serializer $serializer,
        CreditCardRepository $creditCardRepository,
        InvoiceRepository $invoiceRepository,
        BillingAdjustmentRepository $billingAdjustmentRepository
    ): JsonResponse
    {
        $billingPlan = $this->getBillingPlan();
        $creditCard  = $creditCardRepository->findActiveCard($oid);
        $adjustments = $billingAdjustmentRepository->findUnAppliedByOrg($oid);
        $invoices    = $invoiceRepository->findByOrg($oid);
        $nextInvoice = $invoiceRepository->generateInvoice($billingPlan, $adjustments);
        $nonce       = $this->nonce->generate('membershipUpgrade');

        $sInvoices = [];
        foreach($invoices as $invoice) {
            $sInvoices[] = $serializer->serializeInvoice($invoice, $user);
        }

        return $this->json([
            'billingPlan'     => $serializer->serializeBillingPlan($billingPlan, $user),
            'creditCard'      => $serializer->serializeCreditCard($creditCard),
            'invoices'        => $sInvoices,
            'nextInvoice'     => $serializer->serializeInvoice($nextInvoice, $user),
            'nonce'           => $nonce,
            'stripePublicKey' => $this->config->stripe['public'],
        ]);
    }

    /**
     * @Route("/billing/upgrade", name="billing_upgrade")
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function upgradeAction(Request $request): Response
    {
        return $this->renderFrontend($request);
    }

    /**
     * @Route("/api/v1/billing/upgrade", name="api_v1_billing_upgrade")
     *
     * @param int                   $oid
     * @param CreditCardRepository  $creditCardRepository
     * @param BillingPlanRepository $billingPlanRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function apiUpgradeAction(
        int $oid,
        CreditCardRepository $creditCardRepository,
        BillingPlanRepository $billingPlanRepository
    ): JsonResponse
    {
        $creditCard = $creditCardRepository->findActiveCard($oid);
        if (!$creditCard) {
            return $this->json([
                'error' => 'No credit card on file.'
            ]);
        }

        $billingPlan = $this->getBillingPlan();
        if ($billingPlan->isTeam()) {
            return $this->json([
                'error' => 'Already upgraded.'
            ]);
        }

        $billingPlanRepository->upgradeToTeam($billingPlan);

        return $this->json([
            'success' => 'Upgraded to Blocks Edit Team.'
        ]);
    }

    /**
     * @Route("/billing/downgrade", name="billing_downgrade")
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function downgradeAction(Request $request): Response {
        return $this->renderFrontend($request);
    }

    /**
     * @Route("/api/v1/billing/downgrade", name="api_v1_billing_downgrade", methods={"POST"})
     *
     * @param BillingPlanRepository $billingPlanRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function apiDowngradeAction(
        BillingPlanRepository $billingPlanRepository
    ): JsonResponse
    {
        $billingPlan = $this->getBillingPlan();
        if (!$billingPlan->isTeam()) {
            return $this->json([
                'error' => 'Already downgraded.'
            ]);
        }

        $billingPlanRepository->downgradeToSolo($billingPlan);

        return $this->json([
            'success' => 'Downgraded to Blocks Edit Solo.'
        ]);
    }

    /**
     * @Route("/billing/extend", name="billing_extend")
     *
     * @param int                   $oid
     * @param Request               $request
     * @param BillingPlanRepository $billingPlanRepository
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function extendAction(int $oid, Request $request, BillingPlanRepository $billingPlanRepository): RedirectResponse
    {
        $token = $request->query->get('t');
        if (!$token || $this->tokens->verifyToken($oid, Tokens::TOKEN_EXTEND_TRIAL, $token)) {
            $this->throwNotFound();
        }

        $billingPlan = $this->getBillingPlan();
        if (($billingPlan->isTrial() || $billingPlan->isTrialIntegration()) && !$billingPlan->isTrialComplete()) {
            $billingPlanRepository->addDaysToChargeDay($billingPlan, 5);
            $billingPlan->setIsTrialExtended(true);
            $billingPlan->setIsTrialNoticeSent(false);
            $billingPlanRepository->update($billingPlan);

            $this->flash->success('Trial plan extended for another 5 days.');
            return $this->redirectToRoute('billing');
        }

        $this->flash->error('Not using trial plan.');
        return $this->redirectToRoute('billing');
    }

    /**
     * @Route("/billing/cards/remove", name="billing_cards_remove")
     *
     * @param int                  $oid
     * @param Request              $request
     * @param CreditCardRepository $creditCardRepository
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function removeCardAction(
        int $oid,
        Request $request,
        CreditCardRepository $creditCardRepository
    ): RedirectResponse
    {
        if (!$this->nonce->verify('membershipUpgrade', $request->query->get('n'))) {
            $this->flash->error('Session expired.');
            return $this->redirectToRoute('billing');
        }

        $creditCard = $creditCardRepository->findActiveCard($oid);
        if ($creditCard) {
            $creditCard->setIsActive(false);
            $creditCardRepository->update($creditCard);
            $this->flash->success('Credit card removed.');
        }

        return $this->redirectToRoute('billing');
    }

    /**
     * @Route("/api/v1/billing/cards/remove", name="api_v1_billing_cards_remove", methods={"POST"})
     *
     * @param int                  $oid
     * @param CreditCardRepository $creditCardRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function apiRemoveCardAction(
        int $oid,
        CreditCardRepository $creditCardRepository
    ): JsonResponse
    {
        $creditCard = $creditCardRepository->findActiveCard($oid);
        if ($creditCard) {
            $creditCard->setIsActive(false);
            $creditCardRepository->update($creditCard);

            return $this->json([
                'success' => 'Credit card removed.'
            ]);
        }

        return $this->json([
            'error' => 'No card to remove.'
        ]);
    }

    /**
     * @Route("/billing/cc_update", name="billing_cc_update")
     *
     * @param array                $user
     * @param int                  $oid
     * @param Request              $request
     * @param CreditCardRepository $creditCardRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function updateCreditCardAction(
        array $user,
        int $oid,
        Request $request,
        CreditCardRepository $creditCardRepository
    ): JsonResponse {
        $token = $request->json->get('token');
        if (empty($token)) {
            return $this->json(['error' => 'Error, try again later']);
        }

        Stripe::setApiKey($this->config->stripe['secret']);
        $customer = Customer::create(
            [
                'description' => $user['usr_id'],
                'email'       => $user['usr_email'],
                'source'      => $token
            ]
        );
        $customer->save();
        $source = $customer->sources->data[0];

        $creditCards = $creditCardRepository->findByOrg($oid);
        foreach($creditCards as $creditCard) {
            $creditCard->setIsActive(false);
            $creditCardRepository->update($creditCard);
        }

        $creditCard = (new CreditCard())
            ->setOrgId($oid)
            ->setBrand($source['brand'])
            ->setNumber4($source['last4'])
            ->setExpMonth($source['exp_month'])
            ->setExpYear($source['exp_year'])
            ->setStripeId($customer->id)
            ->setIsActive(true);
        $creditCardRepository->insert($creditCard);

        return $this->json('valid');
    }

    /**
     * @Route("/billing/invoice/{id}", name="billing_invoice")
     *
     * @param int                          $id
     * @param int                          $oid
     * @param InvoiceRepository            $invoiceRepository
     * @param InvoiceItemRepository        $itemRepository
     * @param OrganizationsRepository      $orgRepository
     * @param CreditCardRepository         $creditCardRepository
     * @param BillingTransactionRepository $transactionRepository
     *
     * @return Response
     * @throws Exception
     */
    public function invoiceAction(
        int $id,
        int $oid,
        InvoiceRepository $invoiceRepository,
        InvoiceItemRepository $itemRepository,
        OrganizationsRepository $orgRepository,
        CreditCardRepository $creditCardRepository,
        BillingTransactionRepository $transactionRepository
    ): Response
    {
        $invoice = $invoiceRepository->findByID($id);
        if (!$invoice) {
            $this->throwNotFound();
        }
        if ($invoice->getOrgId() !== $oid) {
            $this->throwUnauthorized();
        }

        $invoiceItems       = $itemRepository->findByInvoice($id);
        $billingTransaction = $transactionRepository->findByID($invoice->getBillingTransactionId());
        $creditCard         = $creditCardRepository->findByID($billingTransaction->getCreditCardId());
        $org                = $orgRepository->findByID($invoice->getOrgId());

        return $this->render('billing/blocks/invoice.html.twig', [
            'org'          => $org,
            'invoice'      => $invoice,
            'invoiceItems' => $invoiceItems,
            'creditCard'   => $creditCard
        ]);
    }
}
