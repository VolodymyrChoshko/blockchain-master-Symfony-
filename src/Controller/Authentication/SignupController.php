<?php
namespace Controller\Authentication;

use BlocksEdit\Controller\Controller;
use BlocksEdit\Email\EmailSender;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use Entity\OnboardingSent;
use Entity\OrganizationAccess;
use Entity\User;
use Exception;
use Repository\Exception\CreateException;
use Repository\OnboardingSentRepository;
use Repository\OrganizationAccessRepository;
use Repository\UserRepository;

/**
 * @IsGranted({"ANY"})
 */
class SignupController extends Controller
{
    /**
     * @Route("/signup", name="signup")
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(
        Request $request
    ): Response
    {
        return $this->renderFrontend($request);
    }

    /**
     * @Route("/api/v1/account/signup", name="api_v1_signup", methods={"POST"})
     *
     * @param Request                      $request
     * @param EmailSender                  $emailSender
     * @param UserRepository               $userRepository
     * @param OnboardingSentRepository     $onboardingSentRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function signupAction(
        Request $request,
        EmailSender $emailSender,
        UserRepository $userRepository,
        OnboardingSentRepository $onboardingSentRepository,
        OrganizationAccessRepository $organizationAccessRepository
    ): JsonResponse {
        try {
            $values = $request->json->all();
            $user = (new User())
                ->setName($values['name'] ?? '')
                ->setEmail($values['email'] ?? '')
                ->setPassPlain($values['password'] ?? '')
                ->setJob($values['job'] ?? '')
                ->setOrganization($values['organization'] ?? '')
                ->setTimezone($values['timezone'] ?? '')
                ->setNewsletter((bool)($values['newsletter'] ?? false))
                ->setJoinRef($request->query->get('ref', ''));
            $userRepository->insert($user);

            $emailSender->sendWelcome($user->getEmail());
            $obs = (new OnboardingSent())
                ->setEmail($user->getEmail())
                ->setView('emails/the-welcome-email.phtml');
            $onboardingSentRepository->insert($obs);

            if ($uri = $request->session->get('redirectAfter')) {
                $request->session->remove('redirectAfter');
                return $this->json([
                    'redirect' => $uri
                ]);
            }

            $org = $organizationAccessRepository
                ->findFirstByUserAndAccess($user->getId(), OrganizationAccess::OWNER);

            return $this->json([
                'redirect' => $request->getDomainForOrg($org['rba_org_id'])
            ]);
        } catch (CreateException $e) {
            return $this->json([
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        }
    }
}
