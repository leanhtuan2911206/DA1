<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$guideId = isset($_SESSION['user']['guide_id']) ? (int)$_SESSION['user']['guide_id'] : 0;
$assignments = isset($assignments) && is_array($assignments) ? $assignments : [];
$feedbacks = isset($feedbacks) && is_array($feedbacks) ? $feedbacks : [];
$bookingInfo = isset($bookingInfo) && is_array($bookingInfo) ? $bookingInfo : null;
$feedbackModel = new GuideFeedback();
$feedbackTypes = $feedbackModel->getFeedbackTypes();

$currentType = $_GET['type'] ?? '';
$currentStatus = $_GET['status'] ?? '';
$currentBookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

$formOld = $_SESSION['feedback_form_old'] ?? [];
unset($_SESSION['feedback_form_old']);
?>
<main class="main-content">
    <style>
    .main-content{background:transparent;padding:0;padding-bottom:20px}
    .hdv-container{max-width:1200px;margin:0 auto;padding-bottom:0}
    .hdv-hero{background:#fff;border:2px solid #d9ccff;border-radius:18px;padding:14px;margin-bottom:14px;color:#111;box-shadow:0 8px 24px rgba(0,0,0,.08)}
    .hero-title{font-weight:700;margin:0 0 6px;font-size:20px;color:#1f2937}
    .hero-sub{opacity:.8;font-size:12px;color:#64748b}
    .card-like{background:#fff;border:2px solid #d9ccff;border-radius:14px;padding:16px;box-shadow:0 6px 20px rgba(0,0,0,.06);margin-bottom:16px}
    .card-like:last-child{margin-bottom:0}
    .feedback-item{background:#f8f9fa;border:1px solid #e9ecef;border-radius:10px;padding:14px;margin-bottom:12px}
    .feedback-item:last-child{margin-bottom:0}
    .feedback-item:hover{background:#f1f3f5;border-color:#d9ccff}
    .status-badge{display:inline-block;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:600}
    .status-pending{background:#fff3cd;color:#856404}
    .status-reviewed{background:#d1ecf1;color:#0c5460}
    .status-resolved{background:#d4edda;color:#155724}
    .rating-stars{color:#ffc107;font-size:16px}
    </style>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-bottom: 12px;">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-bottom: 12px;">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="hdv-container">
        <div class="hdv-hero">
            <h1 class="hero-title">📝 Phản hồi đánh giá</h1>
            <p class="hero-sub">HDV gửi ý kiến, nhận xét về chất lượng dịch vụ tour, khách sạn, nhà hàng, xe vận chuyển... sau mỗi chuyến đi để công ty cải thiện dịch vụ và lựa chọn đối tác phù hợp.</p>
        </div>

        <!-- Form tạo phản hồi mới -->
        <div class="card-like">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold">➕ Gửi phản hồi mới</h5>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleFormBtn">
                    <span id="toggleFormText">Thu gọn</span>
                </button>
            </div>
            <form method="post" action="<?= BASE_URL ?>?action=partner-feedback-store" id="feedbackForm" style="display: block;">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Loại phản hồi <span class="text-danger">*</span></label>
                        <select name="feedback_type" class="form-select" required id="feedback_type">
                            <option value="">-- Chọn loại --</option>
                            <?php foreach ($feedbackTypes as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>" <?= ($formOld['feedback_type'] ?? '') === $key ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Booking (tùy chọn)</label>
                        <select name="booking_id" class="form-select" id="booking_select">
                            <option value="">-- Chọn booking --</option>
                            <?php foreach ($assignments as $ass): ?>
                                <?php 
                                $assBookingId = (int)($ass['booking_id'] ?? 0);
                                $selected = ($formOld['booking_id'] ?? $currentBookingId) == $assBookingId ? 'selected' : '';
                                ?>
                                <option value="<?= $assBookingId ?>" <?= $selected ?>>
                                    Booking #<?= $assBookingId ?> - <?= htmlspecialchars($ass['tour_name'] ?? 'Tour') ?> 
                                    (<?= !empty($ass['start_date']) ? date('d/m/Y', strtotime($ass['start_date'])) : '' ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="Nhập tiêu đề phản hồi" required value="<?= htmlspecialchars($formOld['title'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nội dung <span class="text-danger">*</span></label>
                        <textarea name="content" class="form-control" rows="5" placeholder="Mô tả chi tiết về chất lượng dịch vụ, nhà cung cấp..." required><?= htmlspecialchars($formOld['content'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12 col-md-6" id="supplier_name_field" style="display: none;">
                        <label class="form-label">Tên nhà cung cấp</label>
                        <input type="text" name="supplier_name" class="form-control" placeholder="Tên khách sạn, nhà hàng, công ty vận chuyển..." value="<?= htmlspecialchars($formOld['supplier_name'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Điểm đánh giá (1-5)</label>
                        <select name="rating" class="form-select">
                            <option value="">-- Chọn điểm --</option>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= ($formOld['rating'] ?? '') == $i ? 'selected' : '' ?>>
                                    <?= $i ?> <?= str_repeat('⭐', $i) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Đề xuất cải thiện</label>
                        <textarea name="suggestions" class="form-control" rows="3" placeholder="Đề xuất cách cải thiện dịch vụ, lựa chọn đối tác phù hợp hơn..."><?= htmlspecialchars($formOld['suggestions'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            📤 Gửi phản hồi
                        </button>
                        <button type="reset" class="btn btn-outline-secondary">Làm mới</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Bộ lọc -->
        <div class="card-like">
            <h5 class="mb-3 fw-bold">🔍 Lọc phản hồi</h5>
            <form method="get" action="<?= BASE_URL ?>" class="row g-2">
                <input type="hidden" name="action" value="partner-feedback">
                <div class="col-12 col-md-4">
                    <label class="form-label small">Loại phản hồi</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">-- Tất cả --</option>
                        <?php foreach ($feedbackTypes as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $currentType === $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small">Trạng thái</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">-- Tất cả --</option>
                        <option value="pending" <?= $currentStatus === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                        <option value="reviewed" <?= $currentStatus === 'reviewed' ? 'selected' : '' ?>>Đã xem</option>
                        <option value="resolved" <?= $currentStatus === 'resolved' ? 'selected' : '' ?>>Đã xử lý</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">Lọc</button>
                        <a href="<?= BASE_URL ?>?action=partner-feedback" class="btn btn-outline-secondary btn-sm">Xóa bộ lọc</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Danh sách phản hồi -->
        <div class="card-like">
            <h5 class="mb-3 fw-bold">📋 Danh sách phản hồi (<?= count($feedbacks) ?>)</h5>
            <?php if (empty($feedbacks)): ?>
                <div class="text-center text-muted py-4">
                    <p class="mb-0">Chưa có phản hồi nào.</p>
                    <small>Hãy gửi phản hồi đầu tiên của bạn để giúp công ty cải thiện dịch vụ!</small>
                </div>
            <?php else: ?>
                <div class="feedback-list">
                    <?php foreach ($feedbacks as $feedback): ?>
                        <?php
                        $statusClass = 'status-pending';
                        $statusText = 'Chờ xử lý';
                        if ($feedback['status'] === 'reviewed') {
                            $statusClass = 'status-reviewed';
                            $statusText = 'Đã xem';
                        } elseif ($feedback['status'] === 'resolved') {
                            $statusClass = 'status-resolved';
                            $statusText = 'Đã xử lý';
                        }
                        
                        $typeLabel = $feedbackTypes[$feedback['feedback_type']] ?? $feedback['feedback_type'];
                        $createdDate = '';
                        if (!empty($feedback['created_at'])) {
                            try {
                                $dateObj = new DateTime($feedback['created_at']);
                                $createdDate = $dateObj->format('d/m/Y H:i');
                            } catch (Exception $e) {
                                $createdDate = date('d/m/Y H:i', strtotime($feedback['created_at']));
                            }
                        }
                        ?>
                        <div class="feedback-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-info"><?= htmlspecialchars($typeLabel) ?></span>
                                        <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                                        <?php if (!empty($feedback['rating'])): ?>
                                            <span class="rating-stars"><?= str_repeat('⭐', (int)$feedback['rating']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h6 class="mb-1 fw-bold"><?= htmlspecialchars($feedback['title']) ?></h6>
                                    <div class="small text-muted mb-2">
                                        📅 <?= $createdDate ?>
                                        <?php if (!empty($feedback['tour_name'])): ?>
                                            | 🎯 Tour: <?= htmlspecialchars($feedback['tour_name']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($feedback['booking_customer'])): ?>
                                            | 👤 Booking: <?= htmlspecialchars($feedback['booking_customer']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($feedback['supplier_name'])): ?>
                                            | 🏢 Nhà cung cấp: <?= htmlspecialchars($feedback['supplier_name']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-2">
                                <p class="mb-1" style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($feedback['content'])) ?></p>
                            </div>
                            <?php if (!empty($feedback['suggestions'])): ?>
                                <div class="alert alert-info mb-0 py-2 px-3" style="font-size: 13px;">
                                    <strong>💡 Đề xuất:</strong> <?= nl2br(htmlspecialchars($feedback['suggestions'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Hiển thị/ẩn form phản hồi và trường supplier_name
    document.addEventListener('DOMContentLoaded', function() {
        // Xử lý nút thu gọn/mở rộng form
        const toggleFormBtn = document.getElementById('toggleFormBtn');
        const feedbackForm = document.getElementById('feedbackForm');
        const toggleFormText = document.getElementById('toggleFormText');
        
        if (toggleFormBtn && feedbackForm && toggleFormText) {
            toggleFormBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (feedbackForm.style.display === 'none' || feedbackForm.style.display === '') {
                    feedbackForm.style.display = 'block';
                    toggleFormText.textContent = 'Thu gọn';
                } else {
                    feedbackForm.style.display = 'none';
                    toggleFormText.textContent = 'Mở rộng';
                }
            });
        }
        
        // Hiển thị/ẩn trường supplier_name dựa trên loại phản hồi
        const feedbackTypeSelect = document.getElementById('feedback_type');
        const supplierNameField = document.getElementById('supplier_name_field');
        
        function toggleSupplierField() {
            if (!feedbackTypeSelect || !supplierNameField) return;
            
            const selectedType = feedbackTypeSelect.value;
            // Hiển thị trường supplier_name cho các loại: hotel, restaurant, vehicle, supplier
            if (['hotel', 'restaurant', 'vehicle', 'supplier'].includes(selectedType)) {
                supplierNameField.style.display = 'block';
            } else {
                supplierNameField.style.display = 'none';
            }
        }
        
        if (feedbackTypeSelect) {
            feedbackTypeSelect.addEventListener('change', toggleSupplierField);
            toggleSupplierField(); // Gọi ngay khi load trang
        }
    });
    </script>
</main>