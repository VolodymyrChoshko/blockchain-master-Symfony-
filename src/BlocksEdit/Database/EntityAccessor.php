<?php
namespace BlocksEdit\Database;

use DateTime;
use Exception;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Class EntityAccessor
 */
class EntityAccessor
{
    /**
     * @var EntityMeta
     */
    protected $entityMeta;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var string
     */
    protected $columnPrefix = '';

    /**
     * @var array
     */
    protected $nameMap = [];

    /**
     * @var array
     */
    protected $columnTypes = [];

    /**
     * @var string[]
     */
    protected $fixColumnTypes = [];

    /**
     * @var array
     */
    protected $joins = [];

    /**
     * @var Annotations\Column[]
     */
    protected $columns = [];

    /**
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    /**
     * Constructor
     *
     * @param EntityMeta $entityMeta
     */
    public function __construct(EntityMeta $entityMeta)
    {
        $this->entityMeta       = $entityMeta;
        $this->columns          = $entityMeta->getColumns();
        $this->columnPrefix     = $entityMeta->getColumnPrefix();
        $this->nameMap          = $entityMeta->getNameMap();
        $this->entityClass      = $entityMeta->getEntityClass();
        $this->fixColumnTypes   = $entityMeta->getFixColumnTypes();
        $this->columnTypes      = $entityMeta->getColumnTypes();
        $this->joins            = $entityMeta->getJoins();
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableMagicCall()
            ->getPropertyAccessor();
    }

    /**
     * @param object|array $entity
     * @param string $property
     *
     * @return mixed
     */
    public function getValue($entity, string $property)
    {
        return $this->propertyAccessor->getValue($entity, $property);
    }

    /**
     * @param object|array $entity
     * @param string       $property
     * @param mixed        $value
     *
     * @return EntityAccessor
     * @throws Exception
     */
    public function setValue($entity, string $property, $value): EntityAccessor
    {
        try {
            if (isset($this->fixColumnTypes[$property])) {
                settype($value, $this->fixColumnTypes[$property]);
            } else if (isset($this->columns[$property]) && $this->columns[$property]->json) {
                $value = json_decode($value, true);
            }
            $this->propertyAccessor->setValue($entity, $property, $value);

            return $this;
        } catch (Exception $e) {
            throw new Exception(
                sprintf('%s in %s', $e->getMessage(), $this->entityMeta->getEntityClass()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $name
     *
     * @return string
     * @throws Exception
     */
    public function prefixColumnName(string $name): string
    {
        $function = '';
        if (preg_match('/([\w\d_]+)\(([\w\d]+)\)/i', $name, $matches)) {
            $function = $matches[1];
            $name     = $matches[2];
        }

        if ($this->columnPrefix && strpos($name, $this->columnPrefix) !== 0) {
            if (isset($this->nameMap[$name])) {
                $name = $this->nameMap[$name];
                if ($function) {
                    $name = sprintf('%s(%s)', $function, $name);
                }

                return $name;
            }

            $name = $this->columnPrefix . $name;
            if ($function) {
                $name = sprintf('%s(%s)', $function, $name);
            }

            return $name;
        }

        return $name;
    }

    /**
     * @param object $entity
     *
     * @return array
     */
    public function getEntityValues(object $entity): array
    {
        $values = [];
        foreach($this->nameMap as $property => $column) {
            $value = $this->getValue($entity, $property);
            if ($value instanceof DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            } else if (is_bool($value)) {
                $value = (int)$value;
            } else if (is_object($value) && isset($this->joins[$property])) {
                $value = $this->getValue($value, $this->joins[$property]->getReferences());
            } else if ($this->columns[$property]->json) {
                $value = json_encode((array)$value);
            }
            $values[$column] = $value;
        }

        return $values;
    }

    /**
     * @param array $rows
     *
     * @return array
     * @throws Exception
     */
    public function hydrateRows(array $rows): array
    {
        if (!$this->entityClass) {
            return $rows;
        }

        $hydrated = [];
        foreach($rows as $row) {
            $hydrated[] = $this->hydrateRow($row);
        }

        return $hydrated;
    }

    /**
     * @param array $row
     *
     * @return object
     * @throws Exception
     */
    public function hydrateRow(array $row): object
    {
        $className = $this->entityClass;
        $entity    = new $className();
        foreach($this->nameMap as $property => $column) {
            if (isset($row[$column])) {
                $value = $row[$column];
                $type  = $this->columnTypes[$property];
                if ($type === DateTime::class) {
                    $value = new DateTime($value);
                }

                $this->setValue($entity, $property, $value);
            }
        }

        return $entity;
    }
}
