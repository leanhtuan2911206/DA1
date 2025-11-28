<?php

class TourTemplate extends BaseModel
{
    protected $table = 'tour_templates';

    /**
     * Lấy tất cả tour templates
     * @return array
     */
    public function getAll(): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} ORDER BY id ASC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TourTemplate::getAll() error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy tour template theo ID
     * @param int $id
     * @return array|false
     */
    public function find($id)
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TourTemplate::find() error: " . $e->getMessage());
            return false;
        }
    }
}

