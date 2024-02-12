<?php
namespace BlocksEdit\Database;

use DateTime;
use Exception;
use RuntimeException;

/**
 * Class QueryBuilder
 */
class QueryBuilder
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EntityAccessor
     */
    protected $entityAccessor;

    /**
     * @var EntityMeta
     */
    protected $entityMeta;

    /**
     * Constructor
     *
     * @param EntityManager  $em
     * @param EntityMeta     $entityMeta
     * @param EntityAccessor $entityAccessor
     */
    public function __construct(EntityManager $em, EntityMeta $entityMeta, EntityAccessor $entityAccessor)
    {
        $this->em             = $em;
        $this->entityMeta     = $entityMeta;
        $this->entityAccessor = $entityAccessor;
    }

    /**
     * @param Select $select
     *
     * @return Query
     * @throws Exception
     */
    public function select(Select $select): Query
    {
        $excludedColumns = [];
        foreach($select->getExcludedColumns() as $ec) {
            $excludedColumns[] = $this->entityAccessor->prefixColumnName($ec);
        }
        $params = [];
        $wheres = $select->getWheres();

        if ($wheres) {
            $columns = [];
            foreach ($wheres as $key => $value) {
                if ($value instanceof Where) {
                    $column    = $this->entityAccessor->prefixColumnName($value->getColumn());
                    $condition = $value->getCondition();
                    $raw       = $value->isRaw();
                    $value     = $value->getValue();
                    if ($value instanceof WhereOr) {
                        $values = $value->getValues();
                        $parts  = ['('];
                        foreach($values as $v) {
                            if (is_object($v) && method_exists($v, 'getId')) {
                                $v = $v->getId();
                            } else if ($v instanceof DateTime) {
                                $v = $v->format('Y-m-d H:i:s');
                            }
                            $parts[]  = "`$column`";
                            $parts[]  = $condition;
                            $parts[]  = '?';
                            $parts[]  = 'OR';
                            $params[] = $v;
                        }
                        array_pop($parts);
                        $parts[]   = ')';
                        $columns[] = join(' ', $parts);
                    } else {
                        if (is_object($value) && method_exists($value, 'getId')) {
                            $value = $value->getId();
                        } else if ($value instanceof DateTime) {
                            $value = $value->format('Y-m-d H:i:s');
                        }
                        if ($raw) {
                            $columns[] = sprintf('%s %s %s', $this->entityAccessor->prefixColumnName($column), $condition, $value);
                        } else {
                            $params[]  = $value;
                            $columns[] = sprintf('%s %s ?', $this->entityAccessor->prefixColumnName($column), $condition);
                        }
                    }
                } else {
                    if (is_object($value) && method_exists($value, 'getId')) {
                        $value = $value->getId();
                    } else if ($value instanceof DateTime) {
                        $value = $value->format('Y-m-d H:i:s');
                    }
                    $columnName = $this->entityAccessor->prefixColumnName($key);
                    $params[]   = $value;
                    $columns[]  = "$columnName = ?";
                }
            }

            $sql = sprintf(
                'SELECT %s FROM `%s` WHERE %s',
                $this->getSelectColumns($select, $excludedColumns),
                $this->entityMeta->getTableName(),
                join(' AND ', $columns)
            );
        } else {
            $sql = sprintf(
                'SELECT %s FROM `%s`',
                $this->getSelectColumns($select, $excludedColumns),
                $this->entityMeta->getTableName()
            );
        }

        $orderBy = $select->getOrderBy();
        if ($orderBy) {
            $parts = [];
            foreach($orderBy as $key => $value) {
                $parts[] = $this->entityAccessor->prefixColumnName($key) . ' ' . $value;
            }
            $sql = sprintf('%s ORDER BY %s', $sql, join(' ', $parts));
        }

        $limit  = $select->getLimit();
        $offset = $select->getOffset();
        if ($limit && $offset) {
            $sql = sprintf('%s LIMIT %d, %d', $sql, $offset, $limit);
        } else if ($limit) {
            $sql = sprintf('%s LIMIT %d', $sql, $limit);
        }

        return new Query($sql, $params);
    }

    /**
     * @param Update $update
     *
     * @return Query
     * @throws Exception
     */
    public function update(Update $update): Query
    {
        $params  = [];
        $columns = [];
        $ignore  = $update->getIgnoreColumns();
        foreach($update->getValues() as $key => $value) {
            if ((is_scalar($value) || is_null($value)) && !in_array($key, $ignore)) {
                $params[]  = $value;
                $columns[] = sprintf('`%s` = ?', $key);
            } else {
                list($value, $key) = $this->getMappedParams($key, $value);
                if ($value !== null) {
                    $params[]   = $value;
                    $columns[]  = sprintf('`%s` = ?', $key);
                }
            }
        }

        $whereCond = [];
        foreach($update->getWheres() as $key => $value) {
            $params[]    = $value;
            $whereCond[] = $key;
        }

        $setStatement   = join(', ', $columns);
        $whereStatement = join(' AND ', $whereCond);

        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE %s',
            $this->entityMeta->getTableName(),
            $setStatement,
            $whereStatement
        );
        if ($update->getLimit() !== null) {
            $sql .= ' LIMIT ' . $update->getLimit();
        }

        return new Query($sql, $params);
    }

    /**
     * @param Insert $insert
     *
     * @return Query
     * @throws Exception
     */
    public function insert(Insert $insert): Query
    {
        $params  = [];
        $columns = [];
        $holders = [];
        foreach($insert->getValues() as $key => $value) {
            if (is_scalar($value)) {
                $key       = $this->entityAccessor->prefixColumnName($key);
                $params[]  = $value;
                $columns[] = "`$key`";
                $holders[] = '?';
            } else {
                list($value, $key) = $this->getMappedParams($key, $value);
                if ($value !== null) {
                    $params[]   = $value;
                    $columns[]  = "`$key`";
                    $holders[]  = '?';
                }
            }
        }

        $sql  = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $this->entityMeta->getTableName(),
            join(', ', $columns),
            join(', ', $holders)
        );

        return new Query($sql, $params);
    }

    /**
     * @param Select $select
     * @param array  $excludedColumns
     *
     * @return string
     * @throws Exception
     */
    protected function getSelectColumns(Select $select, array $excludedColumns): string
    {
        $selectColumn = $select->getColumn();
        if ($selectColumn === '*') {
            $toSelect = [];
            foreach($this->entityMeta->getColumns() as $selectColumn) {
                $columnName = $this->entityAccessor->prefixColumnName($selectColumn->value);
                if (!in_array($columnName, $excludedColumns)) {
                    $toSelect[] = "`" . $columnName . "`";
                }
            }
            $selectColumn = join(', ', $toSelect);
        }

        return $selectColumn;
    }

    /**
     * @param string $column
     * @param        $value
     *
     * @return array|null[]
     * @throws Exception
     */
    protected function getMappedParams(string $column, $value): array
    {
        $manyToOne = $this->entityMeta->getManyToOne();
        $prop = $this->entityMeta->getPropertyName($column);
        if ($prop && isset($manyToOne[$prop])) {
            $sourceRepo = $this->em->getRepository($manyToOne[$prop]->value);
            if (!$sourceRepo) {
                throw new RuntimeException("ManyToOne entity not found.");
            }
            $primaryKey = $this->em->getMeta($manyToOne[$prop]->value)->getPrimaryKeyPropName();
            $value      = $this->entityAccessor->getValue($value, $primaryKey);
            $key        = $this->entityAccessor->prefixColumnName($column);

            return [$value, $key];
        }

        return [null, null];
    }
}
