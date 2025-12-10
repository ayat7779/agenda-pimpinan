<?php
// File: config/database.php

class DatabaseConfig {
    const HOST = 'localhost';
    const USERNAME = 'root';
    const PASSWORD = '';
    const DATABASE = 'dbagenda';
    const CHARSET = 'utf8mb4';
    
    public static function getConnection() {
        $conn = new mysqli(self::HOST, self::USERNAME, self::PASSWORD, self::DATABASE);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset(self::CHARSET);
        return $conn;
    }
}