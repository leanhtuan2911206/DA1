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
    public function insert($name)
    {
        $sql = "INSERT INTO {$this->table} (name) VALUES (?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$name]); 
    }
    
    // ... Thêm các phương thức khác như getOne(), update(), delete() sau này
}

