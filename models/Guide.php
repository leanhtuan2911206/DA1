<?php

// Kế thừa BaseModel để có sẵn kết nối CSDL
class Guide extends BaseModel
{
    protected $table = 'hdv'; // Tên bảng trong CSDL (dựa trên ảnh image_81a4e9.jpg)

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Lấy tất cả danh sách Hướng dẫn viên (HDV)
     * * @return array Danh sách HDV
     */
    public function getAll()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY HoTen ASC";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Guide::getAll: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Đếm tổng số lượng Hướng dẫn viên (Dùng cho AdminController)
     * * @return int Tổng số HDV
     */
    public function countAll()
    {
        $sql = "SELECT count(HDV_ID) FROM {$this->table}";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error in Guide::countAll: " . $e->getMessage());
            return 0;
        }
    }
}