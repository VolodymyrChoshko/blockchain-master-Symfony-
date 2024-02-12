<?php
namespace Controller\Account;

use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use Entity\NoSend;
use Exception;
use Repository\NoSendRepository;

/**
 * @IsGranted({"ANY"})
 */
class UnsubscribeController extends Controller
{
    /**
     * @Route("/unsubscribe/{base64_email}", name="unsubscribe", middleware={})
     *
     * @param Request          $request
     * @param NoSendRepository $noSendRepository
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(Request $request, NoSendRepository $noSendRepository): Response
    {
        $email = base64_decode($request->route->params->get('base64_email'));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && !$noSendRepository->findByEmail($email)) {
            $noSend = (new NoSend())
                ->setEmail($email)
                ->setReason('unsubscribe');
            $noSendRepository->insert($noSend);
        }

        return $this->render('dashboard/unsubscribe.html.twig');
    }
}
