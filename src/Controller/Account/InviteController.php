<?php
namespace Controller\Account;

use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\RedirectResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\IO\Exception\NotFoundException;
use Entity\User;
use Exception;
use Repository\InvitationsRepository;
use Repository\OrganizationsRepository;
use Service\PeopleService;
use Repository\TemplatesRepository;

/**
 * @IsGranted({"ANY"})
 */
class InviteController extends Controller
{
    /**
     * @Route("/invite/{tid<\d+>}/{token}", name="invite")
     *
     * @param int                 $uid
     * @param array               $user
     * @param int                 $tid
     * @param string              $token
     * @param Request             $request
     * @param TemplatesRepository $templatesRepository
     * @param PeopleService       $peopleService
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function indexAction(
        int $uid,
        array $user,
        int $tid,
        string $token,
        Request $request,
        TemplatesRepository $templatesRepository,
        PeopleService $peopleService
    ): RedirectResponse {
        if (!$user) {
            $path = $request->getUri(true);
            $request->session->set('redirectAfter', $path);
            $this->flash->success('Please login or register to continue.');

            return $this->redirectToRoute('login');
        }

        $invite = $peopleService->acceptInvite($tid, $uid, $token);
        $this->flash->success('Invitation accepted!');

        if ($request->query->get('tid')) {
            $template = $templatesRepository->findByID($request->query->get('tid'));
            if ($template) {
                return $this->redirect(
                    $this->url('index', [], [], $template['tmp_org_id']) . 't/' . $template['tmp_id']
                );
            }
        }

        if ($invite && $invite->getTmpId()) {
            $template = $templatesRepository->findByID($invite->getTmpId());
            if ($template) {
                return $this->redirect(
                    $this->url('index', [], [], $template['tmp_org_id'])
                );
            }
        }

        return $this->redirectToRoute('index');
    }

    /**
     * @Route("/invite/organization/{token}", name="invite_organization")
     *
     * @param int                     $uid
     * @param User|null               $user
     * @param Request                 $request
     * @param OrganizationsRepository $organizationsRepository
     * @param InvitationsRepository   $invitationsRepository
     *
     * @return RedirectResponse|null
     * @throws Exception
     */
    public function organizationAction(
        int $uid,
        ?User $user,
        Request $request,
        OrganizationsRepository $organizationsRepository,
        InvitationsRepository $invitationsRepository
    ): ?RedirectResponse {
        if (!$uid) {
            $path = $request->getPath();
            $request->session->set('redirectAfter', $path);
            $this->flash->success('Please login or register to continue.');

            return $this->redirectToRoute('login');
        }

        $token  = $request->route->params->get('token');
        $invite = $invitationsRepository->findByToken($token);
        if ($invite && $invite->getType() === 'organization') {
            if (!$invite->getIsAccepted()) {
                $invite
                    ->setAcceptedId($uid)
                    ->setIsAccepted(1);
                $invitationsRepository->update($invite);
                $organizationsRepository->invite(
                    $invite->getInviterId(),
                    $invite->getOrgId(),
                    $user,
                    $invite->getOrgAccess()
                );

                $this->flash->success('Invitation accepted!');
            }

            return $this->redirect(
                $this->url('index', [], [], $invite->getOrgId())
            );
        }

        $this->throwNotFound();
    }
}
