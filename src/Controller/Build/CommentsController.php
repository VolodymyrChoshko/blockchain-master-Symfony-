<?php
namespace Controller\Build;

use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\Annotations\InjectEmail;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\RedirectResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\System\Serializer;
use Entity\Comment;
use Entity\Email;
use Entity\Emoji;
use Entity\Notification;
use Entity\User;
use Exception;
use Repository\CommentRepository;
use Repository\EmailRepository;
use Repository\NotificationRepository;
use Repository\TemplatesRepository;
use Service\Mentions;

/**
 * @IsGranted({"USER"})
 */
class CommentsController extends BuildController
{
    /**
     * @Route("/build/comments/{id<\d+>}", name="build_comments_redirect", methods={"GET"})
     *
     * @param User                $user
     * @param int                 $id
     * @param CommentRepository   $commentRepository
     * @param TemplatesRepository $templatesRepository
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function redirectAction(
        User $user,
        int $id,
        CommentRepository $commentRepository,
        TemplatesRepository $templatesRepository
    ): RedirectResponse {
        $comment = $commentRepository->findByID($id);
        if (!$comment) {
            $this->throwNotFound();
        }

        $template = $comment->getEmail()->getTemplate();
        if (!$templatesRepository->hasAccess($user->getId(), $template->getId())) {
            return $this->redirect('/');
        }

        $url = $this->url('build_email', [
            'id'  => $comment->getEmail()->getId(),
            'tid' => $template->getId()
        ], [], $template->getOrganization()->getId()) . '#activity-c-' . $comment->getId();

        return $this->redirect($url);
    }

    /**
     * @Route("/build/comments/{id<\d+>}", name="build_comments_add", methods={"POST"})
     * @InjectEmail()
     * @IsGranted({"email"})
     *
     * @param User              $user
     * @param Email             $email
     * @param CommentRepository $commentRepository
     * @param Request           $request
     * @param Serializer        $serializer
     * @param Mentions          $mentions
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function addAction(
        User $user,
        Email $email,
        CommentRepository $commentRepository,
        Request $request,
        Serializer $serializer,
        Mentions $mentions
    ): JsonResponse {
        $blockId = $request->json->getInt('blockId');
        $content = trim($request->json->get('content'));
        if (!$content) {
            $this->throwBadRequest();
        }

        try {
            $commentRepository->beginTransaction();
            $comment = (new Comment())
                ->setContent($content)
                ->setUser($user)
                ->setEmail($email)
                ->setBlockId($blockId);
            $commentRepository->insert($comment);
            $mentions->importAll($comment);
            $commentRepository->update($comment);
            $commentRepository->commit();
            $mentions->updateAll($comment);

            return $this->json($serializer->serializeComment($comment));
        } catch (Exception $e) {
            $commentRepository->rollback();
            $this->logger->error($e->getMessage(), $e->getTrace());

            return $this->json('error');
        }
    }

    /**
     * @Route("/build/comments/{id<\d+>}/replies/{cid<\d+>}", name="build_comments_reply", methods={"POST"})
     * @InjectEmail()
     * @IsGranted({"email"})
     *
     * @param int                    $cid
     * @param User                   $user
     * @param Email                  $email
     * @param CommentRepository      $commentRepository
     * @param NotificationRepository $notificationRepository
     * @param Request                $request
     * @param Mentions               $mentions
     * @param Serializer             $serializer
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function addReplyAction(
        int $cid,
        User $user,
        Email $email,
        CommentRepository $commentRepository,
        NotificationRepository $notificationRepository,
        Request $request,
        Mentions $mentions,
        Serializer $serializer
    ): JsonResponse {
        $parent = $commentRepository->findByID($cid);
        if (!$parent) {
            $this->throwNotFound();
        }

        $content = trim($request->json->get('content'));
        if (!$content) {
            $this->throwBadRequest();
        }

        try {
            $commentRepository->beginTransaction();
            $comment = (new Comment())
                ->setContent($content)
                ->setUser($user)
                ->setEmail($email)
                ->setParent($parent);
            $commentRepository->insert($comment);
            $mentions->importAll($comment);
            $commentRepository->update($comment);
            $commentRepository->commit();
            $mentions->updateAll($comment);

            $notification = (new Notification())
                ->setTo($parent->getUser())
                ->setFrom($user)
                ->setAction('reply')
                ->setComment($comment);
            $notificationRepository->insert($notification);

            return $this->json($serializer->serializeComment($comment));
        } catch (Exception $e) {
            $commentRepository->rollback();
            $this->logger->error($e->getMessage());

            return $this->json('error');
        }
    }

    /**
     * @Route("/build/comments/{id<\d+>}", name="build_comments_update", methods={"PUT"})
     *
     * @param User              $user
     * @param int               $id
     * @param CommentRepository $commentRepository
     * @param Request           $request
     * @param Mentions          $mentions
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function updateAction(
        User $user,
        int $id,
        CommentRepository $commentRepository,
        Request $request,
        Mentions $mentions
    ): JsonResponse
    {
        $comment = $commentRepository->findByID($id);
        if (!$comment || $comment->getUser()->getId() !== $user->getId()) {
            $this->throwNotFound();
        }

        $content = trim($request->json->get('content'));
        if (!$content) {
            $this->throwBadRequest();
        }

        $comment->setContent($content);
        $commentRepository->update($comment);
        $mentions->updateAll($comment);

        return $this->json('ok');
    }

    /**
     * @Route("/build/comments/{id<\d+>}", name="build_comments_delete", methods={"DELETE"})
     *
     * @param User                   $user
     * @param int                    $id
     * @param CommentRepository      $commentRepository
     * @param NotificationRepository $notificationRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function deleteAction(
        User $user,
        int $id,
        CommentRepository $commentRepository,
        NotificationRepository $notificationRepository
    ): JsonResponse
    {
        $comment = $commentRepository->findByID($id);
        if (!$comment || $comment->getUser()->getId() !== $user->getId()) {
            $this->throwNotFound();
        }

        foreach($notificationRepository->findByComment($comment) as $notification) {
            $notificationRepository->delete($notification);
        }
        $commentRepository->delete($comment);

        return $this->json('ok');
    }

    /**
     * @Route("/build/comments/{id<\d+>}/emojis", name="build_comments_add_emoji", methods={"POST"})
     *
     * @param User                   $user
     * @param int                    $id
     * @param CommentRepository      $commentRepository
     * @param EmailRepository        $emailRepository
     * @param NotificationRepository $notificationRepository
     * @param Request                $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function addEmojiAction(
        User $user,
        int $id,
        CommentRepository $commentRepository,
        EmailRepository $emailRepository,
        NotificationRepository $notificationRepository,
        Request $request
    ): JsonResponse {
        $comment = $commentRepository->findByID($id);
        if (!$comment) {
            $this->throwNotFound();
        }
        if (!$emailRepository->hasAccess($user->getId(), $comment->getEmail()->getId())) {
            $this->throwUnauthorized();
        }

        $uuid = trim($request->json->get('uuid'));
        if (!$uuid) {
            $this->throwBadRequest();
        }
        $code = $request->json->get('code');
        if (!$code || !preg_match('/^[a-zA-Z0-9]+$/', $code)) {
            $this->throwBadRequest();
        }

        $emoji = (new Emoji())
            ->setUuid($uuid)
            ->setComment($comment)
            ->setUser($user)
            ->setCode($code);
        $comment->addEmoji($emoji);
        $commentRepository->update($comment);

        $notification = (new Notification())
            ->setTo($comment->getUser())
            ->setFrom($user)
            ->setAction('emoji')
            ->setComment($comment);
        $notificationRepository->insert($notification);

        return $this->json('ok');
    }

    /**
     * @Route("/build/comments/{id<\d+>}/emojis/{uuid}", name="build_comments_delete_emoji", methods={"DELETE"})
     *
     * @param User              $user
     * @param int               $id
     * @param string            $uuid
     * @param CommentRepository $commentRepository
     * @param EmailRepository   $emailRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function deleteEmoji(
        User $user,
        int $id,
        string $uuid,
        CommentRepository $commentRepository,
        EmailRepository $emailRepository
    ): JsonResponse {
        $comment = $commentRepository->findByID($id);
        if (!$comment) {
            $this->throwNotFound();
        }
        if (!$emailRepository->hasAccess($user->getId(), $comment->getEmail()->getId())) {
            $this->throwUnauthorized();
        }

        $emojis = $comment->getEmojis();
        foreach($emojis as $emoji) {
            if ($emoji->getUuid() === $uuid && $emoji->getUser()->getId() === $user->getId()) {
                $comment->removeEmoji($emoji);
                break;
            }
        }

        $commentRepository->update($comment);

        return $this->json('ok');
    }
}
