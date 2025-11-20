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
            <label class="form-label">Giá</label>
            <input type="number" name="price" class="form-control" step="0.01" min="0">
        </div>

        <div class="mb-3">
            <label class="form-label">Ảnh đại diện</label>
            <input type="file" name="image" class="form-control" accept="image/*">
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
    </div>
</div>
