<?php
namespace Controller\Build;

use BlocksEdit\Html\DomParser;
use BlocksEdit\Html\Imagify;
use BlocksEdit\Http\Annotations\InjectTemplate;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\Exception\BadRequestException;
use BlocksEdit\Http\Exception\NotFoundException;
use BlocksEdit\Http\Exception\UnauthorizedException;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Media\CDNInterface;
use BlocksEdit\Service\WorkerInterface;
use BlocksEdit\System\Serializer;
use BlocksEdit\Util\HttpRequest;
use Entity\PinGroup;
use Entity\SectionLibrary;
use Entity\Template;
use Exception;
use Repository\PinGroupRepository;
use Service\DisplayService;
use Repository\EmailRepository;
use Repository\SectionLibraryRepository;
use Repository\TemplatesRepository;
use Service\LibraryThumbnailsMessageQueue;
use simplehtmldom_1_5\simple_html_dom;

/**
 * @IsGranted({"USER"})
 */
class LibraryController extends BuildController
{
    /**
     * @Route("/build/library/{id}", name="build_library", methods={"GET"})
     *
     * @param int                      $id
     * @param SectionLibraryRepository $sectionLibraryRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function getSingleAction(int $id, SectionLibraryRepository $sectionLibraryRepository): JsonResponse
    {
        $library = $sectionLibraryRepository->findByID($id);

        return $this->json($library);
    }

    /**
     * @Route("/build/libraries", name="build_libraries", methods={"GET"})
     *
     * @param Request             $request
     * @param TemplatesRepository $templatesRepository
     * @param EmailRepository     $emailRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function getAction(
        Request $request,
        TemplatesRepository $templatesRepository,
        EmailRepository $emailRepository
    ): JsonResponse
    {
        die('');
        $id   = $request->query->getInt('id');
        $mode = $request->query->getOrBadRequest('mode');
        if (strpos($mode, 'template') === false) {
            $email = $emailRepository->findByID($id);
            if (!$email) {
                throw new NotFoundException();
            }
            $tid = $email['ema_tmp_id'];
        } else {
            $template = $templatesRepository->findByID($id);
            if (!$template) {
                throw new NotFoundException();
            }
            $tid = $id;
        }

        return $this->json($this->getTemplateLibraries($tid));
    }

    /**
     * @Route("/build/library/htmls", name="build_library_get_htmls", methods={"POST"})
     *
     * @param Request                  $request
     * @param SectionLibraryRepository $sectionLibraryRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function htmlsGetAction(
        Request $request,
        SectionLibraryRepository $sectionLibraryRepository
    ): JsonResponse
    {
        $htmls = [];
        $ids   = $request->json->getArray('ids');
        foreach($ids as $id) {
            $library = $sectionLibraryRepository->findByID($id);
            if ($library) {
                $htmls[$id] = $library->getHtml();
            }
        }

        return $this->json([
            'htmls' => $htmls,
            'ids'   => $ids
        ]);
    }

    /**
     * @Route("/build/library/{id}", name="build_library_update", methods={"POST"})
     *
     * @param int                           $id
     * @param int                           $oid
     * @param int                           $uid
     * @param Request                       $request
     * @param TemplatesRepository           $templatesRepository
     * @param LibraryThumbnailsMessageQueue $messageQueue
     * @param SectionLibraryRepository      $sectionLibraryRepository
     * @param PinGroupRepository            $pinGroupRepository
     *
     * @return JsonResponse
     * @throws NotFoundException
     */
    public function updateAction(
        int $id,
        int $oid,
        int $uid,
        Request $request,
        TemplatesRepository $templatesRepository,
        LibraryThumbnailsMessageQueue $messageQueue,
        SectionLibraryRepository $sectionLibraryRepository,
        PinGroupRepository $pinGroupRepository
    ): JsonResponse {
        $section = $sectionLibraryRepository->findByID($id);
        if (!$section) {
            throw new NotFoundException();
        }

        $template = $templatesRepository->findByID($section->getTmpId());
        if (!$template) {
            $this->throwNotFound();
        }
        if (!$templatesRepository->hasAccess($uid, $section->getTmpId())) {
            $this->throwUnauthorized();
        }

        if ($section->isMobile()) {
            $sectionDesktop = $sectionLibraryRepository->findByID($section->getDesktopId());
            $sectionMobile  = $section;
        } else {
            $sectionMobile  = $sectionLibraryRepository->findByDesktopID($id);
            $sectionDesktop = $section;
        }
        if (!$sectionDesktop || !$sectionMobile) {
            return $this->json('error');
        }

        $name = trim($request->json->get('name'));
        $pinGroupID = $request->json->getInt('pinGroup');

        if ($name) {
            $sectionDesktop->setName($name);
            $sectionMobile->setName($name);
            $sectionLibraryRepository->update($sectionDesktop);
            $sectionLibraryRepository->update($sectionMobile);
        }

        if ($pinGroupID) {
            $pinGroup = $pinGroupRepository->findByID($pinGroupID);
            if ($pinGroup) {
                $sectionDesktop->setPinGroup($pinGroup);
                $sectionMobile->setPinGroup($pinGroup);
                $sectionLibraryRepository->update($sectionDesktop);
                $sectionLibraryRepository->update($sectionMobile);
            }
        } else if ($sectionDesktop->getPinGroup()) {
            $sectionDesktop->setPinGroup(null);
            $sectionMobile->setPinGroup(null);
            $sectionLibraryRepository->update($sectionDesktop);
            $sectionLibraryRepository->update($sectionMobile);
        }

        $html = $request->json->get('html');
        if ($html) {
            $dom = DomParser::fromString($html);
            $this->replaceImages($oid, $dom);
            $html = (string)$dom;
            $sectionDesktop
                ->setThumbnail('')
                ->setHtml($html)
                ->setTmpVersion($template['tmp_version']);
            $sectionMobile
                ->setThumbnail('')
                ->setHtml($html)
                ->setTmpVersion($template['tmp_version']);
            $sectionLibraryRepository->update($sectionDesktop);
            $sectionLibraryRepository->update($sectionMobile);

            $messageQueue->send([
                'desktop' => $sectionDesktop->getId(),
                'mobile'  => $sectionMobile->getId()
            ]);
        }

        return $this->json($this->getTemplateLibraries($sectionDesktop->getTmpId()));
    }

    /**
     * @Route("/build/library/{id}", name="build_library_delete", methods={"DELETE"})
     *
     * @param int                      $id
     * @param int                      $uid
     * @param TemplatesRepository      $templatesRepository
     * @param SectionLibraryRepository $sectionLibraryRepository
     *
     * @return JsonResponse
     * @throws NotFoundException
     * @throws Exception
     */
    public function deleteAction(
        int $id,
        int $uid,
        TemplatesRepository $templatesRepository,
        SectionLibraryRepository $sectionLibraryRepository
    ): JsonResponse {
        $section = $sectionLibraryRepository->findByID($id);
        if (!$section) {
            throw new NotFoundException();
        }

        $template = $templatesRepository->findByID($section->getTmpId());
        if (!$templatesRepository->hasAccess($uid, $template['tmp_id'])) {
            throw new UnauthorizedException();
        }

        if ($section->isMobile()) {
            $sectionDesktop = $sectionLibraryRepository->findByID($section->getDesktopId());
            $sectionMobile  = $section;
        } else {
            $sectionMobile  = $sectionLibraryRepository->findByDesktopID($id);
            $sectionDesktop = $section;
        }
        if (!$sectionDesktop || !$sectionMobile) {
            return $this->json('error');
        }

        $sectionLibraryRepository->delete($sectionDesktop);
        $sectionLibraryRepository->delete($sectionMobile);

        return $this->json($this->getTemplateLibraries($sectionDesktop->getTmpId()));
    }

    /**
     * @Route("/build/library", name="build_library", methods={"POST"})
     *
     * @param int                           $oid
     * @param Request                       $request
     * @param TemplatesRepository           $templatesRepository
     * @param EmailRepository               $emailRepository
     * @param SectionLibraryRepository      $sectionLibraryRepository
     * @param LibraryThumbnailsMessageQueue $messageQueue
     *
     * @return JsonResponse
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws Exception
     */
    public function saveAction(
        int $oid,
        Request $request,
        TemplatesRepository $templatesRepository,
        EmailRepository $emailRepository,
        SectionLibraryRepository $sectionLibraryRepository,
        LibraryThumbnailsMessageQueue $messageQueue
    ): JsonResponse
    {
        $id   = $request->json->getInt('id');
        $mode = $request->json->getOrBadRequest('mode');
        $name = $request->json->getOrBadRequest('name');
        $html = $request->json->getOrBadRequest('html');

        if (strpos($mode, 'template') === false) {
            $email = $emailRepository->findByID($id);
            if (!$email) {
                throw new NotFoundException();
            }
            $tid     = $email['ema_tmp_id'];
            $version = $email['ema_tmp_version'];
        } else {
            $template = $templatesRepository->findByID($id);
            if (!$template) {
                throw new NotFoundException();
            }
            $tid     = $id;
            $version = $template['tmp_version'];
        }

        $dom = DomParser::fromString($html);
        $this->replaceImages($oid, $dom);
        $html = (string)$dom;

        $sectionDesktop = (new SectionLibrary())
            ->setName($name)
            ->setTmpId($tid)
            ->setHtml($html)
            ->setThumbnail('')
            ->setTmpVersion($version)
            ->setIsMobile(false);
        $sectionLibraryRepository->insert($sectionDesktop);

        $sectionMobile = (new SectionLibrary())
            ->setName($name)
            ->setTmpId($tid)
            ->setHtml($html)
            ->setThumbnail('')
            ->setTmpVersion($version)
            ->setIsMobile(true)
            ->setDesktopId($sectionDesktop->getId());
        $sectionLibraryRepository->insert($sectionMobile);

        $messageQueue->send([
            'desktop' => $sectionDesktop->getId(),
            'mobile'  => $sectionMobile->getId()
        ]);

        return $this->json($this->getTemplateLibraries($tid));
    }

    /**
     * @IsGranted({"template"})
     * @InjectTemplate()
     * @Route("/build/libraries/{id<\d+>}/pinGroups", name="build_libraries_pin_groups_save", methods={"POST"})
     *
     * @param Template           $template
     * @param Request            $request
     * @param Serializer         $serializer
     * @param PinGroupRepository $pinGroupRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function savePinGroupAction(
        Template $template,
        Request $request,
        Serializer $serializer,
        PinGroupRepository $pinGroupRepository
    ): JsonResponse
    {
        $name = $request->json->getOrBadRequest('name');
        if (strlen($name) > 60) {
            $this->throwBadRequest();
        }

        $pinGroup = (new PinGroup())
            ->setName($name)
            ->setTemplate($template);
        $pinGroupRepository->insert($pinGroup);

        return $this->json($serializer->serializePinGroup($pinGroup));
    }

    /**
     * @Route("/build/libraries/pinGroups/{id<\d+>}", name="build_libraries_pin_groups_update", methods={"POST"})
     *
     * @param int                 $id
     * @param int                 $uid
     * @param Request             $request
     * @param Serializer          $serializer
     * @param PinGroupRepository  $pinGroupRepository
     * @param TemplatesRepository $templatesRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function updatePinGroupAction(
        int $id,
        int $uid,
        Request $request,
        Serializer $serializer,
        PinGroupRepository $pinGroupRepository,
        TemplatesRepository $templatesRepository
    ): JsonResponse
    {
        $pinGroup = $pinGroupRepository->findByID($id);
        if (!$pinGroup) {
            $this->throwNotFound();
        }
        if (!$templatesRepository->hasAccess($uid, $pinGroup->getTemplate()->getId())) {
            $this->throwUnauthorized();
        }

        $name = $request->json->getOrBadRequest('name');
        if (strlen($name) > 60) {
            $this->throwBadRequest();
        }

        $pinGroup->setName($name);
        $pinGroupRepository->update($pinGroup);

        return $this->json($serializer->serializePinGroup($pinGroup));
    }

    /**
     * @Route("/build/libraries/pinGroups/{id<\d+>}", name="build_libraries_pin_groups_delete", methods={"DELETE"})
     *
     * @param int                      $id
     * @param int                      $uid
     * @param SectionLibraryRepository $sectionLibraryRepository
     * @param PinGroupRepository       $pinGroupRepository
     * @param TemplatesRepository      $templatesRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function deletePinGroupAction(
        int $id,
        int $uid,
        SectionLibraryRepository $sectionLibraryRepository,
        PinGroupRepository $pinGroupRepository,
        TemplatesRepository $templatesRepository
    ): JsonResponse {
        $pinGroup = $pinGroupRepository->findByID($id);
        if (!$pinGroup) {
            $this->throwNotFound();
        }
        if (!$templatesRepository->hasAccess($uid, $pinGroup->getTemplate()->getId())) {
            $this->throwUnauthorized();
        }

        $libs = $sectionLibraryRepository->findByPinGroup($pinGroup);
        foreach($libs as $lib) {
            $lib->setPinGroup(null);
            $sectionLibraryRepository->update($lib);
        }

        $pinGroupRepository->delete($pinGroup);

        return $this->json('ok');
    }

    /**
     * @param int             $oid
     * @param simple_html_dom $dom
     *
     * @return simple_html_dom
     * @throws Exception
     */
    protected function replaceImages(int $oid, simple_html_dom $dom): simple_html_dom
    {
        $cdn            = $this->container->get(CDNInterface::class);
        $imagify        = $this->container->get(Imagify::class);
        $displayService = $this->container->get(DisplayService::class);
        $httpRequest    = new HttpRequest();

        foreach($dom->find('img') as $img) {
            $src = $img->getAttribute('src');
            if ($img->getAttribute('data-be-custom-src') || substr($src, 0, 4) === 'http') {
                continue;
            }
            if ($src[0] === '/') {
                if ($imagify->isImagifyUrl($src)) {
                    $data = $displayService->getEmailImageData($src);
                } else {
                    $src  = $this->config->uri . $src;
                    $data = $httpRequest->get($src);
                }
            } else {
                $data = $httpRequest->get($img->getAttribute('src'));
            }

            $filename = pathinfo($src, PATHINFO_BASENAME);
            $url      = $cdn->prefixed($oid)->upload(CDNInterface::SYSTEM_IMAGES, $filename, $data);
            $img->setAttribute('src', $url);
            $img->setAttribute('data-be-hosted', 1);
        }

        return $dom;
    }

    /**
     * @param int $tid
     *
     * @return array
     * @throws Exception
     */
    protected function getTemplateLibraries(int $tid): array
    {
        $templateHtml = $this->container->get(TemplatesRepository::class)->getHtml($tid);
        $libraries = $this->container->get(SectionLibraryRepository::class)->findByTemplate($tid);

        return $this->filterLibraries($libraries, $templateHtml->getDom());
    }
}
