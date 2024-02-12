<?php
namespace BlocksEdit\Database;

use BlocksEdit\IO\FilesTrait;
use BlocksEdit\IO\PathsTrait;
use BlocksEdit\Cache\CacheTrait;
use BlocksEdit\Config\Config;
use BlocksEdit\Logging\LoggerTrait;
use BlocksEdit\Database\Exception\DatabaseException;
use Exception;
use PDOStatement;
use PDO;
use RuntimeException;

/**
 * Class Repository
 */
abstract class Repository
{
    use LoggerTrait;
    use FilesTrait;
    use PathsTrait;
    use CacheTrait;

    const MAX_JOIN_DEPTH = 10;
    const MAX_ONE_TO_MANY_DEPTH = 3;

    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var EntityMeta|null
     */
    protected $meta;

    /**
     * @var EntityAccessor
     */
    protected $entityAccessor;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var CacheTagInvalidator
     */
    protected $cacheTagInvalidator;

    /**
     * @var int
     */
    private $joinDepth = 0;

    /**
     * @var array
     */
    private $joinSeen = [];

    /**
     * @var int
     */
    private $oneToManyDepth = 0;

    /**
     * @var array
     */
    private $tracking = [];

    /**
     * @var int
     */
    private $transactionCounter = 0;

    /**
     * Constructor
     *
     * @param PDO                 $pdo
     * @param Config              $config
     * @param EntityManager       $em
     * @param CacheTagInvalidator $cacheTagInvalidator
     *
     * @throws Exception
     */
    public function __construct(
        PDO $pdo,
        Config $config,
        EntityManager $em,
        CacheTagInvalidator $cacheTagInvalidator
    )
    {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->em = $em;
        $this->meta = $em->getMeta(get_called_class());
        $this->cacheTagInvalidator = $cacheTagInvalidator;
        $this->entityAccessor = new EntityAccessor($this->meta);
        $this->queryBuilder = new QueryBuilder($em, $this->meta, $this->entityAccessor);
    }

    /**
     * @param object $entity
     *
     * @return int
     * @throws Exception
     */
    public function update(object $entity): int
    {
        $column = $this->meta->getPrimaryKeyColumn();
        $values = $this->entityAccessor->getEntityValues($entity);
        $update = (new Update($values))
            ->setWheres([
                "`$column` = ?" => $values[$column]
            ])
            ->setIgnoreColumns([$column])
            ->setLimit(1);
        $query = $this->queryBuilder->update($update);

        try {
            // $this->beginTransaction();
            $stmt = $this->prepareAndExecute($query->getSql(), $query->getParams());

            foreach ($this->meta->getOneToMany() as $prop => $oneToMany) {
                $newTracking = [];
                $tracking    = $this->getTracked($prop, $entity);
                $targetRepo  = $this->em->getRepository($oneToMany->value);
                $primaryKey  = $targetRepo->meta->getPrimaryKeyPropName();
                $entities    = $this->entityAccessor->getValue($entity, $prop);

                foreach ($entities as $row) {
                    $id = $this->entityAccessor->getValue($row, $primaryKey);
                    $targetRepo->entityAccessor->setValue($row, $oneToMany->mappedBy, $entity);
                    if (!$id) {
                        $targetRepo->insert($row);
                    } else {
                        $targetRepo->update($row);
                    }

                    $newTracking[] = $row;
                    $index         = array_search($row, $tracking, true);
                    if ($index !== false) {
                        unset($tracking[$index]);
                    }
                }

                foreach ($tracking as $e) {
                    $targetRepo->delete($e);
                }
                $this->trackEntities($oneToMany->mappedBy, $entity, $newTracking, true);
            }

            // $this->commit();
            $this->deleteEntityCache($entity);

            return $stmt->rowCount();
        } catch (Exception $e) {
            // $this->rollback();
            $this->logger->error(
                $e->getMessage(),
                ['sql' => $query->getSql(), 'params' => $query->getParams()]
            );
            throw $e;
        }
    }

    /**
     * @param object $entity
     *
     * @throws Exception
     */
    public function insert(object $entity)
    {
        $column   = $this->meta->getPrimaryKeyColumn();
        $property = $this->meta->getPrimaryKeyPropName();
        $values   = $this->entityAccessor->getEntityValues($entity);
        unset($values[$column]);
        $query = $this->queryBuilder->insert(new Insert($values));

        try {
            // $this->beginTransaction();
            $this->prepareAndExecute($query->getSql(), $query->getParams());
            $id = $this->getLastInsertID();

            if ($id) {
                $this->entityAccessor->setValue($entity, $property, $id);

                foreach ($this->meta->getOneToMany() as $prop => $oneToMany) {
                    $targetRepo = $this->em->getRepository($oneToMany->value);
                    if (!$targetRepo) {
                        throw new DatabaseException('Did not find repository for ' . $oneToMany->value);
                    }
                    $primaryKey = $targetRepo->meta->getPrimaryKeyPropName();
                    if (!$primaryKey) {
                        throw new DatabaseException('Did not find primary key for ' . $oneToMany->value);
                    }

                    $entities = $this->entityAccessor->getValue($entity, $prop);
                    foreach ($entities as $e) {
                        $id = $this->entityAccessor->getValue($e, $primaryKey);
                        if (!$id) {
                            $targetRepo->insert($e);
                        } else {
                            $targetRepo->update($e);
                        }
                        $this->entityAccessor->setValue($e, $oneToMany->mappedBy, $entity);
                    }
                    $this->trackEntities($oneToMany->mappedBy, $entity, $entities);
                }
            }

            $this->deleteEntityCache($entity);
            // $this->commit();
        } catch (Exception $e) {
            // $this->rollback();
            $this->logger->error(
                $e->getMessage(),
                ['sql' => $query->getSql(), 'params' => $query->getParams()]
            );
            throw $e;
        }
    }

    /**
     * @param object $entity
     *
     * @return int
     * @throws Exception
     */
    public function delete(object $entity): int
    {
        $column   = $this->meta->getPrimaryKeyColumn();
        $property = $this->meta->getPrimaryKeyPropName();
        $id       = $this->entityAccessor->getValue($entity, $property);
        $sql      = sprintf(
            'DELETE FROM `%s` WHERE `%s` = ? LIMIT 1',
            $this->meta->getTableName(),
            $column
        );

        try {
            // $this->beginTransaction();

            foreach ($this->meta->getJoins() as $p => $join) {
                if ($join->getOnDelete() === 'CASCADE') {
                    $value = $this->entityAccessor->getValue($entity, $p);
                    $this->em->getRepository($join->getEntity())->delete($value);
                }
            }

            $stmt = $this->prepareAndExecute($sql, [$id]);
            $rows = $stmt->rowCount();
            // $this->commit();
            $this->deleteEntityCache($entity);

            if ($rows) {
                $this->entityAccessor->setValue($entity, $property, 0);
            }

            $eid = spl_object_id($entity);
            if (isset($this->tracking[$eid])) {
                unset($this->tracking[$eid]);
            }

            return $rows;
        } catch (Exception $e) {
            // $this->rollback();
            $this->logger->error(
                $e->getMessage(),
                ['sql' => $sql, 'params' => [$id]]
            );
            throw $e;
        }
    }

    /**
     * @param array|Select $wheres
     *
     * @return mixed
     * @throws Exception
     */
    public function findOne($wheres = [])
    {
        $entities = $this->find($wheres, 1);
        if ($entities) {
            return $entities[0];
        }

        return null;
    }

    /**
     * @param array|Select $wheres
     * @param int|null $limit
     * @param int|null $offset
     * @param array    $orderBy
     * @param string   $select
     *
     * @return array
     * @throws Exception
     */
    public function find(
        $wheres = [],
        ?int $limit = null,
        ?int $offset = null,
        array $orderBy = [],
        string $select = '*'
    ): array
    {
        if ($wheres instanceof Select) {
            $select = $wheres;
        } else {
            $select = (new Select($select, $wheres))
                ->setOrderBy($orderBy)
                ->setLimit($limit)
                ->setOffset($offset);
        }

        try {
            $entities  = $this->findExec($select);
            $oneToMany = $this->meta->getOneToMany();
            if ($oneToMany && $this->oneToManyDepth++ < self::MAX_ONE_TO_MANY_DEPTH) {
                foreach ($oneToMany as $otm) {
                    $sourceRepo = $this->em->getRepository($otm->value);
                    if (!$sourceRepo) {
                        throw new RuntimeException('Did not find repository for OneToMany entity ' . $otm->value);
                    }

                    $manyToOne = $sourceRepo->meta->getManyToOne();
                    foreach ($entities as $entity) {
                        $select = (new Select('*'))
                            ->setExcludedColumns([$otm->mappedBy])
                            ->setWheres([
                                $otm->mappedBy => $entity
                            ]);
                        $sourceRows = $sourceRepo->find($select);
                        $targetProp = $manyToOne[$otm->mappedBy]->inversedBy;
                        $this->entityAccessor->setValue($entity, $targetProp, $sourceRows);
                        $this->trackEntities($targetProp, $entity, $sourceRows);
                    }
                }
            }

            return $entities;
        } catch (Exception $e) {
            $query = $this->queryBuilder->select($select);
            $this->logger->error(
                $e->getMessage(),
                ['sql' => $query->getSql(), 'params' => $query->getParams()]
            );
            throw $e;
        } finally {
            $this->joinDepth      = 0;
            $this->joinSeen       = [];
            $this->oneToManyDepth = 0;
        }
    }

    /**
     * @param Select $select
     *
     * @return array
     * @throws DatabaseException
     * @throws Exception
     */
    private function findExec(Select $select): array
    {
        if ($this->joinDepth++ > self::MAX_JOIN_DEPTH) {
            throw new DatabaseException(
                sprintf('Max join depth %d exceeded in %s.', self::MAX_JOIN_DEPTH, get_called_class())
            );
        }

        $query = $this->queryBuilder->select($select);
        $stmt  = $this->prepareAndExecute($query->getSql(), $query->getParams());
        $rows  = $this->fetchAll($stmt);

        $oneToMany = $this->meta->getOneToMany();
        $joins     = $this->meta->getJoins();
        foreach($rows as $i => &$row) {
            foreach($row as $column => &$value) {
                $prop = $this->meta->getPropertyName($column);
                if (empty($joins[$prop]) || isset($oneToMany[$prop])) {
                    continue;
                }
                if (empty($value)) {
                    if ($value === null && $this->meta->getColumn($prop)->nullable) {
                        continue;
                    }

                    unset($rows[$i]);
                    continue;
                }

                $col      = $joins[$prop]->getReferences();
                $entity   = $joins[$prop]->getEntity();
                $valueKey = $value;
                $seenKey  = sprintf('%s::%s', $entity, $col);
                if (!isset($this->joinSeen[$seenKey])) {
                    $this->joinSeen[$seenKey] = [];
                }

                if (isset($this->joinSeen[$seenKey][$valueKey])) {
                    $value = $this->joinSeen[$seenKey][$valueKey];
                    if (!$value) {
                        unset($rows[$i]);
                    }
                } else {
                    $value = $this->em->getRepository($entity)->findOne([
                        $col => $value
                    ]);
                    if (!$value) {
                        unset($rows[$i]);
                    }
                    $this->joinSeen[$seenKey][$valueKey] = $value;
                }
            }
        }

        return $this->entityAccessor->hydrateRows($rows);
    }

    /**
     * @return int
     */
    public function getLastInsertID(): int
    {
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * @return bool
     */
    public function beginTransaction(): bool
    {
        if ($this->transactionCounter === 0) {
            $this->pdo->beginTransaction();
            $this->pdo->query('SET autocommit=0');
        }
        $this->transactionCounter++;

        return true;
    }

    /**
     * @return bool
     */
    public function commit(): bool
    {
        if (--$this->transactionCounter === 0) {
            $this->pdo->commit();
            $this->pdo->query('SET autocommit=1');
        }

        return true;
    }

    /**
     * @return bool
     */
    public function rollback(): bool
    {
        if ($this->inTransaction()) {
            $this->pdo->rollBack();
            $this->pdo->query('SET autocommit=1');
        }
        $this->transactionCounter = 0;

        return true;
    }

    /**
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * @param string $sql
     * @param array  $params
     *
     * @return PDOStatement
     * @throws Exception
     */
    protected function prepareAndExecute(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->throwIfError($stmt);
        // $this->logger->debug($sql, $params);

        return $stmt;
    }

    /**
     * @param PDOStatement $stmt
     *
     * @return array
     * @throws Exception
     */
    protected function fetch(PDOStatement $stmt): array
    {
        $this->throwIfError($stmt);
        if ($stmt->rowCount() === 0) {
            return [];
        }

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param PDOStatement $stmt
     *
     * @return array
     * @throws Exception
     */
    protected function fetchAll(PDOStatement $stmt): array
    {
        $this->throwIfError($stmt);
        if ($stmt->rowCount() === 0) {
            return [];
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param object|array $entity
     *
     * @return void
     * @throws Exception
     */
    protected function deleteEntityCache($entity): void
    {
        $this->cacheTagInvalidator->deleteEntityCache($this, $entity);
    }

    /**
     * @param string          $prop
     * @param object          $parent
     * @param object[]|object $children
     * @param bool            $reset
     *
     * @return void
     */
    protected function trackEntities(string $prop, object $parent, $children, bool $reset = false)
    {
        $eid = spl_object_id($parent);
        if (!isset($this->tracking[$eid])) {
            $this->tracking[$eid] = [];
        }
        if (!isset($this->tracking[$eid][$prop])) {
            $this->tracking[$eid][$prop] = [];
        }
        if (!is_array($children)) {
            $children = [$children];
        }
        if ($reset) {
            $this->tracking[$eid][$prop] = $children;
        } else {
            foreach($children as $c) {
                if (!in_array($c, $this->tracking[$eid][$prop], true)) {
                    $this->tracking[$eid][$prop][] = $c;
                }
            }
        }
    }

    /**
     * @param string $prop
     * @param object $entity
     *
     * @return array
     */
    protected function getTracked(string $prop, object $entity): array
    {
        $eid = spl_object_id($entity);
        if (!isset($this->tracking[$eid]) || !isset($this->tracking[$eid][$prop])) {
            return [];
        }

        return $this->tracking[$eid][$prop];
    }

    /**
     * @param PDOStatement $stmt
     *
     * @throws Exception
     */
    private function throwIfError(PDOStatement $stmt)
    {
        $errorInfo = $stmt->errorInfo();
        if (isset($errorInfo[2])) {
            throw new DatabaseException($errorInfo[2], (int)$errorInfo[0]);
        }
    }
}
