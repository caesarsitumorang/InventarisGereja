<?php
require_once("config/koneksi.php");

$username = $_SESSION['username'];

// Fetch user data
$query = "SELECT * FROM pengguna WHERE username = ?";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'];
    $alamat = $_POST['alamat'];
    $no_hp = $_POST['no_hp'];
    $email = $_POST['email'];
    $new_password = $_POST['new_password'];
    $current_password = $_POST['current_password'];

    $update_fields = [];
    $types = "";
    $values = [];

    // Basic fields
    $update_fields[] = "nama = ?";
    $types .= "s";
    $values[] = $nama;

    $update_fields[] = "alamat = ?";
    $types .= "s";
    $values[] = $alamat;

    $update_fields[] = "no_hp = ?";
    $types .= "s";
    $values[] = $no_hp;

    $update_fields[] = "email = ?";
    $types .= "s";
    $values[] = $email;

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = $username . '_' . time() . '.' . $ext;
            $upload_path = "upload/" . $new_filename;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                $update_fields[] = "photo = ?";
                $types .= "s";
                $values[] = $new_filename;
            }
        }
    }

    // Handle password change
    if (!empty($new_password) && !empty($current_password)) {
        // Verify current password
        $check_query = "SELECT password FROM pengguna WHERE username = ?";
        $check_stmt = mysqli_prepare($koneksi, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $username);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $user_data = mysqli_fetch_assoc($check_result);

        if (password_verify($current_password, $user_data['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_fields[] = "password = ?";
            $types .= "s";
            $values[] = $hashed_password;
        } else {
            $error_msg = "Password saat ini tidak sesuai";
        }
    }

    if (!empty($update_fields)) {
        $values[] = $username; // for WHERE clause
        $types .= "s";

        $query = "UPDATE pengguna SET " . implode(", ", $update_fields) . " WHERE username = ?";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, $types, ...$values);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Profil berhasil diperbarui!";
            // Refresh user data
            $result = mysqli_query($koneksi, "SELECT * FROM pengguna WHERE username = '$username'");
            $user = mysqli_fetch_assoc($result);
        } else {
            $error_msg = "Gagal memperbarui profil";
        }
    }
}
?>

<style>
    .profile-container {
        padding: 2rem;
        background: #f5f6fa;
    }

    .profile-header {
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border: 1px solid #eee;
    }

    .profile-title {
        font-size: 24px;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .profile-subtitle {
        color: #666;
        font-size: 14px;
    }

    .profile-form-container {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border: 1px solid #eee;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    .form-section {
        margin-bottom: 2rem;
    }

    .section-title {
        font-size: 18px;
        color: var(--primary-color);
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #eee;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #444;
        font-weight: 500;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        outline: none;
    }

    .photo-upload-container {
        text-align: center;
        margin-bottom: 2rem;
    }

    .current-photo {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 1rem;
        border: 3px solid var(--primary-color);
    }

    .photo-upload-btn {
        display: inline-block;
        padding: 0.75rem 1.5rem;
        background: var(--primary-color);
        color: white;
        border-radius: 4px;
        cursor: pointer;
        transition: background 0.2s;
    }

    .photo-upload-btn:hover {
        background: var(--secondary-color);
    }

    .btn-update {
        background: var(--primary-color);
        color: white;
        padding: 0.75rem 2rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 500;
        transition: all 0.2s;
    }

    .btn-update:hover {
        background: var(--secondary-color);
        transform: translateY(-1px);
    }

    .alert {
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<div class="profile-container">
    <div class="profile-header">
        <h2 class="profile-title">Edit Profil</h2>
        <p class="profile-subtitle">Perbarui informasi profil dan kata sandi Anda</p>
    </div>

    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="profile-form-container">
        <form method="POST" enctype="multipart/form-data">
            <div class="photo-upload-container">
                <img src="upload/<?php echo !empty($user['photo']) ? $user['photo'] : 'default.jpg'; ?>" 
                     alt="Profile Photo" 
                     class="current-photo">
                <div>
                    <label class="photo-upload-btn">
                        <input type="file" name="photo" style="display: none;" accept="image/*">
                        Ganti Foto Profil
                    </label>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-section">
                    <h3 class="section-title">Informasi Pribadi</h3>
                    
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" class="form-control" value="<?php echo $user['username']; ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" value="<?php echo $user['nama']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" class="form-control" rows="3" required><?php echo $user['alamat']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>No. HP</label>
                        <input type="tel" name="no_hp" class="form-control" value="<?php echo $user['no_hp']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Ganti Password</h3>
                    
                    <div class="form-group">
                        <label>Password Saat Ini</label>
                        <input type="password" name="current_password" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Password Baru</label>
                        <input type="password" name="new_password" class="form-control">
                    </div>

                    <p style="color: #666; font-size: 13px; margin-top: 0.5rem;">
                        Kosongkan field password jika tidak ingin mengubah password
                    </p>
                </div>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <button type="submit" class="btn-update">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelector('input[type="file"]').addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.querySelector('.current-photo').src = e.target.result;
        }
        reader.readAsDataURL(e.target.files[0]);
    }
});
</script>