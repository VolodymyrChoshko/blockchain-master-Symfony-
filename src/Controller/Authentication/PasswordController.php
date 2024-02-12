<?php
namespace Controller\Authentication;

use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use Exception;

/**
 * @IsGranted({"ANY"})
 */
class PasswordController extends Controller
{
    /**
     * @Route("/resetpassword/{token}", name="reset_password")
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function resetAction(Request $request): Response
    {
        return $this->renderFrontend($request);
    }
}
