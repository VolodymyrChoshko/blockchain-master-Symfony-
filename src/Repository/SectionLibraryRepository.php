<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\Media\CDNInterface;
use BlocksEdit\System\Required;
use Entity\PinGroup;
use Entity\SectionLibrary;
use Exception;

/**
 * Class SectionLibraryRepository
 */
class SectionLibraryRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return SectionLibrary|null
     * @throws Exception
     */
    public function findByID(int $id): ?SectionLibrary
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param int $tid
     *
     * @return SectionLibrary[]
     * @throws Exception
     */
    public function findByTemplate(int $tid): array
    {
        return $this->find([
            'tmpId' => $tid
        ]);
    }

    /**
     * @param PinGroup $pinGroup
     *
     * @return SectionLibrary[]
     * @throws Exception
     */
    public function findByPinGroup(PinGroup $pinGroup): array
    {
        return $this->find([
            'pinGroup' => $pinGroup
        ]);
    }

    /**
     * @param int $id
     *
     * @return SectionLibrary|null
     * @throws Exception
     */
    public function findByDesktopID(int $id): ?SectionLibrary
    {
        return $this->findOne([
            'desktopId' => $id
        ]);
    }

    /**
     * @param object|SectionLibrary $entity
     *
     * @return int
     * @throws Exception
     */
    public function delete(object $entity): int
    {
        $thumb = $entity->getThumbnail();
        if (substr($thumb, 0, 4) === 'http') {
            try {
                $this->cdn->removeByURL($thumb);
            } catch (Exception $e) {}
        }

        return parent::delete($entity);
    }

    /**
     * @param int $tid
     *
     * @return int
     * @throws Exception
     */
    public function deleteByTemplate(int $tid): int
    {
        $libraries = $this->findByTemplate($tid);
        foreach($libraries as $library) {
            $this->delete($library);
        }

        return count($libraries);
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
