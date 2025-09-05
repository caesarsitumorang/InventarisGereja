<?php
require_once __DIR__ . '../../../../vendor/autoload.php'; 
include "../../../config/koneksi.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$mpdf = new \Mpdf\Mpdf([
    'margin_top' => 60, 
    'margin_bottom' => 30,
    'margin_left' => 15,
    'margin_right' => 15,
    'format' => 'A4'
]);

$mpdf->showImageErrors = true; 

// Admin login
$id_admin = $_SESSION['id_akun'] ?? null;
$nama_admin = '-';
if ($id_admin) {
    $admin = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT username FROM pengguna WHERE id_akun = '$id_admin'"));
    if ($admin) $nama_admin = $admin['username'];
}

// Ambil data inventaris
$lokasi = "Stasi St. Paulus Rasul (Siak Merambai)";
$query = "SELECT * FROM inventaris WHERE lokasi_simpan = '$lokasi' ORDER BY tgl_pengadaan DESC";
$result = mysqli_query($koneksi, $query);


// Get summary data
$summary = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT 
        COUNT(*) as total_items,
        SUM(CASE WHEN kondisi = 'Baik' THEN 1 ELSE 0 END) as kondisi_baik,
        SUM(CASE WHEN kondisi = 'Rusak Ringan' THEN 1 ELSE 0 END) as rusak_ringan,
        SUM(CASE WHEN kondisi = 'Rusak Berat' THEN 1 ELSE 0 END) as rusak_berat,
        COUNT(DISTINCT kategori) as total_kategori,
        SUM(harga) as total_nilai
    FROM inventaris
    WHERE lokasi_simpan = '$lokasi'
"));

// Path logo
$logoPath = $_SERVER['DOCUMENT_ROOT'] . "/inventaris-gereja/img/logo.jpg";

// Header minimalis
$header = '
<div style="text-align:center; font-family:Arial, sans-serif; margin-bottom:10px;">
  <img src="'.$logoPath.'" width="60" height="60" style="object-fit:contain; margin-bottom:5px;" />
  <h2 style="margin:0; color:#1a237e;">SISTEM INVENTARIS GEREJA</h2>
  <p style="margin:2px 0; font-size:13px; color:#444;">Gereja St Yohanes Pembaptis</p>
  <h3 style="margin:5px 0; color:#333;">LAPORAN DATA INVENTARIS</h3>
  <p style="margin:3px 0; font-size:11px; color:#777; border-bottom:1px solid #ccc; padding-bottom:5px;">
      Dicetak pada '.date('d F Y, H:i').' WIB oleh '.$nama_admin.'
  </p>
</div>';
$mpdf->SetHTMLHeader($header);

// Footer sederhana
$footer = '
<div style="padding-top:5px; font-size:10px; text-align:center; color:#555; font-family:Arial, sans-serif;">
  Halaman {PAGENO} dari {nbpg}
</div>';
$mpdf->SetHTMLFooter($footer);

// Konten
$html = '
<style>
body { 
    font-family: Arial, sans-serif; 
    font-size: 11px;
    line-height: 1.4;
}
h3 { 
    color: #1a237e; 
    margin-top: 25px;
    font-size: 14px;
    margin-bottom: 10px;
}
.summary-container {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
}
.summary-box {
    flex: 1;
    min-width: 150px;
    padding: 10px 12px;
    background: #f9f9f9;
    border-radius: 6px;
}
.summary-box .title {
    font-size: 11px;
    color: #666;
    margin-bottom: 3px;
}
.summary-box .value {
    font-size: 16px;
    font-weight: bold;
    color: #1a237e;
}
.table {
    border-collapse: collapse;
    width: 100%;
    margin-top: 10px;
    font-size: 10px;
}
.table th, .table td {
    padding: 6px;
    text-align: center;
}
.table th {
    background: #1a237e;
    color: white;
    font-weight: normal;
    font-size: 10px;
    text-transform: uppercase;
}
.table tr:nth-child(even) { 
    background: #f8f9fa; 
}
.status-baik { color: #4caf50; font-weight: bold; }
.status-rusak-ringan { color: #ff9800; font-weight: bold; }
.status-rusak-berat { color: #f44336; font-weight: bold; }
.price { text-align: right !important; color: #1a237e; font-weight: bold; }
</style>

<div class="summary-container">
    <div class="summary-box">
        <div class="title">Total Barang</div>
        <div class="value">'.$summary['total_items'].'</div>
    </div>
    <div class="summary-box">
        <div class="title">Kondisi Baik</div>
        <div class="value" style="color: #4caf50;">'.$summary['kondisi_baik'].'</div>
    </div>
    <div class="summary-box">
        <div class="title">Rusak Ringan</div>
        <div class="value" style="color: #ff9800;">'.$summary['rusak_ringan'].'</div>
    </div>
    <div class="summary-box">
        <div class="title">Rusak Berat</div>
        <div class="value" style="color: #f44336;">'.$summary['rusak_berat'].'</div>
    </div>
    <div class="summary-box">
        <div class="title">Total Nilai Inventaris</div>
        <div class="value">Rp '.number_format($summary['total_nilai'],0,",",".").'</div>
    </div>
</div>

<h3>Data Inventaris</h3>
<table class="table">
  <tr>
    <th>No</th>
    <th>Kode Barang</th>
    <th>Nama Barang</th>
    <th>Kategori</th>
    <th>Lokasi Simpan</th>
    <th>Jumlah</th>
    <th>Jumlah Total</th>
    <th>Satuan</th>
    <th>Tgl Pengadaan</th>
    <th>Kondisi</th>
    <th>Sumber</th>
    <th>Harga</th>
    <th>Keterangan</th>
  </tr>';

$no = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $html .= '
    <tr>
        <td>'.$no++.'</td>
        <td>'.htmlspecialchars($row['kode_barang']).'</td>
        <td>'.htmlspecialchars($row['nama_barang']).'</td>
        <td>'.htmlspecialchars($row['kategori']).'</td>
        <td>'.htmlspecialchars($row['lokasi_simpan']).'</td>
        <td>'.htmlspecialchars($row['jumlah']).'</td>
        <td>'.htmlspecialchars($row['jumlah_total']).'</td>
        <td>'.htmlspecialchars($row['satuan']).'</td>
        <td>'.htmlspecialchars($row['tgl_pengadaan']).'</td>
        <td>'.htmlspecialchars($row['kondisi']).'</td>
        <td>'.htmlspecialchars($row['sumber']).'</td>
        <td>Rp '.number_format($row['harga'], 0, ",", ".").'</td>
        <td>'.htmlspecialchars($row['keterangan']).'</td>
    </tr>';
}

$html .= '
</table>

<div style="margin-top:50px; text-align:right; font-size:12px;">
  <p>Medan, '.date('d F Y').'</p>
  <p style="margin-top:60px; font-weight:bold;">'.$nama_admin.'</p>
</div>
';

ob_clean();
$mpdf->WriteHTML($html);
$mpdf->Output("Laporan_Inventaris_" . date('Y-m-d') . ".pdf", "I");
