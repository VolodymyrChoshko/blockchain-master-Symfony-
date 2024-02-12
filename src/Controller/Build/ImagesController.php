<?php
namespace Controller\Build;

use BlocksEdit\Html\Imagify;
use BlocksEdit\Http\Annotations\InjectEmail;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\Exception\NotFoundException;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use BlocksEdit\Http\StatusCodes;
use BlocksEdit\Integrations\FilesystemIntegrationInterface;
use BlocksEdit\IO\Exception\IOException;
use BlocksEdit\Media\CDNInterface;
use BlocksEdit\Media\Images;
use BlocksEdit\IO\Files;
use BlocksEdit\IO\Paths;
use BlocksEdit\Util\HttpRequest;
use BlocksEdit\Util\Media;
use BlocksEdit\Util\Strings;
use Controller\Build\Exception\ImageUploadException;
use Entity\Image;
use Entity\Source;
use Exception;
use Gumlet\ImageResize;
use Gumlet\ImageResizeException;
use Redis;
use Service\DisplayService;
use Repository\EmailRepository;
use Repository\ImagesRepository;
use Repository\SourcesRepository;
use Repository\TemplatesRepository;

/**
 * @IsGranted({"USER"})
 */
class ImagesController extends BuildController
{
    /**
     * @Route("/build/images", name="build_images_upload", methods={"POST"})
     *
     * @param int                 $oid
     * @param int                 $uid
     * @param Request             $request
     * @param Imagify             $imagify
     * @param CDNInterface        $cdn
     * @param ImagesRepository    $imagesRepository
     * @param DisplayService      $displayService
     * @param EmailRepository     $emailRepository
     * @param TemplatesRepository $templatesRepository
     *
     * @return JsonResponse
     * @throws IOException
     * @throws Exception
     */
    public function uploadAction(
        int $oid,
        int $uid,
        Request $request,
        Imagify $imagify,
        CDNInterface $cdn,
        ImagesRepository $imagesRepository,
        DisplayService $displayService,
        EmailRepository $emailRepository,
        TemplatesRepository $templatesRepository
    ): JsonResponse {
        $isTemp = false;
        $mode   = $request->post->get('mode');
        $src    = $request->post->get('src');

        if (strpos($mode, 'template') === 0) {
            $template = $templatesRepository->findByID($request->post->get('id'));
            if (!$template) {
                $this->throwNotFound();
            }
            if (!$templatesRepository->hasAccess($uid, $template['tmp_id'])) {
                $this->throwUnauthorized();
            }
            $isTemp = true;
        } else if (strpos($mode, 'email') === 0) {
            $email = $emailRepository->findByID($request->post->get('id'));
            if (!$email) {
                $this->throwNotFound();
            }
            if (!$emailRepository->hasAccess($uid, $email['ema_id'])) {
                $this->throwUnauthorized();
            }
        } else {
            $this->throwBadRequest();
        }

        try {
            if ($src) {
                if ($imagify->isImagifyUrl($src)) {
                    $data = $displayService->getEmailImageData($src);
                } else {
                    if (substr($src, 0, 4) !== 'http') {
                        $src = $this->config->uri . $src;
                    }
                    $data = (new HttpRequest())->get($src);
                }

                $filename = pathinfo($src, PATHINFO_BASENAME);
                $filename = preg_replace('/[^\x20-\x7E]/','_', $filename);
                $url      = $cdn->prefixed($oid)->upload(CDNInterface::SYSTEM_IMAGES, $filename, $data);
                $image    = (new Image())
                    ->setOrgId($oid)
                    ->setFilename($filename)
                    ->setIsHosted(true)
                    ->setCdnUrl($url);
                if ($isTemp) {
                    $image->setIsTemp(true);
                } else if (isset($email)) {
                    $image
                        ->setEmaId($email['ema_id'])
                        ->setIsNext(true);
                }
                $imagesRepository->insert($image);

                return $this->json([
                    'id'       => $image->getId(),
                    'src'      => $url,
                    'original' => $filename
                ]);
            } else {
                $image = $this->getUploadedImage($request);
            }

            $dimensions = $this->saveUploadedImage($image, $request);
            if ($dimensions) {
                list($name, $width, $height, $destination) = $dimensions;

                $data  = $this->files->read($destination);
                $name  = preg_replace('/[^\x20-\x7E]/','_', $name);
                $url   = $cdn->prefixed($oid)->upload(CDNInterface::SYSTEM_IMAGES, $name, $data);
                $image = (new Image())
                    ->setOrgId($oid)
                    ->setFilename($name)
                    ->setIsHosted(true)
                    ->setCdnUrl($url);
                if ($isTemp) {
                    $image->setIsTemp(true);
                } else if (isset($email)) {
                    $image
                        ->setEmaId($email['ema_id'])
                        ->setIsNext(true);
                }
                $imagesRepository->insert($image);
                $this->files->remove($destination);

                return $this->json([
                    'id'       => $image->getId(),
                    'src'      => $url,
                    'original' => $name,
                    'width'    => (int)$width,
                    'height'   => (int)$height,
                ]);
            }
        } catch (ImageUploadException $e) {
            return $this->json(['error' => $e->getMessage()]);
        }

        return $this->json(['error' => 'move']);
    }

    /**
     * @Route("/build/images/{id}", name="build_images_crop", methods={"POST"})
     *
     * @param int              $id
     * @param int              $uid
     * @param int              $oid
     * @param Request          $request
     * @param CDNInterface     $cdn
     * @param ImagesRepository $imagesRepository
     * @param EmailRepository  $emailRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function cropAction(
        int $id,
        int $uid,
        int $oid,
        Request $request,
        CDNInterface $cdn,
        ImagesRepository $imagesRepository,
        EmailRepository $emailRepository
    ): JsonResponse {
        $image = $imagesRepository->findByID($id);
        if (!$image) {
            throw $this->throwNotFound('Image not found.');
        }
        if (!$image->isTemp() && !$emailRepository->hasAccess($uid, $image->getEmaId())) {
            $this->throwUnauthorized();
        }

        $local       = $image->download(true);
        $destination = $this->cropImage($request, $local);
        if (!$destination) {
            return $this->json([
                'error' => 'Image not found.',
                'name'  => 'File not found.'
            ]);
        }

        $data = file_get_contents($destination);
        $url  = $cdn->prefixed($oid)->upload(CDNInterface::SYSTEM_IMAGES, $image->getFilename(), $data);
        $cdn->removeByURL($image->getCdnUrl());
        $this->files->remove($destination);

        $image->setCdnUrl($url);
        $imagesRepository->update($image);
        $this->files->remove($local);

        return $this->json([
            'id'       => $image->getId(),
            'src'      => $url,
            'original' => $image->getFilename()
        ]);
    }

    /**
     * @Route("/build/images/{id}", name="build_images_delete", methods={"DELETE"})
     *
     * @param int              $id
     * @param ImagesRepository $imagesRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function deleteAction(int $id, ImagesRepository $imagesRepository): JsonResponse
    {
        $image = $imagesRepository->findByID($id);
        if ($image) {
            $image->setIsDeleted(true);
            $imagesRepository->update($image);
        }

        return $this->json('ok');
    }

    /**
     * @Route("/imagify/{token}/{image}", name="image")
     *
     * @param int            $uid
     * @param Request        $request
     * @param DisplayService $displayService
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(int $uid, Request $request, DisplayService $displayService): Response
    {
        $token   = $request->route->params->get('token');
        $version = $request->query->getInt('version');
        $isNext  = (bool)$request->query->getInt('next');
        if (stripos($token, '-public') === false) {
            if (is_numeric($token) && !empty($uid)) {
                $this->throwUnauthorized();
            }
        } else {
            $token = str_replace('-public', '', $token);
        }

        if ('url' == $token) {
            $encoded_url = $request->route->params->get('image');
            if ('nocache' == substr($encoded_url, 0, 7)) {
                $encoded_url = substr($encoded_url, 7);
            }

            $ext   = pathinfo($encoded_url, PATHINFO_EXTENSION);
            $ctype = 'image/jpeg';
            switch ($ext) {
                case 'gif':
                    $ctype = 'image/gif';
                    break;
                case 'png':
                    $ctype = 'image/png';
                    break;
                case 'svg':
                    $ctype = 'image/svg+xml';
                default:
            }

            if (!file_exists($this->config->dirs['cache'] . 'images')) {
                $this->paths->make($this->config->dirs['cache'] . 'images');
            }

            $encode_encoded_url = md5($encoded_url);
            $cached_file        = $this->config->dirs['cache'] . 'images/' . $encode_encoded_url;
            if (file_exists($cached_file) && time() - filemtime($cached_file) < 3600) {
                $contents = $this->files->read($cached_file);
                $contents = base64_decode($contents);
            } else {
                $encoded_url  = str_replace('.' . $ext, '', $encoded_url);
                $contents     = (new HttpRequest())->get(base64_decode($encoded_url));
                $this->files->write($this->config->dirs['cache'] . 'images/' . $encode_encoded_url, base64_encode($contents));
            }

            return new Response($contents, StatusCodes::OK, ['Content-Type' => $ctype]);
        } else {
            $image = $request->route->params->get('image');

            return $displayService->display(
                $token,
                $image,
                $request->query->getInt('eid'),
                $version,
                $isNext
            );
        }
    }

    /**
     * @Route("/build/images/transfer/{uuid}/progress", name="build_images_transfer_progress", methods={"GET"})
     *
     * @param string $uuid
     * @param Redis  $redis
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function progressAction(string $uuid, Redis $redis): JsonResponse
    {
        $key   = sprintf('images:transfer:progress:%s', $uuid);
        $count = $redis->get($key);
        if ($count === false) {
            return new JsonResponse('Not_Found');
        }

        return $this->json($count);
    }

    /**
     * @IsGranted({"email"})
     * @Route("/build/images/transfer/{id}", name="build_images_transfer", methods={"POST"})
     * @InjectEmail()
     *
     * @param int               $uid
     * @param array             $email
     * @param Paths             $paths
     * @param Request           $request
     * @param Redis             $redis
     * @param Imagify           $imagify
     * @param EmailRepository   $emailRepository
     * @param SourcesRepository $sourcesRepository
     * @param ImagesRepository  $imagesRepository
     *
     * @throws Exception
     */
    public function transferAction(
        int $uid,
        array $email,
        Paths $paths,
        Request $request,
        Redis $redis,
        Imagify $imagify,
        EmailRepository $emailRepository,
        SourcesRepository $sourcesRepository,
        ImagesRepository $imagesRepository
    )
    {
        $sid             = $request->json->get('sid');
        $folder          = $request->json->get('folder');
        $version         = $request->json->getInt('emailVersion');
        $templateVersion = $request->json->getInt('templateVersion');
        $integration     = null;

        $source = $sourcesRepository->findByID($sid);
        if ($source && $source->getOrgId() == $request->getOrgSubdomain()) {
            $integration = $sourcesRepository->integrationFactory(
                $source,
                $uid,
                $request->getOrgSubdomain()
            );
        }
        if (!$integration) {
            throw new NotFoundException();
        }

        $toDownload = [];
        $images     = [];
        $foundNames = [];
        $src        = $paths->dirEmail($email['ema_id'], $version);
        foreach($imagesRepository->findByEmailAndVersion($email['ema_id'], $version) as $image) {
            if (!$image->getSrcUrl()) {
                $filename = $image->getFilename();
                if (!in_array($filename, $foundNames)) {
                    $foundNames[] = $filename;
                    if ($image->isDownloadable()) {
                        $toDownload[] = $image;
                    } else if (file_exists(Paths::combine($src, $filename))) {
                        $images[] = $image;
                    }
                }
            }
        }

        $tid    = $email['ema_tmp_id'];
        $dom    = $emailRepository->getHtml($email['ema_id'], $version)->getDom();
        $hosted = $imagify->findHosted($dom);
        foreach($imagesRepository->findByTemplateAndVersion($tid, $templateVersion) as $image) {
            if (!in_array($image->getFilename(), $hosted)) {
                continue;
            }
            if (!$image->getSrcUrl()) {
                $filename = $image->getFilename();
                if (!in_array($filename, $foundNames)) {
                    $foundNames[] = $filename;
                    if ($image->isDownloadable()) {
                        $toDownload[] = $image;
                    } else if (file_exists(Paths::combine($paths->dirTemplate($tid), $filename))) {
                        $images[] = $image;
                    }
                }
            }
        }

        $timeout = 600;
        $uuid    = Strings::uuid();
        $key     = sprintf('images:transfer:progress:%s', $uuid);
        $redis->setex($key, $timeout, '0');

        // Send a response to the client but continue processing.
        set_time_limit($timeout);
        $request->finishRequest(json_encode($uuid));

        if (!empty($toDownload)) {
            try {
                $this->transferUploaded($integration, $source, $toDownload, $folder, $tid, $paths);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage() . "\n" . $e->getTraceAsString());
            } finally {
                $redis->setex($key, $timeout, (string)count($toDownload) - 1);
            }
        }

        foreach($images as $i => $image) {
            try {
                $this->transferUploaded($integration, $source, $image, $folder, $tid, $paths);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage() . "\n" . $e->getTraceAsString());
            } finally {
                $redis->setex($key, $timeout, (string)$i);
            }
        }

        $redis->setex($key, $timeout, '-1');
        die();
    }

    /**
     * @param FilesystemIntegrationInterface $integration
     * @param Source                         $source
     * @param Image|Image[]                  $image
     * @param string                         $folder
     * @param int                            $tid
     * @param Paths                          $paths
     *
     * @throws Exception
     */
    private function transferUploaded(
        FilesystemIntegrationInterface $integration,
        Source $source,
        $image,
        string $folder,
        int $tid,
        Paths $paths
    )
    {
        $imagesRepo = $this->container->get(ImagesRepository::class);
        $files      = $this->container->get(Files::class);

        if (is_array($image)) {
            ImagesRepository::batchDownload($image);
            $remoteFilenames = [];
            $localFilenames  = [];
            $assetIDs        = [];
            foreach($image as $i) {
                if ($i->getTempFile()) {
                    $remoteFilenames[] = sprintf(
                        '%s/%s',
                        $folder,
                        $integration->formatRemoteFilename($i->getFilename())
                    );
                    $localFilenames[]  = $i->getTempFile();
                    $assetIDs[]        = $i->getId();
                }
            }

            if ($integration->supportsBatchUpload()) {
                $integration->batchUploadFiles($remoteFilenames, $localFilenames, 'image', $assetIDs);
                foreach($remoteFilenames as $y => $filename) {
                    $image[$y]
                        // ->setSrcUrl($url)
                        ->setSrcId($source->getId())
                        ->setSrcPath($filename);
                    $imagesRepo->update($image[$y]);
                    $this->logger->debug($source->getId());
                }
            } else {
                foreach($remoteFilenames as $y => $remoteFilename) {
                    $integration->uploadFile($remoteFilename, $localFilenames[$y], 'image', $assetIDs[$y]);
                    $image[$y]
                        // ->setSrcUrl($url)
                        ->setSrcId($source->getId())
                        ->setSrcPath($remoteFilename);
                    $imagesRepo->update($image[$y]);
                }
            }

            $files->remove($image);

            return;
        }

        if ($image->isDownloadable()) {
            $local = $image->download(true);
        } else if ($image->isHosted()) {
            // No, this doesn't make sense.
            $local = Paths::combine($paths->dirTemplate($tid), $image->getFilename());
        } else {
            $local = $this->container->get(EmailRepository::class)->getEmailImageLocation($image);
        }

        $remote = sprintf(
            '%s/%s',
            $folder,
            $integration->formatRemoteFilename($image->getFilename())
        );

        $integration->uploadFile($remote, $local, 'image', $image->getId());
        $image->setSrcId($source->getId());
        $image->setSrcPath($remote);
        $this->container->get(ImagesRepository::class)->update($image);
        if ($image->isDownloadable()) {
            @unlink($local);
        }
    }

    /**
     * @param Request $request
     *
     * @return array
     * @throws ImageUploadException
     */
    protected function getUploadedImage(Request $request): array
    {
        $image = $request->files->get('image');
        if (!$image || !$image['size']) {
            throw new ImageUploadException('Image not uploaded.');
        }
        if (!Images::isMimeTypeAllowed($image['type'])) {
            throw new ImageUploadException('Image file type not supported.');
        }
        if ($image['size'] > Images::MAX_IMAGE) {
            throw new ImageUploadException('The image file size cannot be larger than 5MB.');
        }

        return $image;
    }

    /**
     * @param array   $image
     * @param Request $request
     *
     * @return array
     * @throws IOException
     * @throws ImageResizeException
     */
    protected function saveUploadedImage(array $image, Request $request): array
    {
        $name        = $image['name'];
        $destination = tempnam(sys_get_temp_dir(), 'upload');
        $this->files->moveUploaded($image['tmp_name'], $destination);

        $size   = getimagesize($destination);
        $width  = !empty($size[0]) ? (int)$size[0] : 0;
        $height = !empty($size[1]) ? (int)$size[1] : 0;

        if ($request->post->get('canResize')) {
            $maxWidth    = $request->post->get('maxWidth');
            $maxHeight   = $request->post->get('maxHeight');
            // $ratioWidth  = $request->post->get('ratioWidth');
            // $ratioHeight = $request->post->get('ratioHeight');
            $densityWidth = $request->post->get('densityWidth');
            $densityHeight = $request->post->get('densityHeight');
            if ($maxWidth && $maxHeight) {
                $newWidth  = $densityWidth * $maxWidth;
                $newHeight = $densityHeight * $maxHeight;
                if ($width > $newWidth || $height > $newHeight) {
                    $cropped = new ImageResize($destination);
                    $cropped->resizeToBestFit($newWidth, $newHeight);
                    $cropped->save($destination, null, Media::JPEG_QUALITY);
                }
            } else if ($maxWidth) {
                $newWidth = $densityWidth * $maxWidth;
                if ($width > $newWidth) {
                    $cropped = new ImageResize($destination);
                    $cropped->resizeToWidth($newWidth, true);
                    $cropped->save($destination, null, Media::JPEG_QUALITY);
                }
            } else if ($maxHeight) {
                $newHeight = $densityHeight * $maxHeight;
                if ($height > $newHeight) {
                    $cropped = new ImageResize($destination);
                    $cropped->resizeToHeight($newHeight);
                    $cropped->save($destination, null, Media::JPEG_QUALITY);
                }
            }
        }

        return [$name, $width, $height, $destination];
    }

    /**
     * @param Request $request
     * @param string  $oldImg
     *
     * @return string
     * @throws ImageResizeException
     */
    protected function cropImage(Request $request, string $oldImg): string
    {
        $data             = $request->json->get('cropperData');
        $cropWidth        = $request->json->get('cropWidth');
        $cropHeight       = $request->json->get('cropHeight');
        $isAutoHeight     = $request->json->get('isAutoHeight');
        $isAutoWidth      = $request->json->get('isAuthWidth');
        $src              = $request->json->get('src');
        $cropperWidth     = !empty($data['width']) ? ceil($data['width']) : 0;
        $cropperHeight    = !empty($data['height']) ? ceil($data['height']) : 0;
        $cropperMaxWidth  = !empty($data['maxWidth']) ? ceil($data['maxWidth']) : $cropperWidth;
        $cropperMaxHeight = !empty($data['maxHeight']) ? ceil($data['maxHeight']) : $cropperHeight;
        $cropperLeft      = !empty($data['x']) ? ceil($data['x']) : 0;
        $cropperTop       = !empty($data['y']) ? ceil($data['y']) : 0;

        if (!$data || ($cropperWidth > 0 && $cropperHeight > 0)) {
            $destination = tempnam(sys_get_temp_dir(), 'crop');

            if ($data) {
                $cropped = new ImageResize($oldImg);
                $cropped->quality_png = 9;
                $cropped->freecrop($cropperWidth, $cropperHeight, $cropperLeft, $cropperTop);
                $cropped->save($destination, null, Media::JPEG_QUALITY);
                if ($cropperMaxWidth > 0 && $cropperMaxHeight > 0) {
                    $cropped = new ImageResize($destination);
                    $cropped->quality_png = 9;
                    $cropped->resize((int)$cropperMaxWidth, (int)$cropperMaxHeight);
                    $cropped->save($destination, null, Media::JPEG_QUALITY);
                }
            } else if (stripos($src, '.png') !== false) {
                return $oldImg;
            } else {
                $destination = $oldImg;
            }

            if ($isAutoWidth && !$isAutoHeight) {
                $cropped = new ImageResize($destination);
                $cropped->quality_png = 9;
                $cropped->resizeToHeight($cropHeight, true);
                $cropped->save($destination, null, Media::JPEG_QUALITY);
            } else if ($isAutoHeight && !$isAutoWidth) {
                $cropped = new ImageResize($destination);
                $cropped->quality_png = 9;
                $cropped->resizeToWidth($cropWidth, true);
                $cropped->save($destination, null, Media::JPEG_QUALITY);
                /** @phpstan-ignore-next-line */
            } else if (!$isAutoWidth && !$isAutoHeight) {
                $cropped = new ImageResize($destination);
                $cropped->quality_png = 9;
                $cropped->resize($cropWidth, $cropHeight, true);
                $cropped->save($destination, null, Media::JPEG_QUALITY);
            }

            return $destination;
        }

        return '';
    }
}
