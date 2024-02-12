<?php
namespace BlocksEdit\IO;

use BlocksEdit\Config\Config;
use BlocksEdit\IO\Exception\IOException;
use BlocksEdit\IO\Exception\SecurityException;
use DirectoryIterator;
use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Repository\ComponentsRepository;
use Repository\EmailRepository;
use Repository\SectionLibraryRepository;
use Repository\SectionsRepository;
use Repository\TemplatesRepository;
use RuntimeException;
use SplFileInfo;

/**
 * Class Path
 */
class Paths extends IOBase
{
    const PREFIX_TEMPLATE = '-tmh-';
    const PREFIX_EMAIL = '-emh-';
    const SCREENSHOT = 'screenshot.jpg';
    const SCREENSHOT_MOBILE = 'screenshot-mobile.jpg';
    const SCREENSHOT_200  = 'screenshot-200.jpg';
    const SCREENSHOT_360 = 'screenshot-360px.jpg';

    /**
     * @var TemplatesRepository
     */
    protected $templatesRepository;

    /**
     * @var EmailRepository
     */
    protected $emailRepository;

    /**
     * @var ComponentsRepository
     */
    protected $componentsRepository;

    /**
     * @var SectionsRepository
     */
    protected $sectionsRepository;

    /**
     * @var SectionLibraryRepository
     */
    protected $sectionsLibraryRepository;

    /**
     * @var string
     */
    protected $dirTemplate = '';

    /**
     * @var string
     */
    protected $dirScreenshots = '';

    /**
     * @var string
     */
    protected $dirAvatars = '';

    /**
     * @var string
     */
    protected $urlScreenshots = '';

    /**
     * @var string
     */
    protected $urlAvatars = '';

    /**
     * @var string
     */
    protected $uri = '';

    /**
     * @var array
     */
    protected $modifiableDirs = [];

    /**
     * @param string|int ...$args
     *
     * @return string
     */
    public static function combine(...$args): string
    {
        $path = join(DIRECTORY_SEPARATOR, array_filter(array_map(function($v) {
            return trim($v, '/\\');
        }, $args)));
        if (isset($args[0]) && strpos($args[0], 'http') === false) {
            return DIRECTORY_SEPARATOR . $path;
        }

        return $path;
    }

    /**
     * Constructor
     *
     * @param Config                   $config
     * @param TemplatesRepository      $templatesRepository
     * @param EmailRepository          $emailRepository
     * @param ComponentsRepository     $componentsRepository
     * @param SectionsRepository       $sectionsRepository
     * @param SectionLibraryRepository $sectionLibraryRepository
     */
    public function __construct(
        Config $config,
        TemplatesRepository $templatesRepository,
        EmailRepository $emailRepository,
        ComponentsRepository $componentsRepository,
        SectionsRepository $sectionsRepository,
        SectionLibraryRepository $sectionLibraryRepository
    )
    {
        parent::__construct($config);
        $this->uri                       = $config->uri;
        $this->dirTemplate               = $config->dirs['templates'];
        $this->dirScreenshots            = $config->dirs['screenshots'];
        $this->dirAvatars                = $config->dirs['avatars'];
        $this->urlScreenshots            = $config->uris['screenshots'];
        $this->urlAvatars                = $config->uris['avatars'];
        $this->templatesRepository       = $templatesRepository;
        $this->emailRepository           = $emailRepository;
        $this->componentsRepository      = $componentsRepository;
        $this->sectionsRepository        = $sectionsRepository;
        $this->sectionsLibraryRepository = $sectionLibraryRepository;
    }

    /**
     * @param int $id
     * @param int $version
     *
     * @return string
     * @throws Exception
     */
    public function dirTemplate(int $id, int $version = -1): string
    {
        $template = $this->findTemplateByID($id);

        return self::combine($this->dirTemplate, $this->getTemplateDirName($template, $version));
    }

    /**
     * @param int $pid
     * @param int $id
     * @param int $version
     *
     * @return string
     * @throws Exception
     */
    public function dirLayout(int $pid, int $id, int $version = -1): string
    {
        $parent = $this->findTemplateByID($pid);
        $this->findTemplateByID($id);

        return self::combine(
            $this->dirTemplate,
            $this->getTemplateDirName($parent, $version),
            '/layouts/',
            $id
        );
    }

    /**
     * @param int    $pid
     * @param int    $id
     * @param string $filename
     * @param int    $version
     *
     * @return string
     * @throws Exception
     */
    public function dirLayoutScreenshot(int $pid, int $id, string $filename = '', int $version = -1): string
    {
        $dir = self::combine(
            $this->dirTemplateScreenshot($pid, '', $version),
            'layouts',
            $id
        );
        if (!$filename) {
            return $dir;
        }

        return self::combine($dir, $filename);
    }

    /**
     * @param int    $pid
     * @param int    $id
     * @param string $filename
     * @param int    $version
     *
     * @return string
     * @throws Exception
     */
    public function urlLayoutScreenshot(int $pid, int $id, string $filename = '', int $version = -1): string
    {
        $dir = self::combine(
            $this->urlTemplateScreenshot($pid, '', $version),
            'layouts',
            $id
        );
        if (!$filename) {
            return $dir;
        }

        return self::combine($dir, $filename);
    }

    /**
     * @param int $id
     * @param int $lid
     * @param int $templateVersion
     *
     * @return string
     * @throws Exception
     */
    public function dirLibrary(int $id, int $lid, int $templateVersion = -1): string
    {
        $template = $this->findTemplateByID($id);

        return self::combine(
            'sections',
            $this->getTemplateDirName($template, $templateVersion),
            'library-' . $lid . '.jpg'
        );
    }

    /**
     * @param int $id
     * @param int $version
     * @param int $templateVersion
     *
     * @return string
     * @throws Exception
     */
    public function dirEmail(int $id, int $version = 0, int $templateVersion = -1): string
    {
        $email        = $this->findEmailByID($id);
        $templatePath = $this->dirTemplate($email['ema_tmp_id'], $templateVersion);

        return self::combine($templatePath, $this->getEmailDirName($email, $version));
    }

    /**
     * @param int $id
     * @param int $version
     *
     * @return string
     * @throws Exception
     */
    public function dirEmailNext(int $id, int $version): string
    {
        $emailPath = $this->dirEmail($id, $version);

        return self::combine($emailPath, 'emh-next');
    }

    /**
     * @param int    $id
     * @param string $filename
     * @param int    $version
     *
     * @return string
     * @throws Exception
     */
    public function dirTemplateScreenshot(int $id, string $filename = '', int $version = -1): string
    {
        $template = $this->findTemplateByID($id);
        $dir      = self::combine(
            $this->dirScreenshots,
            'templates',
            $this->getTemplateDirName($template, $version)
        );
        if (!$filename) {
            return $dir;
        }

        return self::combine($dir, $filename);
    }

    /**
     * @param int    $id
     * @param string $filename
     * @param int    $version
     *
     * @return string
     * @throws Exception
     */
    public function urlTemplateScreenshot(int $id, string $filename = '', int $version = -1): string
    {
        $template = $this->findTemplateByID($id);
        $dir      = self::combine(
            $this->uri,
            $this->urlScreenshots,
            'templates',
            $this->getTemplateDirName($template, $version)
        );
        if (!$filename) {
            return $dir;
        }

        return self::combine($dir, $filename);
    }

    /**
     * @param string $temp
     *
     * @return string
     */
    public function dirTemplateTemp(string $temp): string
    {
        return self::combine($this->dirTemplate, 'temporary', $temp);
    }

    /**
     * @param int  $id
     * @param bool $mobile
     * @param int  $templateVersion
     *
     * @return string
     * @throws Exception
     */
    public function dirSectionScreenshot(int $id, bool $mobile, int $templateVersion = -1): string
    {
        $section = $this->findSectionByID($id);
        $dir     = $this->getSectionScreenshotDir($section, $mobile, $templateVersion);

        return self::combine($this->dirScreenshots, $dir);
    }

    /**
     * @param int  $id
     * @param bool $mobile
     * @param int  $templateVersion
     *
     * @return string
     * @throws Exception
     */
    public function dirComponentScreenshot(int $id, bool $mobile, int $templateVersion = -1): string
    {
        $component = $this->findComponentByID($id);
        $dir       = $this->getSectionScreenshotDir($component, $mobile, $templateVersion);

        return self::combine($this->dirScreenshots, $dir);
    }

    /**
     * @param int  $id
     * @param bool $mobile
     * @param int  $templateVersion
     *
     * @return string
     * @throws Exception
     */
    public function urlSectionScreenshot(int $id, bool $mobile, int $templateVersion = -1): string
    {
        $section = $this->findSectionByID($id);
        $dir     = $this->getSectionScreenshotDir($section, $mobile, $templateVersion);

        return self::combine($this->uri, $this->urlScreenshots, $dir);
    }

    /**
     * @param int  $id
     * @param bool $mobile
     * @param int  $templateVersion
     *
     * @return string
     * @throws Exception
     */
    public function urlComponentScreenshot(int $id, bool $mobile, int $templateVersion = -1): string
    {
        $component = $this->findComponentByID($id);
        $dir     = $this->getSectionScreenshotDir($component, $mobile, $templateVersion);

        return self::combine($this->uri, $this->urlScreenshots, $dir);
    }

    /**
     * @param string $name
     * @param bool   $unique
     *
     * @return string
     */
    public function dirAvatar(string $name, bool $unique = true): string
    {
        $filename = self::combine($this->dirAvatars, $name);

        if ($unique) {
            list($name1, $ext) = explode('.', $name);
            $increment = 0;
            while (file_exists($filename)) {
                $increment++;
                $filename = $this->dirAvatar($name1 . '-' . $increment . '.' . $ext);
            }
        }

        return $filename;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function urlAvatar(string $name): string
    {
        return self::combine($this->uri, $this->urlAvatars, $name);
    }

    /**
     * @param array $section
     * @param bool  $mobile
     * @param int   $templateVersion
     * @param int   $pid
     *
     * @return string
     * @throws Exception
     */
    protected function getSectionScreenshotDir(
        array $section,
        bool $mobile,
        int $templateVersion,
        int $pid = 0
    ): string
    {
        if (!empty($section['sec_id'])) {
            $template = $this->findTemplateByID($section['sec_tmp_id']);
            $prefix   = $mobile ? 'sections/mobile' : 'sections';
            $filename = $section['sec_nr'] . '.jpg';
        } else {
            $template = $this->findTemplateByID($section['com_tmp_id']);
            $prefix   = $mobile ? 'components/mobile' : 'components';
            $filename = $section['com_nr'] . '.jpg';
        }

        if ($template['tmp_tmh_enabled']) {
            $version = $templateVersion !== -1 ? $templateVersion : $template['tmp_version'];

            return self::combine(
                $prefix,
                $pid ? $this->getLayoutDirName($pid, $template['tmp_id'], $version) : $this->getTemplateDirName($template, $version),
                $filename
            );
        }

        if (!empty($section['sec_tmp_version']) || !empty($section['com_tmp_version'])) {
            $version = !empty($section['sec_tmp_version']) ? $section['sec_tmp_version'] : $section['com_tmp_version'];

            return self::combine(
                $prefix,
                $pid
                    ? $pid . '-' . $version . '/layouts/' . $template['tmp_id']
                    : $template['tmp_id'] . '-' . $version,
                $filename
            );
        }

        return self::combine(
            $prefix,
            $template['tmp_id'],
            $filename
        );
    }

    /**
     * @param array $template
     * @param int   $version
     *
     * @return string
     */
    protected function getTemplateDirName(array $template, int $version): string
    {
        if ($template['tmp_tmh_enabled']) {
            $version = $version === -1 ? (int)$template['tmp_version'] : $version;
            return $template['tmp_id'] . self::PREFIX_TEMPLATE . $version;
        }

        return $template['tmp_id'];
    }

    /**
     * @param array $email
     * @param int   $version
     *
     * @return string
     */
    protected function getEmailDirName(array $email, int $version): string
    {
        if ($version !== 0) {
            return $email['ema_id'] . self::PREFIX_EMAIL . $version;
        }

        return $email['ema_id'];
    }

    /**
     * @param int $pid
     * @param int $id
     * @param int $version
     *
     * @return string
     * @throws Exception
     */
    protected function getLayoutDirName(int $pid, int $id, int $version = -1): string
    {
        $template = $this->findTemplateByID($pid);

        return self::combine(
            $this->getTemplateDirName($template, $version),
            'layouts',
            $id
        );
    }

    /**
     * @param int $id
     *
     * @return array
     * @throws Exception
     */
    protected function findTemplateByID(int $id): array
    {
        $template = $this->templatesRepository->findByID($id);
        if (!$template) {
            throw new IOException("Template $id not found.");
        }

        return $template;
    }

    /**
     * @param int $id
     *
     * @return array
     * @throws Exception
     */
    protected function findEmailByID(int $id): array
    {
        $email = $this->emailRepository->findByID($id);
        if (!$email) {
            throw new IOException("Email $id not found.");
        }

        return $email;
    }

    /**
     * @param int $id
     *
     * @return array
     * @throws IOException
     * @throws Exception
     */
    protected function findSectionByID(int $id): array
    {
        $section = $this->sectionsRepository->findByID($id);
        if (!$section) {
            throw new IOException("Section $id not found.");
        }

        return $section;
    }

    /**
     * @param int $id
     *
     * @return array
     * @throws IOException
     * @throws Exception
     */
    protected function findComponentByID(int $id): array
    {
        $component = $this->componentsRepository->findByID($id);
        if (!$component) {
            throw new IOException("Component $id not found.");
        }

        return $component;
    }

    /**
     * @param string|string[]|FilePathInterface|FilePathInterface[] $dir
     *
     * @return bool
     * @throws IOException
     */
    public function remove($dir): bool
    {
        foreach($this->getPathsFromArgs($dir) as $path) {
            if (file_exists($path)) {
                if (!is_dir($path)) {
                    throw new IOException("Cannot remove non directory '$path.'");
                }
                $this->verifyModifiable($path);

                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );

                /** @var SplFileInfo $fileInfo */
                foreach ($files as $fileInfo) {
                    if ($fileInfo->isDir()) {
                        if (!@rmdir($fileInfo->getRealPath())) {
                            throw new IOException(
                                sprintf("Failed to rmdir '%s'.", $fileInfo->getRealPath())
                            );
                        }
                    } else {
                        if (!@unlink($fileInfo->getRealPath())) {
                            throw new IOException(
                                sprintf("Failed to unlink '%s'.", $fileInfo->getRealPath())
                            );
                        }
                    }
                }

                if (!@rmdir($path)) {
                    throw new IOException(
                        sprintf("Failed to rmdir '%s'.", $path)
                    );
                }
            }
        }

        return true;
    }

    /**
     * @param string        $source
     * @param string        $target
     * @param int|null      $permissions
     * @param callable|null $filter
     *
     * @return bool
     * @throws IOException
     */
    public function copy(
        string $source,
        string $target,
        ?int $permissions = self::PERMISSIONS,
        ?callable $filter = null
    ): bool
    {
        if ($source === $target) {
            throw new IOException("Source and target directories are the same '$source'.");
        }
        if (!is_dir($source)) {
            throw new IOException("Source is not a directory or does not exist $source.");
        }
        if (file_exists($target) && !is_dir($target)) {
            throw new IOException("Target exists and is not a directory $source.");
        }
        $this->verifyModifiable($target);

        $filter = $filter ?? function() { return true; };
        $permissions = $permissions ?? self::PERMISSIONS;
        $this->make($target, $permissions);

        $it = new DirectoryIterator($source);
        foreach ($it as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }
            if ($fileInfo->isDir()) {
                if ($fileInfo->getRealPath() !== $target && $filter($fileInfo)) {
                    $this->copy(
                        $fileInfo->getRealPath(),
                        self::combine($target, $fileInfo->getFilename()),
                        $permissions
                    );
                }
            } else if ($filter($fileInfo)) {
                $sourceFile = $fileInfo->getRealPath();
                $targetFile = self::combine($target, $fileInfo->getFilename());
                if (!@copy($sourceFile, $targetFile)) {
                    throw new RuntimeException("Failed to copy file '$sourceFile' to '$targetFile'.");
                }
                if (!@chmod($targetFile, $permissions)) {
                    throw new RuntimeException(
                        "Failed to change permissions on file '$targetFile' to $permissions."
                    );
                }
            }
        }

        return true;
    }

    /**
     * @param string        $source
     * @param string        $target
     * @param int           $permissions
     * @param callable|null $filter
     *
     * @return bool
     * @throws IOException
     */
    public function move(
        string $source,
        string $target,
        int $permissions = self::PERMISSIONS,
        ?callable $filter = null
    ): bool
    {
        $this->copy($source, $target, $permissions, $filter);

        return $this->remove($source);
    }

    /**
     * @param string $path
     * @param int    $permissions
     *
     * @return bool
     * @throws IOException
     */
    public function make(string $path, int $permissions = self::PERMISSIONS): bool
    {
        if (file_exists($path) && is_dir($path)) {
            return true;
        }
        if (file_exists($path) && !is_dir($path)) {
            throw new IOException("Cannot make directory, file exists at location $path.");
        }
        $this->verifyModifiable($path);
        if (!@mkdir($path, $permissions, true)) {
            throw new IOException("Could not create directory $path");
        }

        return true;
    }

    /**
     * @param string $source
     * @param string $target
     *
     * @return bool
     * @throws SecurityException
     * @throws IOException
     */
    public function rename(string $source, string $target): bool
    {
        if (!file_exists($source)) {
            throw new IOException("Source directory '$source' does not exist.");
        }
        $this->verifyModifiable($target);

        return rename($source, $target);
    }

    /**
     * @param string $prefix
     * @param string $rootDir
     *
     * @return string
     * @throws IOException
     */
    public function createTempDirectory(string $prefix = '', string $rootDir = ''): string
    {
        if (!$rootDir) {
            $rootDir = sys_get_temp_dir();
        }
        $tempfile = tempnam($rootDir, $prefix);
        unlink($tempfile);
        if (!mkdir($tempfile)) {
            throw new IOException('Unable to create temp directory.');
        }

        return $tempfile;
    }
}
