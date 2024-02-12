<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\System\Required;
use Exception;
use Tag\FolderTag;
use Tag\TemplateTag;

/**
 * Class FoldersRepository
 */
class FoldersRepository extends Repository
{
    /**
     * @param int $fid
     *
     * @return array
     */
    public function fetchById(int $fid): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM fld_folders WHERE fld_id = ? LIMIT 1"
        );
        $stmt->execute([$fid]);

        return $this->fetch($stmt);
    }

    /**
     * @param int $tid
     *
     * @return array
     */
    public function fetchByTemplateId(int $tid): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM fld_folders
            WHERE fld_tmp_id = ?
            ORDER BY fld_parent_id
        ");
        $stmt->execute([$tid]);

        return $this->fetchAll($stmt);
    }

    /**
     * @param int $fid
     *
     * @return array
     */
    public function fetchSubFolders(int $fid): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT *
                FROM (SELECT * FROM fld_folders ORDER BY fld_parent_id, fld_id) folders_sorted,
                (SELECT @pv := ?) initialisation
                WHERE find_in_set(fld_parent_id, @pv)
                AND length(@pv := concat(@pv, ',', fld_id))"
        );

        $stmt->execute([$fid]);

        return $this->fetchAll($stmt);
    }

    /**
     * @param int $fid
     *
     * @return array
     */
    public function fetchUser(int $fid): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.*
                FROM fld_folders f
                LEFT JOIN tmp_templates t ON t.tmp_id = f.fld_tmp_id
                LEFT JOIN usr_users u ON u.usr_id = t.tmp_usr_id
                WHERE fld_id = ?
                LIMIT 1"
        );
        $stmt->execute([$fid]);

        return $this->fetch($stmt);
    }

    /**
     * @param int $fid
     *
     * @return array
     */
    public function fetchTemplate(int $fid): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT t.*
                FROM fld_folders f
                LEFT JOIN tmp_templates t ON t.tmp_id = f.fld_tmp_id
                WHERE fld_id = ?
                LIMIT 1"
        );
        $stmt->execute([$fid]);

        return $this->fetch($stmt);
    }

    /**
     * @param int $uid
     * @param int $fid
     *
     * @return bool
     */
    public function isOwner(int $uid, int $fid): bool
    {
        $user = $this->fetchUser($fid);
        if ($user && $user['usr_id'] == $uid) {
            return true;
        }

        return false;
    }

    /**
     * @param int $uid
     * @param int $fid
     *
     * @return bool
     */
    public function hasAccess(int $uid, int $fid): bool
    {
        $template = $this->fetchTemplate($fid);
        if ($template) {
            $query = $this->pdo->prepare("SELECT * FROM acc_access WHERE acc_tmp_id = ? AND acc_usr_id = ?");
            $query->execute([$template['tmp_id'], $uid]);
            if ($query->rowCount()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int      $tid
     * @param string   $name
     * @param int|null $parentId
     *
     * @return int
     */
    public function create(int $tid, string $name, ?int $parentId = null): int
    {
        $parentId = $parentId ?: null;
        $stmt = $this->pdo->prepare(
            "INSERT INTO
            fld_folders (fld_tmp_id, fld_name, fld_parent_id, fld_created_at, fld_updated_at)
            VALUES (?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([$tid, $name, $parentId]);
        $this->cache->deleteByTag(new TemplateTag($tid));
        if ($parentId) {
            $this->cache->deleteByTag(new FolderTag($parentId));
        }

        return $this->getLastInsertID();
    }

    /**
     * @param int $fid
     *
     * @return bool
     * @throws Exception
     */
    public function remove(int $fid): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM fld_folders WHERE fld_id = ? LIMIT 1"
        );
        foreach($this->fetchSubFolders($fid) as $folder) {
            $this->emailRepository->deleteByFolder($folder['fld_id']);
            $stmt->execute([$folder['fld_id']]);
            $this->cache->deleteByTag(new FolderTag($folder['fld_id']));
        }

        $this->emailRepository->deleteByFolder($fid);
        $stmt = $this->pdo->prepare(
            "DELETE FROM fld_folders WHERE fld_id = ? LIMIT 1"
        );
        $stmt->execute([$fid]);
        $this->cache->deleteByTag(new FolderTag($fid));

        return $stmt->rowCount() > 0;
    }

    /**
     * @param int $fid
     * @param string $name
     *
     * @return bool
     */
    public function rename(int $fid, string $name): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE fld_folders SET fld_name = ? WHERE fld_id = ? LIMIT 1"
        );
        $stmt->execute([$name, $fid]);
        $this->cache->deleteByTag(new FolderTag($fid));

        return $stmt->rowCount() > 0;
    }

    /**
     * @param int $fid
     * @param int $cfid
     *
     * @return bool
     * @throws Exception
     */
    public function moveFolder(int $fid, int $cfid): bool
    {
        if (!$cfid) {
            $stmt = $this->pdo->prepare(
                "UPDATE fld_folders SET fld_parent_id = ? WHERE fld_id = ? LIMIT 1"
            );
            $stmt->execute([null, $fid]);
            $this->cache->deleteByTag(new FolderTag($fid));

            return $stmt->rowCount() > 0;
        }

        $subIds = [];
        foreach($this->fetchSubFolders($cfid) as $subFolder) {
            $subIds[] = $subFolder['fld_id'];
        }
        if (in_array($fid, $subIds)) {
            throw new Exception('Cannot move parent folder into child folder.');
        }

        $stmt = $this->pdo->prepare(
            "UPDATE fld_folders SET fld_parent_id = ? WHERE fld_id = ? LIMIT 1"
        );
        $stmt->execute([$fid, $cfid]);
        $this->cache->deleteByTags([
            new FolderTag($fid),
            new FolderTag($cfid)
        ]);

        return $stmt->rowCount() > 0;
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
}
