<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\Media\CDNInterface;
use Exception;

/**
 * Class SectionsRepository
 */
class SectionsRepository extends Repository
{
    /**
     * @var array
     */
    protected $sectionCache = [];

    /**
     * @param int    $nr
     * @param string $html
     * @param string $style
     * @param string $block
     * @param string $title
     * @param int    $tid
     * @param bool   $mobile
     * @param int    $version
     *
     * @return int
     */
    public function create(
        int $nr,
        string $html,
        string $style,
        string $block,
        string $title,
        int $tid,
        bool $mobile = false,
        int $version = 0
    ): int
    {
        $this->sectionCache = [];
        if ($mobile) {
            $stmt = $this->pdo->prepare("INSERT INTO sec_sections (sec_nr, sec_html, sec_style, sec_block, sec_title, sec_tmp_id, sec_mobile, sec_tmp_version) VALUES (?, ?, ?, ?, ?, ?, 1, ?)");
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO sec_sections (sec_nr, sec_html, sec_style, sec_block, sec_title, sec_tmp_id, sec_tmp_version) VALUES (?, ?, ?, ?, ?, ?, ?)");
        }
        $stmt->execute([$nr, $html, $style, $block, $title, $tid, $version]);

        return $this->getLastInsertID();
    }

    /**
     * @param int $tid
     * @param int $templateVersion
     *
     * @return array
     * @throws Exception
     */
    public function findByTemplateAndVersion(int $tid, int $templateVersion): array
    {
        $stmt = $this->prepareAndExecute('SELECT * FROM sec_sections WHERE sec_tmp_id = ? AND sec_tmp_version = ?', [
            $tid,
            $templateVersion
        ]);

        return $this->fetchAll($stmt);
    }

    /**
     * @param int $tid
     * @param int $templateVersion
     *
     * @return array
     * @throws Exception
     */
    public function findByTemplateAndNotVersion(int $tid, int $templateVersion): array
    {
        $stmt = $this->prepareAndExecute('SELECT * FROM sec_sections WHERE sec_tmp_id = ? AND sec_tmp_version != ?', [
            $tid,
            $templateVersion
        ]);

        return $this->fetchAll($stmt);
    }

    /**
     * @param int $tid
     *
     * @return array
     * @throws Exception
     */
    public function findByTemplate(int $tid): array
    {
        $stmt = $this->prepareAndExecute('SELECT * FROM sec_sections WHERE sec_tmp_id = ?', [
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
    public function findByID(int $id): array
    {
        if (isset($this->sectionCache[$id])) {
            return $this->sectionCache[$id];
        }

        $stmt = $this->prepareAndExecute('SELECT * FROM sec_sections WHERE sec_id = ?', [$id]);
        $this->sectionCache[$id] = $this->fetch($stmt);

        return $this->sectionCache[$id];
    }

    /**
     * @param int    $id
     * @param string $screenshot
     *
     * @return int
     * @throws Exception
     */
    public function updateScreenshot(int $id, string $screenshot): int
    {
        unset($this->sectionCache[$id]);
        $stmt = $this->prepareAndExecute('UPDATE sec_sections SET sec_thumb = ? WHERE sec_id = ?', [
            $screenshot,
            $id
        ]);

        return $stmt->rowCount();
    }

    /**
     * @param int $tid
     *
     * @return int
     * @throws Exception
     */
    public function deleteByTemplate(int $tid): int
    {
        $sections = $this->findByTemplate($tid);
        $stmt = $this->prepareAndExecute('DELETE FROM sec_sections WHERE sec_tmp_id = ?', [$tid]);
        $this->sectionCache = [];

        $toRemove = [];
        foreach($sections as $section) {
            if (!empty($section['sec_thumb'])) {
                $toRemove[] = $section['sec_thumb'];
            }
        }
        if (!empty($toRemove)) {
            $this->cdn->batchRemoveByURL($toRemove);
        }

        return $stmt->rowCount();
    }

    /**
     * @param int $tid
     * @param int $version
     */
    public function updateVersionID(int $tid, int $version)
    {
        $query = $this->pdo->prepare("UPDATE sec_sections SET sec_tmp_version = ? WHERE sec_tmp_id = ? AND sec_tmp_version = '0'");
        $query->execute([$version, $tid]);
        $this->sectionCache = [];
    }

    /**
     * @param int $id
     * @param int $version
     *
     * @return void
     */
    public function updateTemplateVersion(int $id, int $version)
    {
        $query = $this->pdo->prepare("UPDATE sec_sections SET sec_tmp_version = ? WHERE sec_id = ?");
        $query->execute([$version, $id]);
        $this->sectionCache = [];
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
}
