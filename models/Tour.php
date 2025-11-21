<?php

class Tour extends BaseModel
{
    protected $table = 'tours';
    protected $lastError = null;

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

    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $name, $category_id, $price, $description = null, $itinerary = null, $policy = null, $image = null)
    {
        $sql = "UPDATE {$this->table} SET name = ?, category_id = ?, price = ?, description = ?, itinerary = ?, policy = ?, image = COALESCE(?, image), updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$name, $category_id, $price, $description, $itinerary, $policy, $image, $id]);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function insert($name, $category_id, $price = 0, $description = null, $itinerary = null, $policy = null, $image = null)
    {
        // Note: the `tours` table in this project contains columns: id, category_id, name, description, price, image, policy, itinerary, created_at
        // We insert only the columns that exist (set created_at to NOW()) and do not assume updated_at exists.
        $sql = "INSERT INTO {$this->table} (name, category_id, price, description, itinerary, policy, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        try {
            $stmt = $this->pdo->prepare($sql);
            $params = [$name, $category_id, $price, $description, $itinerary, $policy, $image];
            $res = $stmt->execute($params);
            if ($res) {
                return (int) $this->pdo->lastInsertId();
            }

            $this->lastError = $stmt->errorInfo();
            error_log('Tour::insert - execute returned false; errorInfo: ' . json_encode($this->lastError));
            return false;
        } catch (PDOException $e) {
            $this->lastError = ['exception' => $e->getMessage(), 'params' => ['name' => $name, 'category_id' => $category_id, 'price' => $price, 'image' => $image]];
            error_log('Tour::insert exception: ' . $e->getMessage() . ' | params: ' . json_encode([
                'name' => $name,
                'category_id' => $category_id,
                'price' => $price,
                'image' => $image,
            ]));
            return false;
        }
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Resequence primary key ids so they become contiguous starting at 1.
     * WARNING: This rewrites primary keys and will break foreign keys if other tables reference `tours.id`.
     * Use only if there are no FK dependencies.
     * @return bool
     */
    public function resequenceIds(): bool
    {
        try {
            $this->pdo->beginTransaction();
            // reset user variable then update ids in order
            $this->pdo->exec("SET @i = 0");
            $this->pdo->exec("UPDATE {$this->table} SET id = (@i := @i + 1) ORDER BY id");
            // reset auto-increment to next value
            $this->pdo->exec("ALTER TABLE {$this->table} AUTO_INCREMENT = 1");
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            try { $this->pdo->rollBack(); } catch (Throwable $_) {}
            error_log('Tour::resequenceIds error: ' . $e->getMessage());
            return false;
        }
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

