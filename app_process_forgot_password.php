<?php
ob_start();
session_start();
require 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Bangkok');
ob_clean();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
        $email = trim($_POST['email']);

        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $token = bin2hex(random_bytes(50));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $updateQuery = "UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("sss", $token, $expiry, $email);
            $stmt->execute();

            $resetLink = "https://smoscitechkpru.ct.ws/reset_password.php?token=$token";  //Edit redirect to app

            $mail = new PHPMailer(true);
            $mail->CharSet = "UTF-8";
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'respond.noreply@gmail.com';
            $mail->Password = 'lucagvjbtwnbxzit';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('respond.noreply@gmail.com', 'noreply');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'เปลี่ยนรหัสผ่าน';
            $mail->Body = "กดเพื่อเปลี่ยนรหัสผ่าน <a href='$resetLink'>$resetLink</a>";
            $mail->send();

            echo json_encode([
                "status" => "success",
                "message" => "ระบบได้ส่งลิงก์รีเซ็ตรหัสผ่านไปยังอีเมลของคุณแล้ว"
            ]);
            exit;
        } else {
            echo json_encode([
                "status" => "danger",
                "message" => "อีเมลนี้ไม่ได้ลงทะเบียน!"
            ]);
            exit;
        }
    } else {
        echo json_encode([
            "status" => "danger",
            "message" => "คำขอไม่ถูกต้อง"
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode([
        "status" => "danger",
        "message" => "เกิดข้อผิดพลาด: " . $e->getMessage()
    ]);
    exit;
}