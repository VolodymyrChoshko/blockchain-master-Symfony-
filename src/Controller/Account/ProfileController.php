<?php
namespace Controller\Account;

use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\IO\Exception\IOException;
use BlocksEdit\Media\CDNInterface;
use BlocksEdit\Media\Images;
use BlocksEdit\IO\Paths;
use BlocksEdit\Util\Media;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Response;
use Exception;
use Entity\User;
use Gumlet\ImageResize;
use Gumlet\ImageResizeException;
use Redis;
use RedisException;
use Repository\UserRepository;
use Repository\Exception\ChangePasswordException;
use Repository\Exception\UpdateException;

/**
 * @IsGranted({"USER"})
 */
class ProfileController extends Controller
{
    /**
     * @Route("/profile", name="profile_index", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(Request $request): Response
    {
        return $this->renderFrontend($request);
    }

    /**
     * @Route("/api/v1/profile", name="api_v1_profile", methods={"POST"})
     *
     * @param int            $uid
     * @param User           $user
     * @param Redis          $redis
     * @param Request        $request
     * @param CDNInterface   $cdn
     * @param UserRepository $userRepository
     *
     * @return Response
     * @throws Exception
     */
    public function loadAction(
        int $uid,
        User $user,
        Redis $redis,
        Request $request,
        CDNInterface $cdn,
        UserRepository $userRepository
    ): Response {
        try {
            $values   = $request->json->all();
            $darkMode = null;
            if ($values['theme'] !== 'auto') {
                $darkMode = $values['theme'] === 'dark';
            }

            $user
                ->setEmail($values['email'] ?? '')
                ->setName($values['name'] ?? '')
                ->setJob($values['job'] ?? '')
                ->setOrganization($values['organization'] ?? '')
                ->setTimezone($values['timezone'] ?? '')
                ->setIsDarkMode($darkMode)
                ->setSkinTone($values['skinTone']);
            $userRepository->update($user);

            $avatar = $request->json->get('avatar');
            if ($avatar) {
                $key  = sprintf('%d-%s', $uid, $avatar);
                $urls = $redis->get($key);
                if ($urls) {
                    if ($user->getAvatar60()) {
                        $cdn->removeByURL($user->getAvatar60());
                    }
                    if ($user->getAvatar120()) {
                        $cdn->removeByURL($user->getAvatar120());
                    }
                    if ($user->getAvatar240()) {
                        $cdn->removeByURL($user->getAvatar240());
                    }
                    $urls = json_decode($urls, true);
                    $user
                        ->setAvatar('')
                        ->setAvatar60($urls[0])
                        ->setAvatar120($urls[1])
                        ->setAvatar240($urls[2]);
                    $userRepository->update($user);
                    $redis->del($key);
                }
            } else {
                if ($user->getAvatar60()) {
                    $cdn->removeByURL($user->getAvatar60());
                }
                if ($user->getAvatar120()) {
                    $cdn->removeByURL($user->getAvatar120());
                }
                if ($user->getAvatar240()) {
                    $cdn->removeByURL($user->getAvatar240());
                }
                $user
                    ->setAvatar('')
                    ->setAvatar60('')
                    ->setAvatar120('')
                    ->setAvatar240('');
                $userRepository->update($user);
            }
        } catch (UpdateException $e) {
            return $this->json([
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        }

        return $this->json('ok');
    }

    /**
     * @Route("/api/v1/profile/notifications", name="api_v1_profile_save_notifications", methods={"POST"})
     *
     * @param int            $oid
     * @param User           $user
     * @param Request        $request
     * @param UserRepository $userRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function saveNotificationsAction(
        int $oid,
        User $user,
        Request $request,
        UserRepository $userRepository
    ): JsonResponse {
        try {
            $values = $request->json->all();

            $webPushSubscription = $values['webPushSubscription'];
            if ($webPushSubscription) {
                $webPushSubscription = json_decode($webPushSubscription, true);
                if (empty($webPushSubscription['endpoint'])
                    || empty($webPushSubscription['keys'])
                    || empty($webPushSubscription['keys']['p256dh'])
                    || empty($webPushSubscription['keys']['auth'])
                ) {
                    $this->throwBadRequest();
                }
            } else {
                $webPushSubscription = $user->getWebPushSubscription();
            }
            if ($webPushSubscription) {
                $webPushSubscription['oid'] = $oid;
            }

            $user->setIsNotificationsEnabled($values['isNotificationsEnabled'])
                ->setIsEmailsEnabled($values['isEmailsEnabled'])
                ->setIsShowingCount($values['isShowingCount'])
                ->setWebPushSubscription($webPushSubscription);
            $userRepository->update($user);
        } catch (UpdateException $e) {
            return $this->json([
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        }

        return $this->json('ok');
    }

    /**
     * @Route("/api/v1/profile/avatar", name="profile_upload_avatar", methods={"POST"})
     *
     * @param int          $uid
     * @param Paths        $paths
     * @param CDNInterface $cdn
     * @param Redis        $redis
     *
     * @return JsonResponse
     * @throws IOException
     * @throws ImageResizeException
     */
    public function uploadAvatarAction(
        int $uid,
        Paths $paths,
        CDNInterface $cdn,
        Redis $redis
    ): JsonResponse
    {
        $name  = $_FILES['file']['name'];
        $type  = $_FILES['file']['type'];
        $error = (int)$_FILES['file']['error'];
        if ($error !== 0) {
            return $this->json(['error' => 'Error uploading image!']);
        }
        if (!Images::isMimeTypeAllowed($type)) {
            return $this->json(['error' => 'File type not allowed!']);
        }
        if ($_FILES['file']['size'] > Images::MAX_IMAGE) {
            return $this->json(['error' => 'File too large. File must be less than 4 megabytes.']);
        }
        $avatar = $paths->dirAvatar($name);
        $this->files->moveUploaded($_FILES['file']['tmp_name'], $avatar);

        $filename  = pathinfo($avatar, PATHINFO_FILENAME);
        $extension = pathinfo($avatar, PATHINFO_EXTENSION);

        $avatar60  = $paths->dirAvatar($filename . '-60x60' . '.' . $extension);
        $cropped   = new ImageResize($avatar);
        $cropped->freecrop(60, 60);
        $cropped->save($avatar60, null, Media::JPEG_QUALITY);

        $avatar120 = $paths->dirAvatar($filename . '-120x120' . '.' . $extension);
        $cropped   = new ImageResize($avatar);
        $cropped->freecrop(120, 120);
        $cropped->save($avatar120, null, Media::JPEG_QUALITY);

        $avatar240 = $paths->dirAvatar($filename . '-240x240' . '.' . $extension);
        $cropped   = new ImageResize($avatar);
        $cropped->freecrop(240, 240);
        $cropped->save($avatar240, null, Media::JPEG_QUALITY);

        $filenames = [
            '60x60' . '.' . $extension,
            '120x120' . '.' . $extension,
            '240x240' . '.' . $extension
        ];
        $localFiles = [
            $avatar60,
            $avatar120,
            $avatar240
        ];
        $urls = $cdn->prefixed($uid)->batchUpload(CDNInterface::SYSTEM_AVATARS, $filenames, $localFiles);
        $key  = sprintf('%d-%s', $uid, $urls[2]);
        $redis->setex($key, 84600, json_encode($urls));

        return $this->json($urls[2]);
    }

    /**
     * @Route("/api/v1/profile/changepassword", name="change_password", methods={"POST"})
     *
     * @param int            $uid
     * @param array          $user
     * @param Request        $request
     * @param UserRepository $userRepository
     *
     * @return Response
     * @throws Exception
     */
    public function passwordAction(
        int $uid,
        array $user,
        Request $request,
        UserRepository $userRepository
    ): Response {
        if ($user['usr_parent_id'] && empty($user['usr_pass'])) {
            return $this->json([
                'error' => 'Cannot change password.'
            ]);
        }

        $password = trim($request->json->get('password'));
        if (!$password) {
            return $this->json([
                'error' => 'Invalid password.'
            ]);
        }

        try {
            $userRepository->changePassword($uid, $password);

            return $this->json('ok');
        } catch (ChangePasswordException $e) {
            return $this->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * @Route("/api/v1/profile/webpush", name="profile_set_webpush")
     *
     * @param User           $user
     * @param Request        $request
     * @param UserRepository $userRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function webPushSubscriptionAction(
        User $user,
        Request $request,
        UserRepository $userRepository
    ): JsonResponse
    {
        $sub = $request->json->get('subscription');
        if (empty($sub['endpoint'])
            || empty($sub['keys'])
            || empty($sub['keys']['p256dh'])
            || empty($sub['keys']['auth'])
        ) {
            $this->throwBadRequest();
        }

        $user->setWebPushSubscription($sub);
        $userRepository->update($user);

        return $this->json('ok');
    }

    /**
     * @Route("/api/v1/profile/skinTone", name="profile_set_skin_tone")
     *
     * @param User           $user
     * @param UserRepository $userRepository
     * @param Request        $request
     *
     * @return JsonResponse
     * @throws UpdateException
     */
    public function skinToneAction(User $user, UserRepository $userRepository, Request $request): JsonResponse
    {
        $skinTone = $request->json->getInt('skinTone');
        if ($skinTone < -1 || $skinTone > 5) {
            $skinTone = -1;
        }
        $user->setSkinTone($skinTone);
        $userRepository->update($user);

        return $this->json('ok');
    }
}
