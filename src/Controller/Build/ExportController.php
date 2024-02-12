<?php
namespace Controller\Build;

use BlocksEdit\Email\MailerInterface;
use BlocksEdit\Http\Annotations\InjectEmail;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\ContentDispositionResponse;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\StatusCodes;
use BlocksEdit\IO\Exception\IOException;
use BlocksEdit\Service\ChromeServiceInterface;
use BlocksEdit\Util\TokensTrait;
use Entity\Email;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Redis;
use Repository\EmailRepository;
use Repository\ImagesRepository;
use Repository\SourcesRepository;
use Repository\TemplateSourcesRepository;
use Repository\TemplatesRepository;
use Service\Export\ExportService;

/**
 * @IsGranted({"USER"})
 */
class ExportController extends Controller
{
    use TokensTrait;

    /**
     * @IsGranted({"email"})
     * @InjectEmail(includeTemplate=true)
     * @Route("/build/export/email/{id<\d+>}", name="build_export_email")
     *
     * @param array                     $email
     * @param array                     $template
     * @param Request                   $request
     * @param ExportService             $exportService
     * @param SourcesRepository         $sourcesRepo
     * @param ImagesRepository          $imagesRepo
     * @param TemplateSourcesRepository $templateSourcesRepo
     *
     * @return JsonResponse
     * @throws GuzzleException
     * @throws Exception
     */
    public function emailAction(
        array $email,
        array $template,
        Request $request,
        ExportService $exportService,
        SourcesRepository $sourcesRepo,
        ImagesRepository $imagesRepo,
        TemplateSourcesRepository $templateSourcesRepo
    ): JsonResponse {
        $imagesRelative  = $request->query->getBoolean('imagesRelative');
        $imagesSID       = $request->query->getInt('imagesSID');
        $detailedSources = $request->query->getBoolean('detailedSources');
        $version         = $request->query->getInt('version');

        $sources = [];
        $oid     = $template['tmp_org_id'];
        $tid     = $template['tmp_id'];
        foreach($sourcesRepo->findbyOrg($oid) as $source) {
            if ($source->getIntegration() && $templateSourcesRepo->isEnabled($tid, $source->getId())) {
                if ($detailedSources) {
                    $details = [
                        'id'       => $source->getId(),
                        'name'     => $source->getName(),
                        'thumb'    => $source->getIntegration()->getIconURL(),
                        'settings' => $source->getIntegration()->getFrontendSettings()
                    ];
                    if (isset($details['settings']['hooks'])) {
                        $details['settings']['hooks'] = array_keys($details['settings']['hooks']);
                    }
                    $sources[] = $details;
                } else {
                    $sources[$source->getId()] = $source->getName();
                }
            }
        }

        $imageIds = [];
        foreach($imagesRepo->findByEmailAndVersion($email['ema_id'], $version) as $image) {
            $imageIds[] = $image->getId();
        }

        $export = $exportService->forBuilder(
            $email['ema_id'],
            $version,
            $imagesRelative,
            $imagesSID
        );

        return $this->json([
            'sources'   => $sources,
            'hasImages' => count($imageIds) > 0,
            'images'    => $imageIds,
            'imgBase'   => $template['tmp_img_base_url'],
            'html'      => $export->getHtml()
        ]);
    }

    /**
     * @IsGranted({"email"})
     * @Route("/build/export/email/imgBase/{id<\d+>}", name="build_export_email_img_base")
     * @InjectEmail()
     *
     * @param array               $email
     * @param Request             $request
     * @param ExportService       $exportService
     * @param TemplatesRepository $templatesRepo
     *
     * @return JsonResponse
     * @throws GuzzleException
     * @throws Exception
     */
    public function imgBaseAction(
        array $email,
        Request $request,
        ExportService $exportService,
        TemplatesRepository $templatesRepo
    ): JsonResponse {
        $imgBase         = rtrim($request->json->get('imgBase'), '/');
        $imgSource       = $request->json->getInt('imgSource');
        $version         = $request->query->getInt('version');
        $templateVersion = $request->query->getInt('templateVersion');

        $templatesRepo->updateImgBase($email['ema_tmp_id'], $templateVersion, $imgBase);
        $export = $exportService->forBuilder(
            $email['ema_id'],
            $version,
            false,
            $imgSource
        );

        return $this->json([
            'html'      => $export->getHtml(),
            // 'imageUrls' => $imageUrls
        ]);
    }

    /**
     * @IsGranted({"email"})
     * @InjectEmail()
     * @Route("/build/export/email/{id<\d+>}/zip", name="build_export_zip_email")
     *
     * @param int           $id
     * @param Request       $request
     * @param ExportService $exportService
     *
     * @return ContentDispositionResponse
     * @throws GuzzleException|IOException
     */
    public function zipEmailAction(
        int $id,
        Request $request,
        ExportService $exportService
    ): ContentDispositionResponse {
        $imagesRelative  = $request->query->getBoolean('imagesRelative');
        $imagesSID       = $request->query->getInt('imagesSID');
        $version         = $request->query->getInt('version');

        $export = $exportService->forBuilder(
            $id,
            $version,
            $imagesRelative,
            $imagesSID
        );

        return new ContentDispositionResponse(
            $export->getZip(),
            'application/zip',
            $export->getCleanName('zip'),
            null,
            [],
            true
        );
    }

    /**
     * @IsGranted({"email"})
     * @InjectEmail()
     * @Route("/build/export/email/{id<\d+>}/html", name="build_export_html_email")
     *
     * @param array         $email
     * @param Request       $request
     * @param ExportService $exportService
     *
     * @return ContentDispositionResponse
     * @throws GuzzleException
     * @throws Exception
     */
    public function htmlAction(
        array $email,
        Request $request,
        ExportService $exportService
    ): ContentDispositionResponse {
        $imagesRelative  = $request->query->getBoolean('imagesRelative');
        $imagesSID       = $request->query->getInt('imagesSID');
        $version         = $request->query->getInt('version');

        $export = $exportService->forBuilder(
            $email['ema_id'],
            $version,
            $imagesRelative,
            $imagesSID
        );

        $tmpName = tempnam(sys_get_temp_dir(), 'export-');
        $r = $this->files->write($tmpName, $export->getHtml());
        if (!$tmpName || !$r) {
            die('here');
        }

        return new ContentDispositionResponse(
            $tmpName,
            'text/html',
            $export->getCleanName('html'),
            null,
            [],
            true
        );
    }

    /**
     * @IsGranted({"email"})
     * @InjectEmail()
     * @Route("/build/export/email/{id<\d+>}/pdf", name="build_export_pdf_email")
     *
     * @param array                  $email
     * @param Request                $request
     * @param ExportService          $exportService
     * @param ChromeServiceInterface $chromeService
     *
     * @return ContentDispositionResponse
     * @throws Exception
     */
    public function pdfAction(
        array $email,
        Request $request,
        ExportService $exportService,
        ChromeServiceInterface $chromeService
    ): ContentDispositionResponse
    {
        $version = $request->query->getInt('version');
        $size    = $request->query->get('size', 'both');
        if (!in_array($size, ['desktop', 'mobile', 'both'])) {
            $this->throwBadRequest();
        }

        $options = [];
        if ($size === 'desktop') {
            $options['desktopOnly'] = true;
        } else if ($size === 'mobile') {
            $options['mobileOnly'] = true;
        }

        $export = $exportService->forPdf($email['ema_id'], $version);
        $data   = $chromeService->pdf($export->getHtml(), $options);

        return new ContentDispositionResponse(
            $data,
            'application/pdf',
            $export->getCleanName('pdf'),
            StatusCodes::OK,
            [],
            false,
            true
        );
    }

    /**
     * @Route("/build/export/send_link", name="build_export_send_link")
     *
     * @param array           $user
     * @param Request         $request
     * @param MailerInterface $mailer
     * @param ExportService   $exportService
     * @param EmailRepository $emailRepository
     *
     * @return JsonResponse
     */
    public function sendLinkAction(
        int $oid,
        array $user,
        Request $request,
        Redis $redis,
        MailerInterface $mailer,
        ExportService $exportService,
        EmailRepository $emailRepository
    ): JsonResponse {
        try {
            $redisKey = sprintf('send_link_%d_%s', $oid, date('Ymd'));
            $count = $redis->get($redisKey);
            $this->logger->debug($count);
            if ($count && $count > 30) {
                return $this->json('You have reached the limit of sending links.');
            }

            $eid     = $request->json->get('eid');
            $version = $request->json->getInt('version');
            $email   = $emailRepository->findByID($eid);
            if (!$email) {
                throw $this->throwNotFound();
            }

            $export = $exportService->forSendLink($eid, $version);
            $mailer->quickSend(
                $request->json->get('email'),
                '[Preview] ' . $export->getTitle(),
                $export->getHtml(),
                'text/html',
                $user['usr_email']
            );
            $redis->incr($redisKey);

            return $this->json('The email has been sent.');
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], StatusCodes::BAD_REQUEST);
        }
    }

    /**
     * @IsGranted({"email"})
     * @InjectEmail()
     * @Route("/build/export/text/{id<\d+>}", name="build_export_text")
     *
     * @param Email         $email
     * @param Request       $request
     * @param ExportService $exportService
     *
     * @return ContentDispositionResponse
     * @throws Exception
     */
    public function textAction(
        Email $email,
        Request $request,
        ExportService $exportService
    ): ContentDispositionResponse {
        $imagesRelative  = $request->query->getBoolean('imagesRelative');
        $imagesSID       = $request->query->getInt('imagesSID');
        $version         = $request->query->getInt('version');

        $export = $exportService->forText(
            $email->getId(),
            $version,
            $imagesRelative,
            $imagesSID
        );
        $tmpName = tempnam(sys_get_temp_dir(), 'export-');
        $r = $this->files->write($tmpName, $export->getHtml());
        if (!$tmpName || !$r) {
            die('here');
        }

        return new ContentDispositionResponse(
            $tmpName,
            'text/plain',
            $export->getCleanName('txt'),
            null,
            [],
            true
        );
    }

    /**
     * @IsGranted({"email"})
     * @InjectEmail()
     * @Route("/build/export/rtf/{id<\d+>}", name="build_export_rtf")
     *
     * @param Email         $email
     * @param Request       $request
     * @param ExportService $exportService
     *
     * @return ContentDispositionResponse
     * @throws Exception
     */
    public function rtfAction(
        Email $email,
        Request $request,
        ExportService $exportService
    ): ContentDispositionResponse {
        $version = $request->query->getInt('version');
        $export = $exportService->forRichText($email->getId(), $version);
        $tmpName = tempnam(sys_get_temp_dir(), 'export-');
        $r = $this->files->write($tmpName, $export->getHtml());
        if (!$tmpName || !$r) {
            die('here');
        }

        return new ContentDispositionResponse(
            $tmpName,
            'application/rtf',
            $export->getCleanName('rtf'),
            null,
            [],
            true
        );
    }
}
