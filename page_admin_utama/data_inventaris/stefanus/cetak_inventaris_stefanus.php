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
$lokasi = "Stasi St. Stefanus (Zamrud)";
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
// Header yang diperbaiki dengan layout sejajar
$header = '
<table style="width: 100%; border: none; padding: 15px; background: linear-gradient(135deg, #E8F4FD 0%, #D1E7F7 100%); border-bottom: 3px solid #2E86C1;">
    <tr>
        <td style="width: 100px; vertical-align: middle; text-align: left; border: none;">
            <img src="'.$logoPath.'" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" alt="Logo Gereja">
        </td>
        <td style="vertical-align: middle; text-align: center; border: none; padding-left: 20px;">
            <div style="color: #1B4F72; font-family: Georgia, serif;">
                <h2 style="margin: 0 0 5px 0; font-size: 18px; font-weight: bold; color: #154360; text-shadow: 1px 1px 2px rgba(0,0,0,0.1);">
                    Gereja Katolik Santo Yohanes Pembaptis Perawang
                </h2>
                <h3 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #2874A6; font-style: italic;">
                    Keuskupan Agung Padang
                </h3>
                <div style="border-top: 1px solid #5DADE2; padding-top: 8px; margin-top: 8px;">
                    <p style="margin: 2px 0; font-size: 11px; color: #21618C; font-weight: 500;">
                        Jl. Raya Minas - Perawang No. Km 3, Tualang, Kab. Siak, Prov. Riau, 28685
                    </p>
                    <p style="margin: 2px 0; font-size: 11px; color: #21618C; font-weight: 500;">
                        ☎ (061) 1234567 | ✉ parokiyohanes@gmail.or.id
                    </p>
                </div>
            </div>
        </td>
        <td style="width: 100px; border: none;"></td>
    </tr>
</table>';

$mpdf->SetHTMLHeader($header);

// Footer dengan warna yang serasi
$footer = '
<div style="padding: 10px; font-size: 10px; text-align: center; color: #154360; font-family: Arial, sans-serif; background-color: #EBF5FB; border-top: 2px solid #5DADE2;">
    <strong>Halaman {PAGENO} dari {nbpg}</strong> | Dicetak pada: '.date('d-m-Y H:i:s').'
</div>';
$mpdf->SetHTMLFooter($footer);

// Konten dengan palet warna yang diperbaiki
$html = '
<style>
body { 
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
    font-size: 12px;
    line-height: 1.5;
    color: #2C3E50;
}

.title-laporan {
    text-align: center;
    font-weight: bold;
    font-size: 16px;
    margin: 25px 0;
    padding: 12px;
    color: #FFFFFF;
    background: linear-gradient(135deg, #2E86C1 0%, #5DADE2 100%);
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.table {
    border-collapse: collapse;
    width: 100%;
    margin-top: 20px;
    border: 2px solid #2E86C1;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.table th {
    padding: 12px 8px;
    text-align: center;
    border: 1px solid #2E86C1;
    font-size: 11px;
    background: linear-gradient(135deg, #2E86C1 0%, #5DADE2 100%);
    color: #FFFFFF;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    padding: 10px 8px;
    text-align: center;
    border: 1px solid #AED6F1;
    font-size: 10px;
    color: #2C3E50;
}

.table tr:nth-child(even) {
    background-color: #EBF5FB;
}

.table tr:nth-child(odd) {
    background-color: #FFFFFF;
}

.table tr:hover {
    background-color: #D6EAF8;
}

.signature {
    margin-top: 60px;
    text-align: right;
    font-size: 12px;
    color: #1B4F72;
    font-family: Georgia, serif;
}

.signature p {
    margin: 5px 0;
    color: #2C3E50;
}

.signature-name {
    margin-top: 70px;
    font-weight: bold;
    text-decoration: underline;
    font-size: 13px;
    color: #154360;
}

.info-box {
    background: linear-gradient(135deg, #EBF5FB 0%, #D6EAF8 100%);
    padding: 15px;
    border-left: 4px solid #2E86C1;
    margin: 20px 0;
    border-radius: 4px;
}

.info-text {
    font-size: 11px;
    color: #1B4F72;
    font-style: italic;
    text-align: center;
}
</style>

<h3 class="title-laporan">Data Inventaris Gereja</h3>

<table class="table">
  <thead>
    <tr>
      <th style="width: 4%;">No</th>
      <th style="width: 8%;">Kode</th>
      <th style="width: 12%;">Nama Barang</th>
      <th style="width: 8%;">Kategori</th>
      <th style="width: 10%;">Lokasi</th>
      <th style="width: 5%;">Jml</th>
      <th style="width: 5%;">Total</th>
      <th style="width: 6%;">Satuan</th>
      <th style="width: 8%;">Tgl Pengadaan</th>
      <th style="width: 8%;">Kondisi</th>
      <th style="width: 8%;">Sumber</th>
      <th style="width: 10%;">Harga</th>
      <th style="width: 8%;">Keterangan</th>
    </tr>
  </thead>
  <tbody>';

$no = 1;
while ($row = mysqli_fetch_assoc($result)) {
    // Warna kondisi
    $kondisiColor = '';
    switch($row['kondisi']) {
        case 'Baik': $kondisiColor = 'color: #27AE60; font-weight: bold;'; break;
        case 'Rusak Ringan': $kondisiColor = 'color: #F39C12; font-weight: bold;'; break;
        case 'Rusak Berat': $kondisiColor = 'color: #E74C3C; font-weight: bold;'; break;
        default: $kondisiColor = 'color: #2C3E50;';
    }
    
    $html .= '
    <tr>
        <td>'.$no++.'</td>
        <td style="font-weight: 600;">'.htmlspecialchars($row['kode_barang']).'</td>
        <td style="text-align: left; font-weight: 500;">'.htmlspecialchars($row['nama_barang']).'</td>
        <td>'.htmlspecialchars($row['kategori']).'</td>
        <td style="font-size: 9px;">'.htmlspecialchars($row['lokasi_simpan']).'</td>
        <td><strong>'.htmlspecialchars($row['jumlah']).'</strong></td>
        <td><strong>'.htmlspecialchars($row['jumlah_total']).'</strong></td>
        <td>'.htmlspecialchars($row['satuan']).'</td>
        <td>'.date('d/m/Y', strtotime($row['tgl_pengadaan'])).'</td>
        <td style="'.$kondisiColor.'">'.htmlspecialchars($row['kondisi']).'</td>
        <td style="font-size: 9px;">'.htmlspecialchars($row['sumber']).'</td>
        <td style="color: #27AE60; font-weight: bold;">Rp '.number_format($row['harga'], 0, ",", ".").'</td>
        <td style="font-size: 9px; text-align: left;">'.htmlspecialchars($row['keterangan']).'</td>
    </tr>';
}

$html .= '
  </tbody>
</table>

<div class="signature">
    <p><strong>Perawang, '.date('d').' Agustus '.date('Y').'</strong></p>
    <p>Pastor Paroki,</p>
    <div class="signature-name">P. Antonius Dwi Raharjo, SCJ.</div>
</div>
';

ob_clean();
$mpdf->WriteHTML($html);
$mpdf->Output("Laporan_Inventaris_" . date('Y-m-d') . ".pdf", "I");
?>