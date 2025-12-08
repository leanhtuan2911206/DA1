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
            <div class="mb-2">Chọn tour đã được phân công để ghi nhật ký:</div>
            <form method="get" action="<?= BASE_URL ?>">
                <input type="hidden" name="action" value="partner-logs">
                <div class="d-flex flex-wrap gap-2">
                    <select name="tour_id" class="form-select" style="min-width:260px;" required>
                        <option value="">-- Chọn tour --</option>
                        <?php foreach ($tours as $t): ?>
                            <option value="<?= (int)$t['id'] ?>">#<?= (int)$t['id'] ?> - <?= htmlspecialchars($t['name'] ?? 'Tour') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Vào nhật ký</button>
                </div>
            </form>
        </div>
        </main>
        <?php return; ?>
    <?php endif; ?>

    <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addLogModal">+ Thêm nhật ký</button>
    </div>

    <div class="modal fade" id="addLogModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Thêm nhật ký</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="post" action="<?= BASE_URL ?>?action=tour-logs-store" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Chọn tour *</label>
                        <select class="form-select" name="tour_id" id="add_tour_id" required>
                            <option value="">-- Chọn tour --</option>
                            <?php foreach ($tours as $t): ?>
                                <option value="<?= (int)$t['id'] ?>" <?= ((int)($tour['id'] ?? 0) === (int)$t['id'])?'selected':'' ?>>#<?= (int)$t['id'] ?> - <?= htmlspecialchars($t['name'] ?? 'Tour') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($guideId > 0): ?><input type="hidden" name="guide_id" value="<?= (int)$guideId ?>"><?php endif; ?>
                    <div class="mb-3"><label class="form-label">Chọn ngày *</label>
                        <input type="date" class="form-control" name="log_date" id="add_log_date" required>
                    </div>
                    <div class="mb-3"><label class="form-label">Ghi nhật ký *</label>
                        <textarea class="form-control" name="note" rows="6" placeholder="Sự kiện, hoạt động nổi bật, sự cố, cách xử lý, phản hồi khách, ảnh chụp…" required></textarea>
                    </div>
                    <div class="mb-3"><label class="form-label">Hình ảnh</label><input type="file" class="form-control" name="image" accept="image/*"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary">Thêm</button></div>
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
                <?php $i=0; foreach ($logs as $log): if ($i++ >= 10) break; ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold"><?= htmlspecialchars($log['title'] ?? '') ?></div>
                            <div class="small text-muted">
                                <?= !empty($log['log_date']) ? htmlspecialchars($log['log_date']) : date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                            </div>
                            <?php if (!empty($log['description'])): ?>
                                <div class="small text-muted mt-1" style="max-width:700px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars($log['description']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <div class="d-flex align-items-center gap-2">
                        <?php if(!empty($log['image_path'])): ?>
                            <img src="<?= BASE_URL . $log['image_path'] ?>" style="width:48px;height:48px;object-fit:cover;border-radius:4px;" />
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>?action=partner-logs&tour_id=<?= (int)$tour['id'] ?>&edit_id=<?= (int)$log['id'] ?>" class="btn btn-sm btn-outline-secondary">✏️ Sửa</a>
                        <a href="<?= BASE_URL ?>?action=tour-logs-delete&id=<?= (int)$log['id'] ?>&tour_id=<?= (int)$tour['id'] ?>"
                           class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa nhật ký này?')">🗑️ Xóa</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>


    <script>
    function toggleSections(logType, modalId) {
        var ratingField = document.getElementById(modalId + '_rating_field');
        var secIncident = document.getElementById(modalId + '_sections_incident');
        var secFeedback = document.getElementById(modalId + '_sections_feedback');
        var secTimeline = document.getElementById(modalId + '_sections_timeline');
        if (ratingField) ratingField.style.display = (logType === 'rating') ? 'block' : 'none';
        if (secIncident) secIncident.style.display = (logType === 'incident') ? 'block' : 'none';
        if (secFeedback) secFeedback.style.display = (logType === 'feedback') ? 'block' : 'none';
        if (secTimeline) secTimeline.style.display = (logType === 'timeline' || logType === 'incident') ? 'block' : 'none';
    }
    function showSection(section){
        var t=document.getElementById('section_table');
        var d=document.getElementById('section_day');
        if(t) t.style.display = (section==='table')?'block':'none';
        if(d) d.style.display = (section==='day')?'block':'none';
        var tabs=document.querySelectorAll('#hdvLogsTabs .nav-link');
        tabs.forEach(function(a){ a.classList.remove('active'); });
        var map={table:0,day:1}; var idx=map[section]; if(typeof idx!=='undefined' && tabs[idx]) tabs[idx].classList.add('active');
    }
    
    document.getElementById('add_tour_id')?.addEventListener('change', function(){
        // Không cần tải lịch trình; ngày được chọn bằng lịch
    });
    </script>
</main>
