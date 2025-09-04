<?php
session_start();
require_once("config/koneksi.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        echo "<script>alert('Username atau password tidak boleh kosong!'); window.location='login.php';</script>";
        exit;
    }

    $query = "SELECT * FROM pengguna WHERE username = '$username'";
    $result = mysqli_query($koneksi, $query);

    if ($data = mysqli_fetch_assoc($result)) {
        if ($password === $data['password']) {
            $_SESSION['username'] = $data['username'];
            $_SESSION['id_akun'] = $data['id_akun'];
            $_SESSION['peran'] = $data['peran'];

            switch ($data['peran']) {
                case 'admin_utama':
                    header("Location: index_admin_utama.php");
                    exit;
                case 'admin':
                    header("Location: index_admin.php");
                    exit;
                case 'pimpinan':
                default:
                    header("Location: index_pimpinan.php");
                    exit;
            }
        } else {
            echo "<script>alert('Password salah!'); window.location='login.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Username tidak ditemukan!'); window.location='login.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Gereja Katolik St. Yohanes Pembaptis</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Inter', sans-serif;
        background-color: #ffffff;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 20px;
    }

    .login-container {
        background: #ffffff;
        border: 2px solid #e5e7eb;
        border-radius: 16px;
        padding: 48px 40px;
        width: 100%;
        max-width: 420px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        position: relative;
        text-align: center;
    }

    .logo-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        position: relative;
    }

    .logo-container img {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        object-fit: cover;
        border: 2px solid #3b82f6;
    }

    .logo-container::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 40%;
        height: 2px;
        background: linear-gradient(90deg, transparent, #3b82f6, transparent);
    }

    h1 {
        color: #1e40af;
        font-size: 32px;
        margin-bottom: 8px;
        font-weight: 700;
        letter-spacing: -0.5px;
    }

    .subtitle {
        color: #6b7280;
        font-size: 16px;
        margin-bottom: 32px;
        font-weight: 500;
    }

    .form-group {
        margin-bottom: 24px;
        position: relative;
    }

    .form-group i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 18px;
        z-index: 1;
    }

    .form-input {
        width: 100%;
        padding: 16px 16px 16px 50px;
        border-radius: 12px;
        border: 2px solid #e5e7eb;
        font-size: 16px;
        font-weight: 500;
        background-color: #f9fafb;
        transition: all 0.3s ease;
        color: #1f2937;
    }

    .form-input:focus {
        border-color: #3b82f6;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        outline: none;
    }

    .form-input:focus + i {
        color: #3b82f6;
    }

    .login-btn {
        width: 100%;
        padding: 16px;
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #3b82f6, #1e40af);
        color: #ffffff;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        letter-spacing: 0.5px;
        margin-top: 8px;
    }

    .login-btn:hover {
        background: linear-gradient(135deg, #2563eb, #1e3a8a);
        transform: translateY(-1px);
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
    }

    .login-btn:active {
        transform: translateY(0);
    }

    .footer-text {
        margin-top: 32px;
        font-size: 14px;
        color: #6b7280;
        font-weight: 500;
        line-height: 1.4;
        border-top: 1px solid #e5e7eb;
        padding-top: 24px;
    }

    .security-note {
        margin-top: 20px;
        padding: 12px 16px;
        background-color: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        font-size: 13px;
        color: #1e40af;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .security-note i {
        font-size: 16px;
        color: #3b82f6;
    }

    /* Responsive Design */
    @media (max-width: 480px) {
        .login-container {
            padding: 32px 24px;
            margin: 16px;
        }
        
        h1 {
            font-size: 28px;
        }
        
        .logo-container img {
            width: 50px;
            height: 50px;
        }
    }
</style>
</head>
<body>
<div class="login-container">
    <div class="logo-container">
        <img src="img/logo.jpg" alt="Logo Gereja">
        <img src="img/logo.jpg" alt="Logo Gereja">
    </div>

    <h1>MASUK</h1>
    <p class="subtitle">Silakan masuk ke akun Anda</p>

    <form method="post">
        <div class="form-group">
            <input type="text" name="username" class="form-input" placeholder="Username" required>
            <i class="fas fa-user"></i>
        </div>
        <div class="form-group">
            <input type="password" name="password" class="form-input" placeholder="Password" required>
            <i class="fas fa-lock"></i>
        </div>
        <button type="submit" name="submit" class="login-btn">
            <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
            Masuk
        </button>
    </form>

    <div class="footer-text">
        <strong>Gereja Katolik St. Yohanes Pembaptis</strong><br>
        Perawang
    </div>
</div>
</body>
</html>