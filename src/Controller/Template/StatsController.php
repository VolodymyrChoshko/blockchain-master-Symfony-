<?php
namespace Controller\Template;

use BlocksEdit\Controller\Controller;
use BlocksEdit\Email\MailerInterface;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\Request;
use Exception;
use Repository\EmailRepository;

/**
 * Class StatsController
 */
class StatsController extends Controller
{
    /**
     * @Route("/stats/export-email", name="stats")
     *
     * @param array           $user
     * @param Request         $request
     * @param MailerInterface $mailer
     * @param EmailRepository $emailRepository
     *
     * @throws Exception
     */
    public function statsExportEmailAction(
        array $user,
        Request $request,
        MailerInterface $mailer,
        EmailRepository $emailRepository
    )
    {
        $method = $request->post->get('method');
        $eid    = $request->post->get('eid');
        if (!$user || !$method || !$eid) {
            die();
        }

        $email = $emailRepository->findByID($eid);
        if (!$email) {
            die();
        }

        $message = $mailer->message('Blocksedit - User exported email')
            ->setFrom('no-reply@blocksedit.com', $user['usr_name'])
            ->setTo('ovi@blocksedit.com')
            ->setBody(sprintf(
                '%s (%s) exported email #%d "%s" using the "%s" method.',
                $user['usr_name'],
                $user['usr_email'],
                $eid,
                $email['ema_title'],
                $method
            ), 'text/html');
        $mailer->send($message);

        die("ok");
    }
}
