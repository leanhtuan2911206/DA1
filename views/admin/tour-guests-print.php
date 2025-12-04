<?php
$group = isset($group) && is_array($group) ? $group : null;
$guests = isset($guests) && is_array($guests) ? $guests : [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách khách đoàn</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            width: 210mm;
            height: 297mm;
            padding: 20mm;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 12px;
            color: #666;
        }
        .info-section {
            margin-bottom: 15px;
            font-size: 13px;
        }
        .info-section p {
            margin: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 12px;
        }
        table thead {
            background-color: #f0f0f0;
        }
        table th,
        table td {
            border: 1px solid #999;
            padding: 6px 8px;
            text-align: left;
        }
        table th {
            font-weight: bold;
            background-color: #e0e0e0;
        }
        table tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 12px;
        }
        .footer p {
            margin: 5px 0;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .container {
                width: 100%;
                height: 100%;
                padding: 20mm;
                margin: 0;
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>DANH SÁCH KHÁCH ĐOÀN</h1>
            <p>Ngày in: <?= date('d/m/Y H:i') ?></p>
        </div>

        <?php if ($group): ?>
        <div class="info-section">
            <p><strong>Tên đoàn:</strong> <?= htmlspecialchars($group['group_name']) ?></p>
            <p><strong>Tour:</strong> <?= htmlspecialchars($group['tour_name'] ?? '—') ?></p>
            <p><strong>Khởi hành:</strong> <?= htmlspecialchars($group['start_date'] ?? '—') ?> - <strong>Kết thúc:</strong> <?= htmlspecialchars($group['end_date'] ?? '—') ?></p>
            <p><strong>Người đặt:</strong> <?= htmlspecialchars($group['customer_name'] ?? '—') ?></p>
            <p><strong>Số khách đăng ký:</strong> <?= (int)$group['total_guests'] ?> | <strong>Số khách thực tế:</strong> <?= count($guests) ?></p>
        </div>

        <?php if (!empty($guests)): ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">STT</th>
                    <th style="width: 20%;">Họ tên</th>
                    <th style="width: 10%;">Giới tính</th>
                    <th style="width: 12%;">Ngày sinh</th>
                    <th style="width: 15%;">Số giấy tờ</th>
                    <th style="width: 15%;">Điện thoại</th>
                    <th style="width: 13%;">Thanh toán</th>
                    <th style="width: 10%;">Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($guests as $index => $guest): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($guest['full_name']) ?></td>
                    <td>
                        <?php
                            if ($guest['gender'] === 'Male') echo 'Nam';
                            elseif ($guest['gender'] === 'Female') echo 'Nữ';
                            else echo $guest['gender'] ?? '—';
                        ?>
                    </td>
                    <td><?= htmlspecialchars($guest['date_of_birth'] ?? '—') ?></td>
                    <td><?= htmlspecialchars(($guest['id_type'] ?? '') . ' ' . ($guest['id_number'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($guest['phone'] ?? '—') ?></td>
                    <td>
                        <?php
                            $payMap = [
                                'unpaid' => 'Chưa thanh toán',
                                'deposit' => 'Đã cọc',
                                'paid' => 'Đã Thanh toán',
                            ];
                            echo $payMap[$guest['payment_status'] ?? 'unpaid'] ?? '—';
                        ?>
                    </td>
                    <td><?= htmlspecialchars($guest['special_requests'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="text-align: center; margin-top: 20px; color: #999;">Chưa có dữ liệu khách hàng</p>
        <?php endif; ?>

        <div class="footer">
            <p>Hà Nội, ngày <?= date('d') ?> tháng <?= date('m') ?> năm <?= date('Y') ?></p>
            <p style="margin-top: 30px;">
                <strong>Người lập danh sách</strong><br><br><br>
                ___________________
            </p>
        </div>
        <?php else: ?>
        <p style="text-align: center; margin-top: 50px; color: #999;">Không tìm thấy thông tin đoàn khách</p>
        <?php endif; ?>
    </div>

    <script>
        window.onload = function() {
            window.print();
            setTimeout(() => { window.close(); }, 500);
        };
    </script>
</body>
</html>
