<?php
namespace Service;

use BlocksEdit\Cache\CacheTrait;
use BlocksEdit\Config\ConfigTrait;
use BlocksEdit\Html\Imagify;
use BlocksEdit\IO\Exception\IOException;
use BlocksEdit\IO\FilesTrait;
use BlocksEdit\IO\PathsTrait;
use BlocksEdit\Logging\LoggerTrait;
use BlocksEdit\System\Required;
use BlocksEdit\Command\OutputInterface;
use BlocksEdit\Html\DomParser;
use BlocksEdit\IO\Paths;
use BlocksEdit\Media\CDNInterface;
use Entity\EmailHistory;
use Entity\TemplateHistory;
use Exception;
use Redis;
use Repository\ComponentsRepository;
use Repository\EmailHistoryRepository;
use Repository\EmailRepository;
use Repository\Exception\CreateTemplateException;
use Repository\OrganizationsRepository;
use Repository\SectionsRepository;
use Repository\TemplateHistoryRepository;
use Repository\TemplatesRepository;
use Tag\OrganizationTag;
use Tag\SerializedThumbnailsTag;
use Tag\TemplateTag;
use Tag\UserTag;

/**
 * Class TemplateUpgrader
 */
class TemplateUpgrader
{
    use PathsTrait;
    use FilesTrait;
    use LoggerTrait;
    use CacheTrait;
    use ConfigTrait;

    /**
     * @var OutputInterface|null
     */
    protected $output = null;

    /**
     * @var array
     */
    protected $createdDirs = [];

    /**
     * @var array
     */
    protected $createdUrls = [];

    /**
     * @param OutputInterface $output
     *
     * @return void
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param int $tid
     *
     * @return bool
     */
    public function getStatus(int $tid): bool
    {
        if (!$this->redis->exists("templateUpgrading:$tid")) {
            return false;
        }

        return (bool)$this->redis->get("templateUpgrading:$tid");
    }

    /**
     * Do we need to upgrade a template created before versioning was added?
     *
     * @param int $tid
     * @param int $uid
     *
     * @return array
     * @throws CreateTemplateException
     * @throws IOException
     * @throws Exception
     */
    public function upgrade(int $tid, int $uid): array
    {
        $this->createdDirs = [];
        $this->createdUrls = [];

        $template = $this->templatesRepository->findByID($tid);
        if (!$template) {
            throw new CreateTemplateException('Template not found.');
        }

        // Do we need to upgrade a template created before versioning was added?
        if (!$template['tmp_tmh_enabled']) {
            $org = $this->organizationsRepository->findByID($template['tmp_org_id']);
            if (!$org) {
                $this->writeLine("Organization not found for template %d.", $tid);
                return $template;
            }

            set_time_limit(300);
            $this->redis->setex("templateUpgrading:$tid", 3600, 1);
            $this->writeLine("Upgrading template %d for org %d (%s).", $tid, $template['tmp_org_id'], $org['org_name']);
            $this->writeLine('-----------');

            try {
                if ($template['tmp_parent']) {
                    $oldDir = Paths::combine($this->config->dirs['templates'], $template['tmp_parent'], 'layouts', $tid);
                    $newDir = Paths::combine($this->config->dirs['templates'], $template['tmp_parent'] . '-tmh-1', 'layouts', $tid . '-tmh-1');
                } else {
                    $oldDir = Paths::combine($this->config->dirs['templates'], $tid);
                    $newDir = Paths::combine($this->config->dirs['templates'], $tid . '-tmh-1');
                }
                if (!file_exists($oldDir)) {
                    $this->writeLine("\tDirectory does not exist %s.", $oldDir);
                    return [];
                }
                $oldLocation = Paths::combine($oldDir, $template['tmp_location']);
                if (!file_exists($oldLocation)) {
                    $this->writeLine("\tFile does not exist %s.", $oldLocation);
                    return [];
                }

                $this->templatesRepository->beginTransaction();

                $this->copy($oldDir, $newDir);

                $templateHistory = (new TemplateHistory())
                    ->setTmpId($tid)
                    ->setHtml($this->files->read($oldLocation))
                    ->setVersion(1)
                    ->setUsrId($uid)
                    ->setMessage('');
                $this->templateHistoryRepository->insert($templateHistory);
                $this->writeLine("\tCreated history %d.", $templateHistory->getId());

                $this->templatesRepository->updateTmhEnabled($tid, true);
                $this->templatesRepository->updateVersion($tid, 1);
                $template['tmp_version']     = 1;
                $template['tmp_tmh_enabled'] = 1;

                $template = $this->upgradeEmails($template);
                $template = $this->upgradeScreenshots($template, $templateHistory);
                $template = $this->upgradeComponents($template);

                $this->templatesRepository->commit();

                $this->cache->deleteByTags([
                    new OrganizationTag($template['tmp_org_id']),
                    new SerializedThumbnailsTag('template', $template['tmp_id']),
                    new TemplateTag($tid),
                    new UserTag($uid)
                ]);
                $this->writeLine("\tDone!");
            } catch (Exception $e) {
                $this->templatesRepository->rollBack();
                $this->rollbackCreatedDirs();
                $this->rollbackCreatedUrls();

                throw $e;
            } finally {
                $this->redis->del("templateUpgrading:$tid");
            }
        }

        $this->redis->del("templateUpgrading:$tid");

        return $template;
    }

    /**
     * Do we need to upgrade a template created before versioning was added?
     *
     * @param int $tid
     * @param int $uid
     *
     * @return array
     * @throws CreateTemplateException
     * @throws IOException
     * @throws Exception
     */
    public function upgrade2(int $tid, int $uid): array
    {
        $this->createdDirs = [];
        $this->createdUrls = [];

        $template = $this->templatesRepository->findByID($tid);
        if (!$template) {
            throw new CreateTemplateException('Template not found.');
        }

        // Do we need to upgrade a template created before versioning was added?
        // if (!$template['tmp_tmh_enabled']) {
            $org = $this->organizationsRepository->findByID($template['tmp_org_id']);
            if (!$org) {
                $this->writeLine("Organization not found for template %d.", $tid);
                return $template;
            }

            $templateHistory = $this->templateHistoryRepository->findByTemplateVersion($template['tmp_id'], 1);

            set_time_limit(300);
            $this->redis->setex("templateUpgrading:$tid", 3600, 1);
            $this->writeLine("Upgrading template %d for org %d (%s).", $tid, $template['tmp_org_id'], $org['org_name']);
            $this->writeLine('-----------');

            try {
                if ($template['tmp_parent']) {
                    $oldDir = Paths::combine($this->config->dirs['templates'], $template['tmp_parent'], 'layouts', $tid);
                    $newDir = Paths::combine($this->config->dirs['templates'], $template['tmp_parent'] . '-tmh-1', 'layouts', $tid . '-tmh-1');
                } else {
                    $oldDir = Paths::combine($this->config->dirs['templates'], $tid);
                    $newDir = Paths::combine($this->config->dirs['templates'], $tid . '-tmh-1');
                }
                if (!file_exists($oldDir)) {
                    $this->writeLine("\tDirectory does not exist %s.", $oldDir);
                    return [];
                }
                $oldLocation = Paths::combine($oldDir, $template['tmp_location']);
                if (!file_exists($oldLocation)) {
                    $this->writeLine("\tFile does not exist %s.", $oldLocation);
                    return [];
                }

                $this->templatesRepository->beginTransaction();

                if (!$templateHistory) {
                    $templateHistory = (new TemplateHistory())
                        ->setTmpId($tid)
                        ->setHtml($this->files->read($oldLocation))
                        ->setVersion(1)
                        ->setUsrId($uid)
                        ->setMessage('');
                    $this->templateHistoryRepository->insert($templateHistory);
                }

                $dom = DomParser::fromString($templateHistory->getHtml());
                $this->imagify->upgradeHostedImages2(
                    $dom,
                    $template['tmp_id'],
                    $template['tmp_org_id'],
                    1,
                    $oldDir
                );
                $templateHistory->setHtml((string)$dom);
                $this->templateHistoryRepository->update($templateHistory);

                /*$this->copy($oldDir, $newDir);

                $templateHistory = (new TemplateHistory())
                    ->setTmpId($tid)
                    ->setHtml($this->files->read($oldLocation))
                    ->setVersion(1)
                    ->setUsrId($uid)
                    ->setMessage('');
                $this->templateHistoryRepository->insert($templateHistory);
                $this->writeLine("\tCreated history %d.", $templateHistory->getId());

                $this->templatesRepository->updateTmhEnabled($tid, true);
                $this->templatesRepository->updateVersion($tid, 1);
                $template['tmp_version']     = 1;
                $template['tmp_tmh_enabled'] = 1;

                $template = $this->upgradeEmails($template);
                $template = $this->upgradeScreenshots($template, $templateHistory);
                $template = $this->upgradeComponents($template);*/

                $this->templatesRepository->commit();

                /*$this->cache->deleteByTags([
                    new OrganizationTag($template['tmp_org_id']),
                    new SerializedThumbnailsTag('template', $template['tmp_id']),
                    new TemplateTag($tid),
                    new UserTag($uid)
                ]);*/
                $this->writeLine("\tDone!");
            } catch (Exception $e) {
                $this->templatesRepository->rollBack();
                $this->rollbackCreatedDirs();
                $this->rollbackCreatedUrls();

                throw $e;
            } finally {
                $this->redis->del("templateUpgrading:$tid");
            }
        // }

        $this->redis->del("templateUpgrading:$tid");

        return $template;
    }

    /**
     * @param array $template
     *
     * @return array
     * @throws Exception
     */
    protected function upgradeEmails(array $template): array
    {
        $tempDir = $this->paths->dirTemplate($template['tmp_id']);
        $emails  = $this->emailRepository->findByTemplate($template['tmp_id']);
        $this->writeLine("\n\tUpgrading %d emails.", count($emails));

        $upgraded = 0;
        foreach ($emails as $email) {
            $oldDir   = Paths::combine($tempDir, $email['ema_id']);
            $newDir   = Paths::combine($tempDir, $email['ema_id'] . '-emh-1');
            $location = Paths::combine($oldDir, $email['ema_location']);

            if (file_exists($location)) {
                $emailHistory = $this->emailHistoryRepository->findByEmail($email['ema_id']);

                if (!$emailHistory) {
                    $this->writeLine("\tUpgrading email %d.", $email['ema_id']);
                    $this->copy($oldDir, $newDir, "\t\t");

                    $emailDom     = DomParser::fromFile($location);
                    $emailHistory = (new EmailHistory())
                        ->setEmaId($email['ema_id'])
                        ->setVersion(1)
                        ->setUsrId($email['ema_created_usr_id'])
                        ->setMessage('');
                    $ids = $this->imagify->upgradeHostedImages(
                        $emailDom,
                        $email['ema_id'],
                        $template['tmp_org_id'],
                        1,
                        '',
                        true
                    );
                    $emailHistory->setHtml((string)$emailDom);
                    $this->emailHistoryRepository->insert($emailHistory);
                    $this->emailRepository->updateTemplateVersion($email['ema_id'], 1);
                    $this->writeLine("\t\tUpgraded %d images.", count($ids));
                    $upgraded++;
                }
            } else {
                $this->writeLine("\tCould not find email file %s.", $location);
            }
        }

        $this->writeLine("\tUpgraded %d emails.", $upgraded);

        return $template;
    }

    /**
     * @param array           $template
     * @param TemplateHistory $templateHistory
     *
     * @return array
     * @throws IOException
     */
    protected function upgradeScreenshots(array $template, TemplateHistory $templateHistory): array
    {
        $this->writeLine("\n\tUpgrading screenshots.");

        if ($template['tmp_parent']) {
            $oldDir = Paths::combine($this->config->dirs['screenshots'], 'templates', $template['tmp_parent'], 'layouts', $template['tmp_id']);
            $newDir = Paths::combine($this->config->dirs['screenshots'], 'templates', $template['tmp_parent'] . '-tmh-1', 'layouts', $template['tmp_id']);
        } else {
            $oldDir = Paths::combine($this->config->dirs['screenshots'], 'templates', $template['tmp_id']);
            $newDir = Paths::combine($this->config->dirs['screenshots'], 'templates', $template['tmp_id'] . '-tmh-1');
        }
        // $this->copy($oldDir, $newDir, "\t\t");
        $this->writeLine("\t\t%s", $oldDir);

        $localFiles  = [];
        $remoteNames = [];
        $filenames   = [Paths::SCREENSHOT, Paths::SCREENSHOT_200, Paths::SCREENSHOT_360, Paths::SCREENSHOT_MOBILE];
        foreach($filenames as $filename) {
            $oldScreenshot = Paths::combine($oldDir, $filename);
            $this->writeLine("\t\t%s", $oldScreenshot);
            if (file_exists($oldScreenshot)) {
                $localFiles[]  = $oldScreenshot;
                $remoteNames[] = $filename;
            }
        }

        if ($localFiles) {
            $urls = $this->cdn->prefixed($template['tmp_org_id'])
                ->batchUpload(CDNInterface::SYSTEM_SCREENSHOTS, $remoteNames, $localFiles);
            dump($urls);
            $this->createdUrls = array_merge($this->createdUrls, $urls);
            $this->writeLine("\t\tUploaded %d screenshots from %d files.", count($urls), count($localFiles));

            foreach($remoteNames as $i => $filename) {
                $this->writeLine("\t\tSaving %s -> %s.", $filename, $urls[$i]);
                switch($filename) {
                    case Paths::SCREENSHOT:
                        $templateHistory->setThumbNormal($urls[$i]);
                        break;
                    case Paths::SCREENSHOT_200:
                        $templateHistory->setThumb200($urls[$i]);
                        break;
                    case Paths::SCREENSHOT_360:
                        $templateHistory->setThumb360($urls[$i]);
                        break;
                    case Paths::SCREENSHOT_MOBILE:
                        $templateHistory->setThumbMobile($urls[$i]);
                        break;
                }
            }
        }

        return $template;
    }

    /**
     * @param array $template
     *
     * @return array
     * @throws IOException
     * @throws Exception
     */
    protected function upgradeComponents(array $template): array
    {
        $this->writeLine("\n\tUpgrading components and sections.");
        $tid = $template['tmp_id'];
        $filenames = [];
        $localFiles = [];

        $oldDir = Paths::combine($this->config->dirs['screenshots'], 'components', $tid);
        $newDir = Paths::combine($this->config->dirs['screenshots'], 'components', $tid . '-tmh-1');
        $this->copy($oldDir, $newDir, "\t\t");

        $oldDir = Paths::combine($this->config->dirs['screenshots'], 'components/mobile', $tid);
        $newDir = Paths::combine($this->config->dirs['screenshots'], 'components/mobile', $tid . '-tmh-1');
        $this->copy($oldDir, $newDir, "\t\t");

        $oldDir = Paths::combine($this->config->dirs['screenshots'], 'sections', $tid);
        $newDir = Paths::combine($this->config->dirs['screenshots'], 'sections', $tid . '-tmh-1');
        $this->copy($oldDir, $newDir, "\t\t");

        $oldDir = Paths::combine($this->config->dirs['screenshots'], 'sections/mobile', $tid);
        $newDir = Paths::combine($this->config->dirs['screenshots'], 'sections/mobile', $tid . '-tmh-1');
        $this->copy($oldDir, $newDir, "\t\t");

        // Make sure components aren't left behind with version=1 but keep the old column value in
        // case the upgrade needs to be rolled back. We'll do that by adding a - to the number.
        $components = $this->componentsRepository->findByTemplateAndNotVersion($tid, 0);
        foreach($components as $component) {
            $this->componentsRepository->updateTemplateVersion($component['com_id'], "-$component[com_tmp_version]");
        }

        $components = $this->componentsRepository->findByTemplateAndVersion($tid, 0);
        foreach($components as $component) {
            $file = Paths::combine($oldDir, $component['com_nr'] . '.jpg');
            if (file_exists($file)) {
                $filenames[]  = 'components-' . $component['com_id'] . '.jpg';
                $localFiles[] = $file;
            }
        }

        // Make sure sections aren't left behind with version=1 but keep the old column value in
        // case the upgrade needs to be rolled back. We'll do that by adding a - to the number.
        $sections = $this->sectionsRepository->findByTemplateAndNotVersion($tid, 0);
        foreach($sections as $section) {
            $this->sectionsRepository->updateTemplateVersion($section['sec_id'], "-$section[sec_tmp_version]");
        }

        $sections = $this->sectionsRepository->findByTemplateAndVersion($tid, 0);
        foreach($sections as $section) {
            $file = Paths::combine($oldDir, $section['sec_nr'] . '.jpg');
            if (file_exists($file)) {
                $filenames[]  = 'sections-' . $section['sec_id'] . '.jpg';
                $localFiles[] = $file;
            }
        }

        if ($localFiles) {
            $urls = $this->cdn->prefixed($template['tmp_org_id'])
                ->batchUpload(CDNInterface::SYSTEM_SCREENSHOTS, $filenames, $localFiles);
            $this->createdUrls = array_merge($this->createdUrls, $urls);
            $this->writeLine("\t\tUploaded %d screenshots from %d files.", count($urls), count($localFiles));

            foreach ($urls as $i => $url) {
                $this->writeLine("\t\tSaving %s -> %s.", $filenames[$i], $url);

                preg_match('/(sections|components)-(\d+)\.jpg$/', $url, $matches);
                $type = $matches[1];
                $id   = (int)$matches[2];
                if ($type === 'sections') {
                    $this->sectionsRepository->updateScreenshot($id, $url);
                } else {
                    $this->componentsRepository->updateScreenshot($id, $url);
                }
            }
        }

        $this->componentsRepository->updateVersionID($tid, 1);
        $this->sectionsRepository->updateVersionID($tid, 1);

        return $template;
    }

    /**
     * @param string $oldDir
     * @param string $newDir
     * @param string $tabs
     *
     * @return void
     * @throws IOException
     */
    protected function copy(string $oldDir, string $newDir, string $tabs = "\t")
    {
        if (file_exists($oldDir)) {
            $this->paths->copy($oldDir, $newDir);
            $this->createdDirs[] = $newDir;
            $this->writeLine($tabs . "Copied %s -> %s.", $oldDir, $newDir);
        }
    }

    /**
     * @return void
     * @throws IOException
     */
    protected function rollbackCreatedDirs()
    {
        $this->writeLine("\tRolling %d back directories.", count($this->createdDirs));
        foreach($this->createdDirs as $dir) {
            $this->paths->remove($dir);
        }
    }

    /**
     * @return void
     */
    protected function rollbackCreatedUrls()
    {
        $this->writeLine("\tRolling back %d URLs.", count($this->createdUrls));
        $this->cdn->batchRemoveByURL($this->createdUrls);
    }

    /**
     * @param string $msg
     * @param string|int ...$args
     *
     * @return void
     */
    protected function writeLine(string $msg, ...$args)
    {
        if ($this->output) {
            $this->output->writeLine($msg, ...$args);
        } else {
            $this->logger->info(sprintf($msg, ...$args));
        }
    }

    /**
     * @var Imagify
     */
    protected $imagify;

    /**
     * @Required()
     * @param Imagify $imagify
     */
    public function setImagify(Imagify $imagify)
    {
    	$this->imagify = $imagify;
    }

    /**
     * @var TemplatesRepository
     */
    protected $templatesRepository;

    /**
     * @Required()
     * @param TemplatesRepository $templatesRepository
     */
    public function setTemplatesRepository(TemplatesRepository $templatesRepository)
    {
        $this->templatesRepository = $templatesRepository;
    }

    /**
     * @var TemplateHistoryRepository
     */
    protected $templateHistoryRepository;

    /**
     * @Required()
     * @param TemplateHistoryRepository $templateHistoryRepository
     */
    public function setTemplateHistoryRepository(TemplateHistoryRepository $templateHistoryRepository)
    {
        $this->templateHistoryRepository = $templateHistoryRepository;
    }

    /**
     * @var EmailRepository
     */
    protected $emailRepository;

    /**
     * @Required()
     * @param EmailRepository $emailRepository
     */
    public function setEmailRepository(EmailRepository $emailRepository)
    {
        $this->emailRepository = $emailRepository;
    }

    /**
     * @var EmailHistoryRepository
     */
    protected $emailHistoryRepository;

    /**
     * @Required()
     * @param EmailHistoryRepository $emailHistoryRepository
     */
    public function setEmailHistoryRepository(EmailHistoryRepository $emailHistoryRepository)
    {
        $this->emailHistoryRepository = $emailHistoryRepository;
    }

    /**
     * @var ComponentsRepository
     */
    protected $componentsRepository;

    /**
     * @Required()
     * @param ComponentsRepository $componentsRepository
     */
    public function setComponentsRepository(ComponentsRepository $componentsRepository)
    {
        $this->componentsRepository = $componentsRepository;
    }

    /**
     * @var SectionsRepository
     */
    protected $sectionsRepository;

    /**
     * @Required()
     * @param SectionsRepository $sectionsRepository
     */
    public function setSectionsRepository(SectionsRepository $sectionsRepository)
    {
    	$this->sectionsRepository = $sectionsRepository;
    }

    /**
     * @var CDNInterface
     */
    protected $cdn;

    /**
     * @Required()
     * @param CDNInterface $cdn
     */
    public function setCDN(CDNInterface $cdn)
    {
        $this->cdn = $cdn;
    }

    /**
     * @var OrganizationsRepository
     */
    protected $organizationsRepository;

    /**
     * @Required()
     * @param OrganizationsRepository $organizationsRepository
     */
    public function setOrganizationsRepository(OrganizationsRepository $organizationsRepository)
    {
    	$this->organizationsRepository = $organizationsRepository;
    }

    /**
     * @var Redis
     */
    protected $redis;

    /**
     * @Required()
     * @param Redis $redis
     */
    public function setRedis(Redis $redis)
    {
    	$this->redis = $redis;
    }
}
