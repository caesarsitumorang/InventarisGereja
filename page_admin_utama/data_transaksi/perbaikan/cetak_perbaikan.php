<?php
require_once __DIR__ . '../../../../vendor/autoload.php'; 
include "config/koneksi.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ambil parameter filter
$lokasi_filter = isset($_GET['lokasi']) ? trim($_GET['lokasi']) : '';
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : '';
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : '';

if (empty($tanggal_awal) || empty($tanggal_akhir)) {
    die("Tanggal awal dan tanggal akhir harus diisi");
}

$mpdf = new \Mpdf\Mpdf([
    'margin_top' => 80,
    'margin_bottom' => 30,
    'margin_left' => 15,
    'margin_right' => 15,
    'format' => 'A4',
    'orientation' => 'L'
]);

$mpdf->showImageErrors = true; 

// Admin login
$id_admin = $_SESSION['id_akun'] ?? null;
$nama_admin = '-';
if ($id_admin) {
    $admin = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT username FROM pengguna WHERE id_akun = '$id_admin'"));
    if ($admin) $nama_admin = $admin['username'];
}

// Build where clause
$where = "WHERE 1=1";
if (!empty($lokasi_filter)) {
    $lokasi_escaped = mysqli_real_escape_string($koneksi, $lokasi_filter);
    $where .= " AND lokasi_simpan = '$lokasi_escaped'";
}

if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $tanggal_awal_escaped = mysqli_real_escape_string($koneksi, $tanggal_awal);
    $tanggal_akhir_escaped = mysqli_real_escape_string($koneksi, $tanggal_akhir);
    $where .= " AND DATE(tanggal_perbaikan) BETWEEN '$tanggal_awal_escaped' AND '$tanggal_akhir_escaped'";
}

// Query data perbaikan
$query = "SELECT no_perbaikan, tanggal_perbaikan, no_kerusakan, 
                 kode_barang, nama_barang, lokasi_simpan, jumlah, satuan, 
                 biaya_perbaikan, keterangan
          FROM perbaikan $where 
          ORDER BY tanggal_perbaikan DESC, no_perbaikan ASC";
$result = mysqli_query($koneksi, $query);
$total_perbaikan = mysqli_num_rows($result);

// Path logo
$logoPath = $_SERVER['DOCUMENT_ROOT'] . "/inventaris-gereja/img/logo.jpg";

$lokasi_text = !empty($lokasi_filter) ? $lokasi_filter : 'Semua Lokasi';
$periode_text = date('d/m/Y', strtotime($tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($tanggal_akhir));

$header = '
<div style="text-align:center; font-family:Arial, sans-serif; margin-bottom:25px;"> 
  <img src="'.$logoPath.'" width="60" height="60" style="object-fit:contain; margin-bottom:8px;" />
  <h2 style="margin:0; color:#1a237e; font-size:18px;">SISTEM INVENTARIS GEREJA</h2>
  <p style="margin:3px 0; font-size:14px; color:#444;">Gereja St Yohanes Pembaptis</p>
  <h3 style="margin:8px 0; color:#333; font-size:16px;">LAPORAN DATA PERBAIKAN</h3>
  <p style="margin:3px 0; font-size:13px; color:#666; font-weight:bold;">Lokasi: '.$lokasi_text.'</p>
  <p style="margin:3px 0; font-size:13px; color:#666; font-weight:bold;">Periode: '.$periode_text.'</p>
  <p style="margin:3px 0; font-size:11px; color:#777; border-bottom:1px solid #ccc; padding-bottom:8px;">
      Dicetak pada '.date('d F Y, H:i').' WIB oleh '.$nama_admin.'
  </p>
</div>
<div style="height:30px;"></div>
';
$mpdf->SetHTMLHeader($header);

$footer = '
<div style="padding-top:5px; font-size:10px; text-align:center; color:#555; font-family:Arial, sans-serif;">
  Halaman {PAGENO} dari {nbpg} | Laporan Perbaikan - '.$lokasi_text.' | '.$periode_text.'
</div>';
$mpdf->SetHTMLFooter($footer);

// Konten PDF
$html = '
<style>
body { font-family: Arial, sans-serif; font-size: 10px; line-height: 1.3; }
h3 { color: #1a237e; margin-top: 20px; font-size: 14px; margin-bottom: 10px; }
.table { border-collapse: collapse; width: 100%; margin-top: 15px; font-size: 9px; }
.table th, .table td { padding: 5px 4px; text-align: center; border: 1px solid #ddd; }
.table th { background: #1a237e; color: white; font-weight: bold; font-size: 9px; text-transform: uppercase; }
.table tr:nth-child(even) { background: #f8f9fa; }
.text-left { text-align: left !important; }
.text-right { text-align: right !important; }
</style>

<h3>Data Perbaikan - '.$lokasi_text.' ('.$periode_text.')</h3>
<table class="table">
  <tr>
    <th width="4%">No</th>
    <th width="10%">No Perbaikan</th>
    <th width="10%">Tgl Perbaikan</th>
    <th width="10%">No Kerusakan</th>
    <th width="8%">Kode Barang</th>
    <th width="15%">Nama Barang</th>
    <th width="12%">Lokasi Simpan</th>
    <th width="6%">Jumlah</th>
    <th width="7%">Satuan</th>
    <th width="10%">Biaya Perbaikan</th>
    <th width="18%">Keterangan</th>
  </tr>';

if ($total_perbaikan > 0) {
    $no = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $html .= '
        <tr>
            <td>'.$no++.'</td>
            <td class="text-left">'.htmlspecialchars($row['no_perbaikan']).'</td>
            <td>'.date('d/m/Y', strtotime($row['tanggal_perbaikan'])).'</td>
            <td>'.htmlspecialchars($row['no_kerusakan']).'</td>
            <td>'.htmlspecialchars($row['kode_barang']).'</td>
            <td class="text-left">'.htmlspecialchars($row['nama_barang']).'</td>
            <td class="text-left">'.htmlspecialchars($row['lokasi_simpan']).'</td>
            <td class="text-right">'.htmlspecialchars($row['jumlah']).'</td>
            <td>'.htmlspecialchars($row['satuan']).'</td>
            <td class="text-right">'.number_format($row['biaya_perbaikan'],0,',','.').'</td>
            <td class="text-left">'.htmlspecialchars($row['keterangan']).'</td>
        </tr>';
    }
} else {
    $html .= '
    <tr>
        <td colspan="11" style="text-align:center; padding:20px; color:#777; font-style:italic;">
            Tidak ada data perbaikan untuk lokasi: '.$lokasi_text.' pada periode '.$periode_text.'
        </td>
    </tr>';
}

$html .= '</table>';

ob_clean();
$mpdf->WriteHTML($html);
$filename = "Laporan_Perbaikan_" . str_replace(' ', '_', $lokasi_text) . "_" . 
            str_replace('/', '-', date('d-m-Y', strtotime($tanggal_awal))) . "_sampai_" . 
            str_replace('/', '-', date('d-m-Y', strtotime($tanggal_akhir))) . ".pdf";
$mpdf->Output($filename, "I");
exit;
