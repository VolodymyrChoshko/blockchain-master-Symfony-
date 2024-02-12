<?php
namespace Repository;

use Aws\S3\S3Client;
use BlocksEdit\Database\Repository;
use BlocksEdit\Service\ChromeServiceInterface;
use BlocksEdit\Twig\TwigRender;
use BlocksEdit\Util\Strings;
use BlocksEdit\System\Required;
use DateTime;
use Entity\BillingAdjustment;
use Entity\BillingPlan;
use Entity\CreditCard;
use Entity\Invoice;
use Entity\InvoiceItem;
use Exception;
use RuntimeException;

/**
 * Class InvoiceRepository
 */
class InvoiceRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return Invoice|null
     * @throws Exception
     */
    public function findByID(int $id): ?Invoice
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param int $oid
     *
     * @return Invoice[]
     * @throws Exception
     */
    public function findByOrg(int $oid): array
    {
        return $this->find([
            'orgId' => $oid
        ], null, null, ['dateCreated' => 'ASC']);
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getMaxID(): int
    {
        $stmt = $this->prepareAndExecute(
            sprintf('SELECT MAX(%s) FROM `%s`', $this->entityAccessor->prefixColumnName('id'), $this->meta->getTableName())
        );

        return (int)$stmt->fetchColumn();
    }

    /**
     * @param BillingPlan         $billingPlan
     * @param BillingAdjustment[] $adjustments
     *
     * @return Invoice
     * @throws Exception
     */
    public function generateInvoice(BillingPlan $billingPlan, array $adjustments = []): Invoice
    {
        $due          = 0;
        $invoiceItems = [];

        if ($billingPlan->isTeam() || $billingPlan->isTrial()) {
            $amountCents = $this->billingPriceRepository->getAmountCents('membership:team');
            if ($amountCents === -1) {
                throw new RuntimeException('Invoicing, could not determine price for "membership:team"');
            }

            $due         += $amountCents;
            $description = 'Blocks Edit Team';
            if ($billingPlan->isTrial()) {
                $description .= ' (30 day free trial)';
            }

            $invoiceItems[] = (new InvoiceItem())
                ->setType(InvoiceItem::TYPE_CHARGE)
                ->setDescription($description)
                ->setAmountCents($amountCents);
        } else if ($billingPlan->isSolo()) {
            $amountCents = $this->billingPriceRepository->getAmountCents('membership:solo');
            if ($amountCents === -1) {
                throw new RuntimeException('Invoicing, could not determine price for "membership:solo"');
            }

            $due            += $amountCents;
            $invoiceItems[] = (new InvoiceItem())
                ->setType(InvoiceItem::TYPE_CHARGE)
                ->setDescription('Blocks Edit Solo')
                ->setAmountCents($amountCents);
        } else if ($billingPlan->isCustom()) {
            $invoiceItems[] = (new InvoiceItem())
                ->setType(InvoiceItem::TYPE_CHARGE)
                ->setDescription('Custom Plan')
                ->setAmountCents(0);
            $due += $billingPlan->getFixedPriceCents();
        }

        $iFound     = [];
        $numSources = 0;
        $sources    = $this->sourcesRepository->findByOrg($billingPlan->getOrgId());
        foreach ($sources as $i => $source) {
            if ($i === 0 && $billingPlan->hasFlag(BillingPlan::FLAG_FREE_INTEGRATION)) {
                continue;
            }

            $integration = $source->getIntegration();
            $slug        = $integration->getSlug();
            if (in_array($slug, $iFound)) {
                continue;
            }
            $iFound[] = $slug;

            $numSources += 1;
            $amountCents = $this->billingPriceRepository->getAmountCents("integration:$slug");
            if ($amountCents === -1) {
                $amountCents = $integration->getPrice();
            }

            $invoiceItems[] = (new InvoiceItem())
                ->setType(InvoiceItem::TYPE_CHARGE)
                ->setDescription($integration->getDisplayName() . ' integration')
                ->setAmountCents($billingPlan->isCustom() ? 0 : $amountCents);
            if (!$billingPlan->isCustom()) {
                $due += $integration->getPrice();
            }
        }

        if (
            $billingPlan->hasFlag(BillingPlan::FLAG_NONPROFIT_DISCOUNT)
            && ($billingPlan->isTeam() || $billingPlan->isTrial())
            && $due > 0
        ) {
            $discount       = $due * BillingPlan::AMOUNT_NONPROFIT_DISCOUNT;
            $due            -= $discount;
            $invoiceItems[] = (new InvoiceItem())
                ->setType(InvoiceItem::TYPE_DISCOUNT)
                ->setDescription('Non-profit discount')
                ->setAmountCents($discount);
        }

        if ($due > 0) {
            // Apply adjustments but don't let the amount due go below 0.
            $temp = $due;
            foreach ($adjustments as $adj) {
                $adj  = clone $adj;
                $type = $adj->getRemainingCents() > 0 ? InvoiceItem::TYPE_CHARGE : InvoiceItem::TYPE_DISCOUNT;

                if ($temp + $adj->getRemainingCents() > 0) {
                    $temp += $adj->getRemainingCents();
                    $invoiceItems[] = (new InvoiceItem())
                        ->setType($type)
                        ->setDescription($adj->getDescription())
                        ->setAmountCents($adj->getRemainingCents());
                    $adj
                        ->setRemainingCents(0)
                        ->setStatus(BillingAdjustment::STATUS_APPLIED_FULLY);
                } else {
                    $diff        = $temp + $adj->getRemainingCents();
                    $diffDollar  = number_format((($adj->getRemainingCents() - $diff) / 100), 2);
                    $amtDollar   = number_format(($adj->getAmountCents() / 100), 2);
                    $description = $adj->getDescription() . sprintf(' (-$%s of -$%s)', str_replace('-', '', $diffDollar), str_replace('-', '', $amtDollar));
                    $invoiceItems[] = (new InvoiceItem())
                        ->setType($type)
                        ->setDescription($description)
                        ->setAmountCents($adj->getRemainingCents() - $diff);
                    $adj
                        ->setRemainingCents($diff)
                        ->setStatus(BillingAdjustment::STATUS_APPLIED_PARTIALLY);
                    $temp = 0;
                    break;
                }
            }

            $due = $temp;
        }

        $description = 'Blocks Edit services';
        if ($billingPlan->isTeam()) {
            $description = "Blocks Edit Team with $numSources integrations";
        } else if ($billingPlan->isCustom()) {
            $description = "Custom plan with $numSources integrations";
        }

        $lastMonth = new DateTime('last month');
        $lastMonth = mktime(0, 0, 0, (int)$lastMonth->format('n'), $billingPlan->getChargeDay(), (int)$lastMonth->format('Y'));

        return (new Invoice())
            ->setAmountCents($due)
            ->setDescription($description)
            ->setOrgId($billingPlan->getOrgId())
            ->setStatus(Invoice::STATUS_PAID)
            ->setDatePeriodEnd(new DateTime('yesterday'))
            ->setDatePeriodStart(new DateTime('@' . $lastMonth))
            ->setItems($invoiceItems);
    }

    /**
     * @param Invoice         $invoice
     * @param BillingPlan     $billingPlan
     * @param CreditCard|null $creditCard
     *
     * @return false|string
     * @throws Exception
     */
    public function generateInvoicePDF(Invoice $invoice, BillingPlan $billingPlan, ?CreditCard $creditCard = null)
    {
        $org  = $this->organizationsRepository->findByID($billingPlan->getOrgId());
        $html = $this->twigRender->render('billing/blocks/invoice.html.twig', [
            'org'          => $org,
            'invoice'      => $invoice,
            'invoiceItems' => $invoice->getItems(),
            'creditCard'   => $creditCard
        ]);

        $pdf = $this->chromeService->pdf($html, [
            'desktopOnly' => true
        ]);
        $temp = tempnam(sys_get_temp_dir(), 'invoice-');
        $this->files->write($temp, $pdf);

        return $temp;
    }

    /**
     * @param Invoice $invoice
     * @param string  $pdfPath
     *
     * @return string
     */
    public function uploadInvoicePDF(Invoice $invoice, string $pdfPath): string
    {
        $key = sprintf('%s/%s.pdf', $invoice->getOrgId(), Strings::uuid());
        $this->s3Client->putObject([
            'Bucket'     => 'invoices.blocksedit.com',
            'Key'        => $key,
            'SourceFile' => $pdfPath,
            'ACL'        => 'public-read'
        ]);

        return sprintf('https://invoices.blocksedit.com/%s', $key);
    }

    /**
     * @var SourcesRepository
     */
    protected $sourcesRepository;

    /**
     * @var OrganizationsRepository
     */
    protected $organizationsRepository;

    /**
     * @var BillingPriceRepository
     */
    protected $billingPriceRepository;

    /**
     * @var TwigRender
     */
    protected $twigRender;

    /**
     * @var ChromeServiceInterface
     */
    protected $chromeService;

    /**
     * @var S3Client
     */
    protected $s3Client;

    /**
     * @Required()
     * @param OrganizationsRepository $organizationsRepository
     */
    public function setOrganizationsRepository(OrganizationsRepository $organizationsRepository)
    {
        $this->organizationsRepository = $organizationsRepository;
    }

    /**
     * @Required()
     * @param BillingPriceRepository $billingPriceRepository
     */
    public function setBillingPriceRepository(BillingPriceRepository $billingPriceRepository)
    {
        $this->billingPriceRepository = $billingPriceRepository;
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
     * @param ChromeServiceInterface $chromeService
     */
    public function setChromeServiceInterface(ChromeServiceInterface $chromeService)
    {
        $this->chromeService = $chromeService;
    }

    /**
     * @Required()
     * @param TwigRender $twigRender
     */
    public function setTwigRender(TwigRender $twigRender)
    {
        $this->twigRender = $twigRender;
    }

    /**
     * @Required()
     * @param S3Client $s3Client
     */
    public function setS3Client(S3Client $s3Client)
    {
        $this->s3Client = $s3Client;
    }
}
