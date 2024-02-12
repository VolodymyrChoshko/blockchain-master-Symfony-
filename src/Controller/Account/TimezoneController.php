<?php
namespace Controller\Account;

use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Util\Dates;
use Repository\UserRepository;

/**
 * @IsGranted({"USER"})
 */
class TimezoneController extends Controller
{
    /**
     * @Route("/timezone", name="timezone")
     *
     * @param int            $uid
     * @param Request        $request
     * @param UserRepository $userRepository
     *
     * @return JsonResponse
     */
    public function timezoneAction(
        int $uid,
        Request $request,
        UserRepository $userRepository
    ): JsonResponse
    {
        $timezone = $request->post->get('timezone');
        if (Dates::isValidTimezone($timezone)) {
            $userRepository->updateTimezone($uid, $timezone);
        }

        return new JsonResponse('ok');
    }
}
