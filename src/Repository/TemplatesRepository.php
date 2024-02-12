<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\Organization;
use Entity\Template;
use Entity\User;
use Generator;
use Service\TemplateUpgrader;
use Tag\OrganizationTag;
use Tag\TemplateTag;
use BlocksEdit\Html\DomParser;
use BlocksEdit\Html\HtmlData;
use BlocksEdit\Html\Imagify;
use BlocksEdit\Html\Scriptify;
use BlocksEdit\IO\Exception\IOException;
use BlocksEdit\IO\FilesTrait;
use BlocksEdit\IO\PathsTrait;
use BlocksEdit\Media\CDNInterface;
use BlocksEdit\IO\Paths;
use BlocksEdit\System\Required;
use BlocksEdit\System\Serializer;
use BlocksEdit\Util\TokensTrait;
use BlocksEdit\Util\UploadExtract;
use BlocksEdit\Util\UploadExtractor;
use Entity\TemplateHistory;
use Exception;
use Repository\Exception\CreateTemplateException;
use Tag\UserTag;
use DateTime;

/**
 * Class TemplatesRepository
 */
class TemplatesRepository extends Repository
{
    use FilesTrait;
    use PathsTrait;
    use TokensTrait;

    /**
     * @var array
     */
    protected $templatesCache = [];

    /**
     * @var Template[]
     */
    protected $templateEntitiesCache = [];

    /**
     * @throws Exception
     */
    public function getHtml(int $id, int $templateVersion = 0): HtmlData
    {
        $template = $this->findByID($id);
        if (!$template) {
            throw new Exception("Template $id not found.");
        }

        if ($template['tmp_tmh_enabled']) {
            $version = $templateVersion !== 0 ? $templateVersion : $template['tmp_version'];
            $templateHistory = $this->templateHistoryRepository
                ->findByTemplateVersion($id, $version);
            if (!$templateHistory) {
                throw new Exception("Template history for template $id and version $version not found.");
            }

            $html = $templateHistory->getHtml();
        } else {
            if ($template['tmp_parent']) {
                $dir  = $this->paths->dirLayout($template['tmp_parent'], $id);
            } else {
                $dir  = $this->paths->dirTemplate($id);
            }

            $file = Paths::combine($dir, $template['tmp_location']);
            if (!file_exists($file)) {
                throw new Exception("File for template $id not found.");
            }
            $html = trim($this->files->read($file));
        }

        $dom = DomParser::fromString($html);
        if ($this->config->env === 'dev') {
            $body = $dom->find('body', 0);
            if ($body) {
                $body->appendChild($dom->createTextNode("<!-- BE TEMPLATE VERSION $templateVersion -->"));
            }
        }

        return new HtmlData($dom, $templateVersion);
    }

    /**
     * @param int           $uid
     * @param int           $oid
     * @param UploadExtract $extract
     * @param string        $title
     * @param int           $pid
     * @param bool          $isLayout
     *
     * @return TemplateHistory
     * @throws Exception
     */
    public function create(
        int $uid,
        int $oid,
        UploadExtract $extract,
        string $title = '',
        int $pid = 0,
        bool $isLayout = false
    ): TemplateHistory
    {
        $version = 1;
        $template = [];
        if ($isLayout) {
            $template = $this->findByID($pid);
            $version  = (int)$template['tmp_version'];
        }

        try {
            $this->beginTransaction();

            $this->prepareAndExecute(
                'INSERT INTO
                    tmp_templates
                    (
                     tmp_usr_id,
                     tmp_title,
                     tmp_location,
                     tmp_created_at,
                     tmp_org_id,
                     tmp_parent,
                     tmp_version,
                     tmp_tmh_enabled,
                     tmp_updated_at
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())',
                [
                    $uid,
                    $title,
                    $extract->getBaseName(),
                    time(),
                    $oid,
                    $pid,
                    $version
                ]
            );
            $id = $this->getLastInsertID();

            $dom = DomParser::fromFile($extract->getHtmlFile());
            $dom = DomParser::fromString(preg_replace('/<!-- BE (EMAIL|TEMPLATE) VERSION \d+ -->/', '', (string)$dom));

            $templateHistory = (new TemplateHistory())
                ->setTmpId($id)
                ->setHtml((string)$dom)
                ->setVersion($version)
                ->setUsrId($uid)
                ->setMessage('');
            $this->templateHistoryRepository->insert($templateHistory);

            $this->prepareAndExecute('INSERT INTO acc_access (acc_usr_id, acc_tmp_id, acc_responded) VALUES (?, ?, 1)', [
                $uid,
                $id
            ]);

            $this->commit();

            $this->imagify->upgradeHostedImages($dom, $id, $oid, $version, $extract->getTempDir());
            $templateHistory->setHtml((string)$dom);
            $this->templateHistoryRepository->update($templateHistory);
            $this->cache->deleteByTags([
                new OrganizationTag($oid)
            ]);
            if ($pid) {
                $this->clearCache($template);
            }

            $dir = $isLayout ? $this->paths->dirLayout($pid, $id) : $this->paths->dirTemplate($id);
            $this->paths->move($extract->getTempDir(), $dir);

            return $templateHistory;
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * @param int           $uid
     * @param int           $oid
     * @param int           $tid
     * @param UploadExtract $extract
     * @param bool          $isLayout
     *
     * @return TemplateHistory
     * @throws CreateTemplateException
     * @throws IOException
     * @throws Exception
     */
    public function createNewVersion(int $uid, int $oid, int $tid, UploadExtract $extract, bool $isLayout = false): TemplateHistory
    {
        $template = $this->findByID($tid);
        if (!$template) {
            throw new CreateTemplateException('Template not found.');
        }

        $template = $this->templateUpgrader->upgrade($tid, $uid);
        if ($isLayout) {
            $parent      = $this->findByID($template['tmp_parent']);
            $nextVersion = $parent['tmp_version'];
        } else {
            $nextVersion = $template['tmp_version'] + 1;
        }
        $dir = $this->paths->dirTemplate($tid, $nextVersion);
        $this->paths->make($dir);
        $this->logger->error($template['tmp_version'] . ' ' . $nextVersion);

        $templateHistory = $this->templateHistoryRepository->findByTemplateVersion($tid, $template['tmp_version']);
        if (!$templateHistory) {
            throw new CreateTemplateException('Template history not found.');
        }

        $html = $this->files->read($extract->getHtmlFile());
        $dom  = DomParser::fromString($html);
        $this->imagify->upgradeHostedImages($dom, $tid, $oid, $nextVersion, $extract->getTempDir());

        try {
            $this->beginTransaction();

            $html = (string)$dom;
            $templateHistory = (new TemplateHistory())
                ->setTmpId($tid)
                ->setUsrId($uid)
                ->setVersion($nextVersion)
                ->setHtml($html)
                ->setMessage('');
            $this->templateHistoryRepository->insert($templateHistory);

            $query = $this->pdo->prepare(
                "UPDATE tmp_templates SET tmp_location = ?, tmp_updated_at = NOW(), tmp_version = ? WHERE tmp_id = ?"
            );
            $query->execute([
                $extract->getBaseName(),
                $nextVersion,
                $tid
            ]);

            $this->commit();
            $this->clearCache($template);

            return $templateHistory;
        } catch (Exception $e) {
            $this->rollBack();
            $this->paths->remove($dir);

            throw $e;
        }
    }

    /**
     * @param int $uid
     * @param int $oid
     *
     * @return int
     * @throws Exception
     */
    public function cloneStarter(int $uid, int $oid): int
    {
        $tid      = $this->config->starterTemplate;
        $template = $this->findByID($tid);
        if (!$template) {
            $this->logger->error('Starter template not found.');
            return 0;
        }

        $inTransaction = $this->inTransaction();
        try {
            if (!$inTransaction) {
                $this->beginTransaction();
            }

            $htmlData = $this->getHtml($tid);
            $stmt = $this->pdo->prepare(
                "INSERT INTO
                    tmp_templates
                    (
                     tmp_usr_id,
                     tmp_title,
                     tmp_location,
                     tmp_created_at,
                     tmp_org_id,
                     tmp_updated_at,
                     tmp_tmh_enabled,
                     tmp_parent
                    )
                    VALUES (?, ?, ?, ?, ?, NOW(), 1, 0)"
            );
            $stmt->execute([
                $uid,
                $template['tmp_title'],
                $template['tmp_location'],
                time(),
                $oid
            ]);
            $newID = $this->getLastInsertID();

            $templateHistory = (new TemplateHistory())
                ->setTmpId($newID)
                ->setHtml($htmlData->getHtml())
                ->setVersion(1)
                ->setUsrId($uid)
                ->setMessage('');
            $this->templateHistoryRepository->insert($templateHistory);

            $stmt = $this->pdo->prepare(
                "INSERT INTO sec_sections
                    (sec_nr, sec_tmp_id, sec_html, sec_style, sec_block, sec_title, sec_tmp, sec_tmp_version, sec_mobile)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)"
            );
            $sections = $this->sectionsRepository->findByTemplateAndVersion($tid, 0);
            foreach($sections as $section) {
                $stmt->execute([
                    $section['sec_nr'],
                    $newID,
                    $section['sec_html'],
                    $section['sec_style'],
                    $section['sec_block'],
                    $section['sec_title'],
                    $section['sec_tmp'],
                    $section['sec_mobile']
                ]);
                $sid     = $this->getLastInsertID();
                $cSource = $this->paths->dirSectionScreenshot($section['sec_id'], $section['sec_mobile']);
                $cDest   = $this->paths->dirSectionScreenshot($sid, $section['sec_mobile']);
                $this->files->copy($cSource, $cDest);
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO com_components
                    (com_nr, com_tmp_id, com_html, com_style, com_block, com_title, com_tmp, com_tmp_version, com_mobile)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)"
            );
            $components = $this->componentsRepository->findByTemplateAndVersion($tid, 0);
            foreach($components as $component) {
                $stmt->execute([
                    $component['com_nr'],
                    $newID,
                    $component['com_html'],
                    $component['com_style'],
                    $component['com_block'],
                    $component['com_title'],
                    $component['com_tmp'],
                    $component['com_mobile']
                ]);
                $cid     = $this->getLastInsertID();
                $cSource = $this->paths->dirComponentScreenshot($component['com_id'], $component['com_mobile']);
                $cDest   = $this->paths->dirComponentScreenshot($cid, $component['com_mobile']);
                $this->files->copy($cSource, $cDest);
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO acc_access (acc_usr_id, acc_tmp_id, acc_responded, acc_starter) VALUES (?, ?, 1, ?)"
            );
            $stmt->execute([
                $uid,
                $newID,
                1
            ]);

            if (!$inTransaction) {
                $this->commit();
            }

            $dom = $htmlData->getDom();
            $this->imagify->upgradeHostedImages($dom, $newID, $oid, 1);
            $templateHistory->setHtml((string)$dom);
            $this->templateHistoryRepository->update($templateHistory);

            $this->paths->copy(
                $this->paths->dirTemplate($tid),
                $this->paths->dirTemplate($newID)
            );
            foreach([Paths::SCREENSHOT, Paths::SCREENSHOT_MOBILE, Paths::SCREENSHOT_200] as $filename) {
                try {
                    $this->files->copy(
                        $this->paths->dirTemplateScreenshot($tid, $filename),
                        $this->paths->dirTemplateScreenshot($newID, $filename)
                    );
                } catch (Exception $e) {}
            }

            foreach($this->config->starterEmails as $eid) {
                $this->emailRepository->cloneStarter($eid, $newID, $uid);
            }

            $this->cache->deleteByTags([
                new OrganizationTag($oid),
                new UserTag($uid)
            ]);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            if (!$inTransaction) {
                $this->rollBack();
            }
            throw $e;
        }

        return $newID;
    }

    /**
     * @param int  $id
     * @param bool $asEntity
     *
     * @return array|Template|null
     * @throws Exception
     */
    public function findByID(int $id, bool $asEntity = false)
    {
        if ($asEntity) {
            if (isset($this->templateEntitiesCache[$id])) {
                return $this->templateEntitiesCache[$id];
            }

            $this->templateEntitiesCache[$id] = $this->findOne([
                'id' => $id
            ]);

            return $this->templateEntitiesCache[$id];
        }

        if (isset($this->templatesCache[$id])) {
            return $this->templatesCache[$id];
        }

        $stmt                      = $this->prepareAndExecute('SELECT * FROM `tmp_templates` WHERE `tmp_id` = ? LIMIT 1', [$id]);
        $this->templatesCache[$id] = $this->fetch($stmt);

        return $this->templatesCache[$id];
    }

    /**
     * @param int      $oid
     * @param int|null $limit
     * @param int      $offset
     *
     * @return array
     * @throws Exception
     */
    public function findByOrg(int $oid, ?int $limit = null, int $offset = 0): array
    {
        if ($limit) {
            $stmt = $this->prepareAndExecute(
                sprintf('SELECT * FROM `tmp_templates` WHERE `tmp_org_id` = ? ORDER BY `tmp_id` DESC LIMIT %d, %d', $offset, $limit),
                [$oid]
            );
        } else {
            $stmt = $this->prepareAndExecute('SELECT * FROM `tmp_templates` WHERE `tmp_org_id` = ? ORDER BY `tmp_id` DESC', [$oid]);
        }

        return $this->fetchAll($stmt);
    }

    /**
     * @param Organization $org
     *
     * @return Template[]
     * @throws Exception
     */
    public function findEntitiesByOrg(Organization $org): array
    {
        return $this->find([
            'organization' => $org,
            'parent' => 0,
        ]);
    }

    /**
     * @param int $uid
     *
     * @return array
     * @throws Exception
     */
    public function findByUser(int $uid): array
    {
        $stmt = $this->prepareAndExecute('SELECT * FROM `tmp_templates` WHERE `tmp_usr_id` = ?', [
            $uid
        ]);

        return $this->fetchAll($stmt);
    }

    /**
     * @param int $uid
     *
     * @return int
     * @throws Exception
     */
    public function countByUser(int $uid): int
    {
        $stmt = $this->prepareAndExecute('SELECT COUNT(*) FROM `tmp_templates` WHERE `tmp_usr_id` = ?', [
            $uid
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * @param string   $title
     * @param int|null $limit
     * @param int      $offset
     *
     * @return array
     * @throws Exception
     */
    public function findByTitle(string $title, ?int $limit = null, int $offset = 0): array
    {
        if ($limit) {
            $stmt = $this->prepareAndExecute(
                sprintf('SELECT SQL_CALC_FOUND_ROWS * FROM `tmp_templates` WHERE `tmp_title` LIKE ? ORDER BY `tmp_id` DESC LIMIT %d, %d', $offset, $limit),
                ['%' . $title . '%']
            );
        } else {
            $stmt = $this->prepareAndExecute(
                'SELECT SQL_CALC_FOUND_ROWS * FROM `tmp_templates` WHERE `tmp_title` LIKE ? ORDER BY `tmp_id` DESC',
                ['%' . $title . '%']
            );
        }

        return $this->fetchAll($stmt);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return array
     * @throws Exception
     */
    public function findAll(int $limit = 5000, int $offset = 0): array
    {
        $stmt = $this->prepareAndExecute(
            sprintf('SELECT SQL_CALC_FOUND_ROWS * FROM `tmp_templates` ORDER BY `tmp_id` DESC LIMIT %d, %d', $offset, $limit)
        );
        if (0 == $stmt->rowCount()) {
            return [];
        }

        return $this->fetchAll($stmt);
    }

    /**
     * @param string $columns
     * @param int    $offset
     *
     * @return Generator
     * @throws Exception
     */
    public function findGenerator(string $columns = '*', int $offset = 0): Generator
    {
        $stmt = $this->prepareAndExecute(
            "SELECT $columns FROM `tmp_templates` ORDER BY `tmp_id` DESC LIMIT $offset, 10000000"
        );
        while($row = $this->fetch($stmt)) {
            yield $row;
        }
    }

    /**
     * @param int $tid
     *
     * @return array
     */
    public function findLayoutsByTemplate(int $tid): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM tmp_templates WHERE tmp_parent = ?");
        $stmt->execute([$tid]);

        return $this->fetchAll($stmt);
    }

    /**
     * @param int $limit
     * @param int $offset
     *
     * @return array
     * @throws Exception
     */
    public function findAllNotStarter(int $limit = 5000, int $offset = 0): array
    {
        $stmt = $this->prepareAndExecute(
            sprintf("SELECT SQL_CALC_FOUND_ROWS * FROM `tmp_templates` WHERE `tmp_title` != 'Starter' ORDER BY `tmp_id` DESC LIMIT %d, %d", $offset, $limit)
        );

        return $this->fetchAll($stmt);
    }

    /**
     * @return int
     * @throws Exception
     */
    public function countAll(): int
    {
        $stmt = $this->prepareAndExecute('SELECT COUNT(*) FROM `tmp_templates`');

        return (int)$stmt->fetchColumn();
    }

    /**
     * @param DateTime $date
     *
     * @return array
     * @throws Exception
     */
    public function findSince(DateTime $date): array
    {
        $stmt = $this->prepareAndExecute(
            'SELECT * FROM `tmp_templates` WHERE `tmp_created_at` > ?',
            [$date->getTimestamp()]
        );

        return $this->fetchAll($stmt);
    }

    /**
     * @param bool         $isOwner
     * @param User         $user
     * @param Organization $organization
     *
     * @return Template[]
     * @throws Exception
     */
    public function findForDashboard(bool $isOwner, User $user, Organization $organization): array
    {
        $templates = $this->findEntitiesByOrg($organization);
        if (!$isOwner) {
            $invited = [];
            $invites = $this->accessRepository->findByUserAndResponded($user->getId(), 1);
            foreach ($invites as $invite) {
                $invited[$invite['acc_tmp_id']] = $invite['acc_tmp_id'];
            }
            foreach ($templates as $key => $template) {
                if (!in_array($template->getId(), $invited) && !$this->hasAccess($user->getId(), $template->getId())) {
                    unset($templates[$key]);
                }
            }
        }

        return $templates;
    }

    /**
     * @param int $oid
     * @param int $uid
     *
     * @return array
     * @throws Exception
     */
    public function findByOrgAndUser(int $oid, int $uid): array
    {
        $sql = "
            SELECT tmp_templates.*
            FROM tmp_templates
            LEFT JOIN acc_access ON acc_access.acc_tmp_id = tmp_templates.tmp_id
            WHERE acc_access.acc_usr_id = ?
            AND acc_starter = 0
            AND tmp_templates.tmp_org_id = ?
        ";
        $stmt = $this->prepareAndExecute($sql, [
            $uid,
            $oid
        ]);

        return $this->fetchAll($stmt);
    }

    /**
     * @param int $tid
     *
     * @return array
     */
    public function getLayouts(int $tid): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM tmp_templates WHERE tmp_parent = ?");
        $stmt->execute(array($tid));

        return $this->fetchAll($stmt);
    }

    /**
     * @param int $uid
     * @param int $tid
     *
     * @return bool
     */
    public function hasAccess(int $uid, int $tid): bool
    {
        try {
            $template = $this->findByID($tid);
            if (!$template) {
                return false;
            }
            if ($template['tmp_parent'] && $template['tmp_usr_id'] == $uid) {
                return true;
            }
            if ($template['tmp_parent'] && $this->hasAccess($uid, $template['tmp_parent'])) {
                return true;
            }
            if ($this->organizationAccessRepository->isOwner($uid, $template['tmp_org_id'])) {
                return true;
            }
            if ($this->organizationAccessRepository->isAdmin($uid, $template['tmp_org_id'])) {
                return true;
            }

            $access = $this->accessRepository->findByUserAndTemplate($uid, $tid);
            if (!$access) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
        }

        return false;
    }

    /**
     * @param int $uid
     * @param int $tid
     *
     * @return bool
     */
    public function isAuthor(int $uid, int $tid): bool {
        $query = $this->pdo->prepare("SELECT tmp_id FROM tmp_templates WHERE tmp_usr_id = ? AND tmp_id = ?");
        $query->execute(array($uid, $tid));
        if (0 == $query->rowCount()) {
            return false;
        }
        return true;
    }

    /**
     * @param int $uid
     * @param int $tid
     *
     * @return bool
     */
    public function hasStarterAccess(int $uid, int $tid): bool
    {
        $query = $this->pdo->prepare(
            "SELECT * FROM acc_access WHERE acc_tmp_id = ? AND acc_usr_id = ? AND acc_starter = 1"
        );
        $query->execute(array($tid, $uid));
        if (0 == $query->rowCount()) {
            return false;
        }

        return true;
    }

    /**
     * @param int $tid
     *
     * @return bool
     * @throws Exception
     */
    public function deleteByID(int $tid): bool
    {
        $template = $this->findByID($tid);
        if (!$template) {
            return false;
        }

        $doms = [];
        $version = $this->getTemplateLatestVersion($tid);
        for ($i = $version; $i >= 1; $i--) {
            $doms[] = $this->getHtml($tid, $i)->getDom();
        }

        $this->sectionLibraryRepository->deleteByTemplate($tid);
        $this->templateHistoryRepository->deleteByTemplate($tid);
        $this->componentsRepository->deleteByTemplate($tid);
        $this->sectionsRepository->deleteByTemplate($tid);
        $this->emailRepository->deleteByTemplate($tid);
        $this->prepareAndExecute('DELETE FROM tns_template_sources WHERE tns_tmp_id = ?', [$tid]);
        $this->prepareAndExecute('DELETE FROM tmp_templates WHERE tmp_id = ?', [$tid]);
        $this->prepareAndExecute('DELETE FROM acc_access WHERE acc_tmp_id = ?', [$tid]);

        if ($template['tmp_parent']) {
            $dir = $this->paths->dirLayout($template['tmp_parent'], $tid);
        } else {
            $dir = $this->paths->dirTemplate($tid);
        }

        foreach($doms as $dom) {
            $images = $this->imagify->findHosted($dom, false);
            if ($images) {
                $this->cdn->batchRemoveByURL($images);
            }
        }

        try {
            $file = Paths::combine($dir, $template['tmp_location']);
            if (file_exists($file)) {
                $dom    = DomParser::fromFile($file);
                $images = $this->imagify->findHosted($dom, false);
                if ($images) {
                    $this->cdn->batchRemoveByURL($images);
                }
                if (file_exists($dir)) {
                    $this->paths->remove($dir);
                }
            }
        } catch (Exception $e) {}

        $this->clearCache($template);

        return true;
    }

    /**
     * @param int    $uid
     * @param int    $tid
     * @param string $title
     * @param bool   $force
     *
     * @return bool
     * @throws Exception
     */
    public function updateTitle(int $uid, int $tid, string $title, bool $force = false): bool
    {
        if (!$force && $this->hasStarterAccess($uid, $tid)) {
            return false;
        }
        if (!$force && !$this->hasAccess($uid, $tid)) {
            return false;
        }

        $template = $this->findByID($tid);
        $stmt = $this->pdo->prepare("UPDATE tmp_templates SET tmp_title = ? WHERE tmp_id = ?");
        $stmt->execute([$title, $tid]);
        $this->clearCache($template);

        return true;
    }

    /**
     * @param int    $tid
     * @param int    $version
     * @param string $imgBase
     *
     * @return int
     * @throws Exception
     */
    public function updateImgBase(int $tid, int $version, string $imgBase): int
    {
        $template = $this->findByID($tid);
        $stmt     = $this->prepareAndExecute(
            'UPDATE `tmp_templates` SET `tmp_img_base_url` = ? WHERE `tmp_id` = ? AND `tmp_version` = ? LIMIT 1',
            [$imgBase, $tid, $version]
        );
        $this->clearCache($template);

        return $stmt->rowCount();
    }

    /**
     * @param int           $tid
     * @param DateTime|null $now
     *
     * @return int
     * @throws Exception
     */
    public function updateUpdatedAt(int $tid, ?DateTime $now = null): int
    {
        $template = $this->findByID($tid);
        if ($now) {
            $stmt = $this->prepareAndExecute(
                'UPDATE `tmp_templates` SET `tmp_updated_at` = FROM_UNIXTIME(?) WHERE `tmp_id` = ? LIMIT 1',
                [
                    $now->getTimestamp(),
                    $tid
                ]
            );
        } else {
            $stmt = $this->prepareAndExecute(
                'UPDATE `tmp_templates` SET `tmp_updated_at` = NOW() WHERE `tmp_id` = ? LIMIT 1',
                [$tid]
            );
        }
        $this->clearCache($template);

        return $stmt->rowCount();
    }

    /**
     * @param int $tid
     *
     * @return int
     */
    public function getTemplateLatestVersion(int $tid): int
    {
        $stmt = $this->pdo->prepare("SELECT MAX(ema_tmp_version) AS version FROM ema_emails WHERE ema_tmp_id = ?");
        $stmt->execute(array($tid));
        if (0 == $stmt->rowCount()) {
            return 0;
        }

        return (int)$this->fetch($stmt)['version'];
    }

    /**
     * @param int $id
     * @param int $oid
     *
     * @throws Exception
     */
    public function updateOrgId(int $id, int $oid)
    {
        $this->prepareAndExecute('UPDATE tmp_templates SET tmp_org_id = ? WHERE tmp_id = ?', [
            $oid,
            $id
        ]);
        $this->cache->deleteByTag(new TemplateTag($id));
    }

    /**
     * @param int $tid
     * @param int $uid
     *
     * @return int
     * @throws Exception
     */
    public function updateOwner(int $tid, int $uid): int
    {
        $stmt = $this->prepareAndExecute('UPDATE `tmp_templates` SET `tmp_usr_id` = ? WHERE `tmp_id` = ? LIMIT 1', [
            $uid,
            $tid
        ]);
        $this->cache->deleteByTag(new TemplateTag($tid));

        return $stmt->rowCount();
    }

    /**
     * @param int  $tid
     * @param bool $enabled
     *
     * @return int
     * @throws Exception
     */
    public function updateTpaEnabled(int $tid, bool $enabled): int
    {
        $stmt = $this->prepareAndExecute('UPDATE `tmp_templates` SET `tmp_tpa_enabled` = ? WHERE `tmp_id` = ? LIMIT 1', [
            (int)$enabled,
            $tid
        ]);
        $this->cache->deleteByTag(new TemplateTag($tid));

        return $stmt->rowCount();
    }

    /**
     * @param int  $tid
     * @param bool $enabled
     *
     * @return int
     * @throws Exception
     */
    public function updateTmhEnabled(int $tid, bool $enabled): int
    {
        $template = $this->findByID($tid);
        $stmt = $this->prepareAndExecute('UPDATE `tmp_templates` SET `tmp_tmh_enabled` = ? WHERE `tmp_id` = ? LIMIT 1', [
            (int)$enabled,
            $tid
        ]);
        $this->clearCache($template);

        return $stmt->rowCount();
    }

    /**
     * @param int $tid
     * @param int $version
     *
     * @return int
     * @throws Exception
     */
    public function updateVersion(int $tid, int $version): int
    {
        $template = $this->findByID($tid);
        $stmt = $this->prepareAndExecute('UPDATE `tmp_templates` SET `tmp_version` = ? WHERE `tmp_id` = ? LIMIT 1', [
            $version,
            $tid
        ]);
        $this->clearCache($template);

        return $stmt->rowCount();
    }

    /**
     * @param int  $tid
     * @param bool $enabled
     *
     * @return int
     * @throws Exception
     */
    public function updateAliasEnabled(int $tid, bool $enabled): int
    {
        $stmt = $this->prepareAndExecute('UPDATE `tmp_templates` SET `tmp_alias_enabled` = ? WHERE `tmp_id` = ? LIMIT 1', [
            (int)$enabled,
            $tid
        ]);
        $this->cache->deleteByTag(new TemplateTag($tid));

        return $stmt->rowCount();
    }

    /**
     * @param array|Template $template
     *
     * @return void
     * @throws Exception
     */
    protected function clearCache($template)
    {
        if ($template) {
            $id = is_array($template) ? $template['tmp_id'] : $template->getId();
            unset($this->templatesCache[$id]);
            unset($this->templateEntitiesCache[$id]);
            $this->deleteEntityCache($template);
        }
    }

    /**
     * @var EmailHistoryRepository
     */
    protected $emailHistoryRepository;

    /**
     * @var TemplateHistoryRepository
     */
    protected $templateHistoryRepository;

    /**
     * @var UploadExtractor
     */
    protected $uploadExtractor;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var CDNInterface
     */
    protected $cdn;

    /**
     * @var AccessRepository
     */
    protected $accessRepository;

    /**
     * @var SectionLibraryRepository
     */
    protected $sectionLibraryRepository;

    /**
     * @var ComponentsRepository
     */
    protected $componentsRepository;

    /**
     * @var OrganizationsRepository
     */
    protected $organizationsRepository;

    /**
     * @var ImagesRepository
     */
    protected $imagesRepository;

    /**
     * @var EmailTemplateRepository
     */
    protected $emailTemplateRepository;

    /**
     * @var EmailRepository
     */
    protected $emailRepository;

    /**
     * @var Imagify
     */
    protected $imagify;

    /**
     * @var Scriptify
     */
    protected $scriptify;

    /**
     * @var TemplateUpgrader
     */
    protected $templateUpgrader;

    /**
     * @Required()
     * @param TemplateUpgrader $templateUpgrader
     */
    public function setTemplateUpgrader(TemplateUpgrader $templateUpgrader)
    {
    	$this->templateUpgrader = $templateUpgrader;
    }

    /**
     * @Required()
     * @param SectionLibraryRepository $sectionLibraryRepository
     */
    public function setSectionLibraryRepository(SectionLibraryRepository $sectionLibraryRepository)
    {
        $this->sectionLibraryRepository = $sectionLibraryRepository;
    }

    /**
     * @Required()
     * @param AccessRepository $accessRepository
     */
    public function setAccessRepository(AccessRepository $accessRepository)
    {
        $this->accessRepository = $accessRepository;
    }

    /**
     * @Required()
     * @param CDNInterface $cdn
     */
    public function setCDNInterface(CDNInterface $cdn)
    {
        $this->cdn = $cdn;
    }

    /**
     * @Required()
     * @param Scriptify $scriptify
     */
    public function setScriptify(Scriptify $scriptify)
    {
        $this->scriptify = $scriptify;
    }

    /**
     * @Required()
     * @param Serializer $serializer
     */
    public function setSerializer(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @Required()
     * @param Imagify $imagify
     */
    public function setImagify(Imagify $imagify)
    {
        $this->imagify = $imagify;
    }

    /**
     * @Required()
     * @param ImagesRepository $imagesRepository
     */
    public function setImagesRepository(ImagesRepository $imagesRepository)
    {
        $this->imagesRepository = $imagesRepository;
    }

    /**
     * @Required()
     * @param EmailTemplateRepository $emailTemplateRepository
     */
    public function setEmailTemplateRepository(EmailTemplateRepository $emailTemplateRepository)
    {
        $this->emailTemplateRepository = $emailTemplateRepository;
    }

    /**
     * @Required()
     * @param OrganizationsRepository $organizationsRepository
     */
    public function setOrganizationsRepository(OrganizationsRepository $organizationsRepository)
    {
        $this->organizationsRepository = $organizationsRepository;
    }

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
     * @param TemplateHistoryRepository $templateHistoryRepository
     */
    public function setTemplateHistoryRepository(TemplateHistoryRepository $templateHistoryRepository)
    {
        $this->templateHistoryRepository = $templateHistoryRepository;
    }

    /**
     * @Required()
     * @param UploadExtractor $uploadExtractor
     */
    public function setUploadExtractor(UploadExtractor $uploadExtractor)
    {
        $this->uploadExtractor = $uploadExtractor;
    }

    /**
     * @var OrganizationAccessRepository
     */
    protected $organizationAccessRepository;

    /**
     * @Required()
     * @param OrganizationAccessRepository $organizationAccessRepository
     */
    public function setOrganizationAccessRepository(OrganizationAccessRepository $organizationAccessRepository)
    {
    	$this->organizationAccessRepository = $organizationAccessRepository;
    }
}
