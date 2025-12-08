<main class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none" type="button">☰</button>
            <div class="search-wrap">
                <input type="text" class="form-control" placeholder="Tìm kiếm nhanh" readonly/>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary-subtle text-primary">VN</span>
            <div class="avatar rounded-circle bg-secondary-subtle"></div>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="h3 mb-0 text-gray-800">Thêm nhật ký tour: <span class="text-primary"><?= htmlspecialchars($tour['name']) ?></span></h2>
            <p class="text-muted mb-0">Booking ID: <?= $booking_id ?></p>
        </div>
        <a href="<?= BASE_URL ?>?action=bookings-detail&id=<?= $booking_id ?>" class="btn btn-secondary">
            &laquo; Quay lại chi tiết booking
        </a>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">📝 Form nhập nhật ký tour</h6>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>?action=bookings-log-store" method="POST" enctype="multipart/form-data" id="logForm">
                        <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
                        <input type="hidden" name="tour_id" value="<?= $tour['id'] ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Loại nhật ký <span class="text-danger">*</span></label>
                                <select class="form-select" name="log_type" required onchange="toggleLogSections(this.value)">
                                    <option value="">-- Chọn loại --</option>
                                    <option value="incident">Sự cố phát sinh</option>
                                    <option value="feedback">Phản hồi khách hàng</option>
                                    <option value="rating">Đánh giá HDV</option>
                                    <option value="timeline">Lịch trình / Diễn biến</option>
                                    <option value="daily">Nhật ký ngày</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">HDV</label>
                                <select class="form-select" name="guide_id">
                                    <option value="">-- Chọn HDV --</option>
                                    <?php foreach ($guides as $g): ?>
                                        <option value="<?= (int)$g['HDV_ID'] ?>"><?= htmlspecialchars($g['HoTen'] ?? ('HDV #' . $g['HDV_ID'])) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tiêu đề <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" required placeholder="VD: Sự cố thời tiết, Phản hồi khách...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Ngày nhật ký</label>
                                <input type="date" class="form-control" name="log_date" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="mb-3" id="description_section">
                            <label class="form-label fw-bold">Mô tả chi tiết</label>
                            <textarea class="form-control" name="description" id="description_field" rows="4" placeholder="Mô tả chi tiết về nhật ký..."></textarea>
                        </div>

                        <!-- Phần diễn biến tour (hiển thị khi chọn loại phù hợp) -->
                        <div id="section_incident" style="display: none;">
                            <div class="card bg-light mb-3">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">📋 Diễn biến tour</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">🌤️ Thời tiết</label>
                                            <input type="text" class="form-control" name="weather" placeholder="VD: Nắng đẹp, Mưa nhẹ, Nhiều mây...">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">🏥 Tình trạng sức khỏe khách</label>
                                            <input type="text" class="form-control" name="health_status" placeholder="VD: Tốt, Có khách bị say xe...">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">⭐ Hoạt động đặc biệt</label>
                                        <input type="text" class="form-control" name="special_activities" placeholder="VD: Tham quan thêm địa điểm, Tổ chức sinh nhật...">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">📝 Cách xử lý tình huống ngoài dự kiến</label>
                                        <textarea class="form-control" name="handling_notes" rows="3" placeholder="Ghi chú cách xử lý các tình huống ngoài dự kiến, hỗ trợ rút kinh nghiệm cho lần sau..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Phần phản hồi khách hàng -->
                        <div id="section_feedback" style="display: none;">
                            <div class="card bg-light mb-3">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">💬 Phản hồi khách hàng</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Phản hồi của khách hàng</label>
                                        <textarea class="form-control" name="customer_feedback" rows="3" placeholder="Lưu lại phản hồi của khách hàng ngay khi kết thúc tour hoặc từng giai đoạn trong tour..."></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Giai đoạn / Lịch trình</label>
                                        <select class="form-select" name="itinerary_id">
                                            <option value="">-- Chọn mục lịch trình --</option>
                                            <?php foreach ($itineraries as $item): ?>
                                                <option value="<?= (int)$item['id'] ?>">
                                                    Ngày <?= $item['day_number'] ?> - <?= htmlspecialchars($item['title']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Phần đánh giá HDV -->
                        <div id="section_rating" style="display: none;">
                            <div class="card bg-light mb-3">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">⭐ Đánh giá chất lượng HDV</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Đánh giá tổng thể (sao)</label>
                                            <select class="form-select" name="rating">
                                                <option value="">-- Chọn --</option>
                                                <option value="1">1 sao</option>
                                                <option value="2">2 sao</option>
                                                <option value="3">3 sao</option>
                                                <option value="4">4 sao</option>
                                                <option value="5">5 sao</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">🤝 Phối hợp</label>
                                            <select class="form-select" name="rating_coordination">
                                                <option value="">-- Chọn --</option>
                                                <option value="1">1 - Rất kém</option>
                                                <option value="2">2 - Kém</option>
                                                <option value="3">3 - Trung bình</option>
                                                <option value="4">4 - Tốt</option>
                                                <option value="5">5 - Rất tốt</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">💪 Tinh thần làm việc</label>
                                            <select class="form-select" name="rating_spirit">
                                                <option value="">-- Chọn --</option>
                                                <option value="1">1 - Rất kém</option>
                                                <option value="2">2 - Kém</option>
                                                <option value="3">3 - Trung bình</option>
                                                <option value="4">4 - Tốt</option>
                                                <option value="5">5 - Rất tốt</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Nhận xét</label>
                                        <textarea class="form-control" name="rating_comment" id="rating_comment" rows="2" placeholder="Đánh giá chất lượng HDV dựa trên phản hồi khách, sự phối hợp, tinh thần làm việc..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Trạng thái</label>
                                <select class="form-select" name="status">
                                    <option value="pending">Chờ xử lý</option>
                                    <option value="in_progress">Đang xử lý</option>
                                    <option value="resolved">Đã giải quyết</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Hình ảnh (nếu có)</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Lưu nhật ký</button>
                            <a href="<?= BASE_URL ?>?action=bookings-detail&id=<?= $booking_id ?>" class="btn btn-secondary">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    function toggleLogSections(logType) {
        // Ẩn tất cả các section
        document.getElementById('section_incident').style.display = 'none';
        document.getElementById('section_feedback').style.display = 'none';
        document.getElementById('section_rating').style.display = 'none';
        
        // Hiển thị section phù hợp
        if (logType === 'incident' || logType === 'timeline' || logType === 'daily') {
            document.getElementById('section_incident').style.display = 'block';
        }
        if (logType === 'feedback' || logType === 'timeline') {
            document.getElementById('section_feedback').style.display = 'block';
        }
        if (logType === 'rating') {
            document.getElementById('section_rating').style.display = 'block';
            // Khi chọn rating, tự động điền mô tả từ rating_comment
            const ratingComment = document.getElementById('rating_comment');
            const descriptionField = document.getElementById('description_field');
            if (ratingComment && descriptionField) {
                ratingComment.addEventListener('input', function() {
                    descriptionField.value = this.value;
                });
            }
        }
    }
    
    // Xử lý khi submit form - gộp rating_comment vào description nếu có
    document.getElementById('logForm').addEventListener('submit', function(e) {
        const logType = document.querySelector('select[name="log_type"]').value;
        if (logType === 'rating') {
            const ratingComment = document.getElementById('rating_comment').value;
            if (ratingComment) {
                const descriptionField = document.getElementById('description_field');
                if (descriptionField.value) {
                    descriptionField.value = descriptionField.value + '\n\n' + ratingComment;
                } else {
                    descriptionField.value = ratingComment;
                }
            }
        }
    });
</script>

