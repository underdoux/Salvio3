<?php
class User {
    private $db;
    
    public function __construct() {
        $this->db = new Database;
    }
    
    // Find user by username
    public function findByUsername($username) {
        $this->db->query('SELECT * FROM users WHERE username = :username AND deleted_at IS NULL');
        $this->db->bind(':username', $username);
        
        return $this->db->single();
    }
    
    // Find user by email
    public function findByEmail($email) {
        $this->db->query('SELECT * FROM users WHERE email = :email AND deleted_at IS NULL');
        $this->db->bind(':email', $email);
        
        return $this->db->single();
    }
    
    // Find user by ID
    public function findById($id) {
        $this->db->query('SELECT * FROM users WHERE id = :id AND deleted_at IS NULL');
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    // Create new user
    public function create($data) {
        $this->db->query('INSERT INTO users (name, username, email, password, role, created_at) 
                         VALUES (:name, :username, :email, :password, :role, NOW())');
        
        // Bind values
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password', password_hash($data['password'], PASSWORD_DEFAULT));
        $this->db->bind(':role', $data['role']);
        
        // Execute
        return $this->db->execute();
    }
    
    // Update user
    public function update($data) {
        $sql = 'UPDATE users SET 
                name = :name, 
                email = :email,
                updated_at = NOW()';
        
        // Only update password if provided
        if(!empty($data['password'])) {
            $sql .= ', password = :password';
        }
        
        $sql .= ' WHERE id = :id';
        
        $this->db->query($sql);
        
        // Bind values
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':id', $data['id']);
        
        if(!empty($data['password'])) {
            $this->db->bind(':password', password_hash($data['password'], PASSWORD_DEFAULT));
        }
        
        // Execute
        return $this->db->execute();
    }
    
    // Soft delete user
    public function delete($id) {
        $this->db->query('UPDATE users SET deleted_at = NOW() WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    // Get all active users
    public function getAll() {
        $this->db->query('SELECT id, name, username, email, role, created_at, updated_at 
                         FROM users 
                         WHERE deleted_at IS NULL 
                         ORDER BY name ASC');
        
        return $this->db->resultSet();
    }
    
    // Change password
    public function changePassword($userId, $newPassword) {
        $this->db->query('UPDATE users SET 
                         password = :password,
                         updated_at = NOW() 
                         WHERE id = :id');
        
        $this->db->bind(':password', password_hash($newPassword, PASSWORD_DEFAULT));
        $this->db->bind(':id', $userId);
        
        return $this->db->execute();
    }
    
    // Verify current password
    public function verifyPassword($userId, $password) {
        $this->db->query('SELECT password FROM users WHERE id = :id');
        $this->db->bind(':id', $userId);
        
        $user = $this->db->single();
        
        return $user && password_verify($password, $user->password);
    }
}
