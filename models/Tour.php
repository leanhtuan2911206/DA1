<?php

class Tour extends BaseModel
{
    protected $table = 'tours';

    public function countAll(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$this->table}");
        return (int) $stmt->fetchColumn();
    }

    public function listDashboard(int $limit = 10): array
    {
        $sql = "
            SELECT 
                t.id,
                t.name,
                t.price,
                t.itinerary AS place,
                t.policy,
                t.created_at,
                tc.name AS type,
                'Hoạt động' AS status
            FROM {$this->table} AS t
            LEFT JOIN tour_categories AS tc ON tc.id = t.category_id
            ORDER BY t.created_at DESC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách tour phục vụ trang quản lý cùng bộ lọc cơ bản.
     */
    public function listWithCategory(array $filters = []): array
    {
        $sql = "
            SELECT 
                t.*,
                tc.name AS category_name,
                'Hoạt động' AS status
            FROM {$this->table} AS t
            LEFT JOIN tour_categories AS tc ON tc.id = t.category_id
        ";

        $conditions = [];
        $params = [];

        if (!empty($filters['keyword'])) {
            $conditions[] = "(t.name LIKE :keyword OR t.description LIKE :keyword)";
            $params[':keyword'] = '%' . $filters['keyword'] . '%';
        }

        if (!empty($filters['category_id'])) {
            $conditions[] = "t.category_id = :category_id";
            $params[':category_id'] = (int) $filters['category_id'];
        }

        if (!empty($filters['destination'])) {
            $conditions[] = "(t.itinerary LIKE :destination OR t.policy LIKE :destination)";
            $params[':destination'] = '%' . $filters['destination'] . '%';
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $order = ' ORDER BY t.created_at DESC';
        if (($filters['price_order'] ?? '') === 'asc') {
            $order = ' ORDER BY t.price ASC';
        } elseif (($filters['price_order'] ?? '') === 'desc') {
            $order = ' ORDER BY t.price DESC';
        }

        $sql .= $order;

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $paramType);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }
}

