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

    public function listSimple(array $filters = []): array
    {
        $sql = "SELECT b.id, b.tour_id, b.start_date, b.customer_name, b.total_people, t.name AS tour_name FROM {$this->table} AS b LEFT JOIN tours AS t ON t.id = b.tour_id";
        $conditions = [];
        $params = [];
        if (!empty($filters['tour_id'])) { $conditions[] = 'b.tour_id = :tour_id'; $params[':tour_id'] = (int)$filters['tour_id']; }
        if (!empty($filters['start_date_from'])) { $conditions[] = 'b.start_date >= :from'; $params[':from'] = $filters['start_date_from']; }
        if (!empty($filters['start_date_to'])) { $conditions[] = 'b.start_date <= :to'; $params[':to'] = $filters['start_date_to']; }
        if ($conditions) { $sql .= ' WHERE ' . implode(' AND ', $conditions); }
        $sql .= ' ORDER BY b.start_date DESC, b.created_at DESC, b.id DESC';
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $k => $v) { $stmt->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR); }
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) { return []; }
    }

    /**
     * Lấy danh sách booking theo tour, nhóm theo tour_id
     */
    public function getBookingsGroupedByTour(array $filters = []): array
    {
        $sql = "
            SELECT 
                b.*,
                t.name AS tour_name,
                t.price AS tour_price,
                t.itinerary AS tour_itinerary,
                tc.name AS category_name
            FROM {$this->table} AS b
            LEFT JOIN tours AS t ON t.id = b.tour_id
            LEFT JOIN tour_categories AS tc ON tc.id = t.category_id
        ";

        $conditions = [];
        $params = [];

        if (!empty($filters['tour_id'])) {
            $conditions[] = "b.tour_id = :tour_id";
            $params[':tour_id'] = (int) $filters['tour_id'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = "b.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['booking_type'])) {
            $conditions[] = "b.booking_type = :booking_type";
            $params[':booking_type'] = $filters['booking_type'];
        }

        if (!empty($filters['customer_name'])) {
            $conditions[] = "b.customer_name LIKE :customer_name";
            $params[':customer_name'] = '%' . $filters['customer_name'] . '%';
        }

        if (!empty($filters['start_date_from'])) {
            $conditions[] = "b.start_date >= :start_date_from";
            $params[':start_date_from'] = $filters['start_date_from'];
        }

        if (!empty($filters['start_date_to'])) {
            $conditions[] = "b.start_date <= :start_date_to";
            $params[':start_date_to'] = $filters['start_date_to'];
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        // Sort để booking mới nhất hiện lên đầu tiên trong mỗi tour
        $sql .= ' ORDER BY t.name ASC, b.created_at DESC, b.start_date ASC, b.id DESC';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $paramType);
        }
        $stmt->execute();

        $bookings = $stmt->fetchAll();
        
        // Nhóm booking theo tour_id
        $grouped = [];
        foreach ($bookings as $booking) {
            $tourId = $booking['tour_id'] ?? 0;
            if (!isset($grouped[$tourId])) {
                $grouped[$tourId] = [
                    'tour_id' => $tourId,
                    'tour_name' => $booking['tour_name'] ?? 'Chưa xác định',
                    'tour_price' => $booking['tour_price'] ?? 0,
                    'tour_itinerary' => $booking['tour_itinerary'] ?? '',
                    'category_name' => $booking['category_name'] ?? '',
                    'bookings' => []
                ];
            }
            $grouped[$tourId]['bookings'][] = $booking;
        }

        return $grouped;
    }

    /**
     * Lấy booking theo ID kèm thông tin tour
     */
    public function findWithTour($id)
    {
        $sql = "
            SELECT 
                b.*,
                t.name AS tour_name,
                t.price AS tour_price,
                t.itinerary AS tour_itinerary,
                t.description AS tour_description,
                tc.name AS category_name
            FROM {$this->table} AS b
            LEFT JOIN tours AS t ON t.id = b.tour_id
            LEFT JOIN tour_categories AS tc ON tc.id = t.category_id
            WHERE b.id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Tạo booking mới
     */
    public function create(array $data): int|false
    {
        $sql = "
            INSERT INTO {$this->table} 
            (tour_id, start_date, customer_name, customer_phone, customer_email, 
             total_people, booking_type, special_requests, deposit_amount, status, created_at)
            VALUES 
            (:tour_id, :start_date, :customer_name, :customer_phone, :customer_email,
             :total_people, :booking_type, :special_requests, :deposit_amount, :status, NOW())
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':tour_id', $data['tour_id'], PDO::PARAM_INT);
            $stmt->bindValue(':start_date', $data['start_date'], PDO::PARAM_STR);
            $stmt->bindValue(':customer_name', $data['customer_name'], PDO::PARAM_STR);
            $stmt->bindValue(':customer_phone', $data['customer_phone'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':customer_email', $data['customer_email'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':total_people', $data['total_people'], PDO::PARAM_INT);
            $stmt->bindValue(':booking_type', $data['booking_type'] ?? 'individual', PDO::PARAM_STR);
            $stmt->bindValue(':special_requests', $data['special_requests'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':deposit_amount', $data['deposit_amount'] ?? 0.00, PDO::PARAM_STR);
            $stmt->bindValue(':status', $data['status'] ?? 'pending', PDO::PARAM_STR);

            if ($stmt->execute()) {
                $bookingId = (int) $this->pdo->lastInsertId();
                // Ghi lịch sử thay đổi trạng thái
                $this->recordStatusHistory($bookingId, null, $data['status'] ?? 'pending', $data['changed_by'] ?? null);
                return $bookingId;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Cập nhật booking
     */
    public function update($id, array $data): bool
    {
        $oldBooking = $this->find($id);
        if (!$oldBooking) {
            return false;
        }

        $sql = "
            UPDATE {$this->table} 
            SET tour_id = :tour_id,
                start_date = :start_date,
                customer_name = :customer_name,
                customer_phone = :customer_phone,
                customer_email = :customer_email,
                total_people = :total_people,
                booking_type = :booking_type,
                special_requests = :special_requests,
                deposit_amount = :deposit_amount,
                status = :status,
                updated_at = NOW()
            WHERE id = :id
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':tour_id', $data['tour_id'], PDO::PARAM_INT);
            $stmt->bindValue(':start_date', $data['start_date'], PDO::PARAM_STR);
            $stmt->bindValue(':customer_name', $data['customer_name'], PDO::PARAM_STR);
            $stmt->bindValue(':customer_phone', $data['customer_phone'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':customer_email', $data['customer_email'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':total_people', $data['total_people'], PDO::PARAM_INT);
            $stmt->bindValue(':booking_type', $data['booking_type'] ?? 'individual', PDO::PARAM_STR);
            $stmt->bindValue(':special_requests', $data['special_requests'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':deposit_amount', $data['deposit_amount'] ?? 0.00, PDO::PARAM_STR);
            $stmt->bindValue(':status', $data['status'] ?? 'pending', PDO::PARAM_STR);

            $result = $stmt->execute();

            // Ghi lịch sử nếu trạng thái thay đổi
            if ($result && isset($data['status']) && $oldBooking['status'] !== $data['status']) {
                $this->recordStatusHistory($id, $oldBooking['status'], $data['status'], $data['changed_by'] ?? null);
            }

            return $result;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Cập nhật chỉ trạng thái booking
     * Tự động đồng bộ payment_status cho tất cả khách trong booking
     */
    public function updateStatus($id, string $newStatus, ?int $changedBy = null): bool
    {
        $oldBooking = $this->find($id);
        if (!$oldBooking) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET status = :status, updated_at = NOW() WHERE id = :id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':status', $newStatus, PDO::PARAM_STR);
            
            $result = $stmt->execute();

            // Tự động đồng bộ payment_status cho tất cả khách trong booking
            // Khi booking status = 'deposit' thì payment_status của khách = 'deposit'
            // Khi booking status = 'paid' hoặc 'completed' thì payment_status của khách = 'paid'
            if ($result && $oldBooking['status'] !== $newStatus) {
                $customerPaymentStatus = null;
                if ($newStatus === 'deposit') {
                    $customerPaymentStatus = 'deposit';
                } elseif (in_array($newStatus, ['paid', 'completed', 'confirmed'])) {
                    $customerPaymentStatus = 'paid';
                } elseif ($newStatus === 'pending' || $newStatus === 'cancelled') {
                    $customerPaymentStatus = 'unpaid';
                }
                
                if ($customerPaymentStatus !== null) {
                    try {
                        $customerModel = new Customer();
                        $customers = $customerModel->getByBooking($id);
                        foreach ($customers as $customer) {
                            $customerId = (int)($customer['id'] ?? 0);
                            if ($customerId > 0 && ($customer['payment_status'] ?? '') !== $customerPaymentStatus) {
                                $customerModel->update($customerId, [
                                    'payment_status' => $customerPaymentStatus,
                                    'full_name' => $customer['full_name'] ?? '',
                                    'gender' => $customer['gender'] ?? null,
                                    'date_of_birth' => $customer['date_of_birth'] ?? null,
                                    'id_type' => $customer['id_type'] ?? null,
                                    'id_number' => $customer['id_number'] ?? null,
                                    'contact_phone' => $customer['contact_phone'] ?? null,
                                    'email' => $customer['email'] ?? null,
                                    'address' => $customer['address'] ?? null,
                                    'special_requests' => $customer['special_requests'] ?? null,
                                ]);
                            }
                        }
                    } catch (Throwable $e) {
                    }
                }
            }

            // Ghi lịch sử thay đổi trạng thái
            if ($result && $oldBooking['status'] !== $newStatus) {
                $this->recordStatusHistory($id, $oldBooking['status'], $newStatus, $changedBy);
            }

            return $result;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Ghi lịch sử thay đổi trạng thái vào booking_history
     */
    public function recordStatusHistory(int $bookingId, ?string $oldStatus, string $newStatus, ?int $changedBy = null): bool
    {
        $sql = "
            INSERT INTO booking_history 
            (booking_id, old_status, new_status, changed_by, changed_at)
            VALUES 
            (:booking_id, :old_status, :new_status, :changed_by, NOW())
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
            $stmt->bindValue(':old_status', $oldStatus, PDO::PARAM_STR);
            $stmt->bindValue(':new_status', $newStatus, PDO::PARAM_STR);
            $stmt->bindValue(':changed_by', $changedBy, $changedBy ? PDO::PARAM_INT : PDO::PARAM_NULL);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Lấy lịch sử thay đổi trạng thái của booking
     */
    public function getStatusHistory($bookingId): array
    {
        $sql = "
            SELECT 
                bh.*,
                u.name AS changed_by_name
            FROM booking_history AS bh
            LEFT JOIN users AS u ON u.id = bh.changed_by
            WHERE bh.booking_id = ?
            ORDER BY bh.changed_at DESC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$bookingId]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy booking theo ID
     */
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Xóa booking
     */
    public function delete($id): bool
    {
        try {
            $this->pdo->beginTransaction();

            // Xóa lịch sử trước
            $sqlHistory = "DELETE FROM booking_history WHERE booking_id = ?";
            $stmtHistory = $this->pdo->prepare($sqlHistory);
            $stmtHistory->execute([$id]);

            // Xóa booking
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);

            if ($result) {
                $this->pdo->commit();
                // Cập nhật lại ID sau khi commit (tránh conflict transaction)
                $this->resequenceIds();
                return true;
            } else {
                $this->pdo->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Cập nhật lại ID booking để liên tục (1, 2, 3, ...)
     * Đơn giản: đánh số lại từ đầu
     */
    public function resequenceIds(): bool
    {
        try {
            // Tắt foreign key check tạm thời
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            // Lấy tất cả booking theo thứ tự
            $sql = "SELECT id FROM {$this->table} ORDER BY id ASC";
            $stmt = $this->pdo->query($sql);
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Nếu không có booking nào, reset auto increment
            if (empty($bookings)) {
                $this->pdo->exec("ALTER TABLE {$this->table} AUTO_INCREMENT = 1");
                $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                return true;
            }

            // Bước 1: Chuyển tất cả ID sang ID tạm thời lớn (tránh conflict)
            $tempStartId = 999999;
            foreach ($bookings as $index => $booking) {
                $oldId = (int)$booking['id'];
                $tempId = $tempStartId + $index;
                
                // Cập nhật booking_history với ID tạm (nếu có)
                $sqlHistory = "UPDATE booking_history SET booking_id = ? WHERE booking_id = ?";
                $stmtHistory = $this->pdo->prepare($sqlHistory);
                $stmtHistory->execute([$tempId, $oldId]);
                
                // Cập nhật ID booking sang ID tạm
                $sql = "UPDATE {$this->table} SET id = ? WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$tempId, $oldId]);
            }

            // Bước 2: Đánh số lại từ 1, 2, 3...
            $newId = 1;
            foreach ($bookings as $index => $booking) {
                $tempId = $tempStartId + $index;
                
                // Cập nhật booking_history với ID mới (nếu có)
                $sqlHistory = "UPDATE booking_history SET booking_id = ? WHERE booking_id = ?";
                $stmtHistory = $this->pdo->prepare($sqlHistory);
                $stmtHistory->execute([$newId, $tempId]);
                
                // Cập nhật ID booking về ID mới
                $sql = "UPDATE {$this->table} SET id = ? WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$newId, $tempId]);
                
                $newId++;
            }

            // Bật lại foreign key check
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            // Reset auto increment để ID tiếp theo là số tiếp theo
            $maxId = $this->pdo->query("SELECT MAX(id) FROM {$this->table}")->fetchColumn();
            $nextId = (int)$maxId + 1;
            $this->pdo->exec("ALTER TABLE {$this->table} AUTO_INCREMENT = $nextId");

            return true;
        } catch (PDOException $e) {
            try {
                $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            } catch (PDOException $_) {}
            return false;
        }
    }
}
