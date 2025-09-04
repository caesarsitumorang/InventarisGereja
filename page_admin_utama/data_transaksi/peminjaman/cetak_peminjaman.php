<?php
require_once __DIR__ . '../../../../vendor/autoload.php'; 
include "../../../config/koneksi.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ambil parameter lokasi dari URL
$lokasi_filter = isset($_GET['lokasi']) ? trim($_GET['lokasi']) : '';

$mpdf = new \Mpdf\Mpdf([
    'margin_top' => 60, 
    'margin_bottom' => 30,
    'margin_left' => 15,
    'margin_right' => 15,
    'format' => 'A4',
    'orientation' => 'L' // Landscape untuk tabel yang lebih lebar
]);

$mpdf->showImageErrors = true; 

// Admin login
$id_admin = $_SESSION['id_akun'] ?? null;
$nama_admin = '-';
if ($id_admin) {
    $admin = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT username FROM pengguna WHERE id_akun = '$id_admin'"));
    if ($admin) $nama_admin = $admin['username'];
}

// Build where clause untuk filter lokasi
$where = "";
if (!empty($lokasi_filter)) {
    $lokasi_escaped = mysqli_real_escape_string($koneksi, $lokasi_filter);
    $where = "WHERE lokasi_simpan = '$lokasi_escaped'";
}

// Query untuk ambil data peminjaman
$query = "SELECT no_peminjaman, tanggal_pinjam, lokasi_simpan, 
                 kode_barang, nama_barang, jumlah_pinjam, satuan, 
                 lokasi_pinjam, nama_peminjam, keterangan, status 
          FROM peminjaman $where 
          ORDER BY tanggal_pinjam DESC, no_peminjaman ASC";
$result = mysqli_query($koneksi, $query);

// Hitung summary data
$total_peminjaman = mysqli_num_rows($result);
$sudah_kembali = 0;
$belum_kembali = 0;

// Reset pointer untuk menghitung status
mysqli_data_seek($result, 0);
while ($row = mysqli_fetch_assoc($result)) {
    if (strtolower($row['status']) == 'sudah kembali') {
        $sudah_kembali++;
    } else {
        $belum_kembali++;
    }
}

// Reset pointer untuk generate tabel
mysqli_data_seek($result, 0);

// Path logo
$logoPath = $_SERVER['DOCUMENT_ROOT'] . "/inventaris-gereja/img/logo.jpg";

// Header dengan informasi lokasi
$lokasi_text = !empty($lokasi_filter) ? $lokasi_filter : 'Semua Lokasi';
$header = '
<div style="text-align:center; font-family:Arial, sans-serif; margin-bottom:15px;">
  <img src="'.$logoPath.'" width="60" height="60" style="object-fit:contain; margin-bottom:8px;" />
  <h2 style="margin:0; color:#1a237e; font-size:18px;">SISTEM INVENTARIS GEREJA</h2>
  <p style="margin:3px 0; font-size:14px; color:#444;">Gereja St Yohanes Pembaptis</p>
  <h3 style="margin:8px 0; color:#333; font-size:16px;">LAPORAN DATA PEMINJAMAN</h3>
  <p style="margin:3px 0; font-size:13px; color:#666; font-weight:bold;">Lokasi: '.$lokasi_text.'</p>
  <p style="margin:3px 0; font-size:11px; color:#777; border-bottom:1px solid #ccc; padding-bottom:8px;">
      Dicetak pada '.date('d F Y, H:i').' WIB oleh '.$nama_admin.'
  </p>
</div>';
$mpdf->SetHTMLHeader($header);

// Footer
$footer = '
<div style="padding-top:5px; font-size:10px; text-align:center; color:#555; font-family:Arial, sans-serif;">
  Halaman {PAGENO} dari {nbpg} | Laporan Peminjaman - '.$lokasi_text.'
</div>';
$mpdf->SetHTMLFooter($footer);

// Konten PDF
$html = '
<style>
body { 
    font-family: Arial, sans-serif; 
    font-size: 10px;
    line-height: 1.3;
}
h3 { 
    color: #1a237e; 
    margin-top: 20px;
    font-size: 14px;
    margin-bottom: 10px;
}
.summary-container {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    justify-content: center;
}
.summary-box {
    padding: 12px 15px;
    background: #f9f9f9;
    border-radius: 8px;
    text-align: center;
    min-width: 120px;
    border: 1px solid #ddd;
}
.summary-box .title {
    font-size: 10px;
    color: #666;
    margin-bottom: 3px;
    font-weight: normal;
}
.summary-box .value {
    font-size: 18px;
    font-weight: bold;
    color: #1a237e;
}
.summary-box.kembali .value { color: #27ae60; }
.summary-box.pinjam .value { color: #f39c12; }
.table {
    border-collapse: collapse;
    width: 100%;
    margin-top: 15px;
    font-size: 9px;
}
.table th, .table td {
    padding: 5px 4px;
    text-align: center;
    border: 1px solid #ddd;
}
.table th {
    background: #1a237e;
    color: white;
    font-weight: bold;
    font-size: 9px;
    text-transform: uppercase;
}
.table tr:nth-child(even) { 
    background: #f8f9fa; 
}
.status-kembali { 
    color: #27ae60; 
    font-weight: bold; 
    background: #d5f4e6;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 8px;
}
.status-pinjam { 
    color: #f39c12; 
    font-weight: bold; 
    background: #fef5e7;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 8px;
}
.text-left { text-align: left !important; }
.text-right { text-align: right !important; }
</style>

<h3>Data Peminjaman - '.$lokasi_text.'</h3>
<table class="table">
  <tr>
    <th width="4%">No</th>
    <th width="10%">No Peminjaman</th>
    <th width="8%">Tanggal</th>
    <th width="12%">Lokasi</th>
    <th width="8%">Kode Barang</th>
    <th width="15%">Nama Barang</th>
    <th width="5%">Jumlah</th>
    <th width="6%">Satuan</th>
    <th width="12%">Peminjam</th>
    <th width="10%">Lokasi Penggunaan</th>
    <th width="10%">Status</th>
  </tr>';

if ($total_peminjaman > 0) {
    $no = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        // Format status
        $status_class = (strtolower($row['status']) == 'sudah kembali') ? 'status-kembali' : 'status-pinjam';
        
        $html .= '
        <tr>
            <td>'.$no++.'</td>
            <td class="text-left">'.htmlspecialchars($row['no_peminjaman']).'</td>
            <td>'.date('d/m/Y', strtotime($row['tanggal_pinjam'])).'</td>
            <td class="text-left">'.htmlspecialchars($row['lokasi_simpan']).'</td>
            <td class="text-left">'.htmlspecialchars($row['kode_barang']).'</td>
            <td class="text-left">'.htmlspecialchars($row['nama_barang']).'</td>
            <td class="text-right">'.htmlspecialchars($row['jumlah_pinjam']).'</td>
            <td>'.htmlspecialchars($row['satuan']).'</td>
            <td class="text-left">'.htmlspecialchars($row['nama_peminjam']).'</td>
            <td class="text-left">'.htmlspecialchars($row['lokasi_pinjam']).'</td>
            <td><span class="'.$status_class.'">'.htmlspecialchars($row['status']).'</span></td>
        </tr>';
    }
} else {
    $html .= '
    <tr>
        <td colspan="11" style="text-align:center; padding:20px; color:#777; font-style:italic;">
            Tidak ada data peminjaman untuk lokasi: '.$lokasi_text.'
        </td>
    </tr>';
}

$html .= '
</table>

<div style="margin-top:40px; display:flex; justify-content:space-between;">
    <div style="width:45%;">
        <h4 style="margin:0; font-size:12px; color:#333;">Keterangan:</h4>
        <p style="margin:5px 0; font-size:10px; color:#666;">
            • Status "Sudah Kembali" menunjukkan barang telah dikembalikan<br>
            • Status "Belum Kembali" menunjukkan barang masih dipinjam<br>
            • Laporan ini dibuat berdasarkan data per '.date('d F Y, H:i').' WIB
        </p>
    </div>
    <div style="width:45%; text-align:right;">
        <p style="margin:10px 0; font-size:11px;">Medan, '.date('d F Y').'</p>
        <p style="margin:60px 0 10px 0; font-weight:bold; font-size:12px; border-top:1px solid #333; padding-top:5px; display:inline-block;">
            '.$nama_admin.'
        </p>
        <p style="margin:0; font-size:10px; color:#666;">Petugas Sistem Inventaris</p>
    </div>
</div>
';

ob_clean();
$mpdf->WriteHTML($html);
$filename = "Laporan_Peminjaman_" . str_replace(' ', '_', $lokasi_text) . "_" . date('Y-m-d') . ".pdf";
$mpdf->Output($filename, "I");
exit;