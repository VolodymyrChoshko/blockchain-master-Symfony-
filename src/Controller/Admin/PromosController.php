<?php
namespace Controller\Admin;

use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use Entity\BillingPromo;
use Exception;
use Repository\BillingPriceRepository;
use Repository\BillingPromoRepository;
use Repository\SourcesRepository;

/**
 * @IsGranted({"SITE_ADMIN_2FA"})
 * @Route("/admin/promos", name="admin_promos_")
 */
class PromosController extends Controller
{
    /**
     * @Route(name="index")
     *
     * @param BillingPromoRepository $billingPromoRepository
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(BillingPromoRepository $billingPromoRepository): Response
    {
        $billingPromos = $billingPromoRepository->findAll();

        return $this->render('admin/promos/index.html.twig', [
            'billingPromos' => $billingPromos
        ]);
    }

    /**
     * @Route("/create", name="create")
     *
     * @param Request                $request
     * @param SourcesRepository      $sourcesRepository
     * @param BillingPriceRepository $billingPriceRepository
     * @param BillingPromoRepository $billingPromoRepository
     *
     * @return Response
     * @throws Exception
     */
    public function createAction(
        Request $request,
        SourcesRepository $sourcesRepository,
        BillingPriceRepository $billingPriceRepository,
        BillingPromoRepository $billingPromoRepository
    ): Response
    {
        $prices = [
            'membership:team' => $billingPriceRepository->getAmountCents('membership:team')
        ];
        $integrations = $sourcesRepository->getAvailableIntegrations();
        foreach($integrations as $integration) {
            $prices['integration:' . $integration->getSlug()]
                = $billingPriceRepository->getAmountCents('integration:' . $integration->getSlug());
        }

        if ($request->isPost()) {
            $name         = $request->request->get('name');
            $code         = $request->request->get('code');
            $type         = $request->request->get('type');
            $value        = $request->request->get('value');
            $valueType    = $request->request->get('valueType', 'dollar');
            $periodMonths = $request->request->get('periodMonths');
            $description  = $request->request->get('description');
            $isNewUser    = $request->request->get('isNewUser') === 'on';
            $isTeamPlan   = $request->request->get('isTeamPlan') === 'on';
            $targets      = array_keys($request->request->getArray('targets'));

            if ($valueType === BillingPromo::VALUE_TYPE_PERCENT) {
                $value = (int)$value;
            } else {
                $value = $this->toAmountCents($value);
            }

            $billingPromo = (new BillingPromo())
                ->setName($name)
                ->setCode($code)
                ->setType($type)
                ->setValue($value)
                ->setValueType($valueType)
                ->setIsNewUser($isNewUser)
                ->setIsTeamPlan($isTeamPlan)
                ->setDescription($description)
                ->setPeriodMonths($periodMonths)
                ->setTargets(json_encode($targets));
            $billingPromoRepository->insert($billingPromo);
            $this->flash->success('Promotion created.');

            return $this->redirectToRoute('admin_promos_index');
        }

        return $this->render('admin/promos/create.html.twig', [
            'billingPromo' => null,
            'integrations' => $integrations,
            'prices'       => $prices
        ]);
    }

    /**
     * @Route("/{id}", name="view")
     *
     * @param int                    $id
     * @param SourcesRepository      $sourcesRepository
     * @param BillingPriceRepository $billingPriceRepository
     * @param BillingPromoRepository $billingPromoRepository
     *
     * @return Response
     * @throws Exception
     */
    public function viewAction(
        int $id,
        SourcesRepository $sourcesRepository,
        BillingPriceRepository $billingPriceRepository,
        BillingPromoRepository $billingPromoRepository
    ): Response {
        $billingPromo = $billingPromoRepository->findByID($id);
        if (!$billingPromo) {
            $this->throwNotFound();
        }

        $prices = [
            'membership:team' => $billingPriceRepository->getAmountCents('membership:team')
        ];
        $integrations = $sourcesRepository->getAvailableIntegrations();
        foreach($integrations as $integration) {
            $prices['integration:' . $integration->getSlug()]
                = $billingPriceRepository->getAmountCents('integration:' . $integration->getSlug());
        }

        return $this->render('admin/promos/create.html.twig', [
            'billingPromo' => $billingPromo,
            'integrations' => $integrations,
            'prices'       => $prices
        ]);
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
