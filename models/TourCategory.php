<?php 
class TourCategory extends BaseModel 
{
    protected $table = 'tour_categories';

    /**
     * Lấy danh sách danh mục và số lượng tour liên quan.
     * @return array Danh sách danh mục.
     */
    public function getAll()
    {
        try {
            // Tối ưu hóa: Sử dụng LEFT JOIN và GROUP BY để đếm số tour trong MỘT truy vấn duy nhất.
            $sql = "
                SELECT 
                    TC.*, 
                    COUNT(T.id) AS tour_count
                FROM 
                    `tour_categories` AS TC
                LEFT JOIN 
                    `tours` AS T ON TC.id = T.category_id
                GROUP BY 
                    TC.id, TC.name, TC.description, TC.slug, TC.status, TC.created_at, TC.updated_at
                ORDER BY 
                    TC.id ASC
            ";
            
            $stmt = $this->pdo->query($sql);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $categories;
            
        } catch (PDOException $e) {
            error_log("TourCategory::getAll() error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Phương thức thêm mới (insert)
     * @param string $name Tên danh mục.
     * @return void
     */
    public function insert($name, $description = null)
    {
        $sql = "INSERT INTO {$this->table} (name, description) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $name,
            $description
        ]); 
    }
    
    /**
     * Lấy chi tiết một danh mục theo id.
     *
     * @param int $id
     * @return array|false
     */
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Xóa danh mục theo id.
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Cập nhật danh mục.
     *
     * @param int $id
     * @param string $name
     * @param string|null $description
     * @return bool
     */
    public function update($id, $name, $description = null)
    {
        $sql = "UPDATE {$this->table} SET name = ?, description = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $name,
            $description,
            $id
        ]);
    }
    
    // ... Thêm các phương thức khác như getOne(), update() sau này
}

