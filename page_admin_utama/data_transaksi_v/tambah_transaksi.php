<?php
require_once("config/koneksi.php");

// Fungsi generate nomor peminjaman otomatis
function generateNoPeminjaman($koneksi) {
    $prefix = "PJ";

    // Ambil no_peminjaman terakhir
    $query = "SELECT no_peminjaman FROM peminjaman 
              WHERE no_peminjaman LIKE '$prefix%' 
              ORDER BY no_peminjaman DESC LIMIT 1";
    $result = mysqli_query($koneksi, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        $lastNumber = (int)substr($row['no_peminjaman'], 2); 
        $newNumber = str_pad($lastNumber + 1, 7, "0", STR_PAD_LEFT);
    } else {
        $newNumber = "0000001"; 
    }

    return $prefix . $newNumber; 
}

$inventaris = mysqli_query($koneksi, "SELECT kode_barang, nama_barang FROM inventaris ORDER BY kode_barang ASC");

if(isset($_POST['submit'])) {
    $no_peminjaman = generateNoPeminjaman($koneksi);
    $tanggal_pinjam = $_POST['tanggal_pinjam'];
    $lokasi_simpan = $_POST['lokasi_simpan'];
    $kode_barang = $_POST['kode_barang'];
    $nama_barang = $_POST['nama_barang'];
    $jumlah_pinjam = (int)$_POST['jumlah_pinjam'];
    $satuan = $_POST['satuan'];
    $lokasi_pinjam = $_POST['lokasi_pinjam'];
    $nama_peminjam = $_POST['nama_peminjam'];
    $keterangan = $_POST['keterangan'];
    $status = $_POST['status'];

    $query = "INSERT INTO peminjaman 
              (no_peminjaman, tanggal_pinjam, lokasi_simpan, kode_barang, nama_barang, 
               jumlah_pinjam, satuan, lokasi_pinjam, nama_peminjam, keterangan, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
              
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "sssssisisss", 
        $no_peminjaman, $tanggal_pinjam, $lokasi_simpan, $kode_barang, $nama_barang, 
        $jumlah_pinjam, $satuan, $lokasi_pinjam, $nama_peminjam, $keterangan, $status
    );
    
    if(mysqli_stmt_execute($stmt)) {
        echo "<script>
                alert('Data transaksi berhasil ditambahkan');
                window.location.href='index_admin_utama.php?page_admin_utama=data_transaksi_v/data_transaksi_v';
              </script>";
    } else {
        echo "<script>alert('Gagal menambahkan data transaksi');</script>";
    }
}
?>

<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>Tambah Data Transaksi</h2>
        </div>
        
        <form method="POST" class="add-form">
            <div class="form-row">
                <div class="form-group half">
                    <label for="no_peminjaman">No Peminjaman</label>
                    <input type="text" id="no_peminjaman" name="no_peminjaman" 
                           value="<?= generateNoPeminjaman($koneksi); ?>" readonly class="form-control">
                </div>
                <div class="form-group half">
                    <label for="tanggal_pinjam">Tanggal Pinjam</label>
                    <input type="date" id="tanggal_pinjam" name="tanggal_pinjam" required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="lokasi_simpan">Lokasi Simpan</label>
                    <input type="text" id="lokasi_simpan" name="lokasi_simpan" required class="form-control">
                </div>
                <div class="form-group half">
                    <label for="kode_barang">Kode Barang</label>
                    <select id="kode_barang" name="kode_barang" required onchange="updateNamaBarang()" class="form-control">
                        <option value="">Pilih Kode Barang</option>
                        <?php while($row = mysqli_fetch_assoc($inventaris)) { ?>
                            <option value="<?= $row['kode_barang']; ?>" data-nama="<?= htmlspecialchars($row['nama_barang']); ?>">
                                <?= $row['kode_barang']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="nama_barang">Nama Barang</label>
                    <input type="text" id="nama_barang" name="nama_barang" readonly required class="form-control">
                </div>
                <div class="form-group half">
                    <label for="jumlah_pinjam">Jumlah Pinjam</label>
                    <input type="number" id="jumlah_pinjam" name="jumlah_pinjam" min="1" required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="satuan">Satuan</label>
                    <input type="text" id="satuan" name="satuan" required class="form-control">
                </div>
                <div class="form-group half">
                    <label for="lokasi_pinjam">Lokasi Pinjam</label>
                    <input type="text" id="lokasi_pinjam" name="lokasi_pinjam" required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="nama_peminjam">Nama Peminjam</label>
                    <input type="text" id="nama_peminjam" name="nama_peminjam" required class="form-control">
                </div>
                <div class="form-group half">
                    <label for="status">Status</label>
                    <select id="status" name="status" required class="form-control">
                        <option value="">Pilih Status</option>
                        <option value="Dipinjam">Dipinjam</option>
                        <option value="Dikembalikan">Dikembalikan</option>
                    </select>
                </div>
            </div>

            <div class="form-group full">
                <label for="keterangan">Keterangan</label>
                <textarea id="keterangan" name="keterangan" class="form-control"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" name="submit" class="btn-submit">Simpan</button>
                <a href="index_admin_utama.php?page_admin_utama=data_transaksi_v/data_transaksi_v" class="btn-cancel">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
function updateNamaBarang() {
    const select = document.getElementById('kode_barang');
    const selectedOption = select.options[select.selectedIndex];
    const namaBarang = selectedOption.getAttribute('data-nama');
    document.getElementById('nama_barang').value = namaBarang || '';
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

<script>
function formatRupiah(input) {
    let value = input.value.replace(/[^\d]/g, '');
    if(value != '') {
        value = parseInt(value).toLocaleString('id-ID');
        input.value = value;
    }
}


</script>