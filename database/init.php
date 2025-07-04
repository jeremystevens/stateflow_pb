<?php
/**
 * Database Initialization Script
 * Creates the comprehensive database schema for PasteForge
 */

function initializeDatabase($pdo = null) {
    if ($pdo === null) {
        require_once __DIR__ . '/../includes/db.php';
        $pdo = getDatabase();
    }
    
    try {
        // Read and execute the comprehensive schema
        $schemaPath = __DIR__ . '/schema.sql';
        
        if (!file_exists($schemaPath)) {
            throw new Exception("Schema file not found: " . $schemaPath);
        }
        
        $schema = file_get_contents($schemaPath);
        
        if ($schema === false) {
            throw new Exception("Could not read schema file");
        }
        
        // Execute the comprehensive schema
        $pdo->exec($schema);
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Database initialization failed: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Schema loading failed: " . $e->getMessage());
        return false;
    }
}

// Run initialization if called directly
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    if (initializeDatabase()) {
        echo "Database initialized successfully with comprehensive schema!\n";
        echo "Tables created: pastes, users, paste_views, paste_templates, collections, and more.\n";
        echo "Default templates have been inserted.\n";
    } else {
        echo "Database initialization failed. Check logs for details.\n";
        exit(1);
    }
}
?>
