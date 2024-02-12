<?php
namespace Controller\Build;

use BlocksEdit\Html\DomParser;
use BlocksEdit\Html\Imagify;
use BlocksEdit\Html\Scriptify;
use BlocksEdit\Http\Annotations\InjectTemplate;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\ContentDispositionResponse;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use BlocksEdit\Http\StatusCodes;
use BlocksEdit\IO\Exception\IOException;
use BlocksEdit\System\Serializer;
use BlocksEdit\Util\Strings;
use BlocksEdit\Util\Tokens;
use BlocksEdit\Util\TokensTrait;
use BlocksEdit\Util\UploadExtract;
use Entity\Template;
use Entity\User;
use Exception;
use GuzzleHttp\Client;
use Redis;
use Repository\AccessRepository;
use Repository\ComponentsRepository;
use Repository\Exception\CreateTemplateException;
use Repository\OrganizationAccessRepository;
use Repository\PinGroupRepository;
use Repository\SectionLibraryRepository;
use Repository\SectionsRepository;
use Repository\TemplateHistoryRepository;
use Repository\TemplateLinkParamRepository;
use Repository\TemplatesRepository;
use Service\Export\ExportService;
use Service\TemplateImporter;
use Service\TemplateUpgrader;
use Service\UploadingStatus;

/**
 * @IsGranted({"USER"})
 */
class TemplateController extends BuildController
{
    use TokensTrait;

    /**
     * @IsGranted({"ANY"})
     * @Route("/build/template/filterHtml", name="build_template_filter_html", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function filterHtmlAction(Request $request): JsonResponse
    {
        $html = $request->json->get('html');
        if (!$html) {
            $this->throwBadRequest();
        }

        list($dom, $groups, $linkStyles) = $this->getHtmlDomAndGroups($html);

        return $this->json([
            'html'       => (string)$dom,
            'groups'     => (object)$groups,
            'linkStyles' => (array)$linkStyles,
        ]);
    }

    /**
     * @IsGranted({"template"})
     * @Route("/build/template/{id<\d+>}", name="build_template", methods={"GET"})
     * @InjectTemplate()
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(Request $request): Response {
        return $this->renderFrontend($request);
    }

    /**
     * @IsGranted({"template"})
     * @Route("/build/template/{id<\d+>}/versions/{version<\d+>}", name="build_template_version", methods={"GET"})
     * @InjectTemplate()
     * @throws Exception
     */
    public function versionAction(Request $request): Response
    {
        return $this->renderFrontend($request);
    }

    /**
     * @IsGranted({"ANY", "preview"})
     * @Route("/preview/t/{id<\d+>}/{token}", name="build_template_preview")
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function previewAction(Request $request): Response
    {
        return $this->renderFrontend($request);
    }

    /**
     * @IsGranted({"ANY"})
     * @Route("/build/template/{id<\d+>}/html", name="build_template_html", methods={"POST"})
     * @InjectTemplate()
     *
     * @param int                          $uid
     * @param int                          $oid
     * @param User|null                    $user
     * @param Template                     $template
     * @param Request                      $request
     * @param Redis                        $redis
     * @param Scriptify                    $scriptify
     * @param Serializer                   $serializer
     * @param PinGroupRepository           $pinGroupRepository
     * @param ComponentsRepository         $componentsRepository
     * @param SectionsRepository           $sectionsRepository
     * @param TemplatesRepository          $templatesRepository
     * @param SectionLibraryRepository     $sectionLibraryRepository
     * @param TemplateLinkParamRepository  $linkParamRepository
     * @param TemplateHistoryRepository    $templateHistoryRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function htmlAction(
        int $uid,
        int $oid,
        ?User $user,
        Template $template,
        Request $request,
        Redis $redis,
        Scriptify $scriptify,
        Serializer $serializer,
        PinGroupRepository $pinGroupRepository,
        ComponentsRepository $componentsRepository,
        SectionsRepository $sectionsRepository,
        TemplatesRepository $templatesRepository,
        SectionLibraryRepository $sectionLibraryRepository,
        TemplateLinkParamRepository $linkParamRepository,
        TemplateHistoryRepository $templateHistoryRepository,
        OrganizationAccessRepository $organizationAccessRepository
    ): JsonResponse {
        $previewToken = $request->query->get('previewToken');
        if (!$templatesRepository->hasAccess($uid, $template->getId())) {
            if (!$previewToken || !$this->hasPreviewAccess($template->getId(), $previewToken)) {
                $this->throwUnauthorized();
            }
        }

        $version  = $request->query->getInt('version');
        if ($version && !$template->isTmhEnabled()) {
            $this->throwNotFound();
        }
        if (!$version && $template->isTmhEnabled()) {
            $version = $template->getVersion();
        }

        $html  = $templatesRepository->getHtml($template->getId(), $version);
        $token = $this->tokens->generateToken($template->getId(), Tokens::TOKEN_PUBLIC);
        if ($template->isTmhEnabled() && $version !== $template->getVersion()) {
            $previewUrl = $this->url('build_template_preview_version', [
                'id'      => $template->getId(),
                'token'   => $token,
                'version' => $version
            ]);
        } else {
            $previewUrl = $this->url('build_template_preview', [
                'id'    => $template->getId(),
                'token' => $token
            ]);
        }

        $components = $componentsRepository->findByTemplateAndVersion($template->getId(), $version);
        $sections   = $sectionsRepository->findByTemplateAndVersion($template->getId(), $version);
        if ($template->getParent()) {
            $layouts = $templatesRepository->getLayouts($template->getParent());
        } else {
            $layouts = $templatesRepository->getLayouts($template->getId());
        }

        list($dom, $groups, $linkStyles) = $this->getHtmlDomAndGroups($html->getHtml());
        $origDom = $html->getDom();
        $origDom = $scriptify->restoreScriptTags($origDom);

        // $imagify->replaceHostedImagesWithRelative($origDom);
        foreach($origDom->find('.block-component') as $element) {
            if ($element->getAttribute('data-be-component-hidden')) {
                $element->removeAttribute('data-be-component-hidden');
                $style = $element->getAttribute('style');
                if (strpos($style, 'display: none;') !== false) {
                    $style = str_replace('display: none;', '', $style);
                    $style = str_replace(';;', ';', $style);
                    $element->setAttribute('style', $style);
                }
            }
            if ($element->getAttribute('orig-style') !== false) {
                $element->removeAttribute('orig-style');
            }
        }

        $datedUpdated = '0';
        if ($template->getUpdatedAt()) {
            $datedUpdated = $template->getUpdatedAt()->format('YmdHis');
        }
        $libraries = $sectionLibraryRepository->findByTemplate($template->getId());

        $params     = [];
        $linkParams = $linkParamRepository->findByTemplate($template->getId());
        foreach($linkParams as $linkParam) {
            $params[] = $linkParam->getParam();
        }

        $initialState = $this->getInitialState(
            $user,
            'template',
            $template->getId(),
            0,
            $oid
        );
        $initialState['builder']['grant'] = [
            'granted' => true
        ];
        if ($previewToken) {
            $initialState['builder']['mode'] = 'template_preview';
            $hasAccess = false;
            if ($uid !== 0) {
                $hasAccess = $templatesRepository->hasAccess($uid, $template->getId());
            }
            if ($hasAccess) {
                $initialState['builder']['mode'] = 'template';
            }
        }

        $sources = $this->getSources($template->getId(), $oid);

        $isOwner  = $organizationAccessRepository->isOwner($uid, $template->getOrganization()->getId());
        $isAdmin  = $organizationAccessRepository->isAdmin($uid, $template->getOrganization()->getId());
        $isEditor = !$isOwner && !$isAdmin;

        $history = [];
        $templateHistories = $templateHistoryRepository->findByTemplate($template->getId());
        foreach($templateHistories as $templateHistory) {
            $item = $serializer->serializeTemplateHistory($templateHistory);
            $item['user']['isOwner'] = $isOwner;
            $item['user']['isAdmin'] = $isAdmin;
            $history[] = $item;
        }

        $isFirstRulesEdit = false;
        if ($user) {
            $key = sprintf('isFirstTimeRulesEdit:%d', $user->getId());
            $val = $redis->get($key);
            if (!$val) {
                $isFirstRulesEdit = true;
            }
        }

        $pinGroups = [];
        foreach($pinGroupRepository->findByTemplate($template) as $pinGroup) {
            $pinGroups[] = $serializer->serializePinGroup($pinGroup);
        }

        $people = [];
        foreach($this->getTemplatePeople($template) as $person) {
            $people[] = $serializer->serializeUserLight($person);
        }

        $html = (string)$origDom;
        $html = str_replace('font-family: &quot;Basis Grotesque&quot;', "font-family: 'Basis Grotesque'", $html);

        return $this->json([
            'initialState'       => $initialState,
            'title'              => $template->getTitle(),
            'html'               => (string)$dom,
            'origHtml'           => $html,
            'groups'             => (object)$groups,
            'linkStyles'         => (array)$linkStyles,
            'templateLinkParams' => $params,
            'emailLinkParams'    => (object)[],
            'tpaEnabled'         => $template->isTpaEnabled(),
            'epaEnabled'         => $template->isTpaEnabled(),
            'tmpAliasEnabled'    => $template->isAliasEnabled(),
            'emaAliasEnabled'    => $template->isAliasEnabled(),
            'tmhEnabled'         => $template->isTmhEnabled(),
            'isEmpty'            => false,
            'token'              => $token,
            'previewUrl'         => $previewUrl,
            'sources'            => $sources,
            'components'         => $this->filterComponents($components, $datedUpdated, $version),
            'sections'           => $this->filterSections($sections, $datedUpdated, $version),
            'libraries'          => $this->filterLibraries($libraries, $origDom),
            'layouts'            => $this->filterLayouts($layouts, $origDom),
            'pinGroups'          => $pinGroups,
            'isOwner'            => $isOwner,
            'isAdmin'            => $isAdmin,
            'isEditor'           => $isEditor,
            'templateVersion'    => $version,
            'history'            => $history,
            'comments'           => [],
            'people'             => $people,
            'isFirstRulesEdit'   => $isFirstRulesEdit,
        ]);
    }

    /**
     * @IsGranted({"ANY"})
     * @Route("/build/template/{id<\d+>}/save", name="build_template_save", methods={"POST"})
     * @InjectTemplate()
     *
     * @param int                          $uid
     * @param int                          $oid
     * @param array                        $template
     * @param Request                      $request
     * @param Scriptify                    $scriptify
     * @param TemplateImporter             $templateImporter
     * @param ComponentsRepository         $componentsRepository
     * @param SectionsRepository           $sectionsRepository
     * @param TemplateHistoryRepository    $templateHistoryRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     * @param Serializer                   $serializer
     *
     * @return JsonResponse
     * @throws CreateTemplateException
     * @throws Exception
     */
    public function saveAction(
        int $uid,
        int $oid,
        array $template,
        Request $request,
        Scriptify $scriptify,
        TemplateImporter $templateImporter,
        ComponentsRepository $componentsRepository,
        SectionsRepository $sectionsRepository,
        TemplateHistoryRepository $templateHistoryRepository,
        OrganizationAccessRepository $organizationAccessRepository,
        Serializer $serializer
    ): JsonResponse {
        $isOwner = $organizationAccessRepository->isOwner($uid, $template['tmp_org_id']);
        $isAdmin = $organizationAccessRepository->isAdmin($uid, $template['tmp_org_id']);
        if (!$isOwner && !$isAdmin) {
            $this->throwUnauthorized();
        }

        $html    = $request->json->get('html');
        $version = $request->json->get('version');
        $dom     = DomParser::fromString($html);
        foreach($dom->find('[data-be-data]') as $element) {
            $element->removeAttribute('data-be-data');
        }
        foreach($dom->find('[data-be-id]') as $element) {
            $element->removeAttribute('data-be-id');
        }

        $temp    = tempnam(sys_get_temp_dir(), 'template');
        $this->files->write($temp, (string)$dom);

        $file = [
            'type'        => 'text/html',
            'size'        => mb_strlen($html),
            'name'        => $template['tmp_location'],
            'be_tmp_name' => $temp
        ];
        $templateImporter->createNewTemplateVersion($uid, $oid, $template['tmp_id'], $file, '0');
        $this->files->remove($temp);

        $templateHistory = $templateHistoryRepository->findLatest($template['tmp_id']);
        $templateVersion = $templateHistory->getVersion();
        list($dom, $groups, $linkStyles) = $this->getHtmlDomAndGroups($templateHistory->getHtml());
        $origDom = DomParser::fromString($templateHistory->getHtml());
        $origDom = $scriptify->restoreScriptTags($origDom);

        foreach($origDom->find('.block-component') as $element) {
            if ($element->getAttribute('data-be-component-hidden')) {
                $element->removeAttribute('data-be-component-hidden');
                $style = $element->getAttribute('style');
                if (strpos($style, 'display: none;') !== false) {
                    $style = str_replace('display: none;', '', $style);
                    $style = str_replace(';;', ';', $style);
                    $element->setAttribute('style', $style);
                }
            }
            if ($element->getAttribute('orig-style') !== false) {
                $element->removeAttribute('orig-style');
            }
        }

        $datedUpdated = '0';
        if ($template['tmp_updated_at']) {
            $datedUpdated = date('YmdHis', strtotime($template['tmp_updated_at']));
        }
        $components = $componentsRepository->findByTemplateAndVersion($template['tmp_id'], $templateVersion);
        $sections   = $sectionsRepository->findByTemplateAndVersion($template['tmp_id'], $templateVersion);

        $history = [];
        $templateHistories = $templateHistoryRepository->findByTemplate($template['tmp_id']);
        foreach($templateHistories as $templateHistory) {
            $item = $serializer->serializeTemplateHistory($templateHistory);
            $item['user']['isOwner'] = $isOwner;
            $item['user']['isAdmin'] = $isAdmin;
            $history[] = $item;
        }

        return $this->json([
            'history'         => $history,
            'html'            => (string)$dom,
            'origHtml'        => (string)$origDom,
            'groups'          => (object)$groups,
            'linkStyles'      => (array)$linkStyles,
            'templateVersion' => $templateVersion,
            'components'      => $this->filterComponents($components, $datedUpdated, $version),
            'sections'        => $this->filterSections($sections, $datedUpdated, $version),
        ]);
    }

    /**
     * @Route("/build/template", name="build_template_upload", methods={"POST"})
     *
     * @param int              $oid
     * @param int              $uid
     * @param Request          $request
     * @param TemplateImporter $templateImporter
     *
     * @return JsonResponse|void
     * @throws IOException
     */
    public function uploadAction(
        int $oid,
        int $uid,
        Request $request,
        TemplateImporter $templateImporter
    )
    {
        try {
            $html = $request->post->get('html');
            if ($html) {
                $tempFile = tempnam(sys_get_temp_dir(), 'upload');
                $this->files->write($tempFile, $html);
                $file   = [
                    'name'        => $request->post->get('filename'),
                    'type'        => 'text/html',
                    'be_tmp_name' => $tempFile,
                    'size'        => strlen($html)
                ];
            } else {
                $file = $_FILES['template'];
            }

            $uuid = Strings::uuid();
            $request->finishRequest(json_encode($uuid));
            $templateImporter->createTemplate($uid, $oid, $file, $uuid);
        } catch (CreateTemplateException $e) {
            if (isset($tempFile)) {
                $this->files->remove($tempFile);
            }
            return $this->json([
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @IsGranted({"template"})
     * @Route("/build/template/{id<\d+>}", name="build_template_upload_new_version", methods={"POST"})
     * @InjectTemplate()
     *
     * @param int              $id
     * @param int              $uid
     * @param int              $oid
     * @param Request          $request
     * @param TemplateImporter $templateImporter
     *
     * @return Response|void
     * @throws IOException
     */
    public function uploadNewVersionAction(
        int $id,
        int $uid,
        int $oid,
        Request $request,
        TemplateImporter $templateImporter
    )
    {
        try {
            $uuid = Strings::uuid();
            $request->finishRequest(json_encode($uuid));
            $templateImporter->createNewTemplateVersion($uid, $oid, $id, $_FILES['template'], $uuid);
        } catch (CreateTemplateException $e) {
            return $this->json([
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @IsGranted({"template"})
     * @Route("/build/template/{id<\d+>}/layouts", name="build_templates_layout_upload", methods={"POST"})
     *
     * @param int                 $id
     * @param int                 $uid
     * @param int                 $oid
     * @param Request             $request
     * @param TemplateImporter    $templateImporter
     * @param TemplatesRepository $templatesRepository
     *
     * @return JsonResponse|void
     * @throws Exception
     */
    public function uploadLayoutAction(
        int $id,
        int $uid,
        int $oid,
        Request $request,
        TemplateImporter $templateImporter,
        TemplatesRepository $templatesRepository
    )
    {
        $html     = $request->post->get('html');
        $name     = $request->post->get('name');
        $template = $templatesRepository->findByID($id);
        $tempFile = tempnam(sys_get_temp_dir(), 'upload');

        try {
            $this->files->write($tempFile, $html);
            $file = [
                'name'        => $template['tmp_location'],
                'type'        => 'text/html',
                'be_tmp_name' => $tempFile,
                'size'        => strlen($html)
            ];

            $uuid = Strings::uuid();
            $request->finishRequest(json_encode($uuid));
            $templateImporter->createLayout($uid, $oid, $id, $file, $name, $uuid);
        } catch (CreateTemplateException $e) {
            return $this->json([
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        } finally {
            $this->files->remove($tempFile);
        }
    }

    /**
     * @Route("/build/uploadingStatus/{uuid}", name="build_templates_uploading_status", methods={"GET"})
     *
     * @param string          $uuid
     * @param UploadingStatus $uploadingStatus
     *
     * @return JsonResponse
     */
    public function uploadStatusAction(
        string $uuid,
        UploadingStatus $uploadingStatus
    ): JsonResponse
    {
        $status = $uploadingStatus->get($uuid);

        return $this->json($status);
    }

    /**
     * @IsGranted({"template"})
     * @Route("/build/template/{id<\d+>}/download", name="build_template_download")
     * @InjectTemplate()
     *
     * @param array               $template
     * @param Request             $request
     * @param Imagify             $imagify
     * @param Scriptify           $scriptify
     * @param TemplatesRepository $templatesRepository
     *
     * @return ContentDispositionResponse
     * @throws Exception
     */
    public function downloadAction(
        array $template,
        Request $request,
        Imagify $imagify,
        Scriptify $scriptify,
        TemplatesRepository $templatesRepository
    ): ContentDispositionResponse
    {
        $version = $request->query->getInt('version');
        $dom    = $templatesRepository->getHtml($template['tmp_id'], $version)->getDom();
        $name    = Strings::getSlug($template['tmp_title']);
        $dom     = $scriptify->restoreScriptTags($dom);
        $imagify->replaceHostedImagesWithRelative($dom);

        foreach($dom->find('.block-component') as $element) {
            if ($element->getAttribute('data-be-component-hidden')) {
                $element->removeAttribute('data-be-component-hidden');
                $style = $element->getAttribute('style');
                if (strpos($style, 'display: none;') !== false) {
                    $style = str_replace('display: none;', '', $style);
                    $style = str_replace(';;', ';', $style);
                    $element->setAttribute('style', $style);
                }
            }
            if ($element->getAttribute('orig-style') !== false) {
                $element->removeAttribute('orig-style');
            }
        }

        $html = (string)$dom;
        $html = str_replace('font-family: &quot;Basis Grotesque&quot;', "font-family: 'Basis Grotesque'", $html);

        return new ContentDispositionResponse(
            $html,
            'text/html',
            "$name.html",
            StatusCodes::OK,
            [],
            false,
            true
        );
    }

    /**
     * @IsGranted({"template"})
     * @Route("/build/template/{id<\d+>}", name="build_template_delete", methods={"DELETE"})
     *
     * @param int                 $id
     * @param TemplatesRepository $templatesRepository
     *
     * @return Response
     * @throws Exception
     */
    public function deleteAction(int $id, TemplatesRepository $templatesRepository): Response
    {
        $result = $templatesRepository->deleteByID($id);

        return $this->json($result);
    }

    /**
     * @IsGranted({"ANY", "preview"})
     * @Route("/preview/t/{id<\d+>}/{token}/{version<\d+>}", name="build_template_preview_version")
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function previewVersionAction(Request $request): Response
    {
        return $this->renderFrontend($request);
    }

    /**
     * @IsGranted({"template"})
     * @Route("/build/template/{id<\d+>}/upgrade", name="build_template_upgrade", methods={"POST"})
     * @InjectTemplate()
     *
     * @param int              $uid
     * @param Template         $template
     * @param TemplateUpgrader $templateUpgrader
     * @param Request          $request
     *
     * @return JsonResponse
     * @throws CreateTemplateException
     * @throws IOException
     */
    public function upgrade(
        int $uid,
        Template $template,
        TemplateUpgrader $templateUpgrader,
        Request $request
    ): JsonResponse
    {
        $request->finishRequest(json_encode($template->getId()));
        $templateUpgrader->upgrade($template['tmp_id'], $uid);
        die();
    }

    /**
     * @IsGranted({"template"})
     * @Route("/build/template/{id<\d+>}/upgradeStatus", name="build_template_upgrade_status", methods={"GET"})
     *
     * @param int              $id
     * @param TemplateUpgrader $templateUpgrader
     *
     * @return JsonResponse
     */
    public function upgradeStatusAction(int $id, TemplateUpgrader $templateUpgrader): JsonResponse
    {
        $isRunning = $templateUpgrader->getStatus($id);

        return $this->json($isRunning);
    }

    /**
     * @IsGranted({"ANY"})
     * @Route("/build/template/{id<\d+>}/upgradeCheck", name="build_template_upgrade_check", methods={"GET"})
     * @InjectTemplate()
     *
     * @param Template $template
     *
     * @return JsonResponse
     */
    public function upgradeCheck(Template $template): JsonResponse
    {
        return $this->json(!$template->isTmhEnabled());
    }

    /**
     * @Route("/build/template/rulesEditor/start", name="build_template_rules_edit_start", methods={"POST"})
     *
     * @param Redis $redis
     * @param User  $user
     *
     * @return JsonResponse
     */
    public function startRulesEditAction(
        Redis $redis,
        User $user
    ): JsonResponse {
        $key = sprintf('isFirstTimeRulesEdit:%d', $user->getId());
        $redis->set($key, '1');

        return $this->json('ok');
    }

    /**
     * @IsGranted({"ANY"})
     * @Route("/build/template/out/fetch", name="build_template_fetch", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function fetchUrlAction(Request $request): JsonResponse
    {
        $url = $request->query->get('url');
        if (!$url) {
            return $this->json(404);
        }

        try {
            $client = new Client();
            $resp   = $client->get($url, [
                'http_errors' => false
            ]);

            return $this->json($resp->getStatusCode());
        } catch (Exception $e) {
            return $this->json(404);
        }
    }

    /**
     * @param int    $id
     * @param string $token
     *
     * @return bool
     * @throws Exception
     */
    protected function hasPreviewAccess(int $id, string $token): bool
    {
        return str_replace('/', '', $token) === $this->tokens->generateToken($id, Tokens::TOKEN_PUBLIC);
    }
}
