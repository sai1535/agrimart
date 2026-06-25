<<<<<<< HEAD
<?php
include 'includes/db.php';

if ($conn) {
    echo "Database connected successfully!\n\n";
    try {
        $tablesQuery = $conn->query("SHOW TABLES");
        $tables = $tablesQuery->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            echo "Table: $table\n";
            $columnsQuery = $conn->query("DESCRIBE `$table`");
            $columns = $columnsQuery->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $column) {
                echo "  - {$column['Field']} ({$column['Type']}) - Null: {$column['Null']}, Key: {$column['Key']}\n";
            }
            echo "\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Failed to connect to the database.\n";
}
=======
<?php
include 'includes/db.php';

if ($conn) {
    echo "Database connected successfully!\n\n";
    try {
        $tablesQuery = $conn->query("SHOW TABLES");
        $tables = $tablesQuery->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            echo "Table: $table\n";
            $columnsQuery = $conn->query("DESCRIBE `$table`");
            $columns = $columnsQuery->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $column) {
                echo "  - {$column['Field']} ({$column['Type']}) - Null: {$column['Null']}, Key: {$column['Key']}\n";
            }
            echo "\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Failed to connect to the database.\n";
}
>>>>>>> 2cefe18d46f1c09c1a209b404d732967bd8384f4
?>