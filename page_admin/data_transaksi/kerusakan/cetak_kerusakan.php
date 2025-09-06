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

// Path logo
$logoPath = $_SERVER['DOCUMENT_ROOT'] . "/inventaris-gereja/img/logo.jpg";

$lokasi_text = !empty($lokasi_filter) ? $lokasi_filter : 'Semua Lokasi';
$periode_text = date('d/m/Y', strtotime($tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($tanggal_akhir));

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

// Footer
$footer = '
<div style="padding: 10px; font-size: 10px; text-align: center; color: #154360; font-family: Arial, sans-serif; background-color: #EBF5FB; border-top: 2px solid #5DADE2;">
    <strong>Halaman {PAGENO} dari {nbpg}</strong> | Dicetak pada: '.date('d-m-Y H:i:s').'
</div>';
$mpdf->SetHTMLFooter($footer);

// Konten PDF
$html = '
<style>
body { 
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
    font-size: 12px;
    line-height: 1.5;
    color: #2C3E50;
}

.table {
    border-collapse: collapse;
    width: 100%;
    margin-top: 20px;
    border: 2px solid #2E86C1;
}

.table th {
    padding: 12px 8px;
    text-align: center;
    border: 1px solid #2E86C1;
    font-size: 11px;
    background: linear-gradient(135deg, #2E86C1 0%, #5DADE2 100%);
    color: #FFFFFF;
    font-weight: bold;
}

.table td {
    padding: 10px 8px;
    text-align: center;
    border: 1px solid #AED6F1;
    font-size: 10px;
    color: #2C3E50;
}

.table tr:nth-child(even) { background-color: #EBF5FB; }
.table tr:nth-child(odd) { background-color: #FFFFFF; }
.table tr:hover { background-color: #D6EAF8; }

.signature {
    margin-top: 60px;
    text-align: right;
    font-size: 12px;
    color: #1B4F72;
    font-family: Georgia, serif;
}

.signature-name {
    margin-top: 70px;
    font-weight: bold;
    text-decoration: underline;
    font-size: 13px;
    color: #154360;
}
</style>

<h3 style="text-align:center; font-size:16px; margin-top:10px; margin-bottom:10px;">
    Data Kerusakan - '.$lokasi_text.' ('.$periode_text.')
</h3>
<table class="table">
  <tr>
    <th width="4%">No</th>
    <th width="12%">No Kerusakan</th>
    <th width="10%">Tgl Kerusakan</th>
    <th width="12%">Lokasi Simpan</th>
    <th width="8%">Kode Barang</th>
    <th width="20%">Nama Barang</th>
    <th width="6%">Jumlah</th>
    <th width="8%">Satuan</th>
    <th width="20%">Keterangan</th>
  </tr>';

if ($total_kerusakan > 0) {
    $no = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $html .= '
        <tr>
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
    $html .= '
    <tr>
        <td colspan="9" style="text-align:center; padding:20px; color:#777; font-style:italic;">
            Tidak ada data kerusakan untuk lokasi: '.$lokasi_text.' pada periode '.$periode_text.'
        </td>
    </tr>';
}

$html .= '</table>';

// Tambah tanda tangan
$html .= '
<div class="signature">
    <p><strong>Perawang, '.date('d').' Agustus '.date('Y').'</strong></p>
    <p>Pastor Paroki,</p>
    <div class="signature-name">P. Antonius Dwi Raharjo, SCJ.</div>
</div>';

// Bersihkan buffer output sebelum render
if (ob_get_contents()) ob_clean();

$mpdf->WriteHTML($html);
$filename = "Laporan_Kerusakan_" . str_replace(' ', '_', $lokasi_text) . "_" . 
            str_replace('/', '-', date('d-m-Y', strtotime($tanggal_awal))) . "_sampai_" . 
            str_replace('/', '-', date('d-m-Y', strtotime($tanggal_akhir))) . ".pdf";
$mpdf->Output($filename, "I");
exit;