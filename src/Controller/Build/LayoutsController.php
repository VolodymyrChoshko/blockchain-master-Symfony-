<?php
namespace Controller\Build;

use BlocksEdit\Http\Annotations\InjectEmail;
use BlocksEdit\Http\Annotations\InjectTemplate;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Util\Strings;
use Entity\Image;
use Exception;
use Repository\Exception\CreateTemplateException;
use Repository\ImagesRepository;
use Repository\TemplatesRepository;
use Service\TemplateImporter;

/**
 * @IsGranted({"USER"})
 */
class LayoutsController extends BuildController
{
    /**
     * @Route("/build/layout/{id}", name="build_layout", methods={"GET"})
     * @InjectTemplate()
     *
     * @param array $template
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function getAction(array $template): JsonResponse
    {
        $templates = $this->filterLayouts([$template]);

        return $this->json($templates[0]);
    }

    /**
     * @Route("/build/layout/{id}/settings", name="build_layout_settings", methods={"POST"})
     *
     * @param int                 $id
     * @param int                 $uid
     * @param Request             $request
     * @param TemplatesRepository $templatesRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function settingsAction(
        int $id,
        int $uid,
        Request $request,
        TemplatesRepository $templatesRepository
    ): JsonResponse {
        $title  = $request->json->getOrBadRequest('title');
        $result = $templatesRepository->updateTitle($uid, $id, $title);

        return $this->json($result);
    }

    /**
     * @Route("/build/layout/{id}/html", name="build_layout_get_html", methods={"GET"})
     *
     * @param int                 $id
     * @param TemplatesRepository $templatesRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function htmlGetAction(int $id, TemplatesRepository $templatesRepository): JsonResponse
    {
        $html = $templatesRepository->getHtml($id)->getHtml();

        return $this->json([
            'html' => $html,
        ]);
    }

    /**
     * @Route("/build/layout/htmls", name="build_layout_get_htmls", methods={"POST"})
     *
     * @param Request             $request
     * @param TemplatesRepository $templatesRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function htmlsGetAction(Request $request, TemplatesRepository $templatesRepository): JsonResponse
    {
        $htmls = [];
        $ids   = $request->json->getArray('ids');
        foreach($ids as $id) {
            $htmls[$id] = $templatesRepository->getHtml($id)->getHtml();
        }

        return $this->json([
            'htmls' => $htmls,
            'ids' => $ids
        ]);
    }

    /**
     * @Route("/build/layout/{id}/html", name="build_layout_save_html", methods={"POST"})
     *
     * @param int                 $id
     * @param int                 $uid
     * @param int                 $oid
     * @param Request             $request
     * @param TemplatesRepository $templatesRepository
     * @param TemplateImporter    $templateImporter
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function htmlSaveAction(
        int $id,
        int $uid,
        int $oid,
        Request $request,
        TemplatesRepository $templatesRepository,
        TemplateImporter $templateImporter
    ): JsonResponse
    {
        $layout = $templatesRepository->findByID($id);
        if (!$layout) {
            $this->throwNotFound();
        }

        $html     = $request->json->get('html');
        $tempFile = tempnam(sys_get_temp_dir(), 'upload');

        try {
            $this->files->write($tempFile, $html);
            $file = [
                'name'        => $layout['tmp_location'],
                'type'        => 'text/html',
                'be_tmp_name' => $tempFile,
                'size'        => strlen($html)
            ];

            $uuid = Strings::uuid();
            $request->finishRequest(json_encode($uuid));
            $templateImporter->createNewLayout($uid, $oid, $id, $file, $uuid);
        } catch (CreateTemplateException $e) {
            return $this->json([
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        } finally {
            $this->files->remove($tempFile);
        }

        return $this->json('ok');
    }

    /**
     * @IsGranted({"email"})
     * @Route("/build/layout/{id}/{lid}", name="build_layout_load", methods={"POST"})
     * @InjectEmail()
     *
     * @param int                 $uid
     * @param int                 $oid
     * @param array               $email
     * @param int                 $lid
     * @param Request             $request
     * @param ImagesRepository    $imagesRepository
     * @param TemplatesRepository $templatesRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function openAction(
        int $uid,
        int $oid,
        array $email,
        int $lid,
        Request $request,
        ImagesRepository $imagesRepository,
        TemplatesRepository $templatesRepository
    ): JsonResponse {
        $template = $templatesRepository->findById($lid);
        if (!$templatesRepository->hasAccess($uid, $template['tmp_parent'])) {
            $this->throwUnauthorized();
        }

        $emailVersion = $request->json->getInt('emailVersion');
        $dom = $templatesRepository->getHtml($lid)->getDom();
        foreach($dom->find('img') as $item) {
            $imgID = (int)$item->getAttribute('data-be-img-id');
            if ($imgID) {
                $image = $imagesRepository->findByID($imgID);
                if ($image) {
                    $newImage = (new Image())
                        ->setOrgId($oid)
                        ->setEmaId($email['ema_id'])
                        ->setIsHosted($image->isHosted())
                        ->setFilename($image->getFilename())
                        ->setCdnUrl($image->getCdnUrl())
                        ->setEmaVersion($emailVersion);
                    $imagesRepository->insert($newImage);
                    $item->setAttribute('data-be-img-id', $newImage->getId());
                }

                continue;
            }

            if ($item->getAttribute('data-be-hosted') === '1') {
                $src = $item->getAttribute('src');
                $filename = pathinfo($src, PATHINFO_BASENAME);
                $image = (new Image())
                    ->setOrgId($oid)
                    ->setEmaId($email['ema_id'])
                    ->setIsHosted(true)
                    ->setFilename($filename)
                    ->setCdnUrl($src)
                    ->setEmaVersion($emailVersion);
                $imagesRepository->insert($image);
                $item->setAttribute('data-be-img-id', $image->getId());
            }
        }

        // $imagify->convert($dom, $email['ema_token']);

        return $this->json([
            'html' => (string)$dom
        ]);
    }

    /**
     * @Route("/build/layout/{id}", name="build_layout_delete", methods={"DELETE"})
     *
     * @param int                 $id
     * @param TemplatesRepository $templatesRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function deleteAction(
        int $id,
        TemplatesRepository $templatesRepository
    ): JsonResponse {
        $result = $templatesRepository->deleteByID($id);

        return $this->json($result);
    }
}
