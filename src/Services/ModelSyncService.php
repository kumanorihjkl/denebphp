<?php
namespace Deneb\PhpBaas\Services;

use Deneb\PhpBaas\Database\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Comparator;

class ModelSyncService
{
    private $connection;
    private $schema;
    private $comparator;

    public function __construct()
    {
        $this->connection = Connection::getInstance();
        $this->schema = new Schema();
        $this->comparator = new Comparator();
    }

    public function calculateDiff(array $modelDefinition): array
    {
        $currentSchema = $this->connection->createSchemaManager()->introspectSchema();
        $newSchema = $this->createSchemaFromDefinition($modelDefinition);
        
        $schemaDiff = $this->comparator->compareSchemas($currentSchema, $newSchema);
        
        return $this->formatDiff($schemaDiff);
    }

    public function applyMigration(array $changes): bool
    {
        try {
            $this->connection->beginTransaction();

            foreach ($changes as $change) {
                $sql = $this->generateSqlFromChange($change);
                $this->connection->executeStatement($sql);
            }

            $this->connection->commit();
            return true;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    private function createSchemaFromDefinition(array $modelDefinition): Schema
    {
        $schema = new Schema();

        foreach ($modelDefinition['models'] as $model) {
            $table = $schema->createTable($model['name']);

            foreach ($model['fields'] as $field) {
                $options = [
                    'notnull' => !($field['nullable'] ?? false),
                    'autoincrement' => $field['autoIncrement'] ?? false,
                ];

                if (isset($field['length'])) {
                    $options['length'] = $field['length'];
                }

                $table->addColumn($field['name'], $this->mapFieldType($field['type']), $options);

                if ($field['primary'] ?? false) {
                    $table->setPrimaryKey([$field['name']]);
                }

                if ($field['unique'] ?? false) {
                    $table->addUniqueIndex([$field['name']]);
                }

                if (isset($field['foreignKey'])) {
                    $table->addForeignKeyConstraint(
                        $field['foreignKey']['model'],
                        [$field['name']],
                        [$field['foreignKey']['field']]
                    );
                }
            }
        }

        return $schema;
    }

    private function mapFieldType(string $type): string
    {
        $typeMap = [
            'string' => 'string',
            'integer' => 'integer',
            'text' => 'text',
            'datetime' => 'datetime',
            'boolean' => 'boolean',
            'float' => 'float',
        ];

        return $typeMap[$type] ?? 'string';
    }

    private function formatDiff($schemaDiff): array
    {
        $changes = [];

        // Handle new tables
        foreach ($schemaDiff->getCreatedTables() as $table) {
            $changes[] = [
                'action' => 'add_table',
                'table' => $table->getName(),
                'fields' => $this->formatTableFields($table)
            ];
        }

        // Handle modified tables
        foreach ($schemaDiff->getAlteredTables() as $tableDiff) {
            // Added columns
            foreach ($tableDiff->getAddedColumns() as $column) {
                $changes[] = [
                    'action' => 'add_field',
                    'table' => $tableDiff->getName(),
                    'field' => $this->formatColumnDefinition($column)
                ];
            }

            // Modified columns
            foreach ($tableDiff->getModifiedColumns() as $columnDiff) {
                $changes[] = [
                    'action' => 'modify_field',
                    'table' => $tableDiff->getName(),
                    'field' => $columnDiff->getOldColumn()->getName(),
                    'change' => $this->formatColumnDefinition($columnDiff->getNewColumn())
                ];
            }
        }

        return $changes;
    }

    private function formatTableFields($table): array
    {
        $fields = [];
        foreach ($table->getColumns() as $column) {
            $fields[] = $this->formatColumnDefinition($column);
        }
        return $fields;
    }

    private function formatColumnDefinition($column): array
    {
        return [
            'name' => $column->getName(),
            'type' => $column->getType()->getName(),
            'length' => $column->getLength(),
            'nullable' => !$column->getNotnull(),
            'default' => $column->getDefault(),
            'autoIncrement' => $column->getAutoincrement(),
        ];
    }

    private function generateSqlFromChange(array $change): string
    {
        $sql = '';
        switch ($change['action']) {
            case 'add_table':
                $sql = $this->generateCreateTableSql($change);
                break;
            case 'add_field':
                $sql = $this->generateAddFieldSql($change);
                break;
            case 'modify_field':
                $sql = $this->generateModifyFieldSql($change);
                break;
        }
        return $sql;
    }

    private function generateCreateTableSql(array $change): string
    {
        $tableName = $change['table'];
        $fields = [];
        $primaryKey = null;

        foreach ($change['fields'] as $field) {
            $fieldDef = sprintf(
                '%s %s',
                $field['name'],
                $this->getSqlFieldType($field)
            );

            if (!($field['nullable'] ?? true)) {
                $fieldDef .= ' NOT NULL';
            }

            if (isset($field['default'])) {
                $fieldDef .= sprintf(" DEFAULT '%s'", $field['default']);
            }

            if ($field['autoIncrement'] ?? false) {
                $fieldDef .= ' AUTO_INCREMENT';
            }

            $fields[] = $fieldDef;

            if ($field['primary'] ?? false) {
                $primaryKey = $field['name'];
            }
        }

        if ($primaryKey) {
            $fields[] = sprintf('PRIMARY KEY (%s)', $primaryKey);
        }

        return sprintf(
            'CREATE TABLE %s (%s)',
            $tableName,
            implode(', ', $fields)
        );
    }

    private function generateAddFieldSql(array $change): string
    {
        return sprintf(
            'ALTER TABLE %s ADD COLUMN %s %s',
            $change['table'],
            $change['field']['name'],
            $this->getSqlFieldType($change['field'])
        );
    }

    private function generateModifyFieldSql(array $change): string
    {
        return sprintf(
            'ALTER TABLE %s MODIFY COLUMN %s %s',
            $change['table'],
            $change['field'],
            $this->getSqlFieldType($change['change'])
        );
    }

    private function getSqlFieldType(array $field): string
    {
        $type = strtoupper($field['type']);
        if ($type === 'STRING') {
            return sprintf('VARCHAR(%d)', $field['length'] ?? 255);
        }
        return $type;
    }
}
