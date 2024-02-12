<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\Html\DocMeta;
use BlocksEdit\Html\Utils;
use BlocksEdit\IO\IOBase;
use Entity\ChecklistItem;
use Entity\Email;
use SplFileInfo;
use Tag\EmailTag;
use Tag\FolderTag;
use Tag\OrganizationTag;
use Tag\TemplateTag;
use BlocksEdit\Html\DomParser;
use BlocksEdit\Html\Imagify;
use BlocksEdit\Html\HtmlData;
use BlocksEdit\IO\Exception\IOException;
use BlocksEdit\IO\FilesTrait;
use BlocksEdit\IO\PathsTrait;
use BlocksEdit\IO\Paths;
use BlocksEdit\System\Required;
use BlocksEdit\Util\Tokens;
use BlocksEdit\Util\TokensTrait;
use DateTime;
use Entity\Image;
use Exception;
use Entity\EmailHistory;
use Repository\Exception\CreateException;
use Repository\Exception\CreateTemplateException;
use RuntimeException;

/**
 * Class EmailRepository
 */
class EmailRepository extends Repository
{
    use FilesTrait;
    use PathsTrait;
    use TokensTrait;

    /**
     * @var array
     */
    protected $emailCache = [];

    /**
     * @var Email[]
     */
    protected $emailEntitiesCache = [];

    /**
     * @param int $id
     * @param int $version
     *
     * @return HtmlData
     * @throws Exception
     */
    public function getHtml(int $id, int $version = 0): HtmlData
    {
        $email = $this->findByID($id);
        if (!$email) {
            throw new RuntimeException("Email $id not found.");
        }

        if ($version) {
            $emailHistory = $this->emailHistoryRepository->findByEmailVersion($id, $version);
            if (!$emailHistory || $emailHistory->getEmaId() !== $id) {
                throw new RuntimeException("Email history not found $id.");
            }
            $html = $emailHistory->getHtml();
        } else {
            $version      = $this->emailHistoryRepository->findLatestVersion($id);
            $emailHistory = $this->emailHistoryRepository->findByEmailVersion($id, $version);
            if ($emailHistory) {
                $html = $emailHistory->getHtml();
            } else {
                $version = 0;
                $dir  = $this->paths->dirEmail($id);
                $file = Paths::combine($dir, $email['ema_location']);
                $html = trim($this->files->read($file));
            }
        }

        $html = preg_replace('/<!-- BE (EMAIL|TEMPLATE) VERSION \d+ -->/', '', $html);
        $dom  = DomParser::fromString($html);
        if ($this->config->env === 'dev') {
            $body = $dom->find('body', 0);
            if ($body) {
                $body->appendChild($dom->createTextNode("<!-- BE EMAIL VERSION $version -->"));
                $body->appendChild($dom->createTextNode("<!-- BE TEMPLATE VERSION $email[ema_tmp_version] -->"));
            }
        }

        return new HtmlData($dom, $version);
    }

    /**
     * @param int    $id
     * @param int    $version
     * @param string $html
     *
     * @throws IOException
     * @throws Exception
     */
    public function setHtml(int $id, int $version, string $html)
    {
        $email = $this->findByID($id);
        if (!$email) {
            throw new RuntimeException("Email $id not found.");
        }
        $template = $this->templatesRepository->findByID($email['ema_tmp_id']);
        if (!$template) {
            throw new RuntimeException("Template $email[ema_tmp_id] not found.");
        }

        if ($version) {
            $emailHistory = $this->emailHistoryRepository->findByEmailVersion($id, $version);
            if (!$emailHistory || $emailHistory->getEmaId() !== $id) {
                throw new RuntimeException("Email history not found $id.");
            }

            $emailHistory->setHtml($html);
            $this->emailHistoryRepository->update($emailHistory);
            $this->cache->deleteByTag(new EmailTag($id));
            return;
        }

        $emailHistory = $this->emailHistoryRepository->findByEmailVersion($id, 0);
        if ($emailHistory) {
            $emailHistory->setHtml($html);
            $this->emailHistoryRepository->update($emailHistory);
            $this->cache->deleteByTag(new EmailTag($id));
            return;
        }

        $dir = $this->paths->dirEmail($id);
        $file = Paths::combine($dir, $email['ema_location']);
        $this->files->write($file, $html);
        $this->cache->deleteByTag(new EmailTag($id));
    }

    /**
     * @param int    $uid
     * @param int    $tid
     * @param int    $oid
     * @param string $title
     * @param int    $fid
     *
     * @return int
     * @throws CreateTemplateException
     * @throws Exception
     */
    public function create(int $uid, int $tid, int $oid, string $title, int $fid = 0): int
    {
        $template = $this->templatesRepository->findByID($tid);
        if (!$template) {
            throw new CreateTemplateException('Template not found.');
        }

        $version = 0;
        if ($template['tmp_tmh_enabled']) {
            $version = (int)$template['tmp_version'];
        }

        $src = $this->paths->dirTemplate($tid);
        if (!file_exists($src)) {
            throw new CreateTemplateException('The template is missing!');
        }
        $location = $template['tmp_location'];
        $token    = $this->tokens->generateToken($uid, Tokens::TOKEN_PREVIEW);

        $query = $this->pdo->prepare(
            "INSERT INTO ema_emails
                (
                 ema_tmp_id,
                 ema_title,
                 ema_location,
                 ema_created_at,
                 ema_created_usr_id,
                 ema_updated_usr_id,
                 ema_token,
                 ema_folder_id,
                 ema_epa_enabled,
                 ema_alias_enabled,
                 ema_tmp_version
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $query->execute([
            $tid,
            $title,
            $location,
            time(),
            $uid,
            $uid,
            $token,
            $fid ?: null,
            $template['tmp_tpa_enabled'],
            $template['tmp_alias_enabled'],
            $version
        ]);
        $eid = $this->getLastInsertID();

        $dom = $this->templatesRepository->getHtml($tid, $version)->getDom();
        $dom = Utils::removeBlockHide($dom);
        $this->imagify->upgradeHostedImages($dom, $eid, $oid, 0, '', true);

        $sections = $dom->find('.block-section');
        if ($sections) {
            $firstSection = null;
            foreach($sections as $section) {
                if (!$firstSection && strpos($section->getAttribute('class'), 'be-code-edit') === false) {
                    $firstSection = $section;
                } else {
                    $section->outertext = '';
                }
            }

            $firstSection->outertext = sprintf(
                '<%s class="block-section block-section-empty" style="width: 100%%; min-height: 250px; height: 250px;"></%s>',
                $firstSection->tag,
                $firstSection->tag
            );
        }

        try {
            $dst = $this->paths->dirEmail($eid);
            $this->paths->make($dst);
            $this->paths->copy($src, $dst, IOBase::PERMISSIONS, function (SplFileInfo $fileInfo) {
                return !$fileInfo->isDir();
            });
            if (!$template['tmp_tmh_enabled']) {
                $this->files->write(Paths::combine($dst, $location), (string)$dom);
            }
        } catch (IOException $e) {
            $this->deleteByID($eid);
            throw new CreateTemplateException('Uh oh, there was some kind of error! Please try again later.');
        }

        $this->updateUpdatedAt($uid, $eid);

        $emailHistory = (new EmailHistory())
            ->setEmaId($eid)
            ->setHtml((string)$dom)
            ->setVersion(0)
            ->setMessage('')
            ->setUsrId($uid);
        $this->emailHistoryRepository->insert($emailHistory);

        // @see assets/js/builder/modals/TemplateSettingsModal/ChecklistSettings.jsx
        $email = $this->findByID($eid, true);
        $checklistSettings = $email->getTemplate()->getChecklistSettings();
        if (!empty($checklistSettings['enabled'])) {
            if (!empty($checklistSettings['altText'])) {
                $checklistItem = (new ChecklistItem())
                    ->setKey('altText')
                    ->setEmail($email)
                    ->setTemplate($email->getTemplate())
                    ->setIsChecked(false)
                    ->setCheckedUser(null)
                    ->setIsTemplate(true)
                    ->setTitle('Alt text on images')
                    ->setDescription('Images should have necessary descriptive text.');
                $this->checklistItemRepository->insert($checklistItem);
            }
            if (!empty($checklistSettings['links'])) {
                $checklistItem = (new ChecklistItem())
                    ->setKey('links')
                    ->setEmail($email)
                    ->setTemplate($email->getTemplate())
                    ->setIsChecked(false)
                    ->setCheckedUser(null)
                    ->setIsTemplate(true)
                    ->setTitle('Links')
                    ->setDescription('Check for blank links and test for broken links.');
                $this->checklistItemRepository->insert($checklistItem);
            }
            if (!empty($checklistSettings['trackingParams'])) {
                $checklistItem = (new ChecklistItem())
                    ->setKey('trackingParams')
                    ->setEmail($email)
                    ->setTemplate($email->getTemplate())
                    ->setIsChecked(false)
                    ->setCheckedUser(null)
                    ->setIsTemplate(true)
                    ->setTitle('Tracking parameters on links')
                    ->setDescription('Links should have referral parameters added.');
                $this->checklistItemRepository->insert($checklistItem);
            }
            if (!empty($checklistSettings['items'])) {
                foreach($checklistSettings['items'] as $i => $item) {
                    $checklistItem = (new ChecklistItem())
                        ->setKey((string)($i + 1))
                        ->setEmail($email)
                        ->setTemplate($email->getTemplate())
                        ->setIsChecked(false)
                        ->setCheckedUser(null)
                        ->setIsTemplate(false)
                        ->setTitle($item['title'])
                        ->setDescription($item['description']);
                    $this->checklistItemRepository->insert($checklistItem);
                }
            }
        }

        $this->cache->deleteByTags([
            new OrganizationTag($oid),
            new TemplateTag($tid),
        ]);
        $this->clearCache($this->findByID($eid));

        return $eid;
    }

    /**
     * @param int    $uid
     * @param int    $oid
     * @param int    $eid
     * @param string $markup
     * @param string $title
     * @param int    $version
     * @param int    $templateVersion
     *
     * @return EmailHistory
     * @throws CreateTemplateException
     * @throws Exception
     */
    public function save(
        int $uid,
        int $oid,
        int $eid,
        string $markup,
        string $title,
        int $version,
        int $templateVersion = 0
    ): EmailHistory
    {
        $email = $this->findByID($eid);
        if (!$email) {
            throw new CreateTemplateException('Email not found.');
        }
        $template = $this->templatesRepository->findByID($email['ema_tmp_id']);
        if (!$template) {
            throw new CreateTemplateException('Template not found.');
        }

        $markup = preg_replace('/<!-- BE (EMAIL|TEMPLATE) VERSION \d+ -->/', '', $markup);

        // Sometimes the body styles get lost when editing. Don't know why but
        // let's restore them from the original template.
        $templateDOM = $this->templatesRepository->getHtml($template['tmp_id'])->getDom();
        $newDOM      = DomParser::fromString($markup);
        $dir         = $this->paths->dirEmail($eid, $version);

        foreach ($newDOM->find('img') as $element) {
            /** @phpstan-ignore-next-line */
            if (!empty($element->original) && $this->imagify->isImagifyUrl($element->src)) {
                /** @phpstan-ignore-next-line */
                $element->src = $element->original;
                $element->original = null;
            }
        }
        foreach ($newDOM->find('b') as $element) {
            $element->outertext = str_replace( '<b', '<strong', $element->outertext);
            $element->outertext = str_replace( '</b>', '</strong>', $element->outertext);
        }

        $body = $templateDOM->find('body', 0);
        if ($body) {
            $style = $body->getAttribute('style');
            if ($style) {
                $newBody = $newDOM->find('body', 0);
                if ($newBody) {
                    $newBody->setAttribute('style', $style);
                }
            }
        }

        $origBodyCount = count($templateDOM->find('head'));
        if (!$origBodyCount) {
            $templateDOM = $newDOM;
        } else {
            $newBody = $newDOM->find('body', 0);
            if ($newBody) {
                /** @noinspection PhpUndefinedFieldInspection */
                $templateDOM->find('body', 0)->innertext = $newBody->innertext;
            } else {
                /** @noinspection PhpUndefinedFieldInspection */
                $templateDOM->find('body', 0)->innertext = $newDOM;
            }
        }

        $head     = $templateDOM->find('head', 0);
        $titleDom = $head->find('title', 0);
        if (!$titleDom) {
            /** @noinspection PhpUndefinedFieldInspection */
            $head->innertext = $head->innertext . '<title>' . htmlspecialchars($title) . '</title>';
        } else {
            /** @noinspection PhpUndefinedFieldInspection */
            $titleDom->innertext = $title;
        }

        $templateDOM = Utils::removeBlockHide($templateDOM);
        $this->docMeta->updateMetaTags($newDOM, $templateDOM);

        try {
            $this->beginTransaction();

            $html = (string)$templateDOM;
            if (!$version) {
                $file = Paths::combine($dir, $email['ema_location']);
                $this->files->write($file, $html);
            }

            $emailHistory = $this->emailHistoryRepository->save($uid, $eid, $html, $version);
            $newDir = $this->paths->dirEmail($eid, $emailHistory->getVersion());
            $this->paths->make($newDir);

            $dom     = DomParser::fromString($html);
            $nextDir = $this->paths->dirEmailNext($eid, $version);
            $this->imagify->upgradeHostedImages(
                $dom,
                $eid,
                $oid,
                $emailHistory->getVersion(),
                '',
                true
            );

            $this->updateUpdatedAt($uid, $eid);
            $emailHistory->setHtml((string)$dom);
            $this->emailHistoryRepository->update($emailHistory);
            $this->paths->remove($nextDir);

            if ($templateVersion) {
                $this->updateTemplateVersion($eid, $templateVersion);
            }

            $this->commit();
            $this->cache->deleteByTags([
                new EmailTag($eid),
                new TemplateTag($email['ema_tmp_id'])
            ]);
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }

        return $emailHistory;
    }

    /**
     * @param int    $uid
     * @param int    $eid
     * @param string $title
     *
     * @return int
     * @throws Exception
     */
    public function duplicate(int $uid, int $eid, string $title): int
    {
        $email = $this->findByID($eid);
        $tid   = $email['ema_tmp_id'];
        if (!$this->hasAccess($uid, $eid)) {
            return -1;
        }
        if (empty($title)) {
            $title = $email['ema_title'];
        }

        $template = $this->templatesRepository->findByID($tid);

        try {
            $this->beginTransaction();

            $token = $this->tokens->generateToken($uid, Tokens::TOKEN_PREVIEW);
            $stmt = $this->pdo->prepare(
                "INSERT INTO ema_emails (ema_tmp_id, ema_title, ema_location, ema_created_at, ema_created_usr_id, ema_updated_at, ema_updated_usr_id, ema_token, ema_epa_enabled, ema_alias_enabled, ema_tmp_version) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $tid,
                $title,
                $email['ema_location'],
                time(),
                $uid,
                time(),
                $uid,
                $token,
                $template['tmp_tpa_enabled'],
                $template['tmp_alias_enabled'],
                $email['ema_tmp_version']
            ]);
            $newID = $this->getLastInsertID();

            $dom = $this->getHtml($eid)->getDom();
            $this->imagify->upgradeHostedImages($dom, $newID, $template['tmp_org_id'], 1, '', true);
            $emailHistory = (new EmailHistory())
                ->setEmaId($newID)
                ->setHtml((string)$dom)
                ->setVersion(1)
                ->setMessage('')
                ->setUsrId($uid);
            $this->emailHistoryRepository->insert($emailHistory);

            $src = $this->paths->dirEmail($eid);
            if (file_exists($src)) {
                $dst = $this->paths->dirEmail($newID);
                $this->paths->make($dst);
                $this->paths->copy($src, $dst);
            }

            // @see assets/js/builder/modals/TemplateSettingsModal/ChecklistSettings.jsx
            $email = $this->findByID($newID, true);
            $checklistSettings = $email->getTemplate()->getChecklistSettings();
            if (!empty($checklistSettings['enabled'])) {
                if (!empty($checklistSettings['altText'])) {
                    $checklistItem = (new ChecklistItem())
                        ->setKey('altText')
                        ->setEmail($email)
                        ->setTemplate($email->getTemplate())
                        ->setIsChecked(false)
                        ->setCheckedUser(null)
                        ->setIsTemplate(true)
                        ->setTitle('Alt text on images')
                        ->setDescription('Images should have necessary descriptive text.');
                    $this->checklistItemRepository->insert($checklistItem);
                }
                if (!empty($checklistSettings['links'])) {
                    $checklistItem = (new ChecklistItem())
                        ->setKey('links')
                        ->setEmail($email)
                        ->setTemplate($email->getTemplate())
                        ->setIsChecked(false)
                        ->setCheckedUser(null)
                        ->setIsTemplate(true)
                        ->setTitle('Links')
                        ->setDescription('Check for blank links and test for broken links.');
                    $this->checklistItemRepository->insert($checklistItem);
                }
                if (!empty($checklistSettings['trackingParams'])) {
                    $checklistItem = (new ChecklistItem())
                        ->setKey('trackingParams')
                        ->setEmail($email)
                        ->setTemplate($email->getTemplate())
                        ->setIsChecked(false)
                        ->setCheckedUser(null)
                        ->setIsTemplate(true)
                        ->setTitle('Tracking parameters on links')
                        ->setDescription('Links should have referral parameters added.');
                    $this->checklistItemRepository->insert($checklistItem);
                }
                if (!empty($checklistSettings['items'])) {
                    foreach($checklistSettings['items'] as $i => $item) {
                        $checklistItem = (new ChecklistItem())
                            ->setKey((string)($i + 1))
                            ->setEmail($email)
                            ->setTemplate($email->getTemplate())
                            ->setIsChecked(false)
                            ->setCheckedUser(null)
                            ->setIsTemplate(false)
                            ->setTitle($item['title'])
                            ->setDescription($item['description']);
                        $this->checklistItemRepository->insert($checklistItem);
                    }
                }
            }

            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }

        $this->cache->deleteByTags([
            new OrganizationTag($template['tmp_org_id']),
        ]);
        $this->clearCache($email);

        return $newID;
    }

    /**
     * @param int $eid
     * @param int $tid
     * @param int $uid
     *
     * @return int
     * @throws Exception
     */
    public function cloneStarter(int $eid, int $tid, int $uid): int
    {
        $email = $this->findByID($eid);
        if (!$email) {
            $this->logger->error("Email $eid not found.");
            throw new CreateException('Uh oh, there was some kind of error! Please try again later.');
        }
        $template = $this->templatesRepository->findByID($email['ema_tmp_id']);
        if (!$template) {
            $this->logger->error("Template for email $eid not found.");
            throw new CreateException('Uh oh, there was some kind of error! Please try again later.');
        }

        $token = $this->tokens->generateToken($uid, Tokens::TOKEN_PREVIEW);
        $query = $this->pdo->prepare(
            "INSERT INTO ema_emails (ema_tmp_id, ema_title, ema_location, ema_created_at, ema_created_usr_id, ema_updated_usr_id, ema_token, ema_folder_id, ema_epa_enabled, ema_alias_enabled, ema_tmp_version) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $query->execute([
            $tid,
            $email['ema_title'],
            $email['ema_location'],
            time(),
            $uid,
            $uid,
            $token,
            null,
            $template['tmp_tpa_enabled'],
            $template['tmp_alias_enabled'],
            $email['ema_tmp_version']
        ]);
        $newEID = $this->getLastInsertID();

        $src = $this->paths->dirEmail($eid);
        $dst = $this->paths->dirEmail($newEID);
        if (!file_exists($src)) {
            $this->deleteByID($newEID);
            $this->logger->error("Source directory does not exist.");
            throw new CreateException('Uh oh, there was some kind of error! Please try again later.');
        }
        try {
            $this->paths->make($dst);
        } catch (IOException $e) {
            $this->deleteByID($newEID);
            $this->logger->error("Destination directory does not exist.");
            throw new CreateException('Uh oh, there was some kind of error! Please try again later.');
        }

        $this->paths->copy($src, $dst);

        return $newEID;
    }

    /**
     * @param int  $id
     * @param bool $asEntity
     *
     * @return array|Email|null
     * @throws Exception
     */
    public function findByID(int $id, bool $asEntity = false)
    {
        if ($asEntity) {
            if (isset($this->emailEntitiesCache[$id])) {
                return $this->emailEntitiesCache[$id];
            }
            $this->emailEntitiesCache[$id] = $this->findOne([
                'id' => $id
            ]);

            return $this->emailEntitiesCache[$id];
        }

        if (isset($this->emailCache[$id])) {
            return $this->emailCache[$id];
        }

        $stmt                  = $this->prepareAndExecute('SELECT * FROM ema_emails WHERE ema_id = ? LIMIT 1', [$id]);
        $this->emailCache[$id] = $this->fetch($stmt);

        return $this->emailCache[$id];
    }

    /**
     * @param string $token
     *
     * @return array
     * @throws Exception
     */
    public function findByToken(string $token): array
    {
        $stmt = $this->prepareAndExecute('SELECT * FROM ema_emails WHERE ema_token = ? LIMIT 1', [$token]);
        if ($stmt->rowCount() === 0) {
            return [];
        }

        return $this->fetch($stmt);
    }

    /**
     * @param int    $tid
     * @param string $order
     *
     * @return array
     * @throws Exception
     */
    public function findByTemplate(int $tid, string $order = 'ema_created_at ASC'): array
    {
        $stmt = $this->prepareAndExecute("SELECT * FROM ema_emails WHERE ema_tmp_id = ? ORDER BY $order", [
            $tid
        ]);

        return $this->fetchAll($stmt);
    }

    /**
     * @param int $id
     *
     * @return array
     * @throws Exception
     */
    public function findNextEmail(int $id): array
    {
        $email = $this->findByID($id, true);
        if (!$email) {
            return [];
        }

        $stmt = $this->prepareAndExecute("SELECT * FROM ema_emails WHERE ema_id < ? AND ema_tmp_id = ? ORDER BY ema_id DESC", [
            $id,
            $email->getTemplate()->getId()
        ]);

        return $this->fetch($stmt);
    }

    /**
     * @param int|null $limit
     * @param int      $offset
     *
     * @return array
     * @throws Exception
     */
    public function findAll(?int $limit = null, int $offset = 0): array
    {
        if ($limit) {
            $stmt = $this->prepareAndExecute(
                sprintf('SELECT * FROM ema_emails ORDER BY `ema_id` DESC LIMIT %d, %d', $offset, $limit)
            );
        } else {
            $stmt = $this->prepareAndExecute('SELECT * FROM ema_emails ORDER BY `ema_id` DESC');
        }

        return $this->fetchAll($stmt);
    }

    /**
     * @param string $term
     * @param int    $uid
     * @param int    $limit
     * @param int    $offset
     *
     * @return array
     * @throws Exception
     */
    public function findByTerm(string $term, int $uid, int $limit = 100, int $offset = 0): array
    {
        $sql = '
            SELECT ema_emails.*
            FROM ema_emails
            LEFT JOIN tmp_templates ON tmp_templates.tmp_id = ema_emails.ema_tmp_id
            LEFT JOIN acc_access ON acc_access.acc_tmp_id = tmp_templates.tmp_id
            WHERE ema_title LIKE ?
            AND acc_usr_id = ?
            ORDER BY `ema_id` DESC
            LIMIT %d, %d
        ';

        $stmt = $this->prepareAndExecute(
            sprintf($sql, $offset, $limit),
            ['%' . $term . '%', $uid]
        );

        return $this->fetchAll($stmt);
    }

    /**
     * @param int $fid
     *
     * @return array
     * @throws Exception
     */
    public function findByFolder(int $fid): array
    {
        $stmt = $this->prepareAndExecute('SELECT * FROM ema_emails WHERE ema_folder_id = ?', [$fid]);

        return $this->fetchAll($stmt);
    }

    /**
     * @return int
     * @throws Exception
     */
    public function countAll(): int
    {
        $stmt = $this->prepareAndExecute('SELECT COUNT(*) FROM `ema_emails`');

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
            'SELECT * FROM `ema_emails` WHERE `ema_created_at` > ?',
            [$date->getTimestamp()]
        );

        return $this->fetchAll($stmt);
    }

    /**
     * @param int $eid
     * @param int $version
     *
     * @throws Exception
     */
    public function updateTemplateVersion(int $eid, int $version)
    {
        $this->prepareAndExecute('UPDATE ema_emails SET ema_tmp_version = ? WHERE ema_id = ?', [
            $version,
            $eid,
        ]);
        $this->cache->deleteByTag(new EmailTag($eid));
    }

    /**
     * @param int $uid
     * @param int $eid
     *
     * @return bool
     * @throws Exception
     */
    public function hasAccess(int $uid, int $eid): bool
    {
        $email = $this->findByID($eid);
        if (!$email) {
            return false;
        }

        return $this->templatesRepository->hasAccess($uid, $email['ema_tmp_id']);
    }

    /**
     * @param Image $image
     *
     * @return string
     * @throws Exception
     */
    public function getEmailImageLocation(Image $image): string
    {
        return Paths::combine(
            $this->paths->dirEmail($image->getEmaId(), $image->getEmaVersion()),
            $image->getFilename()
        );
    }

    /**
     * @param int $eid
     *
     * @return array
     * @throws Exception
     */
    public function findLayouts(int $eid): array
    {
        $email = $this->findByID($eid);
        $stmt = $this->pdo->prepare("SELECT * FROM tmp_templates WHERE tmp_parent = ?");
        $stmt->execute(array($email['ema_tmp_id']));

        return $this->fetchAll($stmt);
    }

    /**
     * @param int $eid
     *
     * @return bool
     * @throws Exception
     */
    public function deleteByID(int $eid): bool
    {
        $et = $this->emailTemplateRepository->findByEmaId($eid);
        if ($et) {
            throw new RuntimeException('Cannot delete email because it is used as an email template.');
        }

        $email = $this->findByID($eid);
        $this->paths->remove($this->paths->dirEmail($eid));
        foreach($this->emailHistoryRepository->findByEmail($eid) as $item) {
            $this->paths->remove($this->paths->dirEmail($eid, $item->getVersion()));
            $this->emailHistoryRepository->delete($item);
        }
        $this->prepareAndExecute('DELETE FROM ema_emails WHERE ema_id = ?', [
            $eid
        ]);
        $updatedAt = $this->fetchMaxUpdatedAt($email['ema_tmp_id'], $email['ema_tmp_version']);
        $this->templatesRepository->updateUpdatedAt($email['ema_tmp_id'], $updatedAt);
        $this->clearCache($email);

        return true;
    }

    /**
     * @param int $tid
     *
     * @return int
     * @throws Exception
     */
    public function deleteByTemplate(int $tid): int
    {
        $cacheTags = [];
        $emails = $this->findByTemplate($tid);
        foreach($emails as $email) {
            $this->emailHistoryRepository->deleteByEmail($email['ema_id']);
            $cacheTags[] = new EmailTag($email['ema_id']);
        }
        $stmt = $this->prepareAndExecute('DELETE FROM ema_emails WHERE ema_tmp_id = ?', [
            $tid
        ]);
        $this->cache->deleteByTags($cacheTags);
        $this->emailCache = [];

        return $stmt->rowCount();
    }

    /**
     * @param int $fid
     *
     * @return int
     * @throws Exception
     */
    public function deleteByFolder(int $fid): int
    {
        $emails = $this->findByFolder($fid);
        foreach($emails as $email) {
            $this->deleteByID($email['ema_id']);
        }

        return count($emails);
    }

    /**
     * @param int $uid
     * @param int $eid
     *
     * @return int
     * @throws Exception
     */
    public function updateUpdatedAt(int $uid, int $eid): int
    {
        $email = $this->findByID($eid);
        $this->templatesRepository->updateUpdatedAt($email['ema_tmp_id']);
        $stmt = $this->pdo->prepare("UPDATE ema_emails SET ema_updated_at = ?, ema_updated_usr_id = ? WHERE ema_id = ?");
        $stmt->execute([time(), $uid, $eid]);
        $this->clearCache($email);

        return $stmt->rowCount();
    }

    /**
     * @param int $tid
     * @param int $version
     *
     * @return DateTime
     * @throws Exception
     */
    public function fetchMaxUpdatedAt(int $tid, int $version): DateTime
    {
        $stmt = $this->prepareAndExecute(
            "SELECT MAX(IF(ema_updated_at, ema_updated_at, ema_created_at)) as updated_at
                FROM ema_emails
                WHERE ema_tmp_id = ?
                AND ema_tmp_version = ?
                LIMIT 1
            ",
            [$tid, $version]
        );
        $row = $this->fetch($stmt);
        if ($row && !empty($row['updated_at'])) {
            $date = new DateTime("@$row[updated_at]");
        } else {
            $date = new DateTime();
        }

        return $date;
    }

    /**
     * @param int  $eid
     * @param bool $enabled
     *
     * @return int
     * @throws Exception
     */
    public function updateEpaEnabled(int $eid, bool $enabled): int
    {
        $stmt = $this->prepareAndExecute('UPDATE `ema_emails` SET `ema_epa_enabled` = ? WHERE `ema_id` = ? LIMIT 1', [
            (int)$enabled,
            $eid
        ]);
        unset($this->emailCache[$eid]);
        $this->cache->deleteByTag(new EmailTag($eid));

        return $stmt->rowCount();
    }

    /**
     * @param int  $eid
     * @param bool $enabled
     *
     * @return int
     * @throws Exception
     */
    public function updateAliasEnabled(int $eid, bool $enabled): int
    {
        $email = $this->findByID($eid);
        $stmt  = $this->prepareAndExecute('UPDATE `ema_emails` SET `ema_alias_enabled` = ? WHERE `ema_id` = ? LIMIT 1', [
            (int)$enabled,
            $eid
        ]);
        $this->clearCache($email);

        return $stmt->rowCount();
    }

    /**
     * @param int $eid
     * @param int|null $fid
     *
     * @return int
     * @throws Exception
     */
    public function updateFolder(int $eid, ?int $fid): int
    {
        $stmt = $this->prepareAndExecute(
            "UPDATE ema_emails SET ema_folder_id = ? WHERE ema_id = ? LIMIT 1",
            [$fid, $eid]
        );
        $this->cache->deleteByTag(new EmailTag($eid));
        if ($fid) {
            $this->cache->deleteByTag(new FolderTag($fid));
        }

        return $stmt->rowCount();
    }

    /**
     * @param array|Email $email
     *
     * @return void
     * @throws Exception
     */
    protected function clearCache($email)
    {
        if ($email) {
            $id = is_array($email) ? $email['ema_id'] : $email->getId();
            unset($this->emailCache[$id]);
            unset($this->emailEntitiesCache[$id]);
            $this->deleteEntityCache($email);
        }
    }

    /**
     * @var TemplatesRepository
     */
    protected $templatesRepository;

    /**
     * @var ImagesRepository
     */
    protected $imagesRepository;

    /**
     * @var EmailHistoryRepository
     */
    protected $emailHistoryRepository;

    /**
     * @var TemplateHistoryRepository
     */
    protected $templateHistoryRepository;

    /**
     * @var EmailTemplateRepository
     */
    protected $emailTemplateRepository;

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
     * @Required()
     * @param ImagesRepository $imagesRepository
     */
    public function setImagesRepository(ImagesRepository $imagesRepository)
    {
        $this->imagesRepository = $imagesRepository;
    }

    /**
     * @Required()
     * @param TemplatesRepository $templatesRepository
     */
    public function setTemplatesRepository(TemplatesRepository $templatesRepository)
    {
        $this->templatesRepository = $templatesRepository;
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
     * @param EmailTemplateRepository $emailTemplateRepository
     */
    public function setEmailTemplateRepository(EmailTemplateRepository $emailTemplateRepository)
    {
        $this->emailTemplateRepository = $emailTemplateRepository;
    }

    /**
     * @var ChecklistItemRepository
     */
    protected $checklistItemRepository;

    /**
     * @Required()
     * @param ChecklistItemRepository $checklistItemRepository
     */
    public function setChecklistItemRepository(ChecklistItemRepository $checklistItemRepository)
    {
    	$this->checklistItemRepository = $checklistItemRepository;
    }

    /**
     * @var DocMeta
     */
    protected $docMeta;

    /**
     * @Required()
     * @param DocMeta $docMeta
     */
    public function setDocMeta(DocMeta $docMeta)
    {
    	$this->docMeta = $docMeta;
    }
}
