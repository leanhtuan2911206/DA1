<?php 

// class BaseModel phải được định nghĩa trước đó
class TourCategory extends BaseModel 
{
    protected $table = 'tour_categories'; // Tên bảng danh mục

    /**
     * Lấy danh sách danh mục và số lượng tour liên quan.
     * @return array Danh sách danh mục.
     */
    public function getAll()
    {
        try {
            // Query đơn giản: SELECT * FROM tour_categories
            $sql = "SELECT * FROM `tour_categories`";
            $stmt = $this->pdo->query($sql);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Thêm tour_count cho mỗi danh mục
            foreach ($categories as &$category) {
                $tourCount = 0;
                try {
                    // Đếm số tour thuộc danh mục này
                    $countSql = "SELECT COUNT(*) FROM tours WHERE category_id = :id";
                    $countStmt = $this->pdo->prepare($countSql);
                    $countStmt->bindValue(':id', $category['id'], PDO::PARAM_INT);
                    $countStmt->execute();
                    $tourCount = (int) $countStmt->fetchColumn();
                } catch (PDOException $e) {
                    // Nếu lỗi, giữ tour_count = 0
                    error_log("Error counting tours for category {$category['id']}: " . $e->getMessage());
                }
                $category['tour_count'] = $tourCount;
            }
            
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

