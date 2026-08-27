<?php

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->connection->connect_error) {
            throw new Exception("Connection failed: " . $this->connection->connect_error);
        }
        
        $this->connection->set_charset("utf8mb4");
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql) {
        $result = $this->connection->query($sql);
        if (!$result) {
            throw new Exception("Query failed: " . $this->connection->error);
        }
        return $result;
    }
    
    public function prepare($sql) {
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->connection->error);
        }
        return $stmt;
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->prepare($sql);
        
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function fetchOne($sql, $params = []) {
        $result = $this->fetchAll($sql, $params);
        return $result[0] ?? null;
    }
    
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $values = array_values($data);
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->prepare($sql);
        
        $types = str_repeat('s', count($values));
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        
        return $stmt->insert_id;
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach ($data as $column => $value) {
            $setClause[] = "$column = ?";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE $table SET $setClause WHERE $where";
        $stmt = $this->prepare($sql);
        
        $values = array_merge(array_values($data), $whereParams);
        $types = str_repeat('s', count($values));
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->prepare($sql);
        
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        return $stmt->execute();
    }
    
    public function beginTransaction() {
        $this->connection->begin_transaction();
    }
    
    public function commit() {
        $this->connection->commit();
    }
    
    public function rollback() {
        $this->connection->rollback();
    }
    
    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

?>
