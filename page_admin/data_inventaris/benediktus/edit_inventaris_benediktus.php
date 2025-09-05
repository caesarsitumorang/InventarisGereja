<?php
require_once("config/koneksi.php");
$id = $_GET['id']; // ID inventaris yang diedit
$query = mysqli_query($koneksi, "SELECT lokasi_simpan FROM inventaris WHERE id='$id'");
$data = mysqli_fetch_assoc($query);
$lokasi_simpan = $data['lokasi_simpan'];


// Get data inventaris
if(isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $query = "SELECT * FROM inventaris WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    
    if(!$data) {
        echo "<script>
                alert('Data tidak ditemukan');
                window.location.href='index_admin.php?page_admin=data_inventaris/benediktus/data_inventaris_benediktus';
              </script>";
        exit;
    }
}

// Handle form submission
if(isset($_POST['submit'])) {
    $id = (int)$_POST['id'];
    $kode_barang = mysqli_real_escape_string($koneksi, $_POST['kode_barang']);
    $nama_barang = mysqli_real_escape_string($koneksi, $_POST['nama_barang']);
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $lokasi_simpan = mysqli_real_escape_string($koneksi, $_POST['lokasi_simpan']);
    $jumlah = (int)$_POST['jumlah'];
    $jumlah_total = (int)$_POST['jumlah_total'];
    $satuan = mysqli_real_escape_string($koneksi, $_POST['satuan']);
    $tgl_pengadaan = mysqli_real_escape_string($koneksi, $_POST['tgl_pengadaan']);
    $kondisi = mysqli_real_escape_string($koneksi, $_POST['kondisi']);
    $sumber = mysqli_real_escape_string($koneksi, $_POST['sumber']);
    $harga = str_replace([',', '.'], '', $_POST['harga']);
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    $query = "UPDATE inventaris SET 
              kode_barang=?, nama_barang=?, kategori=?, lokasi_simpan=?, 
              jumlah=?, jumlah_total=?, satuan=?, tgl_pengadaan=?, 
              kondisi=?, sumber=?, harga=?, keterangan=? 
              WHERE id=?";
              
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ssssiissssisi", 
        $kode_barang, $nama_barang, $kategori, $lokasi_simpan,
        $jumlah, $jumlah_total, $satuan, $tgl_pengadaan,
        $kondisi, $sumber, $harga, $keterangan, $id
    );
    
    if(mysqli_stmt_execute($stmt)) {
        echo "<script>
                alert('Data berhasil diupdate');
                window.location.href='index_admin.php?page_admin=data_inventaris/benediktus/data_inventaris_benediktus';
              </script>";
    } else {
        echo "<script>alert('Gagal mengupdate data');</script>";
    }
}
?>

<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>Edit Inventaris</h2>
        </div>
        
        <form method="POST" class="add-form" onsubmit="return validateForm()">
            <input type="hidden" name="id" value="<?= $data['id'] ?>">
            
            <div class="form-row">
                <div class="form-group half">
                    <label for="kode_barang">Kode Barang</label>
                    <input type="text" id="kode_barang" name="kode_barang" 
                           value="<?= htmlspecialchars($data['kode_barang']) ?>" required class="form-control">
                </div>

                <div class="form-group half">
                    <label for="nama_barang">Nama Barang</label>
                    <input type="text" id="nama_barang" name="nama_barang" 
                           value="<?= htmlspecialchars($data['nama_barang']) ?>" required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="kategori">Kategori</label>
                    <select id="kategori" name="kategori" required class="form-control">
                        <option value="">Pilih Kategori</option>
                        <option value="Bangunan" <?= ($data['kategori'] == 'Bangunan') ? 'selected' : '' ?>>Bangunan</option>
                        <option value="Liturgi" <?= ($data['kategori'] == 'Liturgi') ? 'selected' : '' ?>>Liturgi</option>
                        <option value="Pakaian Misa" <?= ($data['kategori'] == 'Pakaian Misa') ? 'selected' : '' ?>>Pakaian Misa</option>
                        <option value="Pakaian Misdinar" <?= ($data['kategori'] == 'Pakaian Misdinar') ? 'selected' : '' ?>>Pakaian Misdinar</option>
                        <option value="Buku Misa" <?= ($data['kategori'] == 'Buku Misa') ? 'selected' : '' ?>>Buku Misa</option>
                        <option value="Mebulair" <?= ($data['kategori'] == 'Mebulair') ? 'selected' : '' ?>>Mebulair</option>
                        <option value="Alat Elektronik" <?= ($data['kategori'] == 'Alat Elektronik') ? 'selected' : '' ?>>Alat Elektronik</option>
                        <option value="Alat Rumah Tangga" <?= ($data['kategori'] == 'Alat Rumah Tangga') ? 'selected' : '' ?>>Alat Rumah Tangga</option>
                    </select>
                </div>

                 <div class="form-group half">
                    <label for="lokasi_simpan">Lokasi Penyimpanan</label>
                    <select id="lokasi_simpan" name="lokasi_simpan" required class="form-control">
                        <?php 
                        $stasi_list = [
                            "Paroki",
                            "Stasi St. Fidelis (Karo Simalem)",
                            "Stasi St. Yohanes Penginjil (Minas Jaya)",
                            "Stasi St. Agustinus (Minas Barat)",
                            "Stasi St. Benediktus (Teluk Siak)",
                            "Stasi St. Paulus (Inti 4)",
                            "Stasi St. Fransiskus Asisi (Inti 7)",
                            "Stasi St. Paulus (Empang Pandan)",
                            "Stasi Sta. Maria Bunda Karmel (Teluk Merbau)",
                            "Stasi Sta. Elisabet (Sialang Sakti)",
                            "Stasi St. Petrus (Pangkalan Makmur)",
                            "Stasi St. Stefanus (Zamrud)",
                            "Stasi St. Mikael (Siak Raya)",
                            "Stasi St. Paulus Rasul (Siak Merambai)"
                        ];

                        foreach($stasi_list as $stasi) {
                            $selected = ($stasi == $lokasi_simpan) ? "selected" : "";
                            echo "<option value=\"$stasi\" $selected>$stasi</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group third">
                    <label for="jumlah">Jumlah</label>
                    <input type="number" id="jumlah" name="jumlah" 
                           value="<?= htmlspecialchars($data['jumlah']) ?>" required class="form-control" min="0">
                </div>

                <div class="form-group third">
                    <label for="jumlah_total">Jumlah Total</label>
                    <input type="number" id="jumlah_total" name="jumlah_total" 
                           value="<?= htmlspecialchars($data['jumlah_total']) ?>" required class="form-control" min="0">
                </div>

                <div class="form-group third">
                    <label for="satuan">Satuan</label>
                    <input type="text" id="satuan" name="satuan" 
                           value="<?= htmlspecialchars($data['satuan']) ?>" required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group third">
                    <label for="kondisi">Kondisi</label>
                    <select id="kondisi" name="kondisi" required class="form-control">
                        <option value="">Pilih Kondisi</option>
                        <option value="Baru" <?= ($data['kondisi'] == 'Baru') ? 'selected' : '' ?>>Baru</option>
                        <option value="Lama" <?= ($data['kondisi'] == 'Lama') ? 'selected' : '' ?>>Lama</option>
                    </select>
                </div>

                <div class="form-group third">
                    <label for="sumber">Sumber Pengadaan</label>
                    <select id="sumber" name="sumber" required class="form-control">
                        <option value="">Pilih Sumber</option>
                        <option value="Beli" <?= ($data['sumber'] == 'Beli') ? 'selected' : '' ?>>Beli</option>
                        <option value="Donasi" <?= ($data['sumber'] == 'Donasi') ? 'selected' : '' ?>>Donasi</option>
                        <option value="Hibah" <?= ($data['sumber'] == 'Hibah') ? 'selected' : '' ?>>Hibah</option>
                    </select>
                </div>

                <div class="form-group third">
                    <label for="tgl_pengadaan">Tanggal Pengadaan</label>
                    <input type="date" id="tgl_pengadaan" name="tgl_pengadaan" 
                           value="<?= htmlspecialchars($data['tgl_pengadaan']) ?>" required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="harga">Harga</label>
                    <input type="text" id="harga" name="harga" 
                           value="<?= number_format($data['harga'], 0, ',', '.') ?>" 
                           required class="form-control" onkeyup="formatRupiah(this)">
                </div>

                <div class="form-group half">
                    <label for="keterangan">Keterangan</label>
                    <textarea id="keterangan" name="keterangan" class="form-control"><?= htmlspecialchars($data['keterangan']) ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
                <a href="index_admin.php?page_admin=data_inventaris/benediktus/data_inventaris_benediktus" class="btn-cancel">
                    <i class="fas fa-times"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>

<style>
/* Same styles as tambah_inventaris.php */
</style>

<script>
function formatRupiah(input) {
    let value = input.value.replace(/[^\d]/g, '');
    if(value != '') {
        value = parseInt(value).toLocaleString('id-ID');
        input.value = value;
    }
}

function validateForm() {
    const jumlah = parseInt(document.getElementById('jumlah').value) || 0;
    const jumlahTotal = parseInt(document.getElementById('jumlah_total').value) || 0;
    
    if(jumlahTotal < jumlah) {
        alert('Jumlah total tidak boleh lebih kecil dari jumlah tersedia');
        return false;
    }
    
    return true;
}
</script>


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
    background:#3498db;
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

<script>
function formatRupiah(input) {
    let value = input.value.replace(/[^\d]/g, '');
    if(value != '') {
        value = parseInt(value).toLocaleString('id-ID');
        input.value = value;
    }
}


</script>