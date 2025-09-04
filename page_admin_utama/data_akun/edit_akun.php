<?php
include "config/koneksi.php";

// Ambil data lama (jika ada id dikirim via GET untuk form edit)
$id = $_GET['id'] ?? null;
$data = [];
if ($id) {
    $result = mysqli_query($koneksi, "SELECT * FROM pengguna WHERE id_akun='$id'");
    $data   = mysqli_fetch_assoc($result);
}

// Cek jika form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = $_POST['id'] ?? null;
    $username = $_POST['username'] ?? '';
    $nama     = $_POST['nama'] ?? '';
    $alamat   = $_POST['alamat'] ?? '';
    $no_hp    = $_POST['no_hp'] ?? '';
    $email    = $_POST['email'] ?? '';

    // Ambil data lama untuk cek photo lama & peran lama
    $result = mysqli_query($koneksi, "SELECT * FROM pengguna WHERE id_akun='$id'");
    $data   = mysqli_fetch_assoc($result);

    // Jika peran dipilih baru, pakai yang baru. Kalau tidak, biarkan tetap lama
    $peran = $_POST['peran'] ?? '';
    if (empty($peran)) {
        $peran = $data['peran'];
    }

    // Set field update
    $update_fields = "username=?, nama=?, peran=?, alamat=?, no_hp=?, email=?";
    $params = [$username, $nama, $peran, $alamat, $no_hp, $email];
    $types  = "ssssss";

    // Jika password diisi
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $update_fields .= ", password=?";
        $params[] = $password;
        $types   .= "s";
    }

    // Jika ada foto baru
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed   = ['jpg', 'jpeg', 'png'];
        $filename  = $_FILES['photo']['name'];
        $ext       = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_filename = $username . '_' . time() . '.' . $ext;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], "upload/" . $new_filename)) {
                // Hapus foto lama
                if (!empty($data['photo'])) {
                    $old_photo = "upload/" . $data['photo'];
                    if (file_exists($old_photo)) {
                        unlink($old_photo);
                    }
                }

                $update_fields .= ", photo=?";
                $params[] = $new_filename;
                $types   .= "s";
            }
        }
    }

    // Tambahkan ID untuk WHERE
    $params[] = $id;
    $types   .= "i";

    // Query update
    $query = "UPDATE pengguna SET $update_fields WHERE id_akun=?";
    $stmt  = mysqli_prepare($koneksi, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);

        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Data berhasil diperbarui'); window.location='index_admin_utama.php?page_admin_utama=data_akun/data_akun';</script>";
            exit;
        } else {
            echo "Gagal update data: " . mysqli_error($koneksi);
        }

        mysqli_stmt_close($stmt);
    } else {
        echo "Error prepare statement: " . mysqli_error($koneksi);
    }
}
?>

<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>Edit Akun</h2>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="add-form">
            <input type="hidden" name="id" value="<?= htmlspecialchars($data['id_akun'] ?? '') ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($data['username'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="nama">Nama</label>
                <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($data['nama'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="peran">Peran</label>
                <select id="peran" name="peran">
                    <option value="">(Biarkan untuk tidak mengubah)</option>
                    <option value="admin_utama" <?= (($data['peran'] ?? '') == 'admin_utama') ? 'selected' : '' ?>>Admin Utama</option>
                    <option value="admin" <?= (($data['peran'] ?? '') == 'admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="pimpinan" <?= (($data['peran'] ?? '') == 'pimpinan') ? 'selected' : '' ?>>Pimpinan</option>
                </select>
            </div>

            <div class="form-group">
                <label for="alamat">Alamat</label>
                <textarea id="alamat" name="alamat" required><?= htmlspecialchars($data['alamat'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="no_hp">No Telepon</label>
                <input type="tel" id="no_hp" name="no_hp" value="<?= htmlspecialchars($data['no_hp'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($data['email'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password (Kosongkan jika tidak diubah)</label>
                <input type="password" id="password" name="password">
            </div>

            <div class="form-group">
                <label for="photo">Photo</label>
                <?php if(!empty($data['photo'])): ?>
                    <div>
                        <img src="upload/<?= htmlspecialchars($data['photo']) ?>" alt="Current photo" style="max-width: 100px; margin-bottom: 8px;">
                    </div>
                <?php endif; ?>
                <input type="file" id="photo" name="photo" accept="image/*">
            </div>

            <div class="form-actions">
                <button type="submit" name="submit">Update</button>
                <a href="index_admin_utama.php?page_admin_utama=data_akun/data_akun">Batal</a>
            </div>
        </form>
    </div>
</div>

<style>
/* ====== Import Font Poppins ====== */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

/* ====== Reset ringkas & variabel ====== */
:root{
  --bg:#f5f7fb;
  --card:#ffffff;
  --text:#1f2937;
  --muted:#6b7280;
  --line:#e5e7eb;
  --primary:#2563eb;
  --primary-weak:#eaf1ff;
  --success:#10b981;
  --danger:#ef4444;
  --radius:10px;
  --space:16px;
  --shadow:0 2px 10px rgba(0,0,0,.06);
}
*{box-sizing:border-box}
html,body{height:100%}
body{
  margin:0;
  font-family:'Poppins', sans-serif !important;
  color:var(--text);
  background:var(--bg);
  line-height:1.5;
}

/* ====== Layout Form ====== */
.form-container{
  padding:var(--space);
  min-height:calc(100vh - 60px);
  display:flex;
  align-items:flex-start;
  justify-content:center;
}
.form-card{
  width:100%;
  max-width:860px;
  background:var(--card);
  border:1px solid var(--line);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  overflow:hidden;
}
.form-header{
  padding:18px 22px;
  border-bottom:1px solid var(--line);
}
.form-header h2{
  margin:0;
  font-size:20px;
  font-weight:700;
  letter-spacing:.2px;
}

/* ====== Isi Form ====== */
.add-form{ padding:22px; }
.form-group{
  margin-bottom:14px;
  display:flex;
  flex-direction:column;
  gap:8px;
}
.form-group:last-child{ margin-bottom:0; }

.form-group label{
  font-size:13.5px;
  color:var(--muted);
  font-weight:600;
}

.form-control,
input[type="text"],
input[type="email"],
input[type="password"],
input[type="tel"],
select,
textarea{
  width:100%;
  padding:10px 12px;
  border:1px solid var(--line);
  border-radius:8px;
  font-size:14px;
  background:#fff;
  color:var(--text);
  outline:none;
}

textarea{ min-height:96px; resize:vertical; }

/* Focus state */
.form-control:focus,
input:focus,
select:focus,
textarea:focus{
  border-color:var(--primary);
  box-shadow:0 0 0 3px var(--primary-weak);
}

/* Select arrow custom */
select{
  appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%236b7280' d='M1.41.59 6 5.17 10.59.59 12 2l-6 6-6-6z'/%3E%3C/svg%3E");
  background-repeat:no-repeat;
  background-position:right 12px center;
  background-size:12px 8px;
  padding-right:36px;
}

/* Input file */
input[type="file"]{
  padding:8px;
  border:1px dashed var(--line);
  border-radius:8px;
  background:#fafafa;
}

/* Foto saat ini */
.current-photo{
  display:inline-block;
  padding:6px;
  border:1px solid var(--line);
  border-radius:8px;
  background:#fff;
}
.current-photo img{
  display:block;
  max-width:120px;
  height:auto;
  border-radius:6px;
}
/* ====== Actions ====== */
.form-actions{
  margin-top:22px;
  padding-top:16px;
  border-top:1px solid var(--line);
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}

.form-actions a,
.form-actions button{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:10px 14px;
  border-radius:8px;
  font-size:14px;
  font-weight:600;
  text-decoration:none;
  border:1px solid transparent;
  cursor:pointer;
  transition: all .25s ease;
}

/* Tombol Update (Hijau) */
.form-actions button[type="submit"],
.btn-submit{
  background:var(--success);
  color:#fff;
  border-color:var(--success);
}
.form-actions button[type="submit"]:hover,
.btn-submit:hover{
  background:#0e9f6e; /* sedikit lebih gelap */
}

/* Tombol Batal (Merah) */
.form-actions a,
.btn-cancel{
  background:var(--danger);
  color:#fff;
  border-color:var(--danger);
}
.form-actions a:hover,
.btn-cancel:hover{
  background:#dc2626; /* merah lebih gelap */
}


/* State non-aktif */
button:disabled,
.btn-disabled{
  opacity:.6;
  cursor:not-allowed;
}

.help-text{
  font-size:12.5px;
  color:var(--muted);
}

/* ====== Responsif ====== */
@media (max-width: 768px){
  .form-container{ padding:12px; }
  .add-form{ padding:16px; }
  .form-actions{
    flex-direction:column;
  }
  .btn-submit,
  .btn-cancel{
    width:100%;
    justify-content:center;
  }
}

</style>