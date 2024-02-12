<?php
namespace Controller\Admin;

use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use BlocksEdit\Util\Strings;
use Exception;
use PHPGangsta_GoogleAuthenticator;
use Redis;

/**
 * @IsGranted({"SITE_ADMIN"})
 * @Route("/admin", name="admin_auth_")
 */
class AdminAuthController extends Controller
{
    /**
     * @Route("/login", name="login")
     *
     * @return Response
     * @throws Exception
     */
    public function loginAction(): Response
    {
        return $this->render('admin/auth/login.html.twig');
    }

    /**
     * @Route("/login/verify", name="login_verify", methods={"POST"})
     *
     * @param array   $user
     * @param Request $request
     * @param Redis   $redis
     *
     * @return Response
     * @throws Exception
     */
    public function validateAction(array $user, Request $request, Redis $redis): Response
    {
        $code = $request->request->get('code');
        $ga   = new PHPGangsta_GoogleAuthenticator();
        if ($this->config->env === 'prod' && !$ga->verifyCode($user['usr_2fa_secret'], $code, 2)) {
            $this->flash->error('Invalid code');
            return $this->redirectToRoute('admin_auth_login');
        } else {
            $uuid = Strings::uuid();
            $key  = sprintf('2fa:%s', $uuid);
            $redis->setex($key, 86400, $user['usr_id']);
            $request->session->set('2fa', $uuid);

            $grants = $request->session->get('security.grants', []);
            if (!in_array(IsGranted::GRANT_SITE_ADMIN_2FA, $grants)) {
                $grants[] = IsGranted::GRANT_SITE_ADMIN_2FA;
            }
            $request->session->set('security.grants', $grants);
            $this->flash->success('Successfully authenticated.');
        }

        return $this->redirectToRoute('admin_dashboard_index');
    }
}
