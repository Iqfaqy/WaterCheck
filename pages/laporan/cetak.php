<?php
/**
 * WaterCheck - Generator Laporan PDF Formal (Dompdf)
 * File: pages/laporan/cetak.php
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

checkLogin();

// Cek autoloader Dompdf
$composerAutoload = __DIR__ . '/../../vendor/autoload.php';
$hasDompdf = file_exists($composerAutoload);
if ($hasDompdf) {
    require_once $composerAutoload;
}

$tipe = $_GET['tipe'] ?? 'pemeriksaan';
$id   = (int)($_GET['id'] ?? 0);
$forceHtml = isset($_GET['html']) && $_GET['html'] === '1';

if ($id <= 0) {
    die("ID dokumen laporan tidak valid.");
}

// Persiapkan data sesuai tipe laporan
if ($tipe === 'kalkulasi') {
    // -------------------------------------------------------------------------
    // A. LAPORAN PERHITUNGAN DOSIS KAPORIT
    // -------------------------------------------------------------------------
    $stmt = $pdo->prepare("
        SELECT k.*, s.nama_sumber, s.jenis_sumber, s.lokasi, s.pemilik, u.nama AS nama_petugas
        FROM kalkulasi_kaporit k
        LEFT JOIN sumber_air s ON k.sumber_air_id = s.id
        LEFT JOIN users u ON k.user_id = u.id
        WHERE k.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $data = $stmt->fetch();

    if (!$data) {
        die("Data kalkulasi kaporit tidak ditemukan.");
    }

    $titleDoc = "Lembar_Rekomendasi_Klorinasi_" . date('Ymd_His') . ".pdf";

    // Bangun HTML Laporan Kalkulasi
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Lembar Rekomendasi Klorinasi Air - WaterCheck</title>
        <style>
            @page { size: A4 portrait; margin: 20mm 15mm 20mm 15mm; }
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11pt; color: #333; line-height: 1.5; margin: 0; }
            .header-table { width: 100%; border-bottom: 2px solid #184e77; padding-bottom: 10px; margin-bottom: 15px; }
            .header-title { text-align: center; }
            .header-title h2 { margin: 0; color: #184e77; font-size: 16pt; text-transform: uppercase; letter-spacing: 0.5px; }
            .header-title p { margin: 3px 0 0 0; font-size: 9pt; color: #555; }
            .doc-title { text-align: center; margin: 15px 0 20px 0; }
            .doc-title h3 { margin: 0; font-size: 13pt; text-decoration: underline; color: #184e77; }
            .doc-title span { font-size: 9pt; color: #666; }
            .info-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; font-size: 10pt; }
            .info-table td { padding: 4px 6px; vertical-align: top; }
            .info-table td.label { width: 30%; color: #555; font-weight: bold; }
            .result-box { background: #f0fdf4; border: 2px solid #22c55e; border-radius: 6px; padding: 12px; margin: 15px 0; text-align: center; }
            .result-box .num { font-size: 22pt; font-weight: bold; color: #166534; font-family: 'Courier New', monospace; }
            .calc-table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 10pt; }
            .calc-table th, .calc-table td { border: 1px solid #cbd5e1; padding: 6px 8px; }
            .calc-table th { background: #f8fafc; font-weight: bold; text-align: left; }
            .safety-box { background: #eff6ff; border-left: 4px solid #168aad; padding: 10px; font-size: 8.5pt; color: #1e3a8a; margin-top: 15px; }
            .ttd-table { width: 100%; margin-top: 30px; font-size: 10pt; }
            .ttd-space { height: 60px; }
        </style>
    </head>
    <body>
        <table class="header-table">
            <tr>
                <td style="width: 70px; vertical-align: middle;">
                    <img src="assets/img/logo.svg" width="60" height="60" alt="Logo">
                </td>
                <td class="header-title" style="vertical-align: middle;">
                    <h2>SISTEM INFORMASI PENYEHATAN KUALITAS AIR</h2>
                    <p>UNIT SANITASI & KESEHATAN LINGKUNGAN KERJA (WATERCHECK / HYDROSAN)</p>
                    <p style="font-size: 8pt; color: #888;">Standar Operasional Prosedur Desinfeksi & Klorinasi Sarana Air Bersih</p>
                </td>
            </tr>
        </table>

        <div class="doc-title">
            <h3>LEMBAR REKOMENDASI DOSIS DESINFEKSI AIR</h3>
            <span>Nomor Dokumen: KLOR/<?= date('Ym', strtotime($data['created_at'])); ?>/<?= str_pad((string)$data['id'], 4, '0', STR_PAD_LEFT); ?></span>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">Sarana Sumber Air:</td>
                <td><strong><?= htmlspecialchars($data['nama_sumber'] ?? 'Perhitungan Mandiri'); ?></strong> <?= !empty($data['jenis_sumber']) ? '(' . htmlspecialchars($data['jenis_sumber']) . ')' : ''; ?></td>
            </tr>
            <tr>
                <td class="label">Nama Pemilik / Pengelola:</td>
                <td><?= htmlspecialchars($data['pemilik'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td class="label">Lokasi Sarana:</td>
                <td><?= htmlspecialchars($data['lokasi'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td class="label">Tanggal Perhitungan:</td>
                <td><?= formatTanggal($data['created_at'], 'datetime'); ?></td>
            </tr>
            <tr>
                <td class="label">Petugas Sanitarian:</td>
                <td><?= htmlspecialchars($data['nama_petugas'] ?? 'Petugas Sanitarian'); ?></td>
            </tr>
        </table>

        <div class="result-box">
            <div style="font-size: 10pt; text-transform: uppercase; color: #15803d; font-weight: bold; letter-spacing: 0.5px;">
                Rekomendasi Kebutuhan Produk Desinfektan:
            </div>
            <div class="num"><?= formatAngka($data['kebutuhan_produk_gram'], 2); ?> GRAM</div>
            <div style="font-size: 9pt; color: #4b5563;">
                Bahan: <strong><?= htmlspecialchars($data['jenis_desinfektan']); ?></strong> (Kadar Aktif: <?= formatAngka($data['kadar_klorin_aktif'], 1); ?>%)
            </div>
        </div>

        <table class="calc-table">
            <thead>
                <tr>
                    <th colspan="2">Parameter Perhitungan Teknis</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="width: 50%;">Volume Air yang Didisinfeksi</td>
                    <td><strong><?= formatAngka($data['volume_air'], 2); ?> <?= htmlspecialchars($data['satuan_volume']); ?></strong></td>
                </tr>
                <tr>
                    <td>Target Dosis Klorin Aktif</td>
                    <td><strong><?= formatAngka($data['target_dosis'], 2); ?> mg/L (ppm)</strong></td>
                </tr>
                <tr>
                    <td>Kebutuhan Klorin Aktif Murni</td>
                    <td><strong><?= formatAngka($data['kebutuhan_klorin_aktif'], 2); ?> mg</strong> (<?= formatAngka($data['kebutuhan_klorin_aktif'] / 1000.0, 2); ?> gram)</td>
                </tr>
                <tr>
                    <td>Kebutuhan Produk Komersial</td>
                    <td><strong><?= formatAngka($data['kebutuhan_produk_mg'], 2); ?> mg</strong> (&asymp; <strong><?= formatAngka($data['kebutuhan_produk_gram'], 2); ?> gram</strong>)</td>
                </tr>
                <?php if (!empty($data['catatan'])): ?>
                <tr>
                    <td>Catatan Lapangan</td>
                    <td><?= nl2br(htmlspecialchars($data['catatan'])); ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="safety-box">
            <strong>PERINGATAN KESELAMATAN & PROSEDUR APLIKASI SANITARIAN:</strong>
            <ol style="margin: 4px 0 0 0; padding-left: 18px; line-height: 1.4;">
                <li>Perhitungan ini merupakan estimasi teoritis. Pastikan dosis operasional disesuaikan dengan kebutuhan klorin (<em>chlorine demand</em>).</li>
                <li>Larutkan produk kaporit terlebih dahulu dalam ember plastik berisi air bersih sebelum dituangkan merata ke tandon/sumur.</li>
                <li>Berikan waktu kontak (<em>contact time</em>) minimal <strong>30 menit</strong> sebelum air didistribusikan atau digunakan.</li>
                <li>Lakukan pengujian sisa klor bebas (standar: <strong>0,2 &ndash; 0,5 mg/L</strong>) di titik kran konsumen.</li>
            </ol>
        </div>

        <table class="ttd-table">
            <tr>
                <td style="width: 60%;"></td>
                <td style="text-align: center;">
                    Ditetapkan pada: <?= formatTanggal($data['created_at'], 'lengkap'); ?><br>
                    Petugas Sanitarian Pelaksana,
                    <div class="ttd-space"></div>
                    <strong><u><?= htmlspecialchars($data['nama_petugas'] ?? 'Petugas Sanitarian'); ?></u></strong><br>
                    <span style="font-size: 8.5pt; color: #555;">Tenaga Sanitasi Lingkungan</span>
                </td>
            </tr>
        </table>
    </body>
    </html>
    <?php
    $htmlContent = ob_get_clean();

} else {
    // -------------------------------------------------------------------------
    // B. LAPORAN PEMERIKSAAN KUALITAS AIR
    // -------------------------------------------------------------------------
    $stmt = $pdo->prepare("
        SELECT p.*, s.nama_sumber, s.jenis_sumber, s.lokasi AS lokasi_sumber, s.pemilik, u.nama AS nama_petugas
        FROM pemeriksaan p
        LEFT JOIN sumber_air s ON p.sumber_air_id = s.id
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $pemeriksaan = $stmt->fetch();

    if (!$pemeriksaan) {
        die("Data pemeriksaan tidak ditemukan.");
    }

    $stmtDetail = $pdo->prepare("
        SELECT dp.*, 
               sbm.nama_parameter, sbm.kategori, sbm.nilai_min, sbm.nilai_max, 
               sbm.operator, sbm.wajib, sbm.sumber_regulasi
        FROM detail_pemeriksaan dp
        LEFT JOIN standar_baku_mutu sbm ON dp.standar_id = sbm.id
        WHERE dp.pemeriksaan_id = :id
        ORDER BY FIELD(sbm.kategori, 'Fisik', 'Kimia', 'Mikrobiologi'), sbm.wajib DESC, sbm.id ASC
    ");
    $stmtDetail->execute([':id' => $id]);
    $details = $stmtDetail->fetchAll();

    $titleDoc = "Laporan_Kualitas_Air_" . $pemeriksaan['nomor_pemeriksaan'] . ".pdf";

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Laporan Hasil Pemeriksaan Kualitas Air - <?= htmlspecialchars($pemeriksaan['nomor_pemeriksaan']); ?></title>
        <style>
            @page { size: A4 portrait; margin: 15mm 15mm 15mm 15mm; }
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9.5pt; color: #222; line-height: 1.4; margin: 0; }
            .header-table { width: 100%; border-bottom: 2px solid #184e77; padding-bottom: 8px; margin-bottom: 12px; }
            .header-title { text-align: center; }
            .header-title h2 { margin: 0; color: #184e77; font-size: 15pt; text-transform: uppercase; }
            .header-title p { margin: 2px 0 0 0; font-size: 8.5pt; color: #444; }
            .doc-title { text-align: center; margin: 10px 0 15px 0; }
            .doc-title h3 { margin: 0; font-size: 12pt; text-decoration: underline; color: #184e77; }
            .doc-title span { font-size: 8.5pt; color: #555; }
            .info-table { width: 100%; margin-bottom: 12px; border-collapse: collapse; font-size: 9pt; }
            .info-table td { padding: 3px 5px; vertical-align: top; }
            .info-table td.label { width: 22%; color: #444; font-weight: bold; }
            .param-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 8.5pt; }
            .param-table th, .param-table td { border: 1px solid #94a3b8; padding: 4px 6px; }
            .param-table th { background: #f1f5f9; color: #184e77; font-weight: bold; text-align: center; }
            .cat-header { background: #e2e8f0; font-weight: bold; color: #0f172a; text-transform: uppercase; font-size: 8pt; }
            .badge-ms { color: #065f46; font-weight: bold; }
            .badge-tms { color: #991b1b; font-weight: bold; }
            .badge-pending { color: #92400e; font-weight: bold; }
            .conclusion-box { border: 2px solid #184e77; border-radius: 6px; padding: 10px; margin-bottom: 15px; }
            .conclusion-ms { background: #f0fdf4; border-color: #22c55e; }
            .conclusion-tms { background: #fef2f2; border-color: #ef4444; }
            .conclusion-pending { background: #fefce8; border-color: #eab308; }
            .ttd-table { width: 100%; margin-top: 20px; font-size: 9pt; }
            .ttd-space { height: 50px; }
        </style>
    </head>
    <body>
        <table class="header-table">
            <tr>
                <td style="width: 60px; vertical-align: middle;">
                    <img src="assets/img/logo.svg" width="55" height="55" alt="Logo">
                </td>
                <td class="header-title" style="vertical-align: middle;">
                    <h2>SISTEM PENGELOLAAN KESEHATAN LINGKUNGAN</h2>
                    <p>UNIT LABORATORIUM & PEMERIKSAAN KUALITAS AIR (WATERCHECK)</p>
                    <p style="font-size: 7.5pt; color: #777;">Acuan Regulasi: Peraturan Menteri Kesehatan Republik Indonesia No. 2 Tahun 2023</p>
                </td>
            </tr>
        </table>

        <div class="doc-title">
            <h3>LAPORAN HASIL PENGUJIAN KUALITAS AIR</h3>
            <span>Nomor Pemeriksaan: <strong><?= htmlspecialchars($pemeriksaan['nomor_pemeriksaan']); ?></strong></span>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">Peruntukan Air:</td>
                <td><strong><?= htmlspecialchars($pemeriksaan['jenis_air']); ?></strong></td>
                <td class="label">Tanggal Pengujian:</td>
                <td><?= formatTanggal($pemeriksaan['tanggal_pemeriksaan'], 'lengkap'); ?></td>
            </tr>
            <tr>
                <td class="label">Sarana Sumber Air:</td>
                <td><?= htmlspecialchars($pemeriksaan['nama_sumber'] ?? 'Pengujian Mandiri'); ?> <?= !empty($pemeriksaan['jenis_sumber']) ? '(' . htmlspecialchars($pemeriksaan['jenis_sumber']) . ')' : ''; ?></td>
                <td class="label">Petugas Sanitarian:</td>
                <td><?= htmlspecialchars($pemeriksaan['nama_petugas'] ?? 'Sanitarian Lapangan'); ?></td>
            </tr>
            <tr>
                <td class="label">Titik Lokasi:</td>
                <td colspan="3"><?= htmlspecialchars($pemeriksaan['lokasi']); ?></td>
            </tr>
        </table>

        <table class="param-table">
            <thead>
                <tr>
                    <th style="width: 25px;">No</th>
                    <th>Parameter Kualitas Air</th>
                    <th>Baku Mutu (Permenkes 2/2023)</th>
                    <th style="width: 90px;">Hasil Pengukuran</th>
                    <th style="width: 80px;">Status</th>
                    <th>Metode / Alat</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $currentKategori = '';
                $no = 1;
                foreach ($details as $d):
                    if ($d['kategori'] !== $currentKategori):
                        $currentKategori = $d['kategori'];
                        echo "<tr><td colspan='6' class='cat-header'>PARAMETER " . strtoupper(htmlspecialchars($currentKategori)) . "</td></tr>";
                    endif;
                ?>
                    <tr>
                        <td style="text-align: center;"><?= $no++; ?></td>
                        <td>
                            <?= htmlspecialchars($d['nama_parameter']); ?>
                            <?= ($d['wajib']) ? '<span style="color:#ef4444; font-size:7pt;">(Wajib)</span>' : ''; ?>
                        </td>
                        <td>
                            <?php
                            if ($d['operator'] === 'MIN_MAX') {
                                echo formatAngka($d['nilai_min'], 1) . ' &ndash; ' . formatAngka($d['nilai_max'], 1) . ' ' . $d['satuan'];
                            } elseif ($d['operator'] === 'MAX') {
                                echo '&le; ' . formatAngka($d['nilai_max'], 1) . ' ' . $d['satuan'];
                            } elseif ($d['operator'] === 'EQUALS_ZERO') {
                                echo '0 ' . $d['satuan'];
                            } elseif ($d['operator'] === 'QUALITATIVE') {
                                echo 'Tidak Berbau / Berasa';
                            } elseif ($d['operator'] === 'SUHU') {
                                echo 'Suhu udara &plusmn; 3°C';
                            }
                            ?>
                        </td>
                        <td style="text-align: center; font-family: monospace;">
                            <?php
                            if ($d['operator'] === 'QUALITATIVE') {
                                echo htmlspecialchars($d['nilai_kualitatif'] ?? '-');
                            } else {
                                echo ($d['nilai_hasil'] !== null) ? formatAngka($d['nilai_hasil'], 2) . ' ' . htmlspecialchars($d['satuan']) : '-';
                            }
                            ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($d['status'] === 'Memenuhi Syarat'): ?>
                                <span class="badge-ms">MS</span>
                            <?php elseif ($d['status'] === 'Tidak Memenuhi Syarat'): ?>
                                <span class="badge-tms">TMS</span>
                            <?php else: ?>
                                <span class="badge-pending">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 8pt; color: #444;"><?= htmlspecialchars($d['metode_pemeriksaan'] ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        $boxClass = 'conclusion-pending';
        if ($pemeriksaan['kesimpulan'] === 'Memenuhi Syarat (MS)') $boxClass = 'conclusion-ms';
        if ($pemeriksaan['kesimpulan'] === 'Tidak Memenuhi Syarat (TMS)') $boxClass = 'conclusion-tms';
        ?>
        <div class="conclusion-box <?= $boxClass; ?>">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 65%;">
                        <strong style="font-size: 9.5pt; text-transform: uppercase;">KESIMPULAN HASIL UJI KELAYAKAN AIR:</strong>
                        <div style="font-size: 13pt; font-weight: bold; margin-top: 2px;">
                            <?= htmlspecialchars($pemeriksaan['kesimpulan']); ?>
                        </div>
                        <?php if (!empty($pemeriksaan['catatan'])): ?>
                            <div style="font-size: 8.5pt; color: #444; margin-top: 4px;">
                                <em>Catatan: <?= htmlspecialchars($pemeriksaan['catatan']); ?></em>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="width: 35%; font-size: 8pt; color: #555; vertical-align: middle;">
                        *Keterangan:<br>
                        <strong>MS:</strong> Memenuhi Syarat Baku Mutu<br>
                        <strong>TMS:</strong> Tidak Memenuhi Syarat<br>
                        Dasar: Permenkes No. 2 Tahun 2023
                    </td>
                </tr>
            </table>
        </div>

        <table class="ttd-table">
            <tr>
                <td style="width: 55%; font-size: 8pt; color: #666; vertical-align: top;">
                    <em>Dokumen ini dicetak otomatis melalui sistem WaterCheck.<br>Keabsahan data terverifikasi pada pangkalan data pengujian sanitasi.</em>
                </td>
                <td style="text-align: center;">
                    Dikeluarkan pada: <?= formatTanggal($pemeriksaan['tanggal_pemeriksaan'], 'lengkap'); ?><br>
                    Petugas Sanitarian Penguji,
                    <div class="ttd-space"></div>
                    <strong><u><?= htmlspecialchars($pemeriksaan['nama_petugas'] ?? 'Petugas Sanitarian'); ?></u></strong><br>
                    <span style="font-size: 8pt; color: #555;">Sanitarian Pelaksana Lingkungan</span>
                </td>
            </tr>
        </table>
    </body>
    </html>
    <?php
    $htmlContent = ob_get_clean();
}

// Jika force HTML diminta atau jika Dompdf belum terpasang
if ($forceHtml || !$hasDompdf) {
    echo $htmlContent;
    echo "<script>window.onload = function() { window.print(); };</script>";
    exit;
}

// Render via Dompdf
try {
    $options = new Dompdf\Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'Helvetica');

    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->setBasePath(realpath(__DIR__ . '/../../'));
    $dompdf->loadHtml($htmlContent);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Stream PDF ke browser
    $dompdf->stream($titleDoc, ["Attachment" => false]);
    exit;

} catch (Exception $e) {
    // Fallback bila rendering Dompdf mengalami kendala
    echo "<div style='padding:20px; font-family:sans-serif;'>";
    echo "<h3>Mode Cetak Browser (Fallback Dompdf)</h3>";
    echo "<p>Gagal merender via Dompdf (" . htmlspecialchars($e->getMessage()) . "). Menampilkan tampilan cetak browser langsung:</p>";
    echo "<hr>";
    echo "</div>";
    echo $htmlContent;
    echo "<script>window.onload = function() { window.print(); };</script>";
    exit;
}
