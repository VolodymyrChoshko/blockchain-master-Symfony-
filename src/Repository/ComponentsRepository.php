<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\Media\CDNInterface;
use BlocksEdit\System\Required;
use Exception;

/**
 * Class ComponentsRepository
 */
class ComponentsRepository extends Repository
{
    /**
     * @var array
     */
    protected $componentCache = [];

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
        $this->componentCache = [];
        if ($mobile) {
            $stmt = $this->pdo->prepare("INSERT INTO com_components (com_nr, com_html, com_style, com_block, com_title, com_tmp_id, com_mobile, com_tmp_version) VALUES (?, ?, ?, ?, ?, ?, 1, ?)");
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO com_components (com_nr, com_html, com_style, com_block, com_title, com_tmp_id, com_tmp_version) VALUES (?, ?, ?, ?, ?, ?, ?)");
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
        $stmt = $this->prepareAndExecute('SELECT * FROM com_components WHERE com_tmp_id = ? AND com_tmp_version = ?', [
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
        $stmt = $this->prepareAndExecute('SELECT * FROM com_components WHERE com_tmp_id = ? AND com_tmp_version != ?', [
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
        $stmt = $this->prepareAndExecute('SELECT * FROM com_components WHERE com_tmp_id = ?', [
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
        if (isset($this->componentCache[$id])) {
            return $this->componentCache[$id];
        }

        $stmt = $this->prepareAndExecute('SELECT * FROM com_components WHERE com_id = ?', [$id]);
        $this->componentCache[$id] = $this->fetch($stmt);

        return $this->componentCache[$id];
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
        unset($this->componentCache[$id]);
        $stmt = $this->prepareAndExecute('UPDATE com_components SET com_thumb = ? WHERE com_id = ?', [
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
        $components = $this->findByTemplate($tid);
        $stmt = $this->prepareAndExecute('DELETE FROM com_components WHERE com_tmp_id = ?', [$tid]);
        $this->componentCache = [];

        $toRemove = [];
        foreach($components as $component) {
            if (!empty($component['com_thumb'])) {
                $toRemove[] = $component['com_thumb'];
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
        $query = $this->pdo->prepare("UPDATE com_components SET com_tmp_version = ? WHERE com_tmp_id = ? AND com_tmp_version = '0'");
        $query->execute([$version, $tid]);
        $this->componentCache = [];
    }

    /**
     * @param int $id
     * @param int $version
     *
     * @return void
     */
    public function updateTemplateVersion(int $id, int $version)
    {
        $query = $this->pdo->prepare("UPDATE com_components SET com_tmp_version = ? WHERE com_id = ?");
        $query->execute([$version, $id]);
        $this->componentCache = [];
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
