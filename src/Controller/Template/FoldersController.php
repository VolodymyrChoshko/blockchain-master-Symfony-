<?php
namespace Controller\Template;

use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\Annotations\InjectTemplate;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\System\Serializer;
use Exception;
use Redis;
use Repository\EmailRepository;
use Repository\FoldersRepository;
use Repository\OrganizationAccessRepository;

/**
 * @IsGranted({"USER"})
 */
class FoldersController extends Controller
{
    /**
     * @IsGranted("template")
     * @Route("/api/v1/templates/{id}/folders/{fid}", name="templates_folders_update", methods={"POST"})
     */
    public function updateFolderAction(int $fid, Request $request, FoldersRepository $foldersRepository): JsonResponse
    {
        $title = trim($request->json->get('title'));
        if (!$title) {
            return $this->json('ok');
        }

        $foldersRepository->rename($fid, $title);

        return $this->json('ok');
    }

    /**
     * @IsGranted("template")
     * @Route("/api/v1/templates/{id}/folders", name="templates_folders_create", methods={"PUT"})
     * @InjectTemplate()
     *
     * @param int               $id
     * @param int               $uid
     * @param Request           $request
     * @param Serializer        $serializer
     * @param FoldersRepository $foldersRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function createFolderAction(
        int $id,
        int $uid,
        Request $request,
        Serializer $serializer,
        FoldersRepository $foldersRepository
    ): JsonResponse
    {
        $name     = trim($request->json->get('name'));
        $parentId = $request->json->get('parent_id', 0);

        if (empty($name)) {
            return $this->json([
                'error'   => true,
                'message' => 'The folder needs a name.'
            ]);
        }

        if ($parentId) {
            if (!$foldersRepository->fetchById($parentId)) {
                return $this->json([
                    'error'   => true,
                    'message' => 'Folder does not exist.'
                ]);
            }
            if (!$this->hasFolderAccess($uid, $parentId)) {
                return $this->json([
                    'error'   => true,
                    'message' => 'You do not have access to the folder.'
                ]);
            }
        }

        $folderId = $foldersRepository->create($id, $name, $parentId);
        if (!$folderId) {
            return $this->json([
                'error'   => true,
                'message' => 'Oops, looks like there was a problem.'
            ]);
        }

        $folder = $foldersRepository->fetchById($folderId);
        $folder = $serializer->serializeFolder($folder);

        return $this->json($folder);
    }

    /**
     * @IsGranted("template")
     * @Route("/api/v1/templates/{id}/folders", name="templates_folders_move", methods={"POST"})
     *
     * @param int               $uid
     * @param Request           $request
     * @param EmailRepository   $emailRepository
     * @param FoldersRepository $foldersRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function moveFolderAction(
        int $uid,
        Request $request,
        EmailRepository $emailRepository,
        FoldersRepository $foldersRepository
    ): JsonResponse {
        $action = $request->json->get('action');
        $fid    = $request->json->get('fid');
        $eid    = $request->json->get('eid');
        $cfid   = $request->json->get('cfid');

        if ($eid) {
            $email = $emailRepository->findByID($eid);
            if (!$email) {
                return $this->json([
                    'error' => 'Unknown email.'
                ]);
            }
            if (!$emailRepository->hasAccess($uid, $eid)) {
                return $this->json([
                    'error' => 'You do not have access to the email.'
                ]);
            }
        }
        if ($fid) {
            $folder = $foldersRepository->fetchById($fid);
            if (!$folder) {
                return $this->json([
                    'error' => 'Folder does not exist.'
                ]);
            }
            if (!$this->hasFolderAccess($uid, $fid)) {
                return $this->json([
                    'error' => 'You do not have access to the folder.'
                ]);
            }
        }
        if ($cfid) {
            $childFolder = $foldersRepository->fetchById($cfid);
            if (!$childFolder) {
                return $this->json([
                    'error' => 'Folder does not exist.'
                ]);
            }
            if (!$this->hasFolderAccess($uid, $cfid)) {
                return $this->json([
                    'error' => 'You do not have access to the folder.'
                ]);
            }
        }

        if ($action === 'append_folder') {
            if ($eid) {
                if ($emailRepository->updateFolder($eid, $fid)) {
                    return $this->json([
                        'success' => 'ok'
                    ]);
                }
            } else if ($cfid) {
                try {
                    if ($foldersRepository->moveFolder($fid, $cfid)) {
                        return $this->json([
                            'success' => 'ok'
                        ]);
                    }
                } catch (Exception $e) {
                    return $this->json([
                        'error' => 'Cannot move parent folder into child folder.'
                    ]);
                }
            }
        } else if ($action === 'detach_email') {
            if ($emailRepository->updateFolder($eid, null)) {
                return $this->json([
                    'success' => 'ok'
                ]);
            }
        } else if ($action === 'detach_folder') {
            try {
                if ($foldersRepository->moveFolder($fid, 0)) {
                    return $this->json([
                        'success' => 'ok'
                    ]);
                }
            } catch (Exception $e) {
                return $this->json([
                    'error' => 'Cannot move parent folder into child folder.'
                ]);
            }
        }

        /*
        if ($action === 'append_folder') {
            $email = $emailRepository->findByID($eid);
            if (!$email) {
                return $this->json([
                    'error' => 'Unknown email.'
                ]);
            }

            if (strpos($fid, 'archive-') === 0) {
                if (!$emailRepository->hasAccess($uid, $eid)) {
                    return $this->json([
                        'error' => 'You do not have access to the email.'
                    ]);
                }
                $templatesRepository->archiveEmail($uid, $eid);
                return $this->json([
                    'success' => 'ok'
                ]);
            } else {
                $folder = $foldersRepository->fetchById($fid);
                if (!$folder) {
                    return $this->json([
                        'error' => 'Folder does not exist.'
                    ]);
                }

                if (!$this->hasFolderAccess($uid, $fid)) {
                    return $this->json([
                        'error' => 'You do not have access to the folder.'
                    ]);
                }

                if ($email['ema_archived']) {
                    $templatesRepository->unArchiveEmail($uid, $eid);
                }
                if ($foldersRepository->moveEmail($fid, $eid)) {
                    return $this->json([
                        'success' => 'ok'
                    ]);
                }
            }
        } else if ($action === 'detach_folder') {
            $email = $emailRepository->findByID($eid);
            if (!$email) {
                return $this->json([
                    'error' => 'Unknown email.'
                ]);
            }
            if (!$emailRepository->hasAccess($uid, $eid)) {
                return $this->json([
                    'error' => 'You do not have access to the email.'
                ]);
            }
            if ($email['ema_archived']) {
                $templatesRepository->unArchiveEmail($uid, $eid);
            }
            if ($foldersRepository->detachEmail($eid)) {
                return $this->json([
                    'success' => 'ok'
                ]);
            }
        } else if ($action === 'move_folder') {
            if ($cfid == '0') {
                $parentFolder = $foldersRepository->fetchById($fid);
                if (!$parentFolder) {
                    return $this->json([
                        'error' => 'Invalid folders.'
                    ]);
                }
                if (!$this->hasFolderAccess($uid, $fid)) {
                    return $this->json([
                        'error' => 'You do not have access to the folders.'
                    ]);
                }
                try {
                    if ($foldersRepository->moveFolder($fid, 0)) {
                        return $this->json([
                            'success' => 'ok'
                        ]);
                    }
                } catch (Exception $e) {
                    return $this->json([
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                $parentFolder = $foldersRepository->fetchById($fid);
                $childFolder  = $foldersRepository->fetchById($cfid);
                if (!$parentFolder || !$childFolder) {
                    return $this->json([
                        'error' => 'Invalid folders.'
                    ]);
                }
                if (!$this->hasFolderAccess($uid, $fid) || !$this->hasFolderAccess($uid, $cfid)) {
                    return $this->json([
                        'error' => 'You do not have access to the folders.'
                    ]);
                }
                try {
                    if ($foldersRepository->moveFolder($fid, $cfid)) {
                        return $this->json([
                            'success' => 'ok'
                        ]);
                    }
                } catch (Exception $e) {
                    return $this->json([
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        */

        return $this->json([
            'success' => 'ok'
        ]);
    }

    /**
     * @IsGranted("template")
     * @Route("/api/v1/templates/{id}/folders/{fid}", name="templates_folder_delete", methods={"DELETE"})
     *
     * @param int               $fid
     * @param int               $uid
     * @param FoldersRepository $foldersRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function removeFolderAction(
        int $fid,
        int $uid,
        FoldersRepository $foldersRepository
    ): JsonResponse
    {
        if (!$foldersRepository->fetchById($fid)) {
            return $this->json('Folder does not exist.');
        }
        if (!$this->hasFolderAccess($uid, $fid)) {
            return $this->json('No access to folder.');
        }

        return $this->json($foldersRepository->remove($fid) ? 1 : 0);
    }

    /**
     * @IsGranted("template")
     * @Route("/api/v1/templates/{id}/folders/{fid}/collapse", name="templates_folder_collapse", methods={"POST"})
     *
     * @param int               $id
     * @param int               $fid
     * @param int               $uid
     * @param Redis             $redis
     * @param Request           $request
     * @param FoldersRepository $foldersRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function collapseAction(
        int $id,
        int $fid,
        int $uid,
        Redis $redis,
        Request $request,
        FoldersRepository $foldersRepository
    ): JsonResponse
    {
        if (!$foldersRepository->fetchById($fid)) {
            return $this->json('Folder does not exist.');
        }
        if (!$this->hasFolderAccess($uid, $fid)) {
            return $this->json('No access to folder.');
        }

        $collapsed = $request->json->getInt('collapsed');
        $key = sprintf('folders:%d:%d', $id, $uid);
        $redis->hSet($key, (string)$fid, (string)$collapsed);

        return $this->json('ok');
    }

    /**
     * @param int $uid
     * @param int $fid
     *
     * @return bool
     * @throws Exception
     */
    protected function hasFolderAccess(int $uid, int $fid): bool
    {
        $hasAccess = $this->container->get(FoldersRepository::class)->hasAccess($uid, $fid);
        if (!$hasAccess) {
            $template = $this->container->get(FoldersRepository::class)->fetchTemplate($fid);
            if ($template) {
                $hasAccess = $this->container->get(OrganizationAccessRepository::class)
                        ->isOwner($uid, $template['tmp_org_id']);
                if (!$hasAccess) {
                    $hasAccess = $this->container->get(OrganizationAccessRepository::class)
                        ->isAdmin($uid, $template['tmp_org_id']);
                }
            }
        }

        return $hasAccess;
    }
}
