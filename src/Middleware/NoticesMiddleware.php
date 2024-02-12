<?php
namespace Middleware;

use BlocksEdit\Http\Middleware;
use BlocksEdit\View\View;
use Entity\NoticeSeen;
use Exception;
use Repository\NoticeRepository;
use Repository\NoticeSeenRepository;

/**
 * Class NoticesMiddleware
 */
class NoticesMiddleware extends Middleware
{
    /**
     * @return int
     */
    public function getPriority(): int
    {
        return 2;
    }

    /**
     * @param array $user
     *
     * @throws Exception
     */
    public function request(array $user)
    {
        View::setGlobal('notices', []);

        if (!empty($user['usr_date_prev_login'])) {
            $noticeRepo     = $this->container->get(NoticeRepository::class);
            $noticeSeenRepo = $this->container->get(NoticeSeenRepository::class);
            $notices        = $noticeRepo->findAfterDate($user['usr_date_prev_login']);
            foreach($notices as $notice) {
                $seen = $noticeSeenRepo->findByNoticeAndUser($notice->getId(), $user['usr_id']);
                if (!$seen) {
                    $seen = (new NoticeSeen())
                        ->setNtcId($notice->getId())
                        ->setUsrId($user['usr_id'])
                        ->setIsClosed(false);
                    $noticeSeenRepo->insert($seen);
                }
            }

            $notices = [];
            $seens = $noticeSeenRepo->findUnclosedByUser($user['usr_id']);
            foreach($seens as $seen) {
                $notice = $noticeRepo->findByID($seen->getNtcId());
                if ($notice) {
                    $notices[] = $notice;
                }
            }
            $notices = array_slice($notices, 0, 3);

            View::setGlobal('notices', $notices);
        }
    }
}
