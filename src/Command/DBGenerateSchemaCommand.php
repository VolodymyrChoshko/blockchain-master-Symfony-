<?php
namespace Command;

use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use BlocksEdit\Command\InputInterface;
use BlocksEdit\Command\OutputInterface;
use BlocksEdit\Database\Annotations\ForeignKey;
use BlocksEdit\Database\EntityManager;
use BlocksEdit\Database\EntityMeta;
use Exception;

/**
 * Class DBGenerateSchemaCommand
 */
class DBGenerateSchemaCommand extends Command
{
    static $name = 'db:schema';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritDoc}
     */
    public static function getHelp(): string
    {
        return 'Generates SQL to create tables from entities';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Args $args, OutputInterface $output, InputInterface $input): int
    {
        $entityNames = $args->getArgs();
        if (!$entityNames) {
            $output->errorLine('Missing entity class name(s).');
            return 1;
        }

        foreach($entityNames as $entityName) {
            try {
                if (strpos($entityName, 'Entity\\') !== 0) {
                    $entityName = 'Entity\\' . $entityName;
                }
                $output->writeLine($this->getSchemaSql($entityName));
            } catch (Exception $e) {
                $output->errorLine($e->getMessage());
                return 1;
            }
        }

        return 0;
    }

    /**
     * @param string $entityName
     *
     * @return string
     * @throws Exception
     */
    protected function getSchemaSql(string $entityName): string
    {
        $meta = $this->em->getMeta($entityName);

        $tableSql = sprintf("CREATE TABLE `%s` (\n", $meta->getTableName());
        foreach($meta->getSqlMap() as $propertyName => $sql) {
            $columnName = $meta->getColumnName($propertyName);
            $tableSql .= "  `${columnName}` ${sql},\n";
        }
        $tableSql .= sprintf("  PRIMARY KEY(`%s`)", $meta->getPrimaryKeyColumn());

        if ($meta->getUniqueIndexes()) {
            $tableSql .= "\n";
            foreach($meta->getUniqueIndexes() as $propertyName => $name) {
                if (is_array($name)) {
                    $columnSql = [];
                    foreach($name as $column) {
                        $columnName = $meta->getColumnName($column);
                        $columnSql[] = "`$columnName`";
                    }
                    $columnSql = join(', ', $columnSql);
                    $tableSql .= "  UNIQUE KEY `$propertyName` ($columnSql),\n";
                } else {
                    $columnName = $meta->getColumnName($propertyName);
                    if (!$name) {
                        $name = $columnName . '_idx';
                    }
                    $tableSql .= "  UNIQUE KEY $name (`${columnName}`),\n";
                }
            }
            $tableSql = rtrim($tableSql, ",\n");
        }

        if ($meta->getIndexes()) {
            $tableSql .= ",\n";
            foreach($meta->getIndexes() as $indexName => $columns) {
                $columnSql = [];
                foreach($columns as $column) {
                    $columnName = $meta->getColumnName($column);
                    $columnSql[] = "`$columnName`";
                }
                $columnSql = join(', ', $columnSql);
                $tableSql .= "  KEY `$indexName` ($columnSql),\n";
            }
            $tableSql = rtrim($tableSql, ",\n");
        }

        if ($meta->getForeignKeys()) {
            $tableSql .= ",\n";
            $counter = 1;
            foreach($meta->getForeignKeys() as $propertyName => $foreignKey) {
                $columnName = $meta->getColumnName($propertyName);
                $name = $foreignKey->name;
                if (!$name) {
                    $name = $meta->getTableName() . '_ibfk_' . $counter++;
                }
                list($table, $column) = $this->foreignToNames($foreignKey);
                $tableSql .= sprintf(
                    "  CONSTRAINT `$name` FOREIGN KEY (`%s`) REFERENCES `%s`(`%s`)",
                    $columnName,
                    $table,
                    $column
                );
                if ($foreignKey->onDelete) {
                    $tableSql .= ' ON DELETE ' . $foreignKey->onDelete;
                }
                if ($foreignKey->onUpdate) {
                    $tableSql .= ' ON UPDATE ' . $foreignKey->onUpdate;
                }
                $tableSql .= ",\n";
            }
            $tableSql = rtrim($tableSql, ",\n") . "\n";
        }

        $tableSql = rtrim($tableSql, ",\n");
        $tableSql .= "\n) ENGINE=InnoDB";

        if ($meta->getCharSet() || $meta->getCollate()) {
            $tableSql .= ' DEFAULT';
            if ($meta->getCharSet()) {
                $tableSql .= ' CHARSET=' . $meta->getCharSet();
            }
            if ($meta->getCollate()) {
                $tableSql .= ' COLLATE=' . $meta->getCollate();
            }
        }
        $tableSql .= ";";

        return $tableSql;
    }

    /**
     * @param ForeignKey $foreignKey
     *
     * @return array
     * @throws Exception
     */
    protected function foreignToNames(ForeignKey $foreignKey): array
    {
        if (strtoupper($foreignKey->value[0]) === $foreignKey->value[0] && class_exists($foreignKey->value)) {
            $meta       = $this->em->getMeta($foreignKey->value);
            $columnName = $meta->getColumnName($foreignKey->references);

            return [$meta->getTableName(), $columnName];
        }

        return [$foreignKey->value, $foreignKey->references];
    }
}
