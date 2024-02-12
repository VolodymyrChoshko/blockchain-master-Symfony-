<?php
namespace BlocksEdit\Database;

use BlocksEdit\Cache\CacheTrait;
use Exception;
use RuntimeException;

/**
 * Class CacheTagInvalidator
 */
class CacheTagInvalidator
{
    use CacheTrait;

    const MAX_DEPTH = 10;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EntityAccessor
     */
    protected $entityAccessor;

    /**
     * @var CacheTag[]
     */
    protected $tags = [];

    /**
     * @var array
     */
    protected $seen = [];

    /**
     * @var int
     */
    protected $depth = 0;

    /**
     * Constructor
     *
     * @param EntityManager  $em
     * @param EntityAccessor $entityAccessor
     */
    public function __construct(EntityManager $em, EntityAccessor $entityAccessor)
    {
        $this->em = $em;
        $this->entityAccessor = $entityAccessor;
    }

    /**
     * @param Repository   $repository
     * @param object|array $entity
     *
     * @return int
     * @throws Exception
     */
    public function deleteEntityCache(Repository $repository, $entity): int
    {
        $this->tags = [];
        $this->seen = [];
        $this->depth = 0;
        $this->deleteCache($repository, $entity);
        $this->cache->deleteByTags($this->tags);

        return count($this->tags);
    }

    /**
     * @param Repository   $repository
     * @param object|array $entity
     *
     * @return void
     * @throws Exception
     */
    protected function deleteCache(Repository $repository, $entity): void
    {
        $meta = $this->em->getMeta($repository);
        if (!$meta) {
            return;
        }

        if ($this->depth++ > self::MAX_DEPTH) {
            throw new RuntimeException(
                sprintf('Max cache delete depth %d reached.', $this->depth)
            );
        }

        $entityAccessor = new EntityAccessor($meta);
        foreach($meta->getCacheTags() as $cacheTag) {
            $column = $cacheTag->column;
            if (is_array($entity)) {
                $column = $entityAccessor->prefixColumnName($column);
                if (!isset($entity[$column])) {
                    throw new RuntimeException(
                        sprintf('Entity does not have column %s in repo %s.', $column, get_class($repository))
                    );
                }
                $idValue = $entity[$column];
            } else {
                $idValue = $entityAccessor->getValue($entity, $column);
            }

            if (is_object($idValue) && method_exists($idValue, 'getId')) {
                $idValue = $idValue->getId();
            }
            $seenKey = sprintf('%s:%d', $cacheTag->value, $idValue);
            if (in_array($seenKey, $this->seen)) {
                continue;
            }
            $this->seen[] = $seenKey;

            if ($cacheTag->value) {
                $className    = $cacheTag->value;
                $this->tags[] = new $className($idValue);
            }

            if ($cacheTag->mergeTags) {
                $mergeRepoMeta = $this->em->getMeta($cacheTag->mergeTags);
                $mergeRepo     = $this->em->getRepository($mergeRepoMeta->getEntityClass());
                $primaryKey    = $mergeRepoMeta->getPrimaryKeyColumn();
                if (!$primaryKey) {
                    throw new RuntimeException(
                        sprintf('Could not determine primary key for entity %s.', $mergeRepoMeta->getEntityClass())
                    );
                }

                $mergeEntity = $mergeRepo->findOne([
                    $primaryKey => $idValue
                ]);
                if ($mergeEntity) {
                    $this->deleteCache($mergeRepo, $mergeEntity);
                }
            }
        }
    }
}
