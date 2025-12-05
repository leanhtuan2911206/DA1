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

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Thêm hoạt động cho: <span class="text-primary"><?= htmlspecialchars($tour['name']) ?></span></h2>
        <a href="<?= BASE_URL ?>?action=tours-detail&id=<?= $tour['id'] ?>" class="btn btn-secondary">
            &laquo; Quay lại chi tiết
        </a>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Form nhập thông tin</h6>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>?action=tours-itinerary-store" method="POST" id="itineraryForm">
                        <input type="hidden" name="tour_id" value="<?= $tour['id'] ?>">

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Ngày thứ (Day)</label>
                                <input type="number" name="day_number" id="inp_day" class="form-control" value="1" min="1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Giờ bắt đầu</label>
                                <input type="text" name="time_start" id="inp_time" class="form-control" placeholder="VD: 08:00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Địa điểm</label>
                                <input type="text" name="location" id="inp_loc" class="form-control" placeholder="VD: Sảnh khách sạn">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Tên hoạt động <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="inp_title" class="form-control" required placeholder="VD: Ăn sáng, Tham quan...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Mô tả chi tiết</label>
                            <textarea name="description" id="inp_desc" rows="4" class="form-control"></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Lưu hoạt động</button>
                            <button type="submit" name="save_and_continue" value="1" class="btn btn-outline-primary">Lưu & Thêm tiếp</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card shadow mb-4 bg-light">
                <div class="card-header py-3 bg-info text-white">
                    <h6 class="m-0 font-weight-bold">📌 Gợi ý từ mẫu (Click để điền)</h6>
                </div>
                <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                    <?php if (empty($templateItems)): ?>
                        <div class="p-4 text-center text-muted">
                            <p>Không tìm thấy dữ liệu mẫu (Template) nào được gắn với tour này.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($templateItems as $temp): ?>
                                <button type="button" class="list-group-item list-group-item-action p-3" 
                                        onclick="fillForm(<?= htmlspecialchars(json_encode($temp)) ?>)">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 fw-bold text-dark">Ngày <?= $temp['day_number'] ?> - <?= $temp['time_start'] ?></h6>
                                        <small class="text-muted">📋 Chọn</small>
                                    </div>
                                    <p class="mb-1 text-primary"><?= htmlspecialchars($temp['title']) ?></p>
                                    <small class="text-muted text-truncate d-block"><?= htmlspecialchars($temp['description']) ?></small>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    // Hàm Javascript đổ dữ liệu từ template vào form
    function fillForm(data) {
        document.getElementById('inp_day').value = data.day_number;
        document.getElementById('inp_time').value = data.time_start;
        document.getElementById('inp_title').value = data.title;
        document.getElementById('inp_desc').value = data.description;
        document.getElementById('inp_loc').value = data.location;
        
        // Hiệu ứng nháy nhẹ để báo đã nhận
        const formCard = document.querySelector('.card-body form');
        formCard.style.opacity = '0.5';
        setTimeout(() => formCard.style.opacity = '1', 200);
    }
</script>