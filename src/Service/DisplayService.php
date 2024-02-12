<?php
namespace Service;

use BlocksEdit\Html\Imagify;
use BlocksEdit\Http\Response;
use BlocksEdit\Http\StatusCodes;
use BlocksEdit\IO\PathsTrait;
use BlocksEdit\Media\Images;
use BlocksEdit\IO\Paths;
use BlocksEdit\System\Required;
use Exception;
use Repository\EmailHistoryRepository;
use Repository\EmailRepository;
use Repository\TemplatesRepository;

/**
 * Class DisplayRepository
 */
class DisplayService
{
    use PathsTrait;

    /**
     * @param mixed  $token
     * @param string $image
     * @param int    $eid
     * @param int    $version
     * @param bool   $isNext
     *
     * @return Response
     * @throws Exception
     */
    public function display($token, string $image, int $eid, int $version, bool $isNext): Response
    {
        if (is_numeric($token)) {
            $file = Paths::combine($this->paths->dirTemplate($token), $image);
            if (Images::isFileAllowed($file)) {
                return new Response(file_get_contents($file), StatusCodes::OK, [
                    'Content-Length' => filesize($file),
                    'Content-Type'   => mime_content_type($file)
                ]);
            }

            return new Response();
        }

        // Super secret token which allows public viewing of images
        if ($token !== 'internal-screenshot-MWhhuvIAbTEI0oSz7t5PRI') {
            $email = $this->emailRepository->findByToken($token);
        } else {
            $email = $this->emailRepository->findByID($eid);
        }
        if (!$email) {
            return new Response('Email not found.');
        }

        if ($isNext) {
            $dir = $this->paths->dirEmailNext($email['ema_id'], $version);
        } else {
            if (!$version) {
                $version = $this->emailHistoryRepository->findLatestVersion($email['ema_id']);
            }
            $dir = $this->paths->dirEmail($email['ema_id'], 1, 1);
        }

        $file = Paths::combine($dir, $image);
        if (Images::isFileAllowed($file)) {
            return new Response(file_get_contents($file), StatusCodes::OK, [
                'Content-Length' => filesize($file),
                'Content-Type'   => mime_content_type($file)
            ]);
        }
        $f = $file;

        $file = $this->paths->dirTemplate($email['ema_tmp_id']);
        $file = Paths::combine($file, $image);
        if (Images::isFileAllowed($file)) {
            return new Response(file_get_contents($file), StatusCodes::OK, [
                'Content-Length' => filesize($file),
                'Content-Type'   => mime_content_type($file)
            ]);
        }

        return new Response($f);
    }

    /**
     * @param string $url
     *
     * @return string
     * @throws Exception
     */
    public function getEmailImageData(string $url): string
    {
        $file = $this->getEmailImageFile($url);
        if (!$file) {
            return '';
        }

        return file_get_contents($file);
    }

    /**
     * @param string $url
     *
     * @return string
     * @throws Exception
     */
    public function getEmailImageFile(string $url): string
    {
        list($token, $filename) = $this->imagify->getImagifyParts($url);
        if (!$token) {
            return '';
        }
        $email = $this->emailRepository->findByToken($token);
        if (!$email) {
            return '';
        }

        $dir     = $this->paths->dirEmail($email['ema_id']);
        $file    = Paths::combine($dir, $filename);
        $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp'];
        if (file_exists($file) && in_array(mime_content_type($file), $allowed)) {
            return $file;
        }

        return '';
    }

    /**
     * @param string $url
     *
     * @return string
     * @throws Exception
     */
    public function getEmailImageFile2(string $url): string
    {
        list($token, $filename) = $this->imagify->getImagifyParts($url);
        if (!$token) {
            return '';
        }
        $email = $this->emailRepository->findByToken($token);
        if (!$email) {
            return '';
        }

        $dir     = Paths::combine('/var/www/app.blocksedit.com/templates', $email['ema_tmp_id'], $email['ema_id']);
       // $dir     = $this->paths->dirEmail($email['ema_id']);
        $file    = Paths::combine($dir, $filename);
        $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp'];
        if (file_exists($file) && in_array(mime_content_type($file), $allowed)) {
            return $file;
        }

        return '';
    }

    /**
     * @var EmailRepository
     */
    protected $emailRepository;

    /**
     * @var TemplatesRepository
     */
    protected $templateRepository;

    /**
     * @var EmailHistoryRepository
     */
    protected $emailHistoryRepository;

    /**
     * @var Imagify
     */
    protected $imagify;

    /**
     * @Required()
     * @param EmailRepository $emailRepository
     */
    public function setEmailRepository(EmailRepository $emailRepository)
    {
        $this->emailRepository = $emailRepository;
    }

    /**
     * @Required()
     * @param EmailHistoryRepository $emailHistoryRepository
     */
    public function setEmailHistoryRepository(EmailHistoryRepository $emailHistoryRepository)
    {
        $this->emailHistoryRepository = $emailHistoryRepository;
    }

    /**
     * @Required()
     * @param TemplatesRepository $templatesRepository
     */
    public function setTemplatesRepository(TemplatesRepository $templatesRepository)
    {
        $this->templateRepository = $templatesRepository;
    }

    /**
     * @Required()
     * @param Imagify $imagify
     */
    public function setImagify(Imagify $imagify)
    {
        $this->imagify = $imagify;
    }
}
