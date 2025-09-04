<?php
require_once("config/koneksi.php");

if(isset($_POST['submit'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $peran = mysqli_real_escape_string($koneksi, $_POST['peran']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $no_hp = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Handle file upload
    $photo = '';
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $new_filename = $username . '_' . time() . '.' . $ext;
            if(move_uploaded_file($_FILES['photo']['tmp_name'], "upload/" . $new_filename)) {
                $photo = $new_filename;
            }
        }
    }

    $query = "INSERT INTO pengguna (username, nama, peran, alamat, no_hp, email, password, photo) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ssssssss", $username, $nama, $peran, $alamat, $no_hp, $email, $password, $photo);
    
    if(mysqli_stmt_execute($stmt)) {
        echo "<script>
                alert('Data berhasil ditambahkan');
                window.location.href='index_admin_utama.php?page_admin_utama=data_akun/data_akun';
              </script>";
    } else {
        echo "<script>alert('Gagal menambahkan data');</script>";
    }
}
?>
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>Tambah Akun</h2>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="add-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required class="form-control">
            </div>

            <div class="form-group">
                <label for="nama">Nama</label>
                <input type="text" id="nama" name="nama" required class="form-control">
            </div>

            <div class="form-group">
                <label for="peran">Peran</label>
                <select id="peran" name="peran" required class="form-control">
                    <option value="">Pilih Peran</option>
                    <option value="Admin Utama">Admin Utama</option>
                    <option value="Admin">Admin</option>
                    <option value="Pimpinan">Pimpinan</option>
                </select>
            </div>

            <div class="form-group">
                <label for="alamat">Alamat</label>
                <textarea id="alamat" name="alamat" required class="form-control"></textarea>
            </div>

            <div class="form-group">
                <label for="no_hp">No Telepon</label>
                <input type="tel" id="no_hp" name="no_hp" required class="form-control">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required class="form-control">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required class="form-control">
            </div>

            <div class="form-group">
                <label for="photo">Photo</label>
                <input type="file" id="photo" name="photo" accept="image/*" class="form-control">
            </div>

            <div class="form-actions">
                <button type="submit" name="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="index_admin_utama.php?page_admin_utama=data_akun/data_akun" class="btn-cancel">
                    <i class="fas fa-times"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.form-container {
    padding: 16px;
    background: #f8f9fa;
    min-height: calc(100vh - 60px);
    display: flex;
    justify-content: center;
    align-items: flex-start;
}

.form-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    width: 100%;
    max-width: 800px;
    margin-top: 16px;
}

.form-header {
    padding: 16px 24px;
    border-bottom: 1px solid #eee;
}

.form-header h2 {
    margin: 0;
    font-size: 18px;
    color: #2d3436;
    font-weight: 600;
}

.add-form {
    padding: 24px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #2d3436;
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #dce1e6;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
}

.form-control:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

select.form-control {
    height: 38px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M0 2l4 4 4-4z' fill='%23333'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 8px;
    padding-right: 32px;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

textarea.form-control {
    min-height: 80px;
    resize: vertical;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid #eee;
}

.btn-submit, .btn-cancel {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-submit {
    background:  #3498db;
    color: white;
    border: none;
}

.btn-cancel {
    background: #e74c3c;
    color: white;
    border: none;
}

.btn-submit:hover, .btn-cancel:hover {
    opacity: 0.9;
}

@media (max-width: 768px) {
    .form-container {
        padding: 8px;
    }
    
    .form-card {
        margin-top: 8px;
    }

    .add-form {
        padding: 16px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-submit, .btn-cancel {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
document.getElementById('peran').addEventListener('change', function() {
    const lokasi = document.getElementById('lokasi');
    if(this.value === 'Admin Utama') {
        lokasi.value = 'Paroki St. Fransiskus Asisi Padang Bulan';
        lokasi.disabled = true;
    } else {
        lokasi.disabled = false;
    }
});
</script>