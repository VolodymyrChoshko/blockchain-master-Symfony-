<?php
namespace Controller\Build;

use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\System\Serializer;
use DateTime;
use Entity\User;
use Exception;
use Repository\NotificationRepository;
use Service\Mentions;

/**
 * @IsGranted({"USER"})
 */
class NotificationsController extends BuildController
{
    /**
     * @Route("/build/notifications/{id<\d+>}/status", name="build_notifications_status", methods={"POST"})
     *
     * @param User                   $user
     * @param int                    $id
     * @param NotificationRepository $notificationRepository
     * @param Request                $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function statusAction(
        User $user,
        int $id,
        NotificationRepository $notificationRepository,
        Request $request
    ): JsonResponse {
        $notification = $notificationRepository->findByID($id);
        if (!$notification) {
            $this->throwNotFound();
        }
        if ($notification->getTo()->getId() !== $user->getId()) {
            $this->throwUnauthorized();
        }

        $status = $request->json->get('status');
        if (!in_array($status, ['read', 'unread'])) {
            $this->throwBadRequest();
        }

        $notification->setStatus($status);
        $notificationRepository->update($notification);

        return $this->json('ok');
    }

    /**
     * @Route("/build/notifications/status", name="build_notifications_all_status", methods={"POST"})
     *
     * @param User                   $user
     * @param NotificationRepository $notificationRepository
     * @param Request                $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function statusAllAction(
        User $user,
        NotificationRepository $notificationRepository,
        Request $request
    ): JsonResponse {
        $status = $request->json->get('status');
        if (!in_array($status, ['read', 'unread'])) {
            $this->throwBadRequest();
        }

        $notificationRepository->updateStatusByTo($user, $status);

        return $this->json('ok');
    }

    /**
     * @Route("/build/notifications/{id<\d+>}", name="build_notifications_delete", methods={"DELETE"})
     *
     * @param User                   $user
     * @param int                    $id
     * @param NotificationRepository $notificationRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function deleteAction(User $user, int $id, NotificationRepository $notificationRepository): JsonResponse
    {
        $notification = $notificationRepository->findByID($id);
        if (!$notification) {
            $this->throwNotFound();
        }
        if ($notification->getTo()->getId() !== $user->getId()) {
            $this->throwUnauthorized();
        }

        $notificationRepository->delete($notification);

        return $this->json('ok');
    }
}
