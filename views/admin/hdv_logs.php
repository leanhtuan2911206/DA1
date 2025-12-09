<?php
$tour = isset($tour) && is_array($tour) ? $tour : null;
$logs = isset($logs) && is_array($logs) ? $logs : [];
$tours = isset($tours) && is_array($tours) ? $tours : [];
$itinerary = isset($itinerary) && is_array($itinerary) ? $itinerary : [];
$editingLog = isset($editingLog) && is_array($editingLog) ? $editingLog : null;
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$guideId = isset($_SESSION['user']['guide_id']) ? (int)$_SESSION['user']['guide_id'] : 0;
?>
<main class="main-content">
    <?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <p class="text-uppercase text-muted small mb-1">HDV</p>
            <h2 class="page-title mb-0">Nhật ký tour</h2>
        </div>
        <div>
            <a class="btn btn-light" href="<?= BASE_URL ?>?action=partner&tab=assignments">Phân bổ của tôi</a>
        </div>
    </div>

    <?php if (!$tour): ?>
        <div class="card-like p-3">
            <?php if (empty($tours)): ?>
                <div class="alert alert-warning">
                    <h5 class="alert-heading">⚠️ Chưa có tour nào</h5>
                    <p class="mb-0">
                        <?php if ($guideId <= 0): ?>
                            Không tìm thấy thông tin HDV. Vui lòng liên hệ quản trị viên để được phân công tour.
                        <?php else: ?>
                            Bạn chưa được phân công tour nào. Vui lòng chờ quản trị viên phân công tour hoặc liên hệ để được hỗ trợ.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="mb-2">Chọn tour để ghi nhật ký:</div>
                <form method="get" action="<?= BASE_URL ?>">
                    <input type="hidden" name="action" value="partner-logs">
                    <div class="d-flex flex-wrap gap-2">
                        <select name="tour_id" class="form-select" style="min-width:260px;" required>
                            <option value="">-- Chọn tour --</option>
                            <?php foreach ($tours as $t): ?>
                                <option value="<?= (int)$t['id'] ?>">#<?= (int)$t['id'] ?> - <?= htmlspecialchars(removeVNPrefix($t['name'] ?? 'Tour')) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">Vào nhật ký</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        </main>
        <?php return; ?>
    <?php endif; ?>

    <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addLogModal">+ Thêm nhật ký</button>
    </div>

    <div class="modal fade" id="addLogModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">📝 Thêm nhật ký tour</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="post" action="<?= BASE_URL ?>?action=tour-logs-store" enctype="multipart/form-data" id="addLogForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Chọn tour *</label>
                        <select class="form-select" name="tour_id" id="add_tour_id" required>
                            <option value="">-- Chọn tour --</option>
                            <?php foreach ($tours as $t): ?>
                                <option value="<?= (int)$t['id'] ?>" <?= ((int)($tour['id'] ?? 0) === (int)$t['id'])?'selected':'' ?>>#<?= (int)$t['id'] ?> - <?= htmlspecialchars(removeVNPrefix($t['name'] ?? 'Tour')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if (!empty($bookings)): ?>
                    <div class="mb-3">
                        <label class="form-label">Chọn booking (nếu có nhiều booking)</label>
                        <select class="form-select" name="booking_id" id="add_booking_id">
                            <option value="">-- Tự động chọn booking đầu tiên --</option>
                            <?php foreach ($bookings as $b): ?>
                                <option value="<?= (int)$b['id'] ?>">
                                    Booking #<?= (int)$b['id'] ?> - <?= htmlspecialchars($b['customer_name'] ?? '') ?> 
                                    (<?= $b['start_date'] ? date('d/m/Y', strtotime($b['start_date'])) : '' ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($guideId > 0): ?><input type="hidden" name="guide_id" value="<?= (int)$guideId ?>"><?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Loại nhật ký *</label>
                        <select class="form-select" name="log_type" id="add_log_type" required>
                            <option value="daily">📅 Nhật ký ngày</option>
                            <option value="incident">⚠️ Sự cố</option>
                            <option value="feedback">💬 Phản hồi khách hàng</option>
                            <option value="rating">⭐ Đánh giá HDV</option>
                            <option value="timeline">🕐 Diễn biến tour</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ngày *</label>
                        <input type="date" class="form-control" name="log_date" id="add_log_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tiêu đề *</label>
                        <input type="text" class="form-control" name="title" id="add_title" placeholder="Nhập tiêu đề nhật ký" required>
                    </div>
                    
                    <!-- Section: Sự cố (Incident) -->
                    <div id="add_sections_incident" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Thời tiết</label>
                            <input type="text" class="form-control" name="weather" placeholder="Ví dụ: Nắng đẹp, mưa nhẹ, gió mạnh...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tình trạng sức khỏe khách</label>
                            <textarea class="form-control" name="health_status" rows="2" placeholder="Ghi chú về tình trạng sức khỏe của khách hàng"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hoạt động đặc biệt</label>
                            <textarea class="form-control" name="special_activities" rows="2" placeholder="Các hoạt động ngoài dự kiến hoặc đặc biệt"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cách xử lý *</label>
                            <textarea class="form-control" name="handling_notes" rows="3" placeholder="Mô tả cách xử lý tình huống ngoài dự kiến"></textarea>
                        </div>
                    </div>
                    
                    <!-- Section: Phản hồi khách hàng (Feedback) - Hiển thị khi log_type là feedback hoặc timeline -->
                    <?php $showFeedback = in_array($_POST['log_type'] ?? '', ['feedback', 'timeline']); ?>
                    <div id="add_sections_feedback" style="display: <?= $showFeedback ? 'block' : 'none' ?>;">
                        <div class="mb-3">
                            <label class="form-label">Phản hồi của khách hàng *</label>
                            <textarea class="form-control" name="customer_feedback" rows="4" placeholder="Ghi lại phản hồi, ý kiến của khách hàng về tour"></textarea>
                        </div>
                    </div>
                    
                    <!-- Section: Đánh giá HDV (Rating) - Hiển thị khi log_type là rating -->
                    <?php $showRating = ($_POST['log_type'] ?? '') === 'rating'; ?>
                    <div id="add_sections_rating" style="display: <?= $showRating ? 'block' : 'none' ?>;">
                        <div class="mb-3">
                            <label class="form-label">Đánh giá phối hợp (1-5)</label>
                            <select class="form-select" name="rating_coordination">
                                <option value="">-- Chọn --</option>
                                <option value="1">1 - Rất kém</option>
                                <option value="2">2 - Kém</option>
                                <option value="3">3 - Trung bình</option>
                                <option value="4">4 - Tốt</option>
                                <option value="5">5 - Rất tốt</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tinh thần làm việc (1-5)</label>
                            <select class="form-select" name="rating_spirit">
                                <option value="">-- Chọn --</option>
                                <option value="1">1 - Rất kém</option>
                                <option value="2">2 - Kém</option>
                                <option value="3">3 - Trung bình</option>
                                <option value="4">4 - Tốt</option>
                                <option value="5">5 - Rất tốt</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bình luận đánh giá</label>
                            <textarea class="form-control" name="rating_comment" rows="3" placeholder="Ghi chú thêm về đánh giá"></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mô tả / Ghi chú *</label>
                        <textarea class="form-control" name="description" id="add_description" rows="5" placeholder="Mô tả chi tiết về diễn biến tour, sự kiện, hoạt động..." required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Hình ảnh</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                        <small class="text-muted">Chọn ảnh minh họa cho nhật ký (nếu có)</small>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary">💾 Lưu nhật ký</button></div>
            </form>
        </div></div>
    </div>

    <?php if ($editingLog): ?>
    <div class="card-like">
        <div class="fw-semibold mb-2">Chỉnh sửa nhật ký</div>
        <form method="post" action="<?= BASE_URL ?>?action=tour-logs-update" enctype="multipart/form-data" class="d-flex flex-column gap-2">
            <input type="hidden" name="id" value="<?= (int)$editingLog['id'] ?>">
            <input type="hidden" name="tour_id" value="<?= (int)$tour['id'] ?>">
            <?php if ($guideId > 0): ?><input type="hidden" name="guide_id" value="<?= (int)$guideId ?>"><?php endif; ?>
            <input type="hidden" name="status" value="<?= htmlspecialchars($editingLog['status'] ?? 'pending') ?>">
            <input type="hidden" name="rating" value="<?= htmlspecialchars($editingLog['rating'] ?? '') ?>">
            <div>
                <label class="form-label">Ngày</label>
                <input type="date" class="form-control" name="log_date" value="<?= htmlspecialchars($editingLog['log_date'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Nội dung nhật ký</label>
                <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($editingLog['description'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="form-label">Hình ảnh (tải mới)</label>
                <input type="file" class="form-control" name="image" accept="image/*">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Cập nhật</button>
                <a class="btn btn-light" href="<?= BASE_URL ?>?action=partner-logs&tour_id=<?= (int)$tour['id'] ?>">Hủy</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="card-like mt-3">
        <div class="fw-semibold mb-2">Nhật ký gần đây</div>
        <?php if (empty($logs)):
            $fallback = null;
            if (!empty($_SESSION['last_log_inserted']) && (int)($_SESSION['last_log_inserted']['tour_id'] ?? 0) === (int)($tour['id'] ?? 0)) {
                $fallback = $_SESSION['last_log_inserted'];
            }
        ?>
            <?php if (!$fallback): ?>
                <div class="text-muted">Chưa có nhật ký cho tour này.</div>
            <?php else: ?>
                <div class="list-group">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold"><?= htmlspecialchars($fallback['title'] ?? '') ?></div>
                            <div class="small text-muted">
                                <?= !empty($fallback['log_date']) ? htmlspecialchars($fallback['log_date']) : htmlspecialchars($fallback['created_at'] ?? '') ?>
                            </div>
                            <?php if (!empty($fallback['description'])): ?>
                                <div class="small text-muted mt-1" style="max-width:700px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars($fallback['description']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if(!empty($fallback['image_path'])): ?>
                                <img src="<?= BASE_URL . $fallback['image_path'] ?>" style="width:48px;height:48px;object-fit:cover;border-radius:4px;" />
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="list-group">
                <?php $i=0; foreach ($logs as $log): if ($i++ >= 10) break; 
                    $logTypeText = [
                        'incident' => '⚠️ Sự cố',
                        'feedback' => '💬 Phản hồi',
                        'rating' => '⭐ Đánh giá HDV',
                        'timeline' => '🕐 Diễn biến',
                        'daily' => '📅 Nhật ký ngày'
                    ][$log['log_type'] ?? ''] ?? '📝 Khác';
                    
                    $logDate = $log['log_date'] ?? $log['created_at'] ?? '';
                    if ($logDate) {
                        try {
                            $dateObj = new DateTime($logDate);
                            $logDate = $dateObj->format('d/m/Y');
                        } catch (Exception $e) {
                            $logDate = date('d/m/Y', strtotime($logDate));
                        }
                    }
                ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-primary"><?= $logTypeText ?></span>
                                    <span class="fw-semibold"><?= htmlspecialchars($log['title'] ?? '') ?></span>
                                </div>
                                <div class="small text-muted mb-2">
                                    📅 <?= $logDate ?>
                                </div>
                                <?php if (!empty($log['description'])): ?>
                                    <div class="small text-muted mt-1" style="max-width:700px; white-space:pre-wrap; word-wrap:break-word;">
                                        <?= nl2br(htmlspecialchars(mb_substr($log['description'], 0, 300))) ?><?= mb_strlen($log['description']) > 300 ? '...' : '' ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <?php if(!empty($log['image_path'])): ?>
                                    <img src="<?= BASE_URL . $log['image_path'] ?>" 
                                         style="width:64px;height:64px;object-fit:cover;border-radius:4px;cursor:pointer;" 
                                         href="<?= BASE_URL . $log['image_path'] ?>" target="_blank"
                                         title="Click để xem ảnh lớn">
                                <?php endif; ?>
                                <div class="d-flex flex-column gap-1">
                                    <a href="<?= BASE_URL ?>?action=partner-logs&tour_id=<?= (int)$tour['id'] ?>&edit_id=<?= (int)$log['id'] ?>" 
                                       class="btn btn-sm btn-outline-secondary">✏️ Sửa</a>
                                    <a href="<?= BASE_URL ?>?action=tour-logs-delete&id=<?= (int)$log['id'] ?>&tour_id=<?= (int)$tour['id'] ?>"
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Xóa nhật ký này?')">🗑️ Xóa</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const logTypeSelect = document.getElementById('add_log_type');
    const incidentSection = document.getElementById('add_sections_incident');
    const feedbackSection = document.getElementById('add_sections_feedback');
    const ratingSection = document.getElementById('add_sections_rating');
    
    if (logTypeSelect) {
        function toggleSections() {
            const logType = logTypeSelect.value;
            
            // Ẩn tất cả sections trước
            if (incidentSection) incidentSection.style.display = 'none';
            if (feedbackSection) feedbackSection.style.display = 'none';
            if (ratingSection) ratingSection.style.display = 'none';
            
            // Xóa required từ tất cả các field trong các sections
            const allRequiredFields = document.querySelectorAll('#add_sections_incident [required], #add_sections_feedback [required], #add_sections_rating [required]');
            allRequiredFields.forEach(field => field.removeAttribute('required'));
            
            // Hiển thị section tương ứng và set required
            if (logType === 'incident') {
                if (incidentSection) incidentSection.style.display = 'block';
                const handlingField = document.querySelector('textarea[name="handling_notes"]');
                if (handlingField) handlingField.setAttribute('required', 'required');
            } else if (logType === 'feedback' || logType === 'timeline') {
                if (feedbackSection) feedbackSection.style.display = 'block';
                const feedbackField = document.querySelector('textarea[name="customer_feedback"]');
                if (feedbackField) feedbackField.setAttribute('required', 'required');
            } else if (logType === 'rating') {
                if (ratingSection) ratingSection.style.display = 'block';
            }
        }
        
        // Gọi ngay khi load trang
        toggleSections();
        
        // Lắng nghe sự kiện thay đổi
        logTypeSelect.addEventListener('change', toggleSections);
    }
    
    // Xử lý form submission - chỉ validate các field hiển thị
    const addLogForm = document.getElementById('addLogForm');
    if (addLogForm) {
        addLogForm.addEventListener('submit', function(e) {
            const logType = logTypeSelect ? logTypeSelect.value : '';
            let isValid = true;
            let errorMsg = '';
            
            // Validate các field bắt buộc chung
            const tourId = document.getElementById('add_tour_id');
            const logDate = document.getElementById('add_log_date');
            const title = document.getElementById('add_title');
            const description = document.getElementById('add_description');
            
            if (!tourId || !tourId.value || tourId.value === '') {
                isValid = false;
                errorMsg = 'Vui lòng chọn tour.';
            } else if (!logDate || !logDate.value) {
                isValid = false;
                errorMsg = 'Vui lòng chọn ngày nhật ký.';
            } else if (!title || !title.value.trim()) {
                isValid = false;
                errorMsg = 'Vui lòng nhập tiêu đề.';
            } else if (!description || !description.value.trim()) {
                isValid = false;
                errorMsg = 'Vui lòng nhập mô tả.';
            }
            
            // Validate theo loại nhật ký
            if (isValid && logType === 'incident') {
                const handling = document.querySelector('textarea[name="handling_notes"]');
                if (handling && (!handling.value || !handling.value.trim())) {
                    isValid = false;
                    errorMsg = 'Vui lòng nhập cách xử lý cho sự cố.';
                }
            } else if (isValid && (logType === 'feedback' || logType === 'timeline')) {
                const feedback = document.querySelector('textarea[name="customer_feedback"]');
                if (feedback && (!feedback.value || !feedback.value.trim())) {
                    isValid = false;
                    errorMsg = 'Vui lòng nhập phản hồi của khách hàng.';
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                alert(errorMsg);
                return false;
            }
        });
    }
});
</script>

</main>
