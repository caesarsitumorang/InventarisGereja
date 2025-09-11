<?php
require_once __DIR__ . '../../../../vendor/autoload.php'; 
include "../../../config/koneksi.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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

$id_admin = $_SESSION['id_akun'] ?? null;
$nama_admin = '-';
if ($id_admin) {
    $admin = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT username FROM pengguna WHERE id_akun = '$id_admin'"));
    if ($admin) $nama_admin = $admin['username'];
}

$where = "WHERE 1=1";
$conditions = array();

// Filter lokasi
if (!empty($lokasi_filter)) {
    $lokasi_escaped = mysqli_real_escape_string($koneksi, $lokasi_filter);
    $conditions[] = "lokasi_simpan = '$lokasi_escaped'";
}

// Filter tanggal
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $tanggal_awal_escaped = mysqli_real_escape_string($koneksi, $tanggal_awal);
    $tanggal_akhir_escaped = mysqli_real_escape_string($koneksi, $tanggal_akhir);
    $conditions[] = "DATE(tanggal_pinjam) BETWEEN '$tanggal_awal_escaped' AND '$tanggal_akhir_escaped'";
}

if (!empty($conditions)) {
    $where .= " AND " . implode(" AND ", $conditions);
}

$query = "SELECT no_peminjaman, tanggal_pinjam, lokasi_simpan, 
                 kode_barang, nama_barang, jumlah_pinjam, satuan, 
                 lokasi_pinjam, nama_peminjam, keterangan, status 
          FROM peminjaman $where 
          ORDER BY tanggal_pinjam DESC, no_peminjaman ASC";
$result = mysqli_query($koneksi, $query);

$total_peminjaman = mysqli_num_rows($result);
$sudah_kembali = 0;
$belum_kembali = 0;

mysqli_data_seek($result, 0);
while ($row = mysqli_fetch_assoc($result)) {
    if (strtolower($row['status']) == 'sudah kembali') {
        $sudah_kembali++;
    } else {
        $belum_kembali++;
    }
}

mysqli_data_seek($result, 0);

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

'.$summary_html.'

<h3 style="text-align:center; font-size:16px; margin-top:10px; margin-bottom:10px;">
    Data Peminjaman - '.$lokasi_text.' ('.$periode_text.')
</h3>
<table class="table">
  <tr>
    <th width="4%">No</th>
    <th width="10%">No Peminjaman</th>
    <th width="8%">Tanggal</th>
    <th width="12%">Lokasi Simpan</th>
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
            Tidak ada data peminjaman untuk lokasi: '.$lokasi_text.' pada periode '.$periode_text.'
        </td>
    </tr>';
}

$html .= '
</table>

';
$html .= '
<div class="signature">
    <p><strong>Perawang, '.date('d').' Agustus '.date('Y').'</strong></p>
    <p>Pastor Paroki,</p>
    <div class="signature-name">P. Antonius Dwi Raharjo, SCJ.</div>
</div>';

ob_clean();
$mpdf->WriteHTML($html);
$filename = "Laporan_Peminjaman_" . str_replace(' ', '_', $lokasi_text) . "_" . 
            str_replace('/', '-', date('d-m-Y', strtotime($tanggal_awal))) . "_sampai_" . 
            str_replace('/', '-', date('d-m-Y', strtotime($tanggal_akhir))) . ".pdf";
$mpdf->Output($filename, "I");
exit;