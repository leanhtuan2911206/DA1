<?php

if (!function_exists('debug')) {
    function debug($data)
    {
        echo '<pre>';
        print_r($data);
        die;
    }
}

if (!function_exists('upload_file')) {
    function upload_file($folder, $file)
    {
        $targetFile = $folder . '/' . time() . '-' . $file["name"];

        if (move_uploaded_file($file["tmp_name"], PATH_ASSETS_UPLOADS . $targetFile)) {
            return $targetFile;
        }

        throw new Exception('Upload file không thành công!');
    }
}

// Helper function để loại bỏ "VN " ở đầu tên tour
if (!function_exists('removeVNPrefix')) {
    function removeVNPrefix($name) {
        if (empty($name)) return $name;
        $name = trim($name);
        // Loại bỏ "VN " ở đầu (case-insensitive)
        if (stripos($name, 'VN ') === 0) {
            $name = substr($name, 3);
        } elseif (stripos($name, 'VN') === 0 && strlen($name) > 2 && $name[2] === ' ') {
            $name = substr($name, 3);
        }
        return trim($name);
    }
}

// Helper function để tạo QR code image URL
if (!function_exists('getQRCodeImage')) {
    function getQRCodeImage($data, $size = 200) {
        if (empty($data)) {
            return '';
        }
        
        // Sử dụng QR Server API (miễn phí, không cần API key)
        $encodedData = urlencode($data);
        $size = (int)$size;
        
        // Đảm bảo size hợp lệ (tối thiểu 100, tối đa 1000)
        if ($size < 100) $size = 100;
        if ($size > 1000) $size = 1000;
        
        // Tạo URL QR code từ QR Server API
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encodedData}";
        
        return $qrUrl;
    }
}