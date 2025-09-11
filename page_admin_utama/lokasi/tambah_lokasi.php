<?php
require_once("config/koneksi.php");

// === Fungsi generate kode lokasi otomatis ===
function generateKodeLokasi($koneksi) {
    $prefix = "LOK";
    $query = "SELECT kode_lokasi FROM lokasi 
              WHERE kode_lokasi LIKE '$prefix%' 
              ORDER BY kode_lokasi DESC LIMIT 1";
    $result = mysqli_query($koneksi, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        $lastKode = $row['kode_lokasi'];
        $lastNumber = (int)substr($lastKode, 3); 
        $newNumber = $lastNumber + 1;
        return $prefix . str_pad($newNumber, 3, "0", STR_PAD_LEFT);
    } else {
        return $prefix . "001";
    }
}

$kodeLokasiBaru = generateKodeLokasi($koneksi);

// === Proses tambah lokasi ===
if (isset($_POST['submit'])) {
    $kode_lokasi = generateKodeLokasi($koneksi); // Generate ulang untuk memastikan unik
    $nama_lokasi = mysqli_real_escape_string($koneksi, $_POST['nama_lokasi']);
    $alamat      = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $kontak      = mysqli_real_escape_string($koneksi, $_POST['kontak']);

    $query = "INSERT INTO lokasi (kode_lokasi, nama_lokasi, alamat, kontak) VALUES (?, ?, ?, ?)";
    $stmt  = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ssss", $kode_lokasi, $nama_lokasi, $alamat, $kontak);

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>
                alert('Data lokasi berhasil ditambahkan');
                window.location.href='index_admin_utama.php?page_admin_utama=lokasi/data_lokasi';
              </script>";
    } else {
        echo "<script>alert('Gagal menambahkan data lokasi');</script>";
    }
}
?>

<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>Tambah Lokasi</h2>
        </div>
        
        <form method="POST" class="add-form">
            <div class="form-row">
                <div class="form-group half">
                    <label for="kode_lokasi">Kode Lokasi</label>
                    <input type="text" id="kode_lokasi" name="kode_lokasi" 
                           value="<?php echo $kodeLokasiBaru; ?>" readonly class="form-control">
                </div>
                <div class="form-group half">
                    <label for="nama_lokasi">Nama Lokasi</label>
                    <input type="text" id="nama_lokasi" name="nama_lokasi" required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="alamat">Alamat</label>
                    <input type="text" id="alamat" name="alamat" required class="form-control">
                </div>
                <div class="form-group half">
                    <label for="kontak">Kontak</label>
                    <input type="text" id="kontak" name="kontak" required class="form-control">
                </div>
            </div>

            <div class="form-row">
                
            </div>

            <div class="form-actions">
                <button type="submit" name="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="index_admin.php?page_admin=lokasi/data_lokasi" class="btn-cancel">
                    <i class="fas fa-times"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>


<style>
    .form-group.third {
    flex: 1;
}
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

.form-row {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group.half {
    flex: 1;
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
    background: #3498db;
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
    
    .form-row {
        flex-direction: column;
        gap: 8px;
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


</script>