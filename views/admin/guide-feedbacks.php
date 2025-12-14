<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$feedbacks = isset($feedbacks) && is_array($feedbacks) ? $feedbacks : [];
$guides = isset($guides) && is_array($guides) ? $guides : [];
$tours = isset($tours) && is_array($tours) ? $tours : [];
$feedbackModel = new GuideFeedback();
$feedbackTypes = $feedbackModel->getFeedbackTypes();

$currentType = $_GET['type'] ?? '';
$currentStatus = $_GET['status'] ?? '';
$currentGuideId = isset($_GET['guide_id']) ? (int)$_GET['guide_id'] : 0;
$currentTourId = isset($_GET['tour_id']) ? (int)$_GET['tour_id'] : 0;
?>
<main class="main-content">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <p class="text-uppercase text-muted small mb-1">Quản lý</p>
            <h2 class="page-title mb-0">Phản hồi đánh giá từ HDV</h2>
        </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Bộ lọc -->
    <div class="card-like mb-3">
        <h5 class="mb-3 fw-bold">🔍 Lọc phản hồi</h5>
        <form method="get" action="<?= BASE_URL ?>">
            <input type="hidden" name="action" value="guide-feedbacks">
            <div class="row g-2">
                <div class="col-12 col-md-3">
                    <label class="form-label small">HDV</label>
                    <select name="guide_id" class="form-select form-select-sm">
                        <option value="">-- Tất cả HDV --</option>
                        <?php foreach ($guides as $guide): ?>
                            <option value="<?= (int)$guide['HDV_ID'] ?>" <?= $currentGuideId === (int)$guide['HDV_ID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($guide['HoTen'] ?? ('HDV #' . $guide['HDV_ID'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small">Tour</label>
                    <select name="tour_id" class="form-select form-select-sm">
                        <option value="">-- Tất cả tour --</option>
                        <?php foreach ($tours as $tour): ?>
                            <option value="<?= (int)$tour['id'] ?>" <?= $currentTourId === (int)$tour['id'] ? 'selected' : '' ?>>
                                #<?= (int)$tour['id'] ?> - <?= htmlspecialchars(removeVNPrefix($tour['name'] ?? 'Tour')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
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
                <div class="col-12 col-md-2">
                    <label class="form-label small">Trạng thái</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">-- Tất cả --</option>
                        <option value="pending" <?= $currentStatus === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                        <option value="reviewed" <?= $currentStatus === 'reviewed' ? 'selected' : '' ?>>Đã xem</option>
                        <option value="resolved" <?= $currentStatus === 'resolved' ? 'selected' : '' ?>>Đã xử lý</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">Lọc</button>
                        <a href="<?= BASE_URL ?>?action=guide-feedbacks" class="btn btn-outline-secondary btn-sm">Xóa bộ lọc</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Danh sách phản hồi -->
    <div class="card-like" style="margin-bottom:0">
        <h5 class="mb-3 fw-bold">📋 Danh sách phản hồi (<?= count($feedbacks) ?>)</h5>
        <?php if (empty($feedbacks)): ?>
            <div class="text-center text-muted py-4">
                <p class="mb-0">Chưa có phản hồi nào.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width:60px;">ID</th>
                            <th style="width:150px;">HDV</th>
                            <th style="width:120px;">Loại</th>
                            <th>Tiêu đề</th>
                            <th style="width:100px;">Tour</th>
                            <th style="width:100px;">Booking</th>
                            <th style="width:80px;">Đánh giá</th>
                            <th style="width:120px;">Trạng thái</th>
                            <th style="width:150px;">Ngày tạo</th>
                            <th style="width:150px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedbacks as $feedback): ?>
                            <?php
                            $statusClass = 'bg-warning text-dark';
                            $statusText = 'Chờ xử lý';
                            if ($feedback['status'] === 'reviewed') {
                                $statusClass = 'bg-info';
                                $statusText = 'Đã xem';
                            } elseif ($feedback['status'] === 'resolved') {
                                $statusClass = 'bg-success';
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
                            <tr>
                                <td class="text-muted">#<?= (int)$feedback['id'] ?></td>
                                <td><?= htmlspecialchars($feedback['guide_name'] ?? '—') ?></td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($typeLabel) ?></span></td>
                                <td>
                                    <strong><?= htmlspecialchars($feedback['title']) ?></strong>
                                    <?php if (!empty($feedback['supplier_name'])): ?>
                                        <br><small class="text-muted">🏢 <?= htmlspecialchars($feedback['supplier_name']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($feedback['tour_name'])): ?>
                                        <small>#<?= (int)$feedback['tour_id'] ?></small><br>
                                        <small class="text-muted"><?= htmlspecialchars(mb_substr($feedback['tour_name'], 0, 30)) ?><?= mb_strlen($feedback['tour_name']) > 30 ? '...' : '' ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($feedback['booking_customer'])): ?>
                                        <small>#<?= (int)$feedback['booking_id'] ?></small><br>
                                        <small class="text-muted"><?= htmlspecialchars(mb_substr($feedback['booking_customer'], 0, 20)) ?><?= mb_strlen($feedback['booking_customer']) > 20 ? '...' : '' ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($feedback['rating'])): ?>
                                        <span class="text-warning"><?= str_repeat('⭐', (int)$feedback['rating']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                </td>
                                <td class="text-muted small"><?= $createdDate ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#feedbackModal<?= (int)$feedback['id'] ?>">
                                            👁️ Xem
                                        </button>
                                        <form method="post" action="<?= BASE_URL ?>?action=guide-feedbacks-update-status" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= (int)$feedback['id'] ?>">
                                            <select name="status" class="form-select form-select-sm" style="display:inline-block;width:auto;min-width:120px;" onchange="this.form.submit()">
                                                <option value="pending" <?= $feedback['status'] === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                                                <option value="reviewed" <?= $feedback['status'] === 'reviewed' ? 'selected' : '' ?>>Đã xem</option>
                                                <option value="resolved" <?= $feedback['status'] === 'resolved' ? 'selected' : '' ?>>Đã xử lý</option>
                                            </select>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <!-- Modal xem chi tiết -->
                            <div class="modal fade" id="feedbackModal<?= (int)$feedback['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">📝 Chi tiết phản hồi #<?= (int)$feedback['id'] ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <strong>HDV:</strong> <?= htmlspecialchars($feedback['guide_name'] ?? '—') ?>
                                            </div>
                                            <div class="mb-3">
                                                <strong>Loại:</strong> <span class="badge bg-info"><?= htmlspecialchars($typeLabel) ?></span>
                                            </div>
                                            <div class="mb-3">
                                                <strong>Tiêu đề:</strong> <?= htmlspecialchars($feedback['title']) ?>
                                            </div>
                                            <div class="mb-3">
                                                <strong>Nội dung:</strong>
                                                <div class="mt-2 p-3 bg-light rounded" style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($feedback['content'])) ?></div>
                                            </div>
                                            <?php if (!empty($feedback['supplier_name'])): ?>
                                                <div class="mb-3">
                                                    <strong>Nhà cung cấp:</strong> <?= htmlspecialchars($feedback['supplier_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($feedback['rating'])): ?>
                                                <div class="mb-3">
                                                    <strong>Điểm đánh giá:</strong> <span class="text-warning"><?= str_repeat('⭐', (int)$feedback['rating']) ?> (<?= (int)$feedback['rating'] ?>/5)</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($feedback['suggestions'])): ?>
                                                <div class="mb-3">
                                                    <strong>💡 Đề xuất cải thiện:</strong>
                                                    <div class="mt-2 p-3 bg-info bg-opacity-10 rounded" style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($feedback['suggestions'])) ?></div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($feedback['tour_name'])): ?>
                                                <div class="mb-3">
                                                    <strong>Tour:</strong> #<?= (int)$feedback['tour_id'] ?> - <?= htmlspecialchars($feedback['tour_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($feedback['booking_customer'])): ?>
                                                <div class="mb-3">
                                                    <strong>Booking:</strong> #<?= (int)$feedback['booking_id'] ?> - <?= htmlspecialchars($feedback['booking_customer']) ?>
                                                    <?php if (!empty($feedback['booking_start_date'])): ?>
                                                        (<?= date('d/m/Y', strtotime($feedback['booking_start_date'])) ?>)
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="mb-3">
                                                <strong>Trạng thái:</strong> <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                            </div>
                                            <div class="mb-0">
                                                <strong>Ngày tạo:</strong> <?= $createdDate ?>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

