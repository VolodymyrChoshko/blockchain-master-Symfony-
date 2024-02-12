<?php
namespace BlocksEdit\Database;

use BlocksEdit\Database\Annotations\CacheTag;
use BlocksEdit\Database\Annotations\Column;
use BlocksEdit\Database\Annotations\ForeignKey;
use BlocksEdit\Database\Annotations\Join;
use BlocksEdit\Database\Annotations\ManyToOne;
use BlocksEdit\Database\Annotations\OneToMany;
use Exception;

/**
 * Class EntityMeta
 */
class EntityMeta
{
    /**
     * @var string
     */
    protected $entityClass = '';

    /**
     * @var string
     */
    protected $tableName = '';

    /**
     * @var string
     */
    protected $repositoryClass = '';

    /**
     * @var Column[]
     */
    protected $columns = [];

    /**
     * @var string
     */
    protected $primaryKeyPropName = '';

    /**
     * @var array
     */
    protected $nameMap = [];

    /**
     * @var array
     */
    protected $sqlMap = [];

    /**
     * @var ForeignKey[]
     */
    protected $foreignKeys = [];

    /**
     * @var array
     */
    protected $manyToOne = [];

    /**
     * @var array
     */
    protected $oneToMany = [];

    /**
     * @var array
     */
    protected $indexes = [];

    /**
     * @var array
     */
    protected $uniqueIndexes = [];

    /**
     * @var array
     */
    protected $columnTypes = [];

    /**
     * @var string
     */
    protected $columnPrefix = '';

    /**
     * @var CacheTag[]
     */
    protected $cacheTags = [];

    /**
     * @var string[]
     */
    protected $fixColumnTypes = [];

    /**
     * @var Join[]
     */
    protected $joins = [];

    /**
     * @var string
     */
    protected $charSet = '';

    /**
     * @var string
     */
    protected $collate = '';

    /**
     * Constructor
     *
     * @param Column[] $columns
     *
     * @return EntityMeta
     */
    public function setColumns(array $columns): EntityMeta
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param string $propName
     *
     * @return Column|null
     */
    public function getColumn(string $propName): ?Column
    {
        if ($this->columns[$propName]) {
            return $this->columns[$propName];
        }

        return null;
    }

    /**
     * @return string
     */
    public function getRepositoryClass(): string
    {
        return $this->repositoryClass;
    }

    /**
     * @param string $repositoryClass
     *
     * @return EntityMeta
     */
    public function setRepositoryClass(string $repositoryClass): EntityMeta
    {
        $this->repositoryClass = $repositoryClass;

        return $this;
    }

    /**
     * @param string $propertyName
     *
     * @return string
     * @throws Exception
     */
    public function getColumnName(string $propertyName): string
    {
        if (empty($this->nameMap[$propertyName])) {
            throw new Exception('Missing @column annotation on ' . $propertyName);
        }

        return $this->nameMap[$propertyName];
    }

    /**
     * @param string $columnName
     *
     * @return string
     */
    public function getPropertyName(string $columnName): string
    {
        $key = array_search($columnName, $this->nameMap);
        if ($key) {
            return $key;
        }

        return '';
    }

    /**
     * @return string
     */
    public function getPrimaryKeyColumn(): string
    {
        return $this->nameMap[$this->primaryKeyPropName];
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * @param string $className
     *
     * @return EntityMeta
     */
    public function setEntityClass(string $className): EntityMeta
    {
        $this->entityClass = $className;

        return $this;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     *
     * @return EntityMeta
     */
    public function setTableName(string $tableName): EntityMeta
    {
        $this->tableName = $tableName;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrimaryKeyPropName(): string
    {
        return $this->primaryKeyPropName;
    }

    /**
     * @param string $primaryKeyPropName
     *
     * @return EntityMeta
     */
    public function setPrimaryKeyPropName(string $primaryKeyPropName): EntityMeta
    {
        $this->primaryKeyPropName = $primaryKeyPropName;

        return $this;
    }

    /**
     * @return array
     */
    public function getNameMap(): array
    {
        return $this->nameMap;
    }

    /**
     * @param array $nameMap
     *
     * @return EntityMeta
     */
    public function setNameMap(array $nameMap): EntityMeta
    {
        $this->nameMap = $nameMap;

        return $this;
    }

    /**
     * @return array
     */
    public function getSqlMap(): array
    {
        return $this->sqlMap;
    }

    /**
     * @param array $sqlMap
     *
     * @return EntityMeta
     */
    public function setSqlMap(array $sqlMap): EntityMeta
    {
        $this->sqlMap = $sqlMap;

        return $this;
    }

    /**
     * @return ForeignKey[]
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    /**
     * @param ForeignKey[] $foreignKeys
     *
     * @return EntityMeta
     */
    public function setForeignKeys(array $foreignKeys): EntityMeta
    {
        $this->foreignKeys = $foreignKeys;

        return $this;
    }

    /**
     * @return ManyToOne[]
     */
    public function getManyToOne(): array
    {
        return $this->manyToOne;
    }

    /**
     * @param ManyToOne[] $manyToOne
     *
     * @return EntityMeta
     */
    public function setManyToOne(array $manyToOne): EntityMeta
    {
        $this->manyToOne = $manyToOne;

        return $this;
    }

    /**
     * @return OneToMany[]
     */
    public function getOneToMany(): array
    {
        return $this->oneToMany;
    }

    /**
     * @param OneToMany[] $oneToMany
     *
     * @return EntityMeta
     */
    public function setOneToMany(array $oneToMany): EntityMeta
    {
        $this->oneToMany = $oneToMany;

        return $this;
    }

    /**
     * @return array
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * @param array $indexes
     *
     * @return EntityMeta
     */
    public function setIndexes(array $indexes): EntityMeta
    {
        $this->indexes = $indexes;

        return $this;
    }

    /**
     * @return array
     */
    public function getUniqueIndexes(): array
    {
        return $this->uniqueIndexes;
    }

    /**
     * @param array $uniqueIndexes
     *
     * @return EntityMeta
     */
    public function setUniqueIndexes(array $uniqueIndexes): EntityMeta
    {
        $this->uniqueIndexes = $uniqueIndexes;

        return $this;
    }

    /**
     * @return array
     */
    public function getColumnTypes(): array
    {
        return $this->columnTypes;
    }

    /**
     * @param array $columnTypes
     *
     * @return EntityMeta
     */
    public function setColumnTypes(array $columnTypes): EntityMeta
    {
        $this->columnTypes = $columnTypes;

        return $this;
    }

    /**
     * @param string $column
     *
     * @return string
     */
    public function getColumnType(string $column): string
    {
        return $this->columnTypes[$column];
    }

    /**
     * @return string
     */
    public function getColumnPrefix(): string
    {
        return $this->columnPrefix;
    }

    /**
     * @param string $columnPrefix
     *
     * @return $this
     */
    public function setColumnPrefix(string $columnPrefix): EntityMeta
    {
        $this->columnPrefix = $columnPrefix;

        return $this;
    }

    /**
     * @return CacheTag[]
     */
    public function getCacheTags(): array
    {
        return $this->cacheTags;
    }

    /**
     * @param CacheTag[] $cacheTags
     *
     * @return EntityMeta
     */
    public function setCacheTags(array $cacheTags): EntityMeta
    {
        $this->cacheTags = $cacheTags;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getFixColumnTypes(): array
    {
        return $this->fixColumnTypes;
    }

    /**
     * @param string[] $fixColumnTypes
     *
     * @return EntityMeta
     */
    public function setFixColumnTypes(array $fixColumnTypes): EntityMeta
    {
        $this->fixColumnTypes = $fixColumnTypes;

        return $this;
    }

    /**
     * @return Join[]
     */
    public function getJoins(): array
    {
        return $this->joins;
    }

    /**
     * @param Join[] $joins
     *
     * @return EntityMeta
     */
    public function setJoins(array $joins): EntityMeta
    {
        $this->joins = $joins;

        return $this;
    }

    /**
     * @return string
     */
    public function getCharSet(): string
    {
        return $this->charSet;
    }

    /**
     * @param string $charSet
     *
     * @return EntityMeta
     */
    public function setCharSet(string $charSet): EntityMeta
    {
        $this->charSet = $charSet;

        return $this;
    }

    /**
     * @return string
     */
    public function getCollate(): string
    {
        return $this->collate;
    }

    /**
     * @param string $collate
     *
     * @return EntityMeta
     */
    public function setCollate(string $collate): EntityMeta
    {
        $this->collate = $collate;

        return $this;
    }
}
