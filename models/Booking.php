<?php

class Booking extends BaseModel
{
    protected $table = 'bookings';

    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $stmt = $this->pdo->query($sql);
        return (int) $stmt->fetchColumn();
    }

    public function dailyCountsByMonth(int $year, int $month): array
    {
        $col = $this->detectDateColumn();
        if (!$col) {
            return [];
        }

        $sql = "SELECT DAY($col) AS d, COUNT(*) AS c FROM {$this->table} WHERE YEAR($col)=:y AND MONTH($col)=:m GROUP BY DAY($col) ORDER BY d";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':y', $year, PDO::PARAM_INT);
        $stmt->bindValue(':m', $month, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['d']] = (int)$r['c'];
        }
        return $out;
    }

    protected function detectDateColumn(): ?string
    {
        $sql = "SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.columns WHERE table_schema = :db AND table_name = :tbl";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':db', DB_NAME, PDO::PARAM_STR);
        $stmt->bindValue(':tbl', $this->table, PDO::PARAM_STR);
        $stmt->execute();
        $cols = $stmt->fetchAll();
        $candidates = [];
        foreach ($cols as $c) {
            $dt = strtolower($c['DATA_TYPE']);
            if (in_array($dt, ['date','datetime','timestamp'])) {
                $candidates[] = $c['COLUMN_NAME'];
            }
        }
        $prior = ['booking_date','created_at','date','ngay_dat','ordered_at'];
        foreach ($prior as $p) {
            if (in_array($p, $candidates, true)) {
                return $p;
            }
        }
        return $candidates[0] ?? null;
    }
}