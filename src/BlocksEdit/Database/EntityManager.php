<?php
namespace BlocksEdit\Database;

use BlocksEdit\Cache\CacheInterface;
use BlocksEdit\Config\Config;
use BlocksEdit\Database\Annotations\CacheTag;
use BlocksEdit\Database\Annotations\Column;
use BlocksEdit\Database\Annotations\ForeignKey;
use BlocksEdit\Database\Annotations\Join;
use BlocksEdit\Database\Annotations\ManyToOne;
use BlocksEdit\Database\Annotations\OneToMany;
use BlocksEdit\Database\Annotations\Primary;
use BlocksEdit\Database\Annotations\Sql;
use BlocksEdit\Database\Annotations\Table;
use BlocksEdit\Database\Annotations\UniqueIndex;
use BlocksEdit\IO\FilesTrait;
use BlocksEdit\System\ClassFinderInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Exception;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use RuntimeException;

/**
 * Class EntityManager
 */
class EntityManager
{
    use FilesTrait;

    const CACHE_KEY = 'EntityManager:entityMetas:v1';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var AnnotationReader
     */
    protected $reader;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ClassFinderInterface
     */
    protected $classFinder;

    /**
     * @var array|null
     */
    protected $metas = null;

    /**
     * @var array
     */
    protected $entityRepoMap = [];

    /**
     * @var string
     */
    protected $namespaceRepositories = '';

    /**
     * @var string
     */
    protected $namespaceEntities = '';

    /**
     * Constructor
     *
     * @param Config               $config
     * @param CacheInterface       $cache
     * @param ContainerInterface   $container
     * @param ClassFinderInterface $classFinder
     *
     */
    public function __construct(
        Config $config,
        CacheInterface $cache,
        ContainerInterface $container,
        ClassFinderInterface $classFinder
    )
    {
        $this->config = $config;
        $this->cache  = $cache;
        $this->reader = new AnnotationReader();
        $this->container = $container;
        $this->classFinder = $classFinder;
        $this->namespaceRepositories = $config->namespaces['repositories'];
        $this->namespaceEntities = $config->namespaces['entities'];
    }

    /**
     * @param string|object $entityClassName
     *
     * @return Repository|null
     */
    public function getRepository($entityClassName): ?Repository
    {
        if (is_object($entityClassName)) {
            $entityClassName = get_class($entityClassName);
        }
        if (!$this->isEntityClass($entityClassName)) {
            throw new InvalidArgumentException(
                'Argument to EntityManager::getRepository() must be an entity.'
            );
        }

        return $this->container->get($this->entityRepoMap[$entityClassName]);
    }

    /**
     * @param string|Repository $repoClassName
     *
     * @return EntityMeta|null
     * @throws Exception
     */
    public function getMeta($repoClassName): ?EntityMeta
    {
        if (is_object($repoClassName)) {
            $repoClassName = get_class($repoClassName);
        }
        if (!$this->isRepoOrEntityClass($repoClassName)) {
            throw new InvalidArgumentException(
                'Value to EntityManager::getRepositoryMeta() must be an entity class name or instance of Repository.'
            );
        }

        if ($this->metas === null) {
            $this->metas = [];

            if ($this->config->env !== 'dev' && $this->cache->exists(self::CACHE_KEY)) {
                $this->metas = $this->cache->get(self::CACHE_KEY);
            } else {
                $entityClasses = $this->classFinder->getNamespaceClasses(
                    $this->namespaceEntities,
                    false,
                    false
                );

                foreach($entityClasses as $className) {
                    $meta = $this->generateEntityMeta($className);
                    $this->metas[$meta->getRepositoryClass()] = $meta;
                }
                $this->cache->set(self::CACHE_KEY, $this->metas);
            }

            foreach($this->metas as $meta) {
                $this->entityRepoMap[$meta->getEntityClass()] = $meta->getRepositoryClass();
            }
        }

        if ($this->isEntityClass($repoClassName)) {
            return $this->metas[$this->entityRepoMap[$repoClassName]];
        }

        return $this->metas[$repoClassName];
    }

    /**
     * @return bool
     */
    public function clearCache(): bool
    {
        return $this->cache->delete(self::CACHE_KEY);
    }

    /**
     * @param string $entityClassName
     *
     * @return EntityMeta
     * @throws Exception
     */
    protected function generateEntityMeta(string $entityClassName): EntityMeta
    {
        $entityRefClass = new ReflectionClass($entityClassName);
        $table = $this->reader->getClassAnnotation($entityRefClass, Table::class);
        if (!$table) {
            throw new RuntimeException(
                sprintf('@Table annotation not found on entity class %s.', $entityClassName)
            );
        }

        return (new EntityMeta())
            ->setColumns($this->getColumns($entityRefClass))
            ->setEntityClass($entityClassName)
            ->setColumnPrefix($table->getPrefix())
            ->setRepositoryClass($table->getRepository())
            ->setFixColumnTypes($this->getFixColumnTypes($entityRefClass))
            ->setPrimaryKeyPropName($this->getPrimaryKeyProperty($entityRefClass))
            ->setTableName($table->getPrefix() . $table->getValue())
            ->setNameMap($this->getColumnNameMap($entityRefClass, $table))
            ->setSqlMap($this->getSqlNameMap($entityRefClass))
            ->setIndexes($table->getIndexes())
            ->setUniqueIndexes($this->getUniqueIndexes($entityRefClass, $table))
            ->setForeignKeys($this->getForeignKeys($entityRefClass))
            ->setManyToOne($this->getManyToOne($entityRefClass))
            ->setOneToMany($this->getOneToMany($entityRefClass))
            ->setColumnTypes($this->getEntityColumnTypes($entityRefClass))
            ->setCacheTags($this->getCacheTags($entityRefClass))
            ->setJoins($this->getJoins($entityRefClass))
            ->setCharSet($table->getCharSet())
            ->setCollate($table->getCollate());
    }

    /**
     * @param ReflectionClass $refClass
     *
     * @return Column[]
     */
    protected function getColumns(ReflectionClass $refClass): array
    {
        $columns = [];
        foreach($refClass->getProperties() as $property) {
            $column = $this->reader->getPropertyAnnotation($property, Column::class);
            if ($column) {
                $columns[$property->getName()] = $column;
            }
        }

        return $columns;
    }

    /**
     * @param ReflectionClass $refClass
     * @param Table           $table
     *
     * @return array
     */
    protected function getColumnNameMap(ReflectionClass $refClass, Table $table): array
    {
        $columns = [];
        foreach($refClass->getProperties() as $property) {
            $column = $this->reader->getPropertyAnnotation($property, Column::class);
            if ($column) {
                $columns[$property->getName()] = $this->prefixName($column->value, $table);
            }
        }

        return $columns;
    }

    /**
     * @param ReflectionClass $refClass
     *
     * @return array
     */
    protected function getEntityColumnTypes(ReflectionClass $refClass): array
    {
        $types = [];
        foreach($refClass->getProperties() as $property) {
            if (preg_match('/@var\s+\\\?(\w+)/', $property->getDocComment(), $matches)) {
                $types[$property->getName()] = $matches[1];
            } else {
                $types[$property->getName()] = 'string';
            }
        }

        return $types;
    }

    /**
     * @param ReflectionClass $refClass
     *
     * @return array
     */
    protected function getCacheTags(ReflectionClass $refClass): array
    {
        $cacheTags   = [];
        $annotations = $this->reader->getClassAnnotations($refClass);
        if (!empty($annotations)) {
            foreach($annotations as $annotation) {
                if ($annotation instanceof CacheTag) {
                    $cacheTags[] = $annotation;
                }
            }
        }

        foreach($cacheTags as $cacheTag) {
            if ($cacheTag->value) {
                if (!class_exists($cacheTag->value)) {
                    throw new RuntimeException(
                        sprintf('Tag class %s does not exist on entity %s.', $cacheTag->value, $refClass->getName())
                    );
                }
            }
            if ($cacheTag->mergeTags) {
                if (!class_exists($cacheTag->mergeTags)) {
                    throw new RuntimeException(
                        sprintf('Tag merge tags class %s does not exist on entity %s.', $cacheTag->mergeTags, $refClass->getName())
                    );
                }
            }
        }

        return $cacheTags;
    }

    /**
     * @param ReflectionClass $refClass
     *
     * @return array
     */
    protected function getSqlNameMap(ReflectionClass $refClass): array
    {
        $columns = [];
        foreach($refClass->getProperties() as $property) {
            $sql = $this->reader->getPropertyAnnotation($property, Sql::class);
            if ($sql) {
                $columns[$property->getName()] = $sql->value;
            }
        }

        return $columns;
    }

    /**
     * @param ReflectionClass $refClass
     *
     * @return string
     * @throws Exception
     */
    protected function getPrimaryKeyProperty(ReflectionClass $refClass): string
    {
        foreach($refClass->getProperties() as $property) {
            $primary = $this->reader->getPropertyAnnotation($property, Primary::class);
            if ($primary) {
                return $property->getName();
            }
        }

        throw new Exception(
            sprintf('Missing @Primary annotation on %s.', $refClass->getName())
        );
    }

    /**
     * @param ReflectionClass $refClass
     *
     * @return array
     * @throws Exception
     */
    protected function getForeignKeys(ReflectionClass $refClass): array
    {
        $columns = [];
        foreach($refClass->getProperties() as $property) {
            $foreignKey = $this->reader->getPropertyAnnotation($property, ForeignKey::class);
            if ($foreignKey) {
                $columns[$property->getName()] = $foreignKey;
            }
        }

        return $columns;
    }

    /**
     * @param ReflectionClass $refClass
     *
     * @return array
     */
    protected function getManyToOne(ReflectionClass $refClass): array
    {
        $columns = [];
        foreach($refClass->getProperties() as $property) {
            $manyToOne = $this->reader->getPropertyAnnotation($property, ManyToOne::class);
            if ($manyToOne) {
                $columns[$property->getName()] = $manyToOne;
            }
        }

        return $columns;
    }

    /**
     * @param ReflectionClass $refClass
     *
     * @return array
     */
    public function getOneToMany(ReflectionClass $refClass): array
    {
        $columns = [];
        foreach($refClass->getProperties() as $property) {
            $oneToMany = $this->reader->getPropertyAnnotation($property, OneToMany::class);
            if ($oneToMany) {
                $columns[$property->getName()] = $oneToMany;
            }
        }

        return $columns;
    }

    /**
     * @param ReflectionClass $refClass
     * @param Table           $table
     *
     * @return array
     */
    protected function getUniqueIndexes(ReflectionClass $refClass, Table $table): array
    {
        $columns = [];
        foreach($refClass->getProperties() as $property) {
            $uniqueIndex = $this->reader->getPropertyAnnotation($property, UniqueIndex::class);
            if ($uniqueIndex) {
                $columns[$property->getName()] = $uniqueIndex->value;
            }
        }
        foreach($table->getUniqueIndexes() as $name => $values) {
            $columns[$name] = $values;
        }

        return $columns;
    }

    /**
     * @param ReflectionClass $refClass
     *
     * @return Join[]
     */
    protected function getJoins(ReflectionClass $refClass): array
    {
        $joins = [];
        foreach($refClass->getProperties() as $property) {
            $join = $this->reader->getPropertyAnnotation($property, Join::class);
            if ($join) {
                $joins[$property->getName()] = $join;
            }
        }

        return $joins;
    }

    /**
     * @param ReflectionClass $refClass
     *
     * @return array
     */
    protected function getFixColumnTypes(ReflectionClass $refClass): array
    {
        $fixColumnTypes = [];
        foreach($refClass->getProperties() as $property) {
            $column = $this->reader->getPropertyAnnotation($property, Column::class);
            if ($column && $column->castTo) {
                $fixColumnTypes[$property->getName()] = $column->castTo;
            }
        }

        return $fixColumnTypes;
    }

    /**
     * @param string $name
     * @param Table  $table
     *
     * @return string
     */
    protected function prefixName(string $name, Table $table): string
    {
        if ($table->getPrefix() && strpos($name, $table->getPrefix()) !== 0) {
            $name = $table->getPrefix() . $name;
        }

        return $name;
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    protected function isRepoOrEntityClass(string $className): bool
    {
        return $this->isRepositoryClass($className) || $this->isEntityClass($className);
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    protected function isRepositoryClass(string $className): bool
    {
        return strpos($className, $this->namespaceRepositories) === 0;
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    protected function isEntityClass(string $className): bool
    {
        return strpos($className, $this->namespaceEntities) === 0;
    }
}
