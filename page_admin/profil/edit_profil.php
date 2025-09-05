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
    $new_username = $_POST['username'];
    $nama = $_POST['nama'];
    $alamat = $_POST['alamat'];
    $no_hp = $_POST['no_hp'];
    $email = $_POST['email'];
    $new_password = $_POST['new_password'];

    $update_fields = [];
    $types = "";
    $values = [];

    // Check if username is being changed and if it's available
    if ($new_username != $username) {
        $check_username = "SELECT username FROM pengguna WHERE username = ? AND username != ?";
        $check_stmt = mysqli_prepare($koneksi, $check_username);
        mysqli_stmt_bind_param($check_stmt, "ss", $new_username, $username);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error_msg = "Username sudah digunakan!";
        } else {
            $update_fields[] = "username = ?";
            $types .= "s";
            $values[] = $new_username;
        }
    }

    if (!isset($error_msg)) {
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
                $new_filename = $new_username . '_' . time() . '.' . $ext;
                $upload_path = "upload/" . $new_filename;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                    $update_fields[] = "photo = ?";
                    $types .= "s";
                    $values[] = $new_filename;
                }
            }
        }

        // Handle password change (no current password required)
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_fields[] = "password = ?";
            $types .= "s";
            $values[] = $hashed_password;
        }

        if (!empty($update_fields)) {
            $values[] = $username; // for WHERE clause
            $types .= "s";

            $query = "UPDATE pengguna SET " . implode(", ", $update_fields) . " WHERE username = ?";
            $stmt = mysqli_prepare($koneksi, $query);
            mysqli_stmt_bind_param($stmt, $types, ...$values);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Profil berhasil diperbarui!";
                // Update session if username changed
                if ($new_username != $username) {
                    $_SESSION['username'] = $new_username;
                    $username = $new_username;
                }
                // Refresh user data
                $result = mysqli_query($koneksi, "SELECT * FROM pengguna WHERE username = '$username'");
                $user = mysqli_fetch_assoc($result);
            } else {
                $error_msg = "Gagal memperbarui profil";
            }
        }
    }
}
?>

<style>
    .profile-container {
        padding: 1.5rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border: 1px solid #eee;
        max-width: 800px;
        margin: 0 auto;
    }

    .profile-header {
        text-align: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f0f0f0;
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

    .photo-section {
        text-align: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f0f0f0;
    }

    .current-photo {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 0.5rem;
        border: 2px solid var(--primary-color);
    }

    .photo-upload-btn {
        display: inline-block;
        padding: 0.5rem 1rem;
        background: var(--primary-color);
        color: white;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.3rem;
        color: #444;
        font-weight: 500;
        font-size: 14px;
    }

    .form-control {
        width: 100%;
        padding: 0.6rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        outline: none;
    }

    .password-section {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #f0f0f0;
    }

    .password-note {
        color: #666;
        font-size: 12px;
        margin-top: 0.3rem;
        font-style: italic;
    }

    .btn-update, .btn-back {
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-update {
    background:  #3498db;
    color: #fff;
    border: none;
}

.btn-update:hover {
    background: #163175;
}

.btn-back {
    background: #ef0000ff;
    color:white;
    border: none;
}

.btn-back:hover {
    background: #d1d5db;
}

    .alert {
        padding: 0.75rem;
        border-radius: 4px;
        margin-bottom: 1rem;
        font-size: 14px;
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

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .profile-container {
            padding: 1rem;
        }
    }
</style>
<div class="profile-container">
    <div class="profile-header">
        <h2 class="profile-title">Edit Profil</h2>
        <p class="profile-subtitle">Perbarui informasi profil Anda</p>
    </div>

    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="photo-section">
            <img src="upload/<?php echo !empty($user['photo']) ? $user['photo'] : 'default.jpg'; ?>" 
                 alt="Profile Photo" 
                 class="current-photo">
            <div>
                <label class="photo-upload-btn">
                    <input type="file" name="photo" style="display: none;" accept="image/*">
                    Ganti Foto
                </label>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo $user['username']; ?>" required>
            </div>

            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" value="<?php echo $user['nama']; ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>No. HP</label>
                <input type="tel" name="no_hp" class="form-control" value="<?php echo $user['no_hp']; ?>" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
            </div>
        </div>

        <div class="form-group full-width">
            <label>Alamat</label>
            <textarea name="alamat" class="form-control" rows="2" required><?php echo $user['alamat']; ?></textarea>
        </div>

        <div class="password-section">
            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="new_password" class="form-control" placeholder="Masukkan password baru">
                <div class="password-note">Kosongkan jika tidak ingin mengubah password</div>
            </div>
        </div>

        <!-- Tombol Simpan & Kembali -->
        <div class="form-actions">
            <button type="submit" class="btn-update">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
            <a href="index_admin.php?index_admin=dashboard" class="btn-back">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </form>
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