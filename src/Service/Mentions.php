<?php
namespace Service;

use BlocksEdit\Html\DomParser;
use BlocksEdit\IO\Paths;
use BlocksEdit\System\Serializer;
use Entity\Comment;
use Entity\Mention;
use Entity\Notification;
use Exception;
use Repository\MentionRepository;
use Repository\NotificationRepository;
use Repository\UserRepository;

/**
 * Class Mentions
 */
class Mentions
{
    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var MentionRepository
     */
    protected $mentionRepository;

    /**
     * @var NotificationRepository
     */
    protected $notificationRepository;

    /**
     * @var Paths
     */
    protected $paths;

    /**
     * Constructor
     *
     * @param UserRepository         $userRepository
     * @param MentionRepository      $mentionRepository
     * @param NotificationRepository $notificationRepository
     * @param Serializer             $serializer
     * @param Paths                  $paths
     */
    public function __construct(
        UserRepository $userRepository,
        MentionRepository $mentionRepository,
        NotificationRepository $notificationRepository,
        Serializer $serializer,
        Paths $paths
    )
    {
        $this->userRepository         = $userRepository;
        $this->mentionRepository      = $mentionRepository;
        $this->notificationRepository = $notificationRepository;
        $this->serializer             = $serializer;
        $this->paths                  = $paths;
    }

    /**
     * @param Comment $comment
     *
     * @throws Exception
     */
    public function importAll(Comment $comment)
    {
        $imported = [];
        $content  = $comment->getContent();
        $content  = strip_tags($content, '<span><img>');
        $dom      = DomParser::fromString("<!doctype html><html><body><div id='activity-content'>$content</div></body></html>");
        $div      = $dom->find('#activity-content', 0);
        $spans    = $div->find('.activity-avatar-sm');
        foreach($spans as $span) {
            $span->setAttribute('class', 'activity-avatar-sm');
            $attr = $span->getAllAttributes();
            foreach($attr as $key => $value) {
                if ($key !== 'data-user-id' && $key !== 'title' && $key !== 'data-mention-uuid' && $key !== 'class') {
                    $span->removeAttribute($key);
                }
            }

            $userId = $span->getAttribute('data-user-id');
            $uuid   = $span->getAttribute('data-mention-uuid');
            if (!$userId || !$uuid) {
                $span->outertext = '';
                continue;
            }

            $user = $this->userRepository->findByID($userId, true);
            if (!$user) {
                $span->outertext = '';
                continue;
            }

            $mention = $this->mentionRepository->findByUUID($uuid);
            if ($mention) {
                continue;
            }

            $mention = (new Mention())
                ->setUser($user)
                ->setUuid($uuid)
                ->setComment($comment);
            $this->mentionRepository->insert($mention);
            $imported[] = $uuid;

            $notification = (new Notification())
                ->setTo($user)
                ->setAction('mention')
                ->setMention($mention);
            $this->notificationRepository->insert($notification);
        }

        foreach($this->mentionRepository->findByComment($comment) as $mention) {
            if (!in_array($mention->getUuid(), $imported)) {
                $this->mentionRepository->delete($mention);
            }
        }

        $comment->setContent($div->innertext());
    }

    /**
     * @param Comment $comment
     * @param bool    $isEmail
     *
     * @throws Exception
     */
    public function updateAll(Comment $comment, bool $isEmail = false)
    {
        $content = $comment->getContent();
        $dom   = DomParser::fromString("<!doctype html><html><body><div id='activity-content'>$content</div></body></html>");
        $div   = $dom->find('#activity-content', 0);
        $spans = $div->find('.activity-avatar-sm');
        foreach($spans as $span) {
            $uuid    = $span->getAttribute('data-mention-uuid');
            $mention = $this->mentionRepository->findByUUID($uuid);
            if (!$mention) {
                $span->outertext = '';
                continue;
            }

            $user      = $mention->getUser();
            $name      = $user->getName();
            $firstName = $user->getFirstName();
            $initials  = $user->getInitials();

            $light     = $this->serializer->serializeUserLight($user);
            $span->setAttribute('data-user-info', urlencode(json_encode($light)));
            $span->setAttribute('title', $name);

            $nameNode = $span->find('.activity-avatar-sm-name', 0);
            if (!$nameNode) {
                $span->outertext = '';
                continue;
            }
            $nameNode->innertext = $firstName;

            $initialsNode = $span->find('.activity-avatar-sm-initials', 0);
            if ($initialsNode) {
                $initialsNode->innertext = $initials;
            }

            $img    = $span->find('img', 0);
            $avatar = $this->userRepository->getAnyAvatar($user, 60);
            if (!$isEmail && $avatar) {
                if (!$img) {
                    $span->innertext = "<img src=\"$avatar\" alt=\"\" /> <span class=\"activity-avatar-sm-name\">$firstName</span>";
                } else {
                    $img->setAttribute('src', $avatar);
                }
            } else if (!$isEmail && $img) {
                $span->innertext = "<span class=\"activity-avatar-sm-initials\">$initials</span> <span class=\"activity-avatar-sm-name\">$firstName</span>";
            } else if ($isEmail) {
                $span->innertext = $firstName;
            }
        }

        $comment->setContent($div->innertext());
    }
}
