<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\System\Required;
use Entity\Access;
use Entity\Template;
use Exception;
use Tag\TemplateTag;

/**
 * Class AccessRepository
 */
class AccessRepository extends Repository
{
    /**
     * @param int $oid
     *
     * @return int
     * @throws Exception
     */
    public function findUserCount(int $oid): int
    {
        $stmt = $this->prepareAndExecute(
            "SELECT a.*
            FROM tmp_templates t
            LEFT JOIN acc_access a ON a.acc_tmp_id = t.tmp_id
            WHERE t.tmp_org_id = ?",
            [$oid]
        );
        $rows = $this->fetchAll($stmt);

        $count = 0;
        $found = [];
        foreach($rows as $row) {
            if (!in_array($row['acc_usr_id'], $found)) {
                $found[] = $row['acc_usr_id'];
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param Template $template
     *
     * @return Access[]
     * @throws Exception
     */
    public function findByTemplate(Template $template): array
    {
        return $this->find([
            'template' => $template
        ]);
    }

    /**
     * @param int $tid
     *
     * @return array
     * @throws Exception
     */
    public function findUsersByTemplate(int $tid): array
    {
        $stmt = $this->pdo->prepare('
            SELECT u.* FROM acc_access a LEFT JOIN usr_users u ON(a.acc_usr_id = u.usr_id) WHERE acc_tmp_id = ?
        ');
        $stmt->execute([$tid]);

        return $this->fetchAll($stmt);
    }

    /**
     * @param int $tid
     *
     * @return array
     * @throws Exception
     */
    public function findCollaboratorsByTemplate(int $tid): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.acc_responded, u.* FROM acc_access a LEFT JOIN usr_users u ON a.acc_usr_id = u.usr_id WHERE acc_tmp_id = ?'
        );
        $stmt->execute([$tid]);

        $collaborators = [];
        foreach($this->fetchAll($stmt) as $row) {
            $collaborators[$row['usr_id']] = $row;
        }

        return $collaborators;
    }

    /**
     * @param int $uid
     * @param int $tid
     *
     * @return array
     * @throws Exception
     */
    public function findByUserAndTemplate(int $uid, int $tid): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM acc_access WHERE acc_tmp_id = ? AND acc_usr_id = ? LIMIT 1");
        $stmt->execute([$tid, $uid]);

        return $this->fetch($stmt);
    }

    /**
     * @param int $uid
     * @param int $responded
     *
     * @return array
     * @throws Exception
     */
    public function findByUserAndResponded(int $uid, int $responded): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT acc_tmp_id FROM acc_access WHERE acc_usr_id = ? AND acc_responded = ?"
        );
        $stmt->execute([$uid, $responded]);

        return $this->fetchAll($stmt);
    }

    /**
     * @param int $uid
     * @param int $tid
     * @param int $responded
     *
     * @return int
     */
    public function addAccess(int $uid, int $tid, int $responded = 0): int
    {
        if (!$this->templatesRepository->hasAccess($uid, $tid)) {
            $stmt = $this->pdo->prepare('INSERT INTO acc_access (acc_usr_id, acc_tmp_id, acc_responded) VALUES (?, ?, ?)');
            $stmt->execute([$uid, $tid, $responded]);

            return $stmt->rowCount();
        }

        return 0;
    }

    /**
     * @param int $uid
     * @param int $tid
     * @param int $rid
     * @param int $iid
     * @param int $oid
     *
     * @return bool
     * @throws Exception
     */
    public function removeAccess(int $uid, int $tid, int $rid, int $iid = 0, int $oid = 0): bool
    {
        $template = $this->templatesRepository->findByID($tid);
        $isAuthor = ($uid == $rid) // You can remove yourself from a template
            || $this->templatesRepository->isAuthor($uid, $tid)
            || $this->organizationAccessRepository->isOwner($uid, $template['tmp_org_id'])
            || $this->organizationAccessRepository->isAdmin($uid, $template['tmp_org_id'])
            || $this->organizationAccessRepository->isEditor($uid, $template['tmp_org_id']);

        if ($isAuthor && $this->templatesRepository->hasAccess($uid, $tid)) {
            if ($rid && $this->templatesRepository->hasAccess($rid, $tid)) {
                $stmt = $this->pdo->prepare('DELETE FROM acc_access WHERE acc_tmp_id = ? AND acc_usr_id = ?');
                $stmt->execute([$tid, $rid]);

                // Remove org access if the user no longer has access to any templates in the org.
                if ($oid !== 0) {
                    $templates = $this->templatesRepository->findByOrgAndUser($oid, $rid);
                    if (empty($templates)) {
                        $this->organizationAccessRepository->deleteByUser($rid, $oid);
                    }
                }
            } else if ($iid) {
                $invite = $this->invitationsRepository->findByID($iid);
                if ($invite) {
                    $this->invitationsRepository->delete($invite);
                }
            }

            $this->cache->deleteByTag(new TemplateTag($tid));

            return true;
        }

        return false;
    }

    /**
     * @param int $tid
     * @param int $uid
     * @param int $oid
     *
     * @return int
     * @throws Exception
     */
    public function adminRemoveAccess(int $tid, int $uid, int $oid): int
    {
        $stmt = $this->prepareAndExecute('DELETE FROM acc_access WHERE acc_tmp_id = ? AND acc_usr_id = ?', [
            $tid,
            $uid
        ]);

        // Remove org access if the user no longer has access to any templates in the org.
        if ($oid !== 0) {
            $templates = $this->templatesRepository->findByOrgAndUser($oid, $uid);
            if (empty($templates)) {
                $this->organizationAccessRepository->deleteByUser($uid, $oid);
            }
        }

        return $stmt->rowCount();
    }

    /**
     * @param int $tid
     * @param int $uid
     *
     * @return int
     */
    public function updateResponded(int $tid, int $uid): int
    {
        $stmt = $this->pdo->prepare('UPDATE acc_access SET acc_responded = 1 WHERE acc_tmp_id = ? AND acc_usr_id = ?');
        $stmt->execute([$tid, $uid]);

        return $stmt->rowCount();
    }

    /**
     * @var TemplatesRepository
     */
    protected $templatesRepository;

    /**
     * @var OrganizationsRepository
     */
    protected $organizationsRepository;

    /**
     * @var InvitationsRepository
     */
    protected $invitationsRepository;

    /**
     * @Required()
     * @param InvitationsRepository $invitationsRepository
     */
    public function setInvitationsRepository(InvitationsRepository $invitationsRepository)
    {
        $this->invitationsRepository = $invitationsRepository;
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
     * @param TemplatesRepository $templatesRepository
     */
    public function setTemplatesRepository(TemplatesRepository $templatesRepository)
    {
        $this->templatesRepository = $templatesRepository;
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
