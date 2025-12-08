<?php

class TourLog extends BaseModel
{
    protected $table = 'tour_logs';

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    protected function ensureSchema(): void
    {
        try {
            $this->pdo->query("SELECT 1 FROM {$this->table} LIMIT 1");
        } catch (Throwable $e) {
            $sql = "
                CREATE TABLE IF NOT EXISTS {$this->table} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    tour_id INT NOT NULL,
                    guide_id INT NULL,
                    itinerary_id INT NULL,
                    log_date DATE NULL,
                    log_type VARCHAR(50) NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    status VARCHAR(50) DEFAULT 'pending',
                    rating INT NULL,
                    image_path VARCHAR(255) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_tour (tour_id),
                    INDEX idx_guide (guide_id),
                    INDEX idx_itinerary (itinerary_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            $this->pdo->exec($sql);
        }
        try {
            $hasLogDate = (int)$this->pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '{$this->table}' AND column_name = 'log_date'")->fetchColumn() > 0;
            if (!$hasLogDate) {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN log_date DATE NULL AFTER itinerary_id");
            }
            $hasImage = (int)$this->pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '{$this->table}' AND column_name = 'image_path'")->fetchColumn() > 0;
            if (!$hasImage) {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN image_path VARCHAR(255) NULL AFTER rating");
            }
        } catch (Throwable $_) {}
    }

    public function create(array $data): int|false
    {
        $cols = ['tour_id','log_type','title','description','status','rating'];
        $place = [':tour_id',':log_type',':title',':description',':status',':rating'];
        if (isset($data['itinerary_id'])) { $cols[] = 'itinerary_id'; $place[] = ':itinerary_id'; }
        if (isset($data['log_date'])) { $cols[] = 'log_date'; $place[] = ':log_date'; }
        if (isset($data['guide_id'])) { $cols[] = 'guide_id'; $place[] = ':guide_id'; }
        if (isset($data['image_path'])) { $cols[] = 'image_path'; $place[] = ':image_path'; }
        $sql = 'INSERT INTO ' . $this->table . ' (' . implode(', ', $cols) . ") VALUES (" . implode(', ', $place) . ')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':tour_id', (int)$data['tour_id'], PDO::PARAM_INT);
        $stmt->bindValue(':log_type', $data['log_type']);
        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'pending');
        if (isset($data['rating']) && $data['rating'] !== '') {
            $stmt->bindValue(':rating', (int)$data['rating'], PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':rating', null, PDO::PARAM_NULL);
        }
        if (isset($data['itinerary_id'])) { $stmt->bindValue(':itinerary_id', (int)$data['itinerary_id'], PDO::PARAM_INT); }
        if (isset($data['log_date'])) { $stmt->bindValue(':log_date', $data['log_date']); }
        if (isset($data['guide_id'])) { $stmt->bindValue(':guide_id', (int)$data['guide_id'], PDO::PARAM_INT); }
        if (isset($data['image_path'])) { $stmt->bindValue(':image_path', $data['image_path']); }
        if ($stmt->execute()) { return (int)$this->pdo->lastInsertId(); }
        return false;
    }

    public function getByTourId(int $tourId, ?int $guideId = null): array
    {
        try {
            $hasHdv = (int)$this->pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'hdv'")->fetchColumn() > 0;
            $sql = 'SELECT tl.*' . ($hasHdv ? ', h.HoTen AS guide_name' : '') . ' FROM ' . $this->table . ' tl' . ($hasHdv ? ' LEFT JOIN hdv h ON h.HDV_ID = tl.guide_id' : '') . ' WHERE tl.tour_id = :tid';
            $params = [':tid' => $tourId];
            if (!empty($guideId)) { $sql .= ' AND tl.guide_id = :gid'; $params[':gid'] = $guideId; }
            $sql .= ' ORDER BY tl.created_at DESC';
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $k=>$v) { $stmt->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR); }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    public function update(int $id, array $data): bool
    {
        $set = [];
        $bind = [];
        foreach (['title','description','status','rating','guide_id','itinerary_id','log_date','image_path'] as $col) {
            if (array_key_exists($col, $data)) { $set[] = "$col = :$col"; $bind[":$col"] = $data[$col]; }
        }
        if (!$set) { return false; }
        $sql = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $set) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        foreach ($bind as $k=>$v) {
            if ($k === ':rating' || $k === ':guide_id' || $k === ':itinerary_id') {
                $stmt->bindValue($k, ($v === '' ? null : (int)$v), $v === '' ? PDO::PARAM_NULL : PDO::PARAM_INT);
            } else {
                $stmt->bindValue($k, ($v === '' ? null : $v));
            }
        }
        return $stmt->execute();
    }

    public function find(int $id): array|false
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM ' . $this->table . ' WHERE id = :id LIMIT 1');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (Throwable $e) { return false; }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->table . ' WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
