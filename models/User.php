<?php

class User extends BaseModel
{
    protected $table = 'users';

    public function countAll(): int
        // ... (hàm countAll giữ nguyên) ...
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $stmt = $this->pdo->query($sql);
        return (int) $stmt->fetchColumn();
    }
    
    // Thêm hàm tìm người dùng theo email
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }
}