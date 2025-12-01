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

    /**
     * Lấy tour template theo category_id (lấy template đầu tiên của danh mục)
     * @param int $categoryId
     * @return array|false
     */
    public function findByCategoryId($categoryId)
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE category_id = ? ORDER BY id ASC LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$categoryId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TourTemplate::findByCategoryId() error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy tất cả tour templates theo category_id
     * @param int $categoryId
     * @return array
     */
    public function getAllByCategoryId($categoryId): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE category_id = ? ORDER BY id ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$categoryId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TourTemplate::getAllByCategoryId() error: " . $e->getMessage());
            return [];
        }
    }
}

