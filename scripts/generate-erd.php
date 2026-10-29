<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Schema;

// Read-only schema documentation. Run after migrations against a test database.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$tables = collect(Schema::getTables())->pluck('name')->sort()->values();
$out = "# TukodTrack ERD and data dictionary\n\nGenerated from the migrated schema. SQL types reflect the documentation database driver; migration precision remains authoritative for PostgreSQL decimals. Each diagram shows one table and its direct parent relationships. Nullable foreign keys allow zero or one parent; nonnullable keys require one. Children are zero-to-many.\n\n## Contents\n";
foreach ($tables as $table) {
    $out .= '- ['.$table.'](#'.$table.")\n";
}
foreach ($tables as $table) {
    $cols = Schema::getColumns($table);
    $fks = Schema::getForeignKeys($table);
    $indexes = Schema::getIndexes($table);
    $primary = collect($indexes)->firstWhere('primary', true)['columns'] ?? [];
    $out .= "\n## ".$table."\n\n```mermaid\nerDiagram\n    direction TB\n    ".$table." {\n";
    foreach ($cols as $col) {
        $key = in_array($col['name'], $primary) ? ' PK' : (collect($fks)->contains(fn ($fk) => in_array($col['name'], $fk['columns'])) ? ' FK' : '');
        $out .= '        '.preg_replace('/[^a-zA-Z0-9_]/', '_', $col['type']).' '.$col['name'].$key."\n";
    }
    $out .= "    }\n";
    foreach ($fks as $i => $fk) {
        if ($i > 0 && $i % 4 === 0) $out .= "```\n\n```mermaid\nerDiagram\n    direction TB\n";
        $nullable = collect($cols)->firstWhere('name', $fk['columns'][0])['nullable'];
        $out .= '    '.$fk['foreign_table'].($nullable ? ' |o--o{ ' : ' ||--o{ ').$table.' : "'.implode(', ', $fk['columns']).'"'."\n";
    }
    $out .= "```\n\n| Column | Type | Nullable | Default |\n| --- | --- | --- | --- |\n";
    foreach ($cols as $col) {
        $out .= '| '.$col['name'].' | '.$col['type'].' | '.($col['nullable'] ? 'Yes' : 'No').' | '.str_replace('|', '\\|', (string) ($col['default'] ?? '—'))." |\n";
    }
    if ($fks) {
        $out .= "\n| Foreign key | References | Delete behavior |\n| --- | --- | --- |\n";
        foreach ($fks as $fk) {
            $out .= '| '.implode(', ', $fk['columns']).' | '.$fk['foreign_table'].'('.implode(', ', $fk['foreign_columns']).') | '.$fk['on_delete']." |\n";
        }
    }
    $out .= "\nIndexes: ".implode('; ', array_map(fn ($i) => $i['name'].' ('.implode(', ', $i['columns']).')'.($i['unique'] ? ' UNIQUE' : ''), $indexes)).".\n";
}
$out .= "\n## Logical references without database foreign keys\n\n- stock_movements.reverses_movement_id refers to stock_movements.id; the original schema does not enforce a self-foreign-key.\n- sessions.user_id is indexed but has no enforced foreign key.\n- password_reset_tokens.email is the Laravel reset-token lookup key, not an enforced users.email foreign key.\n- settings, cache and queue tables are independent framework/application storage.\n";
file_put_contents(__DIR__.'/../docs/ERD.md', $out);
echo 'Documented '.count($tables)." tables.\n";
