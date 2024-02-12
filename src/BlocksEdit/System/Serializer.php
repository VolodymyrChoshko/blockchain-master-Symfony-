<?php
namespace BlocksEdit\System;

use BlocksEdit\Cache\CacheInterface;
use BlocksEdit\Config\Config;
use DateTimeZone;
use Entity\ChecklistItem;
use Entity\Comment;
use Entity\Emoji;
use Entity\Mention;
use Entity\Notification;
use Entity\PinGroup;
use Entity\SectionLibrary;
use Entity\Template;
use Entity\User;
use Tag\SerializedHistoryTag;
use Tag\SerializedThumbnailsTag;
use Tag\SerializedTemplateTag;
use BlocksEdit\IO\Paths;
use Entity\BillingPlan;
use Entity\CreditCard;
use Entity\EmailHistory;
use Entity\Invitation;
use Entity\Invoice;
use Entity\InvoiceItem;
use Entity\Notice;
use Entity\Source;
use BlocksEdit\Integrations\IntegrationInterface;
use Entity\TemplateHistory;
use Exception;
use Repository\UserRepository;
use Repository\TemplateHistoryRepository;

/**
 * Class Serializer
 */
class Serializer
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var TemplateHistoryRepository
     */
    protected $templateHistoryRepository;

    /**
     * Constructor
     *
     * @param Config                    $config
     * @param CacheInterface            $cache
     * @param UserRepository            $userRepository
     * @param TemplateHistoryRepository $templateHistoryRepository
     */
    public function __construct(
        Config $config,
        CacheInterface $cache,
        UserRepository $userRepository,
        TemplateHistoryRepository $templateHistoryRepository
    )
    {
        $this->config            = $config;
        $this->cache             = $cache;
        $this->userRepository = $userRepository;
        $this->templateHistoryRepository = $templateHistoryRepository;
    }

    /**
     * @required
     *
     * @param Paths $paths
     */
    public function setPaths(Paths $paths)
    {
        $this->paths = $paths;
    }

    /**
     * @param array|User $user
     *
     * @return array
     */
    public function serializeUser($user): array
    {
        if ($user instanceof User) {
            $userState = [
                'id'                     => $user->getId(),
                'email'                  => $user->getEmail(),
                'name'                   => $user->getName(),
                'job'                    => $user->getJob(),
                'timezone'               => $user->getTimezone(),
                'organization'           => $user->getOrganization(),
                'hasPass'                => $user->getPass() !== '',
                'parentID'               => $user->getParent() ? $user->getParent()->getId() : 0,
                'isDarkMode'             => $user->getIsDarkMode() === null ? null : $user->getIsDarkMode(),
                'isNotificationsEnabled' => $user->isNotificationsEnabled(),
                'isEmailsEnabled'        => $user->isEmailsEnabled(),
                'isShowingCount'         => $user->isShowingCount(),
                'skinTone'               => $user->getSkinTone(),
                'avatar'                 => $user->getAvatar60(),
                'avatar60'               => $user->getAvatar60(),
                'avatar120'              => $user->getAvatar120(),
                'avatar240'              => $user->getAvatar240(),
                'accResponded'           => $user->isResponded(),
                'isOwner'                => $user->isOwner(),
                'isAdmin'                => $user->isAdmin(),
                'hasEditedTmplSettings'  => $user->getEditedTemplateSettings(),
            ];
            if ($user->getAvatar() && !$user->getAvatar60()) {
                $parts = pathinfo($user['usr_avatar']);
                $userState['avatar'] = $this->paths->urlAvatar($parts['filename'] . '-60x60.' . $parts['extension']);
            }
            if ($user->isSiteAdmin()) {
                $userState['isSiteAdmin'] = true;
            }

            return $userState;
        }

        $userState = [
            'id'                     => (int)$user['usr_id'],
            'email'                  => $user['usr_email'],
            'name'                   => $user['usr_name'],
            'job'                    => $user['usr_job'],
            'timezone'               => $user['usr_timezone'],
            'organization'           => $user['usr_organization'],
            'hasPass'                => $user['usr_pass'] !== '',
            'parentID'               => (int)$user['usr_parent_id'],
            'isDarkMode'             => $user['usr_is_dark_mode'] === null ? null : (bool)$user['usr_is_dark_mode'],
            'isNotificationsEnabled' => (bool)$user['usr_is_notifications_enabled'] ?? false,
            'isEmailsEnabled'        => (bool)$user['usr_is_emails_enabled'] ?? false,
            'isShowingCount'         => (bool)$user['usr_is_showing_count'] ?? true,
            'skinTone'               => (int)$user['usr_skin_tone'],
            'avatar'                 => $user['usr_avatar_60'],
            'avatar60'               => $user['usr_avatar_60'],
            'avatar120'              => $user['usr_avatar_120'],
            'avatar240'              => $user['usr_avatar_240']
        ];
        if (!empty($user['usr_avatar']) && empty($user['usr_avatar_60'])) {
            $parts = pathinfo($user['usr_avatar']);
            $userState['avatar'] = $this->paths->urlAvatar($parts['filename'] . '-60x60.' . $parts['extension']);
        }
        if ($user['usr_is_site_admin']) {
            $userState['isSiteAdmin'] = true;
        }
        if (isset($user['acc_responded'])) {
            $userState['accResponded'] = (bool)$user['acc_responded'];
        }
        if (isset($user['isOwner'])) {
            $userState['isOwner'] = (bool)$user['isOwner'];
        }
        if (isset($user['isAdmin'])) {
            $userState['isAdmin'] = (bool)$user['isAdmin'];
        }
        if (!empty($user['hasEditedTmplSettings'])) {
            $userState['hasEditedTmplSettings'] = (bool)$user['usr_has_edited_tmpl_settings'];
        }

        return $userState;
    }

    /**
     * @param User $user
     *
     * @return array
     */
    public function serializeUserLight(User $user): array
    {
        $userState = [
            'id'        => $user->getId(),
            'email'     => $user->getEmail(),
            'name'      => $user->getName(),
            'job'       => $user->getJob(),
            'timezone'  => $user->getTimezone(),
            'avatar'    => $user->getAvatar60(),
            'avatar60'  => $user->getAvatar60(),
            'avatar120' => $user->getAvatar120(),
            'avatar240' => $user->getAvatar240(),
            'skinTone'  => $user->getSkinTone(),
            'isOwner'   => $user->isOwner(),
            'isAdmin'   => $user->isAdmin(),
        ];
        if ($user->getAvatar() && !$user->getAvatar60()) {
            $parts = pathinfo($user['usr_avatar']);
            $userState['avatar'] = $this->paths->urlAvatar($parts['filename'] . '-60x60.' . $parts['extension']);
        }
        if ($user->isSiteAdmin()) {
            $userState['isSiteAdmin'] = true;
        }

        return $userState;
    }

    /**
     * @param array|Template $template
     *
     * @return array
     * @throws Exception
     */
    public function serializeTemplate($template): array
    {
        if ($template instanceof Template) {
            $id      = $template->getId();
            $version = $template->getVersion();
        } else {
            $id      = (int)$template['tmp_id'];
            $version = (int)($template['tmp_version'] ?? 0);
        }
        $serialized = $this->cache->get("serialized:template:$id");
        if ($serialized) {
            // return $serialized;
        }

        $thumbnails = $this->getTemplateThumbnails($id, $version);

        if ($template instanceof Template) {
            $serialized = [
                'id'          => $id,
                'createdAt'   => $template->getCreatedAt(),
                'updatedAt'   => $template->getUpdatedAt() ? $template->getUpdatedAt()->getTimestamp() : 0,
                'oid'         => $template->getOrganization()->getId(),
                'title'       => $template->getTitle(),
                'thumbnail'   => $thumbnails['lg'],
                'thumbnailSm' => $thumbnails['sm'],
                'version'     => $version,
                'uid'         => $template->getUser()->getId(),
                'tmhEnabled'  => $template->isTmhEnabled()
            ];
        } else {
            $serialized = [
                'id'          => $id,
                'createdAt'   => (int)$template['tmp_created_at'],
                'updatedAt'   => (int)(strtotime($template['tmp_updated_at'])),
                'oid'         => (int)$template['tmp_org_id'],
                'title'       => $template['tmp_title'],
                'thumbnail'   => $thumbnails['lg'],
                'thumbnailSm' => $thumbnails['sm'],
                'version'     => $version,
                'uid'         => (int)$template['tmp_usr_id'],
                'tmhEnabled'  => (bool)$template['tmp_tmh_enabled'],
            ];
        }

        $this->cache->set("serialized:template:$id", $serialized, CacheInterface::ONE_MONTH, [
            new SerializedTemplateTag($id),
        ]);

        return $serialized;
    }

    /**
     * @param array|Template $layout
     *
     * @return array
     * @throws Exception
     */
    public function serializeLayout($layout): array
    {
        if ($layout instanceof Template) {
            $id      = $layout->getId();
            $version = $layout->getVersion();
        } else {
            $id      = (int)$layout['tmp_id'];
            $version = $layout['tmp_version'];
        }
        $serialized = $this->cache->get("serialized:layout:$id");
        if ($serialized) {
            // return $serialized;
        }

        if ($layout instanceof Template) {
            $thumbnails = $this->getLayoutThumbnails($id, $layout->getParent(), $version);
            $serialized = [
                'type'              => 'layout',
                'id'                => $id,
                'title'             => $layout->getTitle(),
                'version'           => (int)$version,
                'upgrading'         => false,
                'screenshotDesktop' => $thumbnails['lg'],
                'screenshotMobile'  => $thumbnails['sm'],
            ];
        } else {
            $thumbnails = $this->getLayoutThumbnails($id, $layout['tmp_parent'], $version);
            $serialized = [
                'type'              => 'layout',
                'id'                => (int)$layout['tmp_id'],
                'title'             => $layout['tmp_title'],
                'version'           => (int)$version,
                'upgrading'         => false,
                'screenshotDesktop' => $thumbnails['lg'],
                'screenshotMobile'  => $thumbnails['sm'],
            ];
        }

        $this->cache->set("serialized:layout:$id", $serialized, CacheInterface::ONE_MONTH, [
            new SerializedTemplateTag($id),
        ]);

        return $serialized;
    }

    /**
     * @param array $email
     *
     * @return array
     */
    public function serializeEmail(array $email): array
    {
        return [
            'id'            => (int)$email['ema_id'],
            'createdAt'     => (int)$email['ema_created_at'],
            'updatedAt'     => (int)$email['ema_updated_at'],
            'createdUserID' => (int)$email['ema_created_usr_id'],
            'updatedUserID' => (int)$email['ema_updated_usr_id'],
            'title'         => $email['ema_title'],
            'token'         => $email['ema_token'],
            'fid'           => (int)$email['ema_folder_id'],
            'tid'           => (int)$email['ema_tmp_id'],
        ];
    }

    /**
     * @param array $folder
     *
     * @return array
     */
    public function serializeFolder(array $folder): array
    {
        return [
            'id'        => (int)$folder['fld_id'],
            'pid'       => (int)$folder['fld_parent_id'],
            'name'      => $folder['fld_name'],
            'createdAt' => strtotime($folder['fld_created_at']),
            'updatedAt' => strtotime($folder['fld_updated_at'])
        ];
    }

    /**
     * @param array $org
     *
     * @return array
     */
    public function serializeOrganization(array $org): array
    {
        return [
            'id'   => $org['org_id'],
            'name' => $org['org_name']
        ];
    }

    /**
     * @param Notice $notice
     *
     * @return array
     */
    public function serializeNotice(Notice $notice): array
    {
        return [
            'id'      => $notice->getId(),
            'content' => $notice->getContent()
        ];
    }

    /**
     * @param Source $source
     *
     * @return array
     */
    public function serializeSource(Source $source): array
    {
        return [
            'id'          => $source->getId(),
            'integration' => $this->serializeIntegration($source->getIntegration()),
            'name'        => $source->getName()
        ];
    }

    /**
     * @param IntegrationInterface $integration
     *
     * @return array
     */
    public function serializeIntegration(IntegrationInterface $integration): array
    {
        return [
            'displayName'     => $integration->getDisplayName(),
            'iconURL'         => $integration->getIconURL(),
            'instructionsURL' => $integration->getInstructionsURL(),
            'slug'            => $integration->getSlug()
        ];
    }

    /**
     * @param BillingPlan $billingPlan
     * @param User        $user
     *
     * @return array
     * @throws Exception
     */
    public function serializeBillingPlan(BillingPlan $billingPlan, User $user): array
    {
        $billingPlan->getNextBillingDate()->setTimezone(new DateTimeZone($user->getTimezone()));

        return [
            'id'                => $billingPlan->getId(),
            'isSolo'             => $billingPlan->isSolo(),
            'isTeam'             => $billingPlan->isTeam(),
            'isTrial'            => $billingPlan->isTrial(),
            'isTrialComplete'    => $billingPlan->isTrialComplete(),
            'isDeclined'         => $billingPlan->isDeclined(),
            'isPaused'           => $billingPlan->isPaused(),
            'isDowngraded'       => $billingPlan->isDowngraded(),
            'isTrialIntegration' => $billingPlan->isTrialIntegration(),
            'nextBillingDate'    => $billingPlan->getNextBillingDate($billingPlan->isTrial() ? 1 : 0)->format('F j, Y'),
            'daysUntilTrialEnds' => $billingPlan->getDaysUntilTrialEnds()
        ];
    }

    /**
     * @param Invitation $invite
     *
     * @return array
     */
    public function serializeInvite(Invitation $invite): array
    {
        return [
            'id'    => $invite->getId(),
            'name'  => $invite->getName(),
            'email' => $invite->getEmail(),
        ];
    }

    /**
     * @param Invoice $invoice
     * @param User    $user
     *
     * @return array
     */
    public function serializeInvoice(Invoice $invoice, User $user): array
    {
        $items = [];
        foreach($invoice->getItems() as $item) {
            $items[] = $this->serializeInvoiceItem($item);
        }

        $invoice->getDateCreated()->setTimezone(new DateTimeZone($user->getTimezone()));

        return [
            'id'          => $invoice->getId(),
            'amountCents' => $invoice->getAmountCents(),
            'fileUrl'     => $invoice->getFileUrl(),
            'description' => $invoice->getDescription(),
            'dateCreated' => $invoice->getDateCreated()->format('F j, Y'),
            'items'       => $items
        ];
    }

    /**
     * @param InvoiceItem $invoiceItem
     *
     * @return array
     */
    public function serializeInvoiceItem(InvoiceItem $invoiceItem): array
    {
        return [
            'id'          => $invoiceItem->getId(),
            'amountCents' => $invoiceItem->getAmountCents(),
            'type'        => $invoiceItem->getType(),
            'description' => $invoiceItem->getDescription()
        ];
    }

    /**
     * @param CreditCard|null $creditCard
     *
     * @return array
     */
    public function serializeCreditCard(?CreditCard $creditCard): ?array
    {
        if (!$creditCard) {
            return null;
        }

        return [
            'brand'   => $creditCard->getBrand(),
            'number4' => $creditCard->getNumber4()
        ];
    }

    /**
     * @param EmailHistory $emailHistory
     *
     * @return array
     * @throws Exception
     */
    public function serializeEmailHistory(EmailHistory $emailHistory): array
    {
        $id         = $emailHistory->getId();
        $serialized = $this->cache->get("serialized:emailHistory:$id");
        if ($serialized) {
            // return $serialized;
        }

        $user = $this->userRepository->findByID($emailHistory->getUsrId(), true);
        if ($user) {
            $emailHistory->getDateCreated()->setTimezone(new DateTimeZone($user->getTimezone()));
            $user = $this->serializeUser($user);
        }

        $serialized = [
            'id'          => $id,
            'eid'         => $emailHistory->getEmaId(),
            'version'     => $emailHistory->getVersion(),
            'user'        => $user,
            'message'     => $emailHistory->getMessage(),
            'dateCreated' => $emailHistory->getDateCreated()->getTimestamp(),
        ];
        $this->cache->set("serialized:emailHistory:$id", $serialized, CacheInterface::ONE_MONTH, [
            new SerializedHistoryTag('email', $id),
        ]);

        return $serialized;
    }

    /**
     * @param TemplateHistory $templateHistory
     *
     * @return array
     * @throws Exception
     */
    public function serializeTemplateHistory(TemplateHistory $templateHistory): array
    {
        $id         = $templateHistory->getId();
        $serialized = $this->cache->get("serialized:templateHistory:$id");
        if ($serialized) {
            // return $serialized;
        }

        $user = $this->userRepository->findByID($templateHistory->getUsrId(), true);
        if ($user) {
            $templateHistory->getDateCreated()->setTimezone(new DateTimeZone($user->getTimezone()));
            $user = $this->serializeUser($user);
        }

        $serialized = [
            'id'          => $id,
            'tid'         => $templateHistory->getTmpId(),
            'version'     => $templateHistory->getVersion(),
            'user'        => $user,
            'message'     => $templateHistory->getMessage(),
            'dateCreated' => $templateHistory->getDateCreated()->getTimestamp(),
        ];
        $this->cache->set("serialized:templateHistory:$id", $serialized, CacheInterface::ONE_MONTH, [
            new SerializedHistoryTag('template', $id),
        ]);

        return $serialized;
    }

    /**
     * @param PinGroup $pinGroup
     *
     * @return array
     */
    public function serializePinGroup(PinGroup $pinGroup): array
    {
        return [
            'id'   => $pinGroup->getId(),
            'name' => $pinGroup->getName(),
        ];
    }

    /**
     * @param Comment $comment
     *
     * @return array
     * @throws Exception
     */
    public function serializeComment(Comment $comment): array
    {
        $emojis = [];
        foreach($comment->getEmojis() as $i => $emoji) {
            $emojis[] = $this->serializeEmojis($emoji);
        }

        $email = $comment->getEmail();

        return [
            'id'          => $comment->getId(),
            'email'       => [
                'id'    => $email->getId(),
                'tid'   => $email->getTemplate()->getId(),
                'title' => $email->getTitle(),
            ],
            'content'     => $comment->getContent(),
            'user'        => $this->serializeUserLight($comment->getUser()),
            'parent'      => $comment->getParent() ? $comment->getParent()->getId() : null,
            'status'      => $comment->getStatus(),
            'emojis'      => $emojis,
            'blockId'     => $comment->getBlockId(),
            'dateCreated' => $comment->getDateCreated()->getTimestamp()
        ];
    }

    /**
     * @param Mention $mention
     *
     * @return array
     * @throws Exception
     */
    public function serializeMention(Mention $mention): array
    {
        return [
            'id'          => $mention->getId(),
            'uuid'        => $mention->getUuid(),
            'user'        => $this->serializeUserLight($mention->getUser()),
            'comment'     => $this->serializeComment($mention->getComment()),
            'dateCreated' => $mention->getDateCreated()->getTimestamp()
        ];
    }

    /**
     * @param Emoji $emoji
     *
     * @return array
     */
    public function serializeEmojis(Emoji $emoji): array
    {
        return [
            'id'        => $emoji->getId(),
            'uuid'      => $emoji->getUuid(),
            'code'      => $emoji->getCode(),
            'timeAdded' => $emoji->getDateCreated()->getTimestamp(),
            'user'      => $this->serializeUserLight($emoji->getUser()),
        ];
    }

    /**
     * @param Notification $notification
     *
     * @return array
     * @throws Exception
     */
    public function serializeNotification(Notification $notification): array
    {
        $mention = null;
        $comment = null;
        $from    = null;
        if ($notification->getMention()) {
            $mention = $this->serializeMention($notification->getMention());
        }
        if ($notification->getComment()) {
            $comment = $this->serializeComment($notification->getComment());
        }
        if ($notification->getFrom()) {
            $from = $this->serializeUserLight($notification->getFrom());
        }

        return [
            'id'          => $notification->getId(),
            'to'          => $this->serializeUserLight($notification->getTo()),
            'from'        => $from,
            'action'      => $notification->getAction(),
            'mention'     => $mention,
            'comment'     => $comment,
            'message'     => $notification->getMessage(),
            'status'      => $notification->getStatus(),
            'dateCreated' => $notification->getDateCreated()->getTimestamp()
        ];
    }

    /**
     * @param ChecklistItem $item
     *
     * @return array
     */
    public function serializeChecklistItem(ChecklistItem $item): array
    {
        return [
            'id'          => $item->getId(),
            'template'    => $item->getTemplate()->getId(),
            'email'       => $item->getEmail()->getId(),
            'key'         => $item->getKey(),
            'title'       => $item->getTitle(),
            'description' => $item->getDescription(),
            'checked'     => $item->isChecked(),
            'checkedUser' => $item->getCheckedUser() ? $this->serializeUserLight($item->getCheckedUser()) : null,
            'dateCreated' => $item->getDateCreated()->getTimestamp()
        ];
    }

    /**
     * @param int $id
     * @param int $version
     *
     * @return array
     * @throws Exception
     */
    protected function getTemplateThumbnails(int $id, int $version): array
    {
        $thumbnails = $this->cache->get("serialized:thumbnails:template:$id");
        $thumbnails = false;
        if (!$thumbnails || !isset($thumbnails['sm']) || !isset($thumbnails['lg'])) {
            $templateHistory = $this->templateHistoryRepository->findByTemplateVersion(
                $id,
                $version
            );
            if ($templateHistory && $templateHistory->getThumbNormal()) {
                $thumbnail = $templateHistory->getThumbNormal();
            } else {
                $thumbnail = $this->paths->urlTemplateScreenshot($id, Paths::SCREENSHOT);
            }
            if ($templateHistory && $templateHistory->getThumb200()) {
                $thumbnailSm = $templateHistory->getThumb200();
            } else {
                $thumbnailSm = $this->paths->urlTemplateScreenshot($id, Paths::SCREENSHOT_200);
            }

            $thumbnails = [
                'sm' => $thumbnailSm,
                'lg' => $thumbnail
            ];
            $this->cache->set("serialized:thumbnails:template:$id", $thumbnails, CacheInterface::ONE_MONTH, [
                new SerializedThumbnailsTag('template', $id),
            ]);
        }

        return $thumbnails;
    }

    /**
     * @param int $id
     * @param int $pid
     * @param int $version
     *
     * @return array
     * @throws Exception
     */
    protected function getLayoutThumbnails(int $id, int $pid, int $version): array
    {
        $thumbnails = $this->cache->get("serialized:thumbnails:layout:$id");
        $thumbnails = false;
        if (!$thumbnails || !isset($thumbnails['sm']) || !isset($thumbnails['lg'])) {
            $templateHistory = $this->templateHistoryRepository->findByTemplateVersion(
                $id,
                $version
            );

            if ($templateHistory && $templateHistory->getThumbNormal()) {
                $thumbnail = $templateHistory->getThumbNormal();
            } else {
                $thumbnail = $this->paths->urlLayoutScreenshot($pid, $id, Paths::SCREENSHOT);
            }
            if ($templateHistory && $templateHistory->getThumb200()) {
                $thumbnailSm = $templateHistory->getThumb200();
            } else {
                $thumbnailSm = $this->paths->urlLayoutScreenshot($pid, $id, Paths::SCREENSHOT_200);
            }

            $thumbnails = [
                'sm' => $thumbnailSm,
                'lg' => $thumbnail
            ];
            $this->cache->set("serialized:thumbnails:layout:$id", $thumbnails, CacheInterface::ONE_MONTH, [
                new SerializedThumbnailsTag('template', $id),
            ]);
        }

        return $thumbnails;
    }
}
