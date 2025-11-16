<?php

class Tour extends BaseModel
{
    protected $table = 'tours';

    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $stmt = $this->pdo->query($sql);
        return (int) $stmt->fetchColumn();
    }

    public function listDashboard(int $limit = 10): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY id DESC LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $mapped = [];
        foreach ($rows as $row) {
            $name = $row['name'] ?? ($row['ten_tour'] ?? ($row['title'] ?? ''));
            $type = $row['type'] ?? ($row['loai_tour'] ?? '');
            $place = $row['location'] ?? ($row['dia_diem'] ?? '');
            $price = $row['price'] ?? ($row['gia'] ?? null);
            $status = $row['status'] ?? ($row['trang_thai'] ?? '');
            $revenue = $row['revenue'] ?? null;

            $mapped[] = [
                'name' => $name,
                'type' => $type,
                'place' => $place,
                'price' => $price,
                'revenue' => $revenue,
                'status' => $status,
            ];
        }

        return $mapped;
    }
}