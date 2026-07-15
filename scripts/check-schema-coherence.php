<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Aquest script nomes es pot executar des de terminal.\n");
    exit(1);
}

$pdo = require dirname(__DIR__) . '/config/database.php';
$errors = [];
$warnings = [];

$editionScopedTables = [
    'assessment_sources' => [
        'requiredColumns' => ['project_academic_year_id'],
        'forbiddenColumns' => ['project_id'],
        'fks' => [[
            'column' => 'project_academic_year_id',
            'referencedTable' => 'project_academic_years',
            'referencedColumn' => 'id',
        ]],
        'uniqueIndexes' => [['project_academic_year_id', 'name']],
    ],
    'assessment_import_runs' => [
        'requiredColumns' => ['project_academic_year_id'],
        'forbiddenColumns' => ['project_id'],
        'fks' => [[
            'column' => 'project_academic_year_id',
            'referencedTable' => 'project_academic_years',
            'referencedColumn' => 'id',
        ]],
        'indexes' => [['project_academic_year_id', 'started_at'], ['source_id']],
    ],
    'documents' => [
        'requiredColumns' => ['project_academic_year_id'],
        'forbiddenColumns' => ['project_id'],
        'fks' => [[
            'column' => 'project_academic_year_id',
            'referencedTable' => 'project_academic_years',
            'referencedColumn' => 'id',
        ]],
        'uniqueIndexes' => [['project_academic_year_id', 'slug']],
        'indexes' => [['project_academic_year_id', 'is_active', 'display_order', 'title']],
    ],
    'project_class_assignments' => [
        'requiredColumns' => ['project_academic_year_id'],
        'forbiddenColumns' => ['project_id'],
        'fks' => [[
            'column' => 'project_academic_year_id',
            'referencedTable' => 'project_academic_years',
            'referencedColumn' => 'id',
        ]],
        'uniqueIndexes' => [['project_academic_year_id', 'class_id']],
        'indexes' => [['project_academic_year_id'], ['class_id']],
    ],
    'google_sources' => [
        'requiredColumns' => ['project_academic_year_id'],
        'forbiddenColumns' => ['project_id'],
        'fks' => [[
            'column' => 'project_academic_year_id',
            'referencedTable' => 'project_academic_years',
            'referencedColumn' => 'id',
        ]],
        'uniqueIndexes' => [['project_academic_year_id', 'source_type', 'google_file_id', 'sheet_name', 'range_name']],
        'indexes' => [['project_academic_year_id', 'is_active', 'source_type'], ['google_file_id']],
    ],
    'synced_documents' => [
        'requiredColumns' => ['project_academic_year_id'],
        'fks' => [[
            'column' => 'project_academic_year_id',
            'referencedTable' => 'project_academic_years',
            'referencedColumn' => 'id',
        ]],
        'uniqueIndexes' => [['google_source_id', 'language_code']],
        'indexes' => [['project_academic_year_id', 'is_active', 'synced_at'], ['google_source_id']],
    ],
    'synced_sheet_rows' => [
        'requiredColumns' => ['project_academic_year_id'],
        'fks' => [[
            'column' => 'project_academic_year_id',
            'referencedTable' => 'project_academic_years',
            'referencedColumn' => 'id',
        ]],
        'uniqueIndexes' => [['google_source_id', 'external_id']],
        'indexes' => [['project_academic_year_id', 'is_active', 'row_number'], ['google_source_id', 'row_number']],
    ],
    'google_sync_runs' => [
        'requiredColumns' => ['project_academic_year_id'],
        'fks' => [[
            'column' => 'project_academic_year_id',
            'referencedTable' => 'project_academic_years',
            'referencedColumn' => 'id',
        ]],
        'indexes' => [['project_academic_year_id', 'started_at'], ['google_source_id'], ['started_by_user_id']],
    ],
    'google_sync_errors' => [
        'requiredColumns' => ['project_academic_year_id'],
        'fks' => [[
            'column' => 'project_academic_year_id',
            'referencedTable' => 'project_academic_years',
            'referencedColumn' => 'id',
        ]],
        'indexes' => [['google_sync_run_id'], ['project_academic_year_id'], ['row_number']],
    ],
];

foreach ($editionScopedTables as $table => $rules) {
    if (!tableExists($pdo, $table)) {
        $warnings[] = "Taula absent (encara no implementada?): {$table}";
        continue;
    }

    foreach ($rules['requiredColumns'] ?? [] as $column) {
        if (!columnExists($pdo, $table, $column)) {
            $errors[] = "{$table}: falta la columna requerida {$column}.";
        }
    }

    foreach ($rules['forbiddenColumns'] ?? [] as $column) {
        if (columnExists($pdo, $table, $column)) {
            $errors[] = "{$table}: encara existeix la columna legacy {$column}.";
        }
    }

    foreach ($rules['fks'] ?? [] as $fk) {
        if (!foreignKeyExists($pdo, $table, $fk['column'], $fk['referencedTable'], $fk['referencedColumn'])) {
            $errors[] = sprintf(
                '%s: la FK de %s cap a %s(%s) no existeix.',
                $table,
                $fk['column'],
                $fk['referencedTable'],
                $fk['referencedColumn']
            );
        }
    }

    foreach ($rules['uniqueIndexes'] ?? [] as $columns) {
        if (!indexExistsWithColumns($pdo, $table, $columns, true)) {
            $errors[] = "{$table}: falta UNIQUE(" . implode(', ', $columns) . ").";
        }
    }

    foreach ($rules['indexes'] ?? [] as $columns) {
        if (!indexExistsWithColumns($pdo, $table, $columns, false)) {
            $errors[] = "{$table}: falta l'index(" . implode(', ', $columns) . ").";
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, "Errors de coherencia detectats:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, "- {$error}\n");
    }
}

if ($warnings !== []) {
    fwrite(STDOUT, "Avisos:\n");
    foreach ($warnings as $warning) {
        fwrite(STDOUT, "- {$warning}\n");
    }
}

if ($errors !== []) {
    exit(1);
}

fwrite(STDOUT, "Coherencia del schema OK.\n");
exit(0);

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table');
    $stmt->execute(['table' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column');
    $stmt->execute(['table' => $table, 'column' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function foreignKeyExists(PDO $pdo, string $table, string $column, string $referencedTable, string $referencedColumn): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table
           AND COLUMN_NAME = :column
           AND REFERENCED_TABLE_NAME = :referenced_table
           AND REFERENCED_COLUMN_NAME = :referenced_column'
    );
    $stmt->execute([
        'table' => $table,
        'column' => $column,
        'referenced_table' => $referencedTable,
        'referenced_column' => $referencedColumn,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function indexExistsWithColumns(PDO $pdo, string $table, array $columns, bool $unique): bool
{
    $stmt = $pdo->prepare(
        'SELECT INDEX_NAME, NON_UNIQUE, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ",") AS cols
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table
         GROUP BY INDEX_NAME, NON_UNIQUE'
    );
    $stmt->execute(['table' => $table]);

    $target = implode(',', $columns);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ($unique && (int) $row['NON_UNIQUE'] !== 0) {
            continue;
        }

        if (!$unique && (int) $row['NON_UNIQUE'] === 0) {
            continue;
        }

        if ((string) $row['cols'] === $target) {
            return true;
        }
    }

    return false;
}
