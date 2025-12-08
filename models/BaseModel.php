<?php

class BaseModel
{
    protected $table;
    public $pdo;

    // Kết nối CSDL
    public function __construct()
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME);

        try {
            $this->pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
        } catch (PDOException $e) {
            // Xử lý lỗi kết nối
            die("Kết nối cơ sở dữ liệu thất bại: {$e->getMessage()}. Vui lòng thử lại sau.");
        }
    }

    // Hủy kết nối CSDL
    public function __destruct()
    {
        $this->pdo = null;
    }

    // Kiểm tra cột có tồn tại trong bảng hiện tại hay không
    protected function columnExists(string $table, string $column): bool
    {
        try {
            $sql = "SHOW COLUMNS FROM `" . $table . "` LIKE :col";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':col', $column, PDO::PARAM_STR);
            $stmt->execute();
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res !== false && !empty($res);
        } catch (Throwable $e) {
            return false;
        }
    }
}
