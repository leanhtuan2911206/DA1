<div class="main-content p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Sửa Danh mục Tour</h3>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger" role="alert">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" role="alert">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <div class="card p-3">
        <form action="<?= BASE_URL ?>?action=tour-categories-update" method="POST">
            <input type="hidden" name="id" value="<?= htmlspecialchars($category['id']) ?>">

            <div class="mb-3">
                <label for="name" class="form-label">Tên Danh mục (*)</label>
                <input type="text" class="form-control" id="name" name="name" required value="<?= htmlspecialchars($category['name']) ?>">
                <div class="form-text">Nhập tên danh mục tour (Bắt buộc).</div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Mô tả</label>
                <textarea class="form-control" id="description" name="description" rows="3" placeholder="Mô tả ngắn gọn về danh mục (không bắt buộc)"><?= htmlspecialchars($category['description'] ?? '') ?></textarea>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= BASE_URL ?>?action=tour-categories" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
            </div>
            
        </form>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

