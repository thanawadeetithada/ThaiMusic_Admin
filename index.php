<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ensembles.php"); 
    exit();
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                if (isset($user['userrole']) && $user['userrole'] === 'user') {
                    $error_message = "บัญชีนี้ไม่มีสิทธิ์เข้าถึงส่วนผู้ดูแลระบบ";
                } else {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['userrole'] = $user['userrole'] ?? 'admin';
                    
                    header("Location: ensembles.php");
                    exit();
                }
            } else {
                $error_message = "รหัสผ่านไม่ถูกต้อง กรุณาลองอีกครั้ง";
            }
        } else {
            $error_message = "ไม่พบอีเมลนี้ในระบบ";
        }
        $stmt->close();
    } else {
        $error_message = "กรุณากรอกอีเมลและรหัสผ่านให้ครบถ้วน";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thai Music - Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-gradient-1: #0f2027;
            --bg-gradient-2: #203a43;
            --bg-gradient-3: #2c5364;
            --primary-color: #d4af37; /* สีทอง */
            --primary-hover: #b5952f;
            --text-dark: #333333;
        }

        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-2));
            display: flex;
            align-items: center;
            justify-content: center;
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            padding: 50px 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            text-align: center;
            position: relative;
            margin: 20px;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), #f3e5ab);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
            box-shadow: 0 10px 20px rgba(212, 175, 55, 0.3);
        }

        .logo-icon i {
            font-size: 35px;
            color: #fff;
        }

        .brand-title {
            font-size: 26px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .brand-subtitle {
            font-size: 14px;
            color: #777;
            margin-bottom: 35px;
            font-weight: 300;
        }

        .input-group-custom {
            background: #f4f6f9;
            border-radius: 12px;
            display: flex;
            align-items: center;
            padding: 5px 20px;
            margin-bottom: 20px;
            border: 1px solid #e1e5eb;
            transition: all 0.3s ease;
        }

        .input-group-custom:focus-within {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
            background: #fff;
        }

        .input-group-custom i {
            color: #a0a5b1;
            font-size: 18px;
            width: 25px;
            transition: color 0.3s;
        }

        .input-group-custom:focus-within i {
            color: var(--primary-color);
        }

        .input-group-custom input {
            border: none;
            padding: 12px 10px;
            flex-grow: 1;
            outline: none;
            background: transparent;
            font-size: 15px;
            color: var(--text-dark);
        }

        .input-group-custom input::placeholder {
            color: #adb5bd;
        }

        .btn-login {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
            margin-top: 10px;
        }

        .btn-login:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.4);
            color: white;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-alert {
            background-color: #fee2e2;
            color: #b91c1c;
            border-radius: 10px;
            padding: 12px;
            font-size: 14px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
        }

    </style>
</head>

<body>

    <div class="login-card">
        <div class="logo-icon">
            <i class="fa-solid fa-music"></i>
        </div>

        <div class="brand-title">Thai Music</div>
        <div class="brand-subtitle">Admin Control Panel</div>

        <?php if(!empty($error_message)): ?>
            <div class="error-alert">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="input-group-custom">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" placeholder="อีเมลผู้ดูแลระบบ" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="input-group-custom">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="รหัสผ่าน" required>
                <i class="fa-solid fa-eye-slash" id="togglePassword" style="cursor: pointer; width: auto; margin-left: auto;"></i>
            </div>

            <button type="submit" class="btn-login">
                เข้าสู่ระบบ <i class="fa-solid fa-arrow-right ms-2"></i>
            </button>
        </form>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
            
            this.style.color = type === 'text' ? 'var(--primary-color)' : '#a0a5b1';
        });
    </script>
</body>
</html>