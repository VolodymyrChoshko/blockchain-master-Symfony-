<?php
namespace Controller\Integrations;

use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Integrations\Exception\IntegrationException;
use BlocksEdit\Integrations\Exception\OAuthUnauthorizedException;
use BlocksEdit\Integrations\FilesystemIntegrationInterface;
use BlocksEdit\Integrations\IntegrationInterface;
use BlocksEdit\Integrations\Filesystem\FileInfo;
use BlocksEdit\IO\Paths;
use BlocksEdit\Media\CDNInterface;
use BlocksEdit\Util\Media;
use Controller\Integrations\Exception\IllegalCommandException;
use Entity\Image;
use Entity\Source;
use Exception;
use Service\Export\ExportService;
use Gumlet\ImageResize;
use GuzzleHttp\Exception\GuzzleException;
use Repository\EmailRepository;
use Repository\ImagesRepository;
use Repository\PreviewRepository;
use Repository\SourcesRepository;
use Repository\TemplateSourcesRepository;
use Repository\TemplatesRepository;

/**
 * @IsGranted({"USER"})
 */
class SourcesController extends Controller
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var SourcesRepository
     */
    protected $sourcesRepo;

    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @var IntegrationInterface|FilesystemIntegrationInterface
     */
    protected $integration;

    /**
     * @var Source
     */
    protected $source;

    /**
     * @var string|null
     */
    protected $homeDir;

    /**
     * @var int
     */
    protected $uid;

    /**
     * @var int
     */
    protected $oid;

    /**
     * @Route("/integrations/sources", name="integrations_sources")
     *
     * @param int                       $uid
     * @param Request                   $request
     * @param EmailRepository           $emailRepository
     * @param SourcesRepository         $sourcesRepository
     * @param TemplatesRepository       $templatesRepository
     * @param TemplateSourcesRepository $templateSourcesRepository
     *
     * @return JsonResponse
     * @throws GuzzleException
     * @throws OAuthUnauthorizedException
     * @throws Exception
     */
    public function indexAction(
        int $uid,
        Request $request,
        EmailRepository $emailRepository,
        SourcesRepository $sourcesRepository,
        TemplatesRepository $templatesRepository,
        TemplateSourcesRepository $templateSourcesRepository
    ): JsonResponse {
        $iid             = $request->request->getOrBadRequest('iid');
        $cmd             = $request->request->getOrBadRequest('cmd');
        $args            = $request->request->getArray('args');
        $oid             = $request->request->get('oid');
        $tid             = $request->request->get('tid');
        $eid             = $request->request->get('eid');
        $emailVersion    = $request->request->get('emailVersion');
        $templateVersion = $request->request->get('templateVersion');
        $token           = $request->request->get('token');
        if (!$oid) {
            $oid = $request->oid;
        }

        $this->request = $request;
        $this->uid     = $uid;
        $this->oid     = $oid;
        $tmp           = null;
        if ($tid) {
            $tmp = $templatesRepository->findByID($tid);
            if (!$tmp) {
                $this->throwNotFound();
            }

            $oid = $tmp['tmp_org_id'];
        } else if ($eid) {
            $email = $emailRepository->findByID($eid);
            if (!$email) {
                $this->throwNotFound();
            }
            $tmp = $templatesRepository->findByID($email['ema_tmp_id']);
            if (!$tmp) {
                $this->throwNotFound();
            }

            $oid = $tmp['tmp_org_id'];
        }

        if (!$oid) {
            $this->throwBadRequest();
        }

        $this->paths       = $this->container->get(Paths::class);
        $this->sourcesRepo = $sourcesRepository;
        $this->source      = $this->sourcesRepo->findByID($iid);
        if (!$this->source || !$this->source->getIntegration()) {
            $this->throwNotFound();
        }
        if (!$this->sourcesRepo->hasIntegrationByOrg($this->source, $oid)) {
            $this->throwUnauthorized();
        }

        $this->homeDir     = null;
        $settings          = $this->sourcesRepo->findSettings($this->source);
        $this->integration = $this->source->getIntegration();
        $this->integration->setUser($this->uid, $oid);
        $this->integration->setSettings($settings);
        $this->integration->setLogger($this->logger);
        if ($tmp
            && $settings = $templateSourcesRepository->findByTemplateAndSource($tmp['tmp_id'], $iid)) {
            $this->homeDir = $settings->getHomeDir();
        }
        $settings = $this->integration->getSettings();
        if (!$this->homeDir || ($this->homeDir === '/' && isset($settings['home_dir']))) {
            $this->homeDir = $settings['home_dir'] ?? '/';
        }

        try {
            $args['oid']             = $oid;
            $args['tid']             = $tid;
            $args['eid']             = $eid;
            $args['token']           = $token;
            $args['emailVersion']    = $emailVersion;
            $args['templateVersion'] = $templateVersion;
            $args['_req']            = $request;

            return $this->json($this->execCommand($cmd, $args));
        } catch (IllegalCommandException $e) {
            $this->logger->error($e->getMessage());
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (OAuthUnauthorizedException $e) {
            if ($this->integration->requiresOauthRedirect()) {
                $request->session->set('oauth_oid', $oid);
                $request->session->set('oauth_sid', $this->source->getId());

                return $this->json([
                    'redirect' => $this->integration->getOauthURL(),
                    'name'     => $this->integration->getDisplayName(),
                    'oauth'    => true
                ]);
            }
            throw $e;
        } catch (Exception $e) {
            throw $e;
            $this->logger->error($e->getMessage());
            if ($this->config->env === 'dev') {
               return $this->json(['error' => $e->getMessage()], 500);
            }
            return $this->json(['error' => 'System error.'], 500);
        }
    }

    /**
     * @param string $cmd
     * @param array  $args
     *
     * @return FileInfo[]|array
     * @throws GuzzleException
     * @throws IllegalCommandException
     * @throws OAuthUnauthorizedException
     * @throws Exception
     */
    private function execCommand(string $cmd, array $args)
    {
        switch($cmd) {
            case 'ls':
                return $this->cmdLs($args);
            case 'select':
                return $this->cmdSelect($args);
            case 'download':
                return $this->cmdDownload($args);
            case 'import':
                return $this->cmdImport($args);
            case 'import-image':
                return $this->cmdImportImage($args);
            //case 'export':
            //    return $this->cmdExport($args);
            case 'export-html':
                return $this->cmdExportHtml($args);
            case 'export-image':
                return $this->cmdExportImage($args);
            case 'delete':
                return $this->cmdDelete($args);
            case 'mkdir':
                return $this->cmdMkdir($args);
            case 'rename':
                return $this->cmdRename($args);
            case 'close':
                return $this->cmdClose();
        }

        return [];
    }

    /**
     * @param array $args
     *
     * @return array
     * @throws GuzzleException
     * @throws OAuthUnauthorizedException
     * @throws IllegalCommandException
     * @throws Exception
     */
    private function cmdLs(array $args): array
    {
        $dir = str_replace('\\', '/', $args[0]);
        if ($dir === '~') {
            $dir = $this->homeDir;
        }
        if (strpos($dir, '..')) {
            throw new IllegalCommandException('Illegal dot characters.');
        }

        $files = $this->integration->getDirectoryListing($dir);
        $files = $this->filesToArray($files);

        return [
            'wdir'  => $dir,
            'files' => $files,
            'depth' => $this->getDirectoryDepth($dir)
        ];
    }

    /**
     * @param array $args
     *
     * @return array
     * @throws GuzzleException
     * @throws OAuthUnauthorizedException
     * @throws Exception
     */
    private function cmdDownload(array $args): array
    {
        $hash  = md5($args[0] . microtime(true) . mt_rand());
        $file  = pathinfo($args[0], PATHINFO_BASENAME);
        $name  = sprintf('%s-%s', $hash, $file);
        $local = tempnam(sys_get_temp_dir(), 'upload');

        if ($this->integration->downloadFile($args[0], $local)) {
            $url = $this->container->get(CDNInterface::class)->prefixed($args['oid'])
                ->upload(CDNInterface::SYSTEM_IMAGES, $name, file_get_contents($local));
            $image = (new Image())
                ->setOrgId($args['oid'])
                ->setIsTemp(true)
                ->setFilename($name)
                ->setCdnUrl($url)
                ->setIsHosted(true);
            $this->container->get(ImagesRepository::class)->insert($image);

            switch(pathinfo($args[0], PATHINFO_EXTENSION)) {
                case 'zip':
                    return [];
                case 'html':
                case 'htm':
                    return [
                        'preview' => [
                            'name' => $file,
                            'html' => $url
                        ],
                        'depth'   => $this->getDirectoryDepth($args[0])
                    ];
                default:
                    $size     = getimagesize($local);
                    $filesize = $this->files->humanReadableFileSize($local);

                    return [
                        'preview' => [
                            'name' => $file,
                            'img'  => $url,
                            'size' => "${size[0]}x${size[1]} $filesize"
                        ],
                        'depth'   => $this->getDirectoryDepth($args[0])
                    ];
            }
        }

        return [];
    }

    /**
     * @param array $args
     *
     * @return array
     * @throws GuzzleException
     * @throws OAuthUnauthorizedException
     * @throws Exception
     */
    private function cmdSelect(array $args): array
    {
        $original = $this->integration->getFileURL($args[0]);
        if (!$original) {
            $this->throwNotFound();
        }

        return [
            'original' => $original,
            // 'url'      => $this->container->get(Imagify::class)->srcToImagify($original, $args['tid'])
        ];
    }

    /**
     * @param array $args
     *
     * @return array
     * @throws GuzzleException
     * @throws OAuthUnauthorizedException
     * @throws Exception
     */
    private function cmdImport(array $args): array
    {
        $local = tempnam(sys_get_temp_dir(), 'upload');
        if ($this->integration->downloadFile($args[0], $local)) {
            $html = $this->files->read($local);

            return [
                'name' => $args[0],
                'html' => $html
            ];
        }

        return [];
    }

    /**
     * @param array $args
     *
     * @return array
     * @throws Exception
     * @throws GuzzleException
     * @throws OAuthUnauthorizedException
     */
    private function cmdImportImage(array $args): array
    {
        $name  = pathinfo($args[0], PATHINFO_BASENAME);
        $local = tempnam(sys_get_temp_dir(), 'upload');
        if (!$this->integration->downloadFile($args[0], $local)) {
            throw new Exception('Failed to download file');
        }

        $url = $this->container->get(CDNInterface::class)->prefixed($args['oid'])
            ->upload(CDNInterface::SYSTEM_IMAGES, $name, file_get_contents($local));

        // args[1] = width
        // args[2] = height
        // args[3] = isAutoHeight
        // args[4] = isAutoWidth
        $cropped = false;
        if (!empty($args['nocrop']) || !empty($args[1])) {
            $cropped = new ImageResize($local);
            if (!empty($args[3])) {
                if (!empty($args['width'])) {
                    $cropped->resizeToWidth($args['width'], true);
                } else {
                    $cropped->resizeToWidth($args[1], true);
                }
            } else if (!empty($args[4])) {
                if (!empty($args['height'])) {
                    $cropped->resizeToHeight($args['height'], true);
                } else {
                    $cropped->resizeToHeight($args[2], true);
                }
            }
            $cropped->save($local, null, Media::JPEG_QUALITY);
            $cropped = true;
        }

        $image = (new Image())
            ->setOrgId($args['oid'])
            ->setEmaId($args['eid'])
            ->setEmaVersion($args['emailVersion'])
            ->setFilename($name);
        if ($this->integration->shouldExportOriginalImageUrls() && !$cropped) {
            usleep(500);
            $url = $this->integration->getFileURL($args[0]);
            if ($url) {
                $image->setSrcUrl($url);
            }
        } else {
            $image
                ->setCdnUrl($url)
                ->setIsHosted(true);
        }
        $this->container->get(ImagesRepository::class)->insert($image);
        $size = getimagesize($local);
        $this->files->remove($local);

        return [
            'id'       => $image->getId(),
            'url'      => $url,
            'src'      => $url,
            'original' => $name,
            'width'    => !empty($size[0]) ? (int)$size[0] : 0,
            'height'   => !empty($size[1]) ? (int)$size[1] : 0
        ];
    }

    /**
     * @param array $args
     *
     * @return array
     * @throws Exception
     */
    /*private function cmdExport(array $args): array
    {
        $emailRepo   = $this->container->get(EmailRepository::class);
        $previewRepo = $this->container->get(PreviewRepository::class);
        $imagesRepo  = $this->container->get(ImagesRepository::class);
        $files       = $this->container->get(Files::class);

        $email = $emailRepo->findByID($args[1]);
        if (!$email) {
            $this->throwNotFound();
        }
        if (!$emailRepo->hasAccess($this->uid, $email['ema_id'])) {
            $this->throwUnauthorized();
        }

        $imageUrls  = [];
        $toDownload = [];
        foreach($imagesRepo->findByEmailAndVersion($email['ema_id'], $args['emailVersion']) as $image) {
            if ($image->isDownloadable()) {
                $toDownload[] = $image;
            }
        }
        foreach($imagesRepo->findByTemplateAndVersion($email['ema_tmp_id'], $args['templateVersion'] ?? 0) as $image) {
            if ($image->isDownloadable()) {
                $toDownload[] = $image;
            }
        }
        ImagesRepository::batchDownload($toDownload);
        foreach($toDownload as $image) {
            $remote = sprintf('%s/%s', $args[0], $image->getFilename());
            $imageUrls[$image->getFilename()] = $this->integration->uploadFile(
                $remote,
                $image->getTempFile(),
                'image',
                $image->getId()
            );
            $image->setSrcId($this->source->getId());
            $image->setSrcPath($remote);
            $imagesRepo->update($image);
        }
        $files->remove($toDownload);

        foreach($imagesRepo->findByEmailAndVersion($email['ema_id'], $args['emailVersion']) as $image) {
            if (!$image->isDownloadable()) {
                $local  = $emailRepo->getEmailImageLocation($image);
                $remote = sprintf('%s/%s', $args[0], $image->getFilename());
                $imageUrls[$image->getFilename()] = $this->integration->uploadFile(
                    $remote,
                    $local,
                    'image',
                    $image->getId()
                );
                $image->setSrcId($this->source->getId());
                $image->setSrcPath($remote);
                $imagesRepo->update($image);
            }
        }

        foreach($imagesRepo->findByTemplateAndVersion($email['ema_tmp_id'], $args['templateVersion'] ?? 0) as $image) {
            if (!$image->isDownloadable()) {
                $local  = Paths::combine($this->paths->dirTemplate($email['ema_tmp_id']), $image->getFilename());
                $remote = sprintf('%s/%s', $args[0], $image->getFilename());
                $imageUrls[$image->getFilename()] = $this->integration->uploadFile(
                    $remote,
                    $local,
                    'image',
                    $image->getId()
                );

                $image->setSrcId($this->source->getId());
                $image->setSrcPath($remote);
                $imagesRepo->update($image);
            }
        }

        $html = $previewRepo->exportEmail($email['ema_id'], $imageUrls, [], $args['emailVersion']);
        if ($args[2]) {
            $local  = tempnam(sys_get_temp_dir(), 'export-');
            $remote = sprintf('%s/%s', $args[0], $this->integration->formatRemoteFilename($email));
            $this->files->write($local, $html);
            $this->integration->uploadFile($remote, $local, 'email', $email['ema_id']);
            $this->files->remove($local);
        }

        return [
            'html'   => $html,
            'images' => $imageUrls
        ];
    }*/

    /**
     * @param array $args
     *
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    private function cmdExportHtml(array $args): array
    {
        /**
         * $args[0] = remote folder
         * $args[1] = base type (source, relative, manual)
         * $args[2] = checkExisting
         * $args[3] = source ID when base type is source, manual url when base type is manual, otherwise null
         * $args[4] = remote filename
         * $args[5] = extras
         */
        $emailRepo     = $this->container->get(EmailRepository::class);
        $exportService = $this->container->get(ExportService::class);

        $email = $emailRepo->findByID($args['eid']);
        if (!$email) {
            $this->throwNotFound();
        }
        if (!$emailRepo->hasAccess($this->uid, $email['ema_id'])) {
            $this->throwUnauthorized();
        }

        $sourceID = 0;
        if ($args[3] && !is_string($args[3])) {
            $sourceID = $args[3];
        }
        if (!empty($args[4])) {
            $base = str_replace('/', '', $args[4]);
        } else {
            $base = $this->integration->formatRemoteFilename($email);
        }

        $export = $exportService->forBuilder(
            $email['ema_id'],
            $args['emailVersion'],
            $args[1] === 'relative',
            $sourceID
        );

        $local = tempnam(sys_get_temp_dir(), 'export-');
        if (!empty($args[0])) {
            $remote = sprintf('%s/%s', $args[0], $base);
        } else {
            $remote = $base;
        }

        if ($args[2] === '1' && $this->integration->exists($remote)) {
            return [
                'exists'   => true,
                'filename' => $base
            ];
        }

        try {
            $this->files->write($local, $export->getHtml());
            $args[5]['name'] = $email['ema_title'];
            $this->integration->uploadFile($remote, $local, 'email', $email['ema_id'], $export->getTitle(), $args[5]);
            $this->files->remove($local);
        } catch (IntegrationException $e) {
            return [
                'error' => $e->getMessage(),
                'code'  => $e->getCode()
            ];
        }

        return [
            'html' => $export->getHtml(),
        ];
    }

    /**
     * @param array $args
     *
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    private function cmdExportImage(array $args): array
    {
        $emailRepo  = $this->container->get(EmailRepository::class);
        $imagesRepo = $this->container->get(ImagesRepository::class);
        $email      = $emailRepo->findByID($args['eid']);
        if (!$email) {
            $this->throwNotFound();
        }
        if (!$emailRepo->hasAccess($this->uid, $email['ema_id'])) {
            $this->throwUnauthorized();
        }

        $image = $imagesRepo->findByID($args[1]);
        if (!$image) {
            $this->throwNotFound();
        }

        if ($image->isDownloadable()) {
            $local = $image->download(true);
        } else if ($image->isHosted()) {
            // No, this doesn't make sense.
            $local = Paths::combine($this->paths->dirTemplate($email['ema_tmp_id']), $image->getFilename());
        } else {
            $local = $emailRepo->getEmailImageLocation($image);
        }

        if ($args[3]) {
            $remote = sprintf(
                '%s/%s',
                $args[0],
                $this->integration->formatRemoteFilename($args[3])
            );
        } else {
            $remote = sprintf(
                '%s/%s',
                $args[0],
                $this->integration->formatRemoteFilename($image->getFilename())
            );
        }

        if ($args[2] && $this->integration->exists($remote)) {
            if ($image->isDownloadable()) {
                $this->files->remove($local);
            }
            return [
                'exists'   => true,
                'filename' => $image->getFilename()
            ];
        }

        $url = $this->integration->uploadFile($remote, $local, 'image', $image->getId());
        $image->setSrcId($this->source->getId());
        $image->setSrcPath($remote);
        $imagesRepo->update($image);
        if ($image->isDownloadable()) {
            $this->files->remove($local);
        }

        return [
            'id'     => $image->getId(),
            'remote' => $remote,
            'url'    => $url
        ];
    }

    /**
     * @param array $args
     *
     * @return array
     * @throws GuzzleException
     * @throws OAuthUnauthorizedException
     */
    private function cmdDelete(array $args): array
    {
        if ($args[1] === 'folder') {
            $this->integration->deleteDirectory($args[0]);
        } else {
            $this->integration->deleteFile($args[0]);
        }

        return [];
    }

    /**
     * @param array $args
     *
     * @return array|JsonResponse
     * @throws Exception
     * @throws GuzzleException
     */
    private function cmdMkdir(array $args)
    {
        try {
            if ($this->integration->createDirectory($args[0])) {
                return ['ok'];
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'special characters') !== false) {
                return $this->json(['error' => $msg], 500);
            }
            throw $e;
        }

        throw new Exception('Unable to make directory.');
    }

    /**
     * @param array $args
     *
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    private function cmdRename(array $args): array
    {
        if ($this->integration->rename($args[0], $args[1])) {
            return ['ok'];
        }

        throw new Exception('Unable to make directory.');
    }

    /**
     * @return array
     * @throws Exception
     */
    private function cmdClose(): array
    {
        return ['ok'];
    }

    /**
     * @param array $files
     *
     * @return array
     */
    private function filesToArray(array $files): array
    {
        $result = [];
        foreach($files as $file) {
            if ($array = $this->infoToArray($file)) {
                $result[] = $array;
            }
        }

        return $this->sortFiles($result);
    }

    /**
     * @param FileInfo $fileInfo
     *
     * @return array
     */
    private function infoToArray(FileInfo $fileInfo): array
    {
        if ($fileInfo->isDot()) {
            return [];
        }

        $array = [
            'name' => $fileInfo->getName(),
            'path' => $fileInfo->getPathName(),
            'dir'  => pathinfo($fileInfo->getPathName(), PATHINFO_DIRNAME)
        ];

        if ($fileInfo->isDir()) {
            $array['type'] = 'folder';
        } else {
            $ext = strtolower(pathinfo($fileInfo->getName(), PATHINFO_EXTENSION));
            if (in_array($ext, ['html', 'htm', 'zip'])) {
                $array['type'] = 'html';
            } else if (in_array($ext, ['jpg', 'jpeg', 'gif', 'png', 'svg'])) {
                $array['type'] = 'image';
            } else {
                $array['type'] = 'file';
            }
        }

        return $array;
    }

    /**
     * @param array $files
     *
     * @return array
     */
    private function sortFiles(array $files): array
    {
        usort($files, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $files;
    }

    /**
     * @param string $dir
     *
     * @return int
     */
    private function getDirectoryDepth(string $dir): int
    {
        $homeDir = $this->integration->translateHomeDirectory($this->homeDir);
        $homeDir = '/' . ltrim($homeDir, '/');
        $path    = trim(substr($dir, strlen($homeDir)), '/');
        $parts   = array_filter(explode('/', $path));

        return count($parts);
    }
}
