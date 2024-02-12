<?php
namespace Controller\Admin;

use BlocksEdit\Controller\Controller;
use BlocksEdit\Email\EmailSender;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use BlocksEdit\Http\StatusCodes;
use Exception;
use Repository\DevEmailRepository;
use Repository\EmailRepository;
use Repository\EmailTemplateRepository;

/**
 * @IsGranted({"SITE_ADMIN_2FA"})
 * @Route("/admin/email", name="admin_email_")
 */
class EmailController extends Controller
{
    /**
     * @Route(name="index")
     *
     * @param Request            $request
     * @param DevEmailRepository $emailRepository
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(Request $request, DevEmailRepository $emailRepository): Response
    {
        $limit     = 50;
        $search    = $request->query->get('search');
        $page      = $request->query->getInt('page', 1);
        $offset    = ($page - 1) * $limit;

        if ($search) {
            $emails = $emailRepository->findByTo($search, $limit, $offset);
            $total  = $emailRepository->findCountByTo($search);
        } else {
            $emails = $emailRepository->findAll($limit, $offset);
            $total  = $emailRepository->findCount();
        }

        $tos   = [];
        $froms = [];
        foreach($emails as $email) {
            $parts = [];
            $to    = json_decode($email->getTo());
            foreach($to as $key => $value) {
                if ($value) {
                    $parts[] = sprintf('%s (%s)', $value, $key);
                } else {
                    $parts[] = $key;
                }
            }
            $tos[$email->getId()] = join(', ', $parts);

            $parts = [];
            $from  = json_decode($email->getFrom());
            foreach($from as $key => $value) {
                if ($value) {
                    $parts[] = sprintf('%s (%s)', $value, $key);
                } else {
                    $parts[] = $key;
                }
            }
            $froms[$email->getId()] = join(', ', $parts);
        }

        return $this->render('admin/email/index.html.twig', [
            'emails'     => $emails,
            'froms'      => $froms,
            'tos'        => $tos,
            'search'     => $search,
            'totalPages' => ceil($total / $limit),
            'total'      => $total,
            'page'       => $page
        ]);
    }

    /**
     * @Route("/templates", name="templates")
     *
     * @param EmailTemplateRepository $emailTemplateRepository
     *
     * @return Response
     * @throws Exception
     */
    public function templatesAction(EmailTemplateRepository $emailTemplateRepository): Response
    {
        $templates = $emailTemplateRepository->findAll();

        return $this->render('admin/email/templates.html.twig', [
            'templates' => $templates
        ]);
    }

    /**
     * @Route("/templates/{id}", name="template_edit")
     *
     * @param int                     $id
     * @param Request                 $request
     * @param EmailRepository         $emailRepository
     * @param EmailTemplateRepository $emailTemplateRepository
     *
     * @return Response
     * @throws Exception
     */
    public function templateEditAction(
        int $id,
        Request $request,
        EmailRepository $emailRepository,
        EmailTemplateRepository $emailTemplateRepository
    ): Response
    {
        $template = $emailTemplateRepository->findByID($id);
        if (!$template) {
            $this->throwNotFound();
        }

        if ($request->query->get('emaIdCheck') !== null) {
            $emaId = $request->query->get('emaIdCheck');
            $email = $emailRepository->findByID($emaId);
            if (!$email) {
                return $this->json(null);
            }

            $screenshot = $this->config->uris['screenshots'] . '/templates/' . $email['ema_tmp_id'] . '/screenshot-200.jpg';

            return $this->json([
                'title'      => $email['ema_title'],
                'screenshot' => $screenshot
            ]);
        }

        if ($request->isPost()) {
            try {
                $subject     = $request->request->get('subject');
                $location    = $request->request->get('location');
                $content     = trim($request->request->get('content'));
                $emaId       = $request->request->getInt('emaId');
                $noSendCheck = $request->request->get('noSendCheck') === 'on';

                if ($location === 'builder') {
                    if (!$emaId) {
                        $this->flash->error('Builder email not specified.');
                        throw new Exception();
                    }
                    $email = $emailRepository->findByID($emaId);
                    if (!$email) {
                        $this->flash->error('Builder email not found.');
                        throw new Exception();
                    }
                } else if ($location === 'database' && !$content) {
                    $this->flash->error('Content cannot be empty.');
                    throw new Exception();
                }
                if ($location === 'database' || $location === 'disk') {
                    $emaId = null;
                }

                $template
                    ->setSubject($subject)
                    ->setLocation($location)
                    ->setContent($content)
                    ->setEmaId($emaId)
                    ->setNoSendCheck($noSendCheck);
                $emailTemplateRepository->update($template);
                $this->flash->success('Email template updated.');

                return $this->redirectToRoute('admin_email_template_edit', ['id' => $id]);
            } catch (Exception $e) {
                dump($e->getMessage());die();
            }
        }

        $title      = '';
        $screenshot = '';
        if ($template->getEmaId()) {
            $email = $emailRepository->findByID($template->getEmaId());
            if ($email) {
                $title      = $email['ema_title'];
                $screenshot = $this->config->uris['screenshots'] . '/templates/' . $email['ema_tmp_id'] . '/screenshot-200.jpg';
            }
        }

        return $this->render('admin/email/template-edit.html.twig', [
            'template'   => $template,
            'title'      => $title,
            'screenshot' => $screenshot,
            'assets'   => [
                'js' => [
                    'build/js/dashboard.js'
                ]
            ]
        ]);
    }

    /**
     * @Route("/templates/{id}/test", name="template_test", methods={"POST"})
     *
     * @param int                     $id
     * @param Request                 $request
     * @param EmailSender             $emailSender
     * @param EmailTemplateRepository $emailTemplateRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function templateSendTest(
        int $id,
        Request $request,
        EmailSender $emailSender,
        EmailTemplateRepository $emailTemplateRepository
    ): JsonResponse {
        $template = $emailTemplateRepository->findByID($id);
        if (!$template) {
            $this->throwNotFound();
        }

        $email     = $request->request->get('email');
        $location  = $request->request->get('location');
        $content   = $request->request->get('content');
        $emaId     = $request->request->getInt('emaId');
        $subject   = $request->request->get('subject');
        $variables = $request->request->getArray('variables');

        $template
            ->setLocation($location)
            ->setContent($content)
            ->setEmaId($emaId)
            ->setSubject($subject);
        $emailSender->sendTest($email, $template, $variables);

        return $this->json('ok');
    }

    /**
     * @Route("/{id}", name="view")
     *
     * @param int                $id
     * @param DevEmailRepository $emailRepository
     *
     * @return Response
     * @throws Exception
     */
    public function viewAction(int $id, DevEmailRepository $emailRepository): Response
    {
        $email = $emailRepository->findByID($id);
        if (!$email) {
            $this->throwNotFound();
        }

        $tos   = [];
        $froms = [];
        $parts = [];
        $to    = json_decode($email->getTo());
        foreach($to as $key => $value) {
            if ($value) {
                $parts[] = sprintf('%s (%s)', $value, $key);
            } else {
                $parts[] = $key;
            }
        }
        $tos[$email->getId()] = join(', ', $parts);

        $parts = [];
        $from  = json_decode($email->getFrom());
        foreach($from as $key => $value) {
            if ($value) {
                $parts[] = sprintf('%s (%s)', $value, $key);
            } else {
                $parts[] = $key;
            }
        }
        $froms[$email->getId()] = join(', ', $parts);

        return $this->render('admin/email/view.html.twig', [
            'email' => $email,
            'tos'   => $tos,
            'froms' => $froms
        ]);
    }

    /**
     * @Route("/{id}/iframe", name="iframe")
     *
     * @param int                $id
     * @param DevEmailRepository $emailRepository
     *
     * @return Response
     * @throws Exception
     */
    public function iframeAction(int $id, DevEmailRepository $emailRepository): Response
    {
        $email = $emailRepository->findByID($id);
        if (!$email) {
            $this->throwNotFound();
        }

        return new Response($email->getBody(), StatusCodes::OK, [
            'Content-Type' => $email->getContentType()
        ]);
    }
}
