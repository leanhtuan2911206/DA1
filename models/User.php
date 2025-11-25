<?php

class User extends BaseModel
{
    protected $table = 'users';

    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $stmt = $this->pdo->query($sql);
        return (int) $stmt->fetchColumn();
    }
    
    public function getAll(): array
    {
        $sql = "SELECT id, name, email FROM {$this->table} ORDER BY id ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    
    public function create(array $data): bool
    {
        $sql = "INSERT INTO {$this->table} (name, email, password) VALUES (:name, :email, :password)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':name' => $data['name'] ?? '',
            ':email' => $data['email'] ?? '',
            ':password' => $data['password'] ?? '',
        ]);
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $setPassword = isset($data['password']) && $data['password'] !== '';
        if ($setPassword) {
            $sql = "UPDATE {$this->table} SET name = :name, email = :email, password = :password WHERE id = :id";
        } else {
            $sql = "UPDATE {$this->table} SET name = :name, email = :email WHERE id = :id";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':name', $data['name'] ?? '');
        $stmt->bindValue(':email', $data['email'] ?? '');
        if ($setPassword) {
            $stmt->bindValue(':password', $data['password']);
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}