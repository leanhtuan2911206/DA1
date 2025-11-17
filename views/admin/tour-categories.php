<div class="main-content p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Quản lý Danh mục Tour</h3>
        <a href="<?= BASE_URL ?>?action=tour-categories-create" class="btn btn-primary">
            <i class="fas fa-plus"></i> Thêm Danh mục
        </a>
    </div>

    <table class="table table-bordered table-striped">
        <thead>
            <tr class="table-primary">
                <th width="5%">ID</th>
                <th>Tên Danh mục</th>
                <th width="15%">Số Tour</th>
                <th width="15%">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($listCategories) && !empty($listCategories)): ?>
                <?php foreach ($listCategories as $category): ?>
                    <tr>
                        <td><?= $category['id'] ?></td>
                        <td><?= htmlspecialchars($category['name']) ?></td>
                        <td><?= $category['tour_count'] ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>?action=tour-categories-edit&id=<?= $category['id'] ?>" class="btn btn-sm btn-warning">Sửa</a>
                            <a href="<?= BASE_URL ?>?action=tour-categories-delete&id=<?= $category['id'] ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Bạn có chắc chắn muốn xóa danh mục: <?= htmlspecialchars($category['name']) ?>?');">Xóa</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center text-muted">Chưa có danh mục nào.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">