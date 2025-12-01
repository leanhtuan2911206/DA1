<div class="main-content p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>➕ Thêm Tour</h3>
        <a href="<?= BASE_URL ?>?action=tours" class="btn btn-secondary">Quay lại</a>
    </div>
<div class="container">
    <form action="<?= BASE_URL ?>?action=tours-store" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Tên tour</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Danh mục</label>
            <select name="category_id" class="form-select" required>
                <option value="">-- Chọn danh mục --</option>
                <?php if (isset($categories) && !empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Chọn tour mẫu hoặc tour có sẵn (tự động điền lịch trình và chính sách)</label>
            <select id="tour-template-select" class="form-select">
                <option value="">-- Không chọn --</option>
                <?php if (isset($templates) && !empty($templates)): ?>
                    <optgroup label="Tour Mẫu">
                        <?php foreach ($templates as $template): ?>
                            <option value="template_<?= $template['id'] ?>" data-type="template" data-id="<?= $template['id'] ?>">
                                <?= htmlspecialchars($template['name']) ?> (Mẫu)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
                <?php if (isset($existingTours) && !empty($existingTours)): ?>
                    <optgroup label="Tour Có Sẵn">
                        <?php foreach ($existingTours as $tour): ?>
                            <option value="tour_<?= $tour['id'] ?>" data-type="tour" data-id="<?= $tour['id'] ?>">
                                <?= htmlspecialchars($tour['name']) ?> (Tour #<?= $tour['id'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Trạng thái tour</label>
            <select name="tour_status" class="form-select">
                <option value="">-- Chọn trạng thái --</option>
                <option value="Upcoming">Sắp diễn ra</option>
                <option value="Active" selected>Hoạt động</option>
                <option value="Completed">Đã kết thúc</option>
                <option value="Cancelled">Đã hủy</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Giá</label>
            <input type="number" name="price" class="form-control" step="0.01" min="0">
        </div>

        <div class="mb-3">
            <label class="form-label">Ảnh đại diện</label>
            <div class="d-flex align-items-center gap-3">
                <img src="" alt="" class="tour-thumb rounded" style="display:none;">
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Mô tả</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Lịch trình</label>
            <textarea name="itinerary" class="form-control" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Chính sách</label>
            <textarea name="policy" class="form-control" rows="3"></textarea>
        </div>

        <button type="submit" class="btn btn-success">Lưu</button>
    </form>

    <script>
    // Hiển thị preview ảnh ngay khi chọn file ở form Thêm Tour
    document.addEventListener('DOMContentLoaded', function () {
        var img = document.querySelector('.tour-thumb');
        var fileInput = document.querySelector('input[type="file"][name="image"]');
        if (!img || !fileInput) return;

        fileInput.addEventListener('change', function (e) {
            var file = e.target.files && e.target.files[0];
            if (!file) {
                img.style.display = 'none';
                img.src = '';
                return;
            }
            var url = URL.createObjectURL(file);
            img.src = url;
            img.style.display = 'block';
        });

        // Tự động điền lịch trình và chính sách khi chọn tour/template
        var tourSelect = document.getElementById('tour-template-select');
        var itineraryTextarea = document.querySelector('textarea[name="itinerary"]');
        var policyTextarea = document.querySelector('textarea[name="policy"]');

        if (tourSelect && itineraryTextarea && policyTextarea) {
            tourSelect.addEventListener('change', function () {
                var selectedOption = this.options[this.selectedIndex];
                if (!selectedOption || !selectedOption.value) {
                    return;
                }

                var type = selectedOption.dataset.type; // 'template' hoặc 'tour'
                var id = selectedOption.dataset.id;

                if (!type || !id) {
                    return;
                }

                // Gọi API để lấy thông tin tour/template
                var url = '<?= BASE_URL ?>?action=tours-get-info&type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id);
                
                fetch(url)
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        if (data.success) {
                            // Điền lịch trình và chính sách
                            if (data.itinerary) {
                                itineraryTextarea.value = data.itinerary;
                            }
                            if (data.policy) {
                                policyTextarea.value = data.policy;
                            }
                        } else {
                            console.error('Lỗi khi lấy thông tin tour:', data.message);
                            alert('Không thể lấy thông tin tour/template. Vui lòng thử lại.');
                        }
                    })
                    .catch(function(error) {
                        console.error('Lỗi khi gọi API:', error);
                        alert('Đã xảy ra lỗi khi lấy thông tin tour/template.');
                    });
            });
        }
    });
    </script>
    </div>
</div>
