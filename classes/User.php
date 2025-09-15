<?php
class User {
    private $db;
    public function __construct($db) {
        $this->db = $db;
    }
    // Add your user methods here
    public function getById($id) {
        // Example stub, replace with real DB logic
        return [
            'id' => $id,
            'username' => 'demo',
            'role' => 'user'
        ];
    }
}
