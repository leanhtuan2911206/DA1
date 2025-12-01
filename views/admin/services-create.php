<?php
?>
<div class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="page-title mb-0">Đặt dịch vụ</div>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?action=services">← Danh sách</a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="card-like">
        <form id="serviceForm" method="post" action="<?= BASE_URL ?>?action=services-store">
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <label class="form-label">Booking</label>
                    <select class="form-select" name="booking_id" id="booking_id" required>
                        <option value="">-- Chọn tour --</option>
                        <?php foreach (($bookings ?? []) as $b): ?>
                            <option value="<?= $b['id'] ?>" data-people="<?= (int)($b['total_people'] ?? 1) ?>">#<?= $b['id'] ?> - <?= htmlspecialchars($b['tour_name'] ?? ($b['customer_name'] ?? '')) ?> (<?= htmlspecialchars($b['start_date'] ?? '') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Loại dịch vụ</label>
                    <select class="form-select" name="service_type" id="service_type" required>
                        <?php foreach ([['vehicle','Xe'],['hotel','Khách sạn'],['flight','Vé máy bay'],['restaurant','Nhà hàng'],['activity','Tham quan']] as [$v,$t]): ?>
                            <option value="<?= $v ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12 col-lg-6">
                    <label class="form-label">Nhà cung cấp</label>
                    <input type="text" class="form-control" name="supplier_name" id="supplier_name" required>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Số lượng</label>
                    <input type="number" class="form-control" name="quantity" id="quantity" min="1" value="1">
                </div>
                
                <div class="col-12 col-lg-6">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" name="status">
                        <?php foreach ([["chờ","Chờ"],["xác nhận","Xác nhận"],["hoàn tất","Hoàn tất"],["hủy","Hủy"],["tạm ngưng","Tạm ngưng"]] as [$v,$t]): ?>
                            <option value="<?= $v ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Thời gian bắt đầu</label>
                    <input type="datetime-local" class="form-control" name="start_time">
                </div>
                
                <div class="col-12 col-lg-6">
                    <label class="form-label">Thời gian kết thúc</label>
                    <input type="datetime-local" class="form-control" name="end_time">
                </div>
                
                <div class="col-12 mt-4" id="specific-fields-container">
                    
                    <div id="vehicle-fields" class="row g-3">
                        <h4 class="mb-3 border-bottom pb-2">Thông tin Xe</h4>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Chọn xe</label>
                            <select class="form-select" name="master_vehicle_id" id="master_vehicle_id">
                                <option value="">-- Chọn xe --</option>
                                <?php foreach (($vehicles ?? []) as $v): ?>
                                    <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Thông tin xe</label>
                            <div class="p-2 rounded" style="background:#f8fafc; border:1px solid #e6ebf2">
                                <div><b>Biển số:</b> <span id="veh_plate" contenteditable="true">—</span></div>
                                <div><b>Tài xế:</b> <span id="veh_driver" contenteditable="true">—</span></div>
                                <div><b>SĐT tài xế:</b> <span id="veh_phone" contenteditable="true">—</span></div>
                                <div><b>Số chỗ ngồi:</b> <span id="veh_capacity" contenteditable="true">—</span></div>
                            </div>
                            <input type="hidden" name="license_plate" id="license_plate">
                            <input type="hidden" name="driver_name" id="driver_name">
                            <input type="hidden" name="driver_phone" id="driver_phone">
                            <input type="hidden" name="driver_capacity" id="driver_capacity">
                        </div>
                    </div>

                    <div id="hotel-fields" class="row g-3">
                        <h4 class="mb-3 border-bottom pb-2">Khách sạn</h4>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Chọn khách sạn</label>
                            <select class="form-select" name="master_hotel_id" id="master_hotel_id">
                                <option value="">-- Chọn khách sạn --</option>
                                <?php foreach (($hotels ?? []) as $h): ?>
                                    <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['hotel_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Thông tin</label>
                            <div class="p-2 rounded" style="background:#f8fafc; border:1px solid #e6ebf2">
                                <div><b>Địa chỉ:</b> <span id="hotel_address">—</span></div>
                                <div><b>Hạng sao:</b> <span id="hotel_star">—</span></div>
                                <div><b>SĐT:</b> <span id="hotel_phone">—</span></div>
                                <div><b>Phòng có sẵn:</b> <span id="hotel_rooms">—</span></div>
                            </div>
                        </div>
                    </div>

                    <div id="flight-fields" class="row g-3">
                        <h4 class="mb-3 border-bottom pb-2">Vé máy bay</h4>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Chọn chuyến bay</label>
                            <select class="form-select" name="master_flight_id" id="master_flight_id">
                                <option value="">-- Chọn chuyến bay --</option>
                                <?php foreach (($flights ?? []) as $f): ?>
                                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['flight_number']) ?> - <?= htmlspecialchars($f['airline']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Thông tin</label>
                            <div class="p-2 rounded" style="background:#f8fafc; border:1px solid #e6ebf2">
                                <div><b>Đi:</b> <span id="flight_origin">—</span></div>
                                <div><b>Đến:</b> <span id="flight_destination">—</span></div>
                                <div><b>Giá tham chiếu:</b> <span id="flight_price">—</span></div>
                            </div>
                        </div>
                    </div>

                    <div id="restaurant-fields" class="row g-3">
                        <h4 class="mb-3 border-bottom pb-2">Nhà hàng</h4>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Chọn nhà hàng</label>
                            <select class="form-select" name="master_restaurant_id" id="master_restaurant_id">
                                <option value="">-- Chọn nhà hàng --</option>
                                <?php foreach (($restaurants ?? []) as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['restaurant_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Thông tin</label>
                            <div class="p-2 rounded" style="background:#f8fafc; border:1px solid #e6ebf2">
                                <div><b>Loại ẩm thực:</b> <span id="rest_cuisine">—</span></div>
                                <div><b>SĐT:</b> <span id="rest_phone">—</span></div>
                                <div><b>Sức chứa:</b> <span id="rest_capacity">—</span></div>
                            </div>
                        </div>
                    </div>

                    <div id="activity-fields" class="row g-3">
                        <h4 class="mb-3 border-bottom pb-2">Tham quan/Hoạt động</h4>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Chọn địa điểm/hoạt động</label>
                            <select class="form-select" name="master_activity_id" id="master_activity_id">
                                <option value="">-- Chọn địa điểm/hoạt động --</option>
                                <?php foreach (($activities ?? []) as $a): ?>
                                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['location_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Thông tin</label>
                            <div class="p-2 rounded" style="background:#f8fafc; border:1px solid #e6ebf2">
                                <div><b>Địa chỉ:</b> <span id="act_address">—</span></div>
                                <div><b>Loại vé/phí:</b> <span id="act_ticket">—</span></div>
                                <div><b>Liên hệ:</b> <span id="act_contact">—</span></div>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <div class="col-12">
                    <label class="form-label">Ghi chú</label>
                    <textarea class="form-control" name="notes" rows="3"></textarea>
                </div>
            </div>
            
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-success">Lưu</button>
                <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?action=services">Hủy</a>
            </div>
        </form>
    </div>
    <script>
        (function(){
            const serviceTypeEl = document.getElementById('service_type');
            const bookingEl = document.getElementById('booking_id');
            const quantityEl = document.getElementById('quantity');
            const supplierEl = document.getElementById('supplier_name');
            const data = {
                vehicle: <?php echo json_encode($vehicles ?? []); ?>,
                hotel: <?php echo json_encode($hotels ?? []); ?>,
                flight: <?php echo json_encode($flights ?? []); ?>,
                restaurant: <?php echo json_encode($restaurants ?? []); ?>,
                activity: <?php echo json_encode($activities ?? []); ?>,
            };

            // Hiển thị tất cả khối dịch vụ mặc định

            function bindInfo(selectId, arr, fields, supplierProp){
                const sel = document.getElementById(selectId);
                if (!sel) return;
                sel.addEventListener('change', function(){
                    const id = this.value;
                    let info = null;
                    for (let i=0;i<arr.length;i++){ if (String(arr[i].id) === String(id)) { info = arr[i]; break; } }
                    Object.keys(fields).forEach(key => {
                        const el = document.getElementById(fields[key]);
                        const val = info ? (info[key] || '—') : '—';
                        if (el) el.textContent = val;
                    });
                    if (info && supplierProp && !supplierEl.value) { supplierEl.value = info[supplierProp] || ''; }
                    if (selectId === 'master_vehicle_id') {
                        document.getElementById('license_plate').value = info ? (info['license_plate'] || '') : '';
                        document.getElementById('driver_name').value = info ? (info['driver_name'] || '') : '';
                        document.getElementById('driver_phone').value = info ? (info['driver_phone'] || '') : '';
                        document.getElementById('veh_plate').textContent = info ? (info['license_plate'] || '—') : '—';
                        document.getElementById('veh_driver').textContent = info ? (info['driver_name'] || '—') : '—';
                        document.getElementById('veh_phone').textContent = info ? (info['driver_phone'] || '—') : '—';
                        const capVal = info ? (info['capacity'] || '') : '';
                        const capEl = document.getElementById('veh_capacity');
                        if (capEl) capEl.textContent = capVal !== '' ? capVal : '—';
                        const capHidden = document.getElementById('driver_capacity');
                        if (capHidden) capHidden.value = capVal;
                    }
                });
            }

            function syncVehicleHidden(){
                document.getElementById('license_plate').value = document.getElementById('veh_plate').textContent.trim();
                document.getElementById('driver_name').value = document.getElementById('veh_driver').textContent.trim();
                document.getElementById('driver_phone').value = document.getElementById('veh_phone').textContent.trim();
                var capEl = document.getElementById('veh_capacity');
                var capHidden = document.getElementById('driver_capacity');
                if (capEl && capHidden) { capHidden.value = capEl.textContent.trim(); }
            }

            document.addEventListener('DOMContentLoaded', function(){
                if (bookingEl && quantityEl) {
                    const applyQty = function(){
                        const opt = bookingEl.options[bookingEl.selectedIndex];
                        const p = opt && opt.getAttribute('data-people');
                        if (p) { quantityEl.value = parseInt(p, 10) || 1; }
                    };
                    bookingEl.addEventListener('change', applyQty);
                    applyQty();
                }

                bindInfo('master_vehicle_id', data.vehicle, {license_plate:'veh_plate', driver_name:'veh_driver', driver_phone:'veh_phone', capacity:'veh_capacity'}, 'name');
                bindInfo('master_hotel_id', data.hotel, {address:'hotel_address', star_rating:'hotel_star', contact_phone:'hotel_phone', room_types_available:'hotel_rooms'}, 'hotel_name');
                bindInfo('master_flight_id', data.flight, {route_origin:'flight_origin', route_destination:'flight_destination', default_price:'flight_price'}, null);
                bindInfo('master_restaurant_id', data.restaurant, {cuisine_type:'rest_cuisine', contact_phone:'rest_phone', max_capacity:'rest_capacity'}, 'restaurant_name');
                bindInfo('master_activity_id', data.activity, {address:'act_address', ticket_type_info:'act_ticket', contact_person:'act_contact'}, 'location_name');

                const setTypeOnSelect = (id, typeVal) => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.addEventListener('change', function(){
                        if (this.value) { serviceTypeEl.value = typeVal; }
                    });
                };
                setTypeOnSelect('master_vehicle_id','vehicle');
                setTypeOnSelect('master_hotel_id','hotel');
                setTypeOnSelect('master_flight_id','flight');
                setTypeOnSelect('master_restaurant_id','restaurant');
                setTypeOnSelect('master_activity_id','activity');

                ['veh_plate','veh_driver','veh_phone','veh_capacity'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.addEventListener('input', syncVehicleHidden);
                });
                const form = document.getElementById('serviceForm');
                form && form.addEventListener('submit', function(){
                    if (serviceTypeEl.value === 'vehicle') syncVehicleHidden();
                });
            });
        })();
    </script>
</div>
