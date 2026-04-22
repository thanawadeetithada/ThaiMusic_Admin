<?php
session_start();
require 'db.php';

date_default_timezone_set('Asia/Bangkok');

$modal_message = $_SESSION['modal_message'] ?? '';
$modal_type = $_SESSION['modal_type'] ?? '';
unset($_SESSION['modal_message'], $_SESSION['modal_type']);

if (!isset($_GET['token']) || empty($_GET['token'])) {
    $_SESSION['modal_message'] = "Token ไม่ถูกต้อง!";
    $_SESSION['modal_type'] = "danger";
    header("Location: index.php");
    exit;
}

$token = $_GET['token'];

$query = "SELECT * FROM users WHERE reset_token = ? AND reset_expiry > NOW() LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['modal_message'] = "ลิงก์นี้หมดอายุหรือไม่ถูกต้อง!";
    $_SESSION['modal_type'] = "danger";
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-title" content="App Premium">
    <meta name="application-name" content="App Premium">
    <meta name="theme-color" content="#96a1cd">
    <title>เปลี่ยนรหัสผ่าน</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
    body {
        font-family: 'Prompt', sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background: #cfd8e5;
        margin: 0;
    }

    .container {
        background: rgba(255, 255, 255, 0.9);
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 500px;
        text-align: center;
        margin: 30px;
    }

    h2 {
        margin-bottom: 20px;
        color: black;
    }

    .form-group {
        display: flex;
        align-items: center;
        margin: 10px 0;
        justify-content: space-between;
    }

    .form-group label {
        width: 45%;
        font-size: 16px;
        text-align: left;
    }

    .form-group input {
        width: 50%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 16px;
    }

    button {
        width: fit-content;
        padding: 12px;
        background: #8c99bc;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 18px;
        cursor: pointer;
        transition: 0.3s;
        margin-top: 20px;
    }

    button:hover {
        background: #6f7ca1;
    }

    .modal-content {
        background: rgba(255, 255, 255, 0.97);
        border-radius: 20px;
        animation: fadeInUp 0.3s ease-in-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }


    .modal-content {
        background: rgba(255, 255, 255, 0.97);
        border-radius: 20px;
        transition: transform 0.25s ease-in-out;
        animation: fadeInUp 0.3s ease-in-out;
    }

    .modal.fade .modal-dialog {
        transform: translateY(-30px);
        transition: transform 0.3s ease-out;
    }

    .modal.show .modal-dialog {
        transform: translateY(0);
    }

    .btn-custom,
    .btn-success,
    .btn-danger {
        background-color: #8c99bc;
        border: none;
        transition: 0.3s;
    }

    .btn-custom:hover,
    .btn-success:hover,
    .btn-danger:hover {
        background-color: #6f7ca1;
    }

    .text-muted {
        color: #333 !important;
    }
    </style>
</head>

<body>
    <div class="container">
        <h2>เปลี่ยนรหัสผ่าน</h2>
        <form action="process_reset_password.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="form-group">
                <label for="password">รหัสผ่านใหม่</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">ยืนยันรหัสผ่าน</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            <button type="submit">เปลี่ยนรหัสผ่าน</button>
        </form>
    </div>

    <div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg text-center rounded-4 p-4"
                style="animation: fadeInUp 0.3s ease-in-out;">

                <div class="modal-icon mb-3">
                    <?php if ($modal_type === "success"): ?>
                    <div style="font-size: 50px; color: #28a745;"></div>
                    <?php elseif ($modal_type === "warning"): ?>
                    <div style="font-size: 50px; color: #ffc107;"></div>
                    <?php else: ?>
                    <div style="font-size: 50px; color: #dc3545;"></div>
                    <?php endif; ?>
                </div>

                <h5 class="fw-bold mb-3"
                    style="color: <?php echo ($modal_type === 'success') ? '#28a745' : (($modal_type === 'warning') ? '#856404' : '#dc3545'); ?>">
                    <?php
                    if ($modal_type === "success") echo "สำเร็จ!";
                    elseif ($modal_type === "warning") echo "แจ้งเตือน!";
                    else echo "ข้อผิดพลาด!";
                ?>
                </h5>

                <p class="text-muted mb-2 fs-5">
                    <?php echo htmlspecialchars($modal_message, ENT_QUOTES, 'UTF-8'); ?>
                </p>

                <div class="d-flex justify-content-center">
                    <button type="button" class="btn btn-custom px-4 py-2" data-dismiss="modal"
                        style="background-color: #8c99bc; color: white; transition: 0.3s;">
                        ตกลง
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
        <?php if (!empty($modal_message)): ?>
        $('#messageModal').modal('show');
        <?php endif; ?>
    });
    </script>
</body>

</html>