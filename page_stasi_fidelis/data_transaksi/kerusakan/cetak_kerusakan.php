<?php
require_once __DIR__ . '/../../../vendor/autoload.php'; 
include "config/koneksi.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ambil parameter filter
$lokasi_filter = $_GET['lokasi'] ?? '';
$tanggal_awal = $_GET['tanggal_awal'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';

if (empty($tanggal_awal) || empty($tanggal_akhir)) {
    die("Tanggal awal dan tanggal akhir harus diisi!");
}

// Inisialisasi mPDF
$mpdf = new \Mpdf\Mpdf([
    'margin_top' => 60,
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
    $where .= " AND DATE(tanggal_kerusakan) BETWEEN '$tanggal_awal_escaped' AND '$tanggal_akhir_escaped'";
}

// Query data kerusakan
$query = "SELECT no_kerusakan, tanggal_kerusakan, lokasi_simpan,
                 kode_barang, nama_barang, jumlah, satuan, keterangan
          FROM kerusakan $where 
          ORDER BY tanggal_kerusakan DESC, no_kerusakan ASC";
$result = mysqli_query($koneksi, $query);
$total_kerusakan = mysqli_num_rows($result);

// Path logo (pastikan path benar)
$logoPath = $_SERVER['DOCUMENT_ROOT'] . "/inventaris-gereja/img/logo.jpg";

// Variabel header
$lokasi_text = !empty($lokasi_filter) ? $lokasi_filter : 'Semua Lokasi';
$periode_text = date('d/m/Y', strtotime($tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($tanggal_akhir));

// Header
$header = '
<table style="width: 100%; border: none; padding: 15px; background: #EAF2F8; border-bottom: 2px solid #2874A6;">
    <tr>
        <td style="width: 100px; text-align: left; border: none;">
            <img src="'.$logoPath.'" style="width: 70px; height: 70px; object-fit: cover; border-radius: 8px;">
        </td>
        <td style="text-align: center; border: none;">
            <h2 style="margin: 0; font-size: 16px; font-weight: bold; color: #154360;">
                Gereja Katolik Santo Yohanes Pembaptis Perawang
            </h2>
            <h3 style="margin: 5px 0; font-size: 13px; font-style: italic; color: #21618C;">
                Keuskupan Agung Padang
            </h3>
            <p style="margin: 2px 0; font-size: 11px; color: #34495E;">
                Jl. Raya Minas - Perawang No. Km 3, Tualang, Kab. Siak, Prov. Riau, 28685
            </p>
            <p style="margin: 2px 0; font-size: 11px; color: #34495E;">
                ☎ (061) 1234567 | ✉ parokiyohanes@gmail.or.id
            </p>
        </td>
        <td style="width: 100px; border: none;"></td>
    </tr>
</table>';
$mpdf->SetHTMLHeader($header);

// Footer
$footer = '
<div style="padding: 8px; font-size: 10px; text-align: center; color: #154360; background-color: #EBF5FB; border-top: 1px solid #5DADE2;">
    Dicetak oleh: '.$nama_admin.' | Halaman {PAGENO} dari {nbpg} | Dicetak pada '.date('d-m-Y H:i:s').'
</div>';
$mpdf->SetHTMLFooter($footer);

// Konten PDF
$html = '
<style>
body { font-family: Arial, sans-serif; font-size: 11px; color: #2C3E50; }
.table { border-collapse: collapse; width: 100%; margin-top: 20px; }
.table th, .table td { border: 1px solid #7FB3D5; padding: 6px; text-align: center; }
.table th { background: #2874A6; color: #fff; font-size: 11px; }
.table tr:nth-child(even) { background: #EBF5FB; }
.table tr:nth-child(odd) { background: #FFFFFF; }
</style>

<h3 style="text-align:center; margin-bottom:10px;">
    Data Kerusakan - '.$lokasi_text.' ('.$periode_text.')
</h3>
<table class="table">
  <tr>
    <th>No</th>
    <th>No Kerusakan</th>
    <th>Tgl Kerusakan</th>
    <th>Lokasi Simpan</th>
    <th>Kode Barang</th>
    <th>Nama Barang</th>
    <th>Jumlah</th>
    <th>Satuan</th>
    <th>Keterangan</th>
  </tr>';

if ($total_kerusakan > 0) {
    $no = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $html .= '<tr>
            <td>'.$no++.'</td>
            <td>'.htmlspecialchars($row['no_kerusakan']).'</td>
            <td>'.date('d/m/Y', strtotime($row['tanggal_kerusakan'])).'</td>
            <td>'.htmlspecialchars($row['lokasi_simpan']).'</td>
            <td>'.htmlspecialchars($row['kode_barang']).'</td>
            <td>'.htmlspecialchars($row['nama_barang']).'</td>
            <td>'.htmlspecialchars($row['jumlah']).'</td>
            <td>'.htmlspecialchars($row['satuan']).'</td>
            <td>'.htmlspecialchars($row['keterangan']).'</td>
        </tr>';
    }
} else {
    $html .= '<tr>
        <td colspan="9" style="padding:15px; text-align:center; color:#777; font-style:italic;">
            Tidak ada data kerusakan untuk '.$lokasi_text.' pada periode '.$periode_text.'
        </td>
    </tr>';
}
$html .= '</table>';

// Tambah tanda tangan
$html .= '
<div style="margin-top:50px; text-align:right; font-size:12px; color:#154360;">
    <p>Perawang, '.date('d-m-Y').'</p>
    <p>Pastor Paroki,</p>
    <br><br><br>
    <p style="font-weight:bold; text-decoration:underline;">P. Antonius Dwi Raharjo, SCJ.</p>
</div>';

// Bersihkan buffer sebelum render
if (ob_get_contents()) ob_clean();

$mpdf->WriteHTML($html);
$filename = "Laporan_Kerusakan_" . str_replace(' ', '_', $lokasi_text) . "_" . date('d-m-Y', strtotime($tanggal_awal)) . "_sampai_" . date('d-m-Y', strtotime($tanggal_akhir)) . ".pdf";
$mpdf->Output($filename, "I");
exit;
