<div class="main-content p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>✏️ Sửa Tour</h3>
        <a href="<?= BASE_URL ?>?action=tours" class="btn btn-secondary">Quay lại</a>
    </div>

    <?php if (!isset($tour) || !$tour): ?>
        <div class="alert alert-warning">Tour không tìm thấy.</div>
    <?php else: ?>
        <div class="container">
        <form action="<?= BASE_URL ?>?action=tours-update" method="POST">
            <input type="hidden" name="id" value="<?= $tour['id'] ?>">

            <div class="mb-3">
                <label class="form-label">Tên tour</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($tour['name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Danh mục</label>
                <select name="category_id" class="form-select" required>
                    <option value="">-- Chọn danh mục --</option>
                    <?php if (isset($categories) && !empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($tour['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Giá</label>
                <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($tour['price']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Mô tả</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($tour['description'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Lịch trình</label>
                <textarea name="itinerary" class="form-control" rows="3"><?= htmlspecialchars($tour['itinerary'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Chính sách</label>
                <textarea name="policy" class="form-control" rows="3"><?= htmlspecialchars($tour['policy'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Cập nhật</button>
        </form>
        </div>
    <?php endif; ?>
</div>
