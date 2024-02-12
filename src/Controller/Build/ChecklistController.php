<?php
namespace Controller\Build;

use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\Annotations\InjectEmail;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\System\Serializer;
use Entity\ChecklistItem;
use Entity\Comment;
use Entity\Email;
use Entity\User;
use Exception;
use Repository\ChecklistItemRepository;
use Repository\CommentRepository;
use Service\Mentions;

/**
 * @IsGranted({"USER"})
 */
class ChecklistController extends BuildController
{
    /**
     * @IsGranted({"email"})
     * @Route("/build/checklist/{id<\d+>}/{key}", name="build_checklist_check", methods={"POST"})
     * @InjectEmail()
     *
     * @param Email                   $email
     * @param User                    $user
     * @param string                  $key
     * @param Request                 $request
     * @param Serializer              $serializer
     * @param ChecklistItemRepository $checklistItemRepository
     * @param CommentRepository       $commentRepository
     * @param Mentions                $mentions
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function checkAction(
        Email $email,
        User $user,
        string $key,
        Request $request,
        Serializer $serializer,
        ChecklistItemRepository $checklistItemRepository,
        CommentRepository $commentRepository,
        Mentions $mentions
    ): JsonResponse {
        $templateSettings = $email->getTemplate()->getChecklistSettings();
        if (!$templateSettings || empty($templateSettings['enabled'])) {
            $this->throwNotFound();
        }
        $item = $checklistItemRepository->findByKey($email, $key);
        if (!$item) {
            $this->throwBadRequest();
        }
        $checked = $request->json->getBoolean('checked', null);
        if ($checked === null) {
            $this->throwBadRequest();
        }

        if ($checked) {
            $item
                ->setIsChecked(true)
                ->setCheckedUser($user);
        } else {
            $item
                ->setIsChecked(false)
                ->setCheckedUser(null);
        }

        $checklistItemRepository->update($item);

        $comment = (new Comment())
            ->setEmail($email)
            ->setUser($user)
            ->setMentions([])
            ->setContent($item->getTitle())
            ->setStatus($checked ? 'checked' : 'unchecked');
        $commentRepository->insert($comment);

        $comments = [];
        foreach($commentRepository->findByEmail($email) as $comment) {
            $mentions->updateAll($comment);
            $comments[] = $serializer->serializeComment($comment);
        }

        return $this->json([
            'comments' => $comments,
            'items' => $serializer->serializeChecklistItem($item)
        ]);
    }
}
