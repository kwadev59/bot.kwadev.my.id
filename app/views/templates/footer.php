        </div>
    </div>
</main>

<!-- Footer -->
<footer class="footer mt-auto py-3 bg-light border-top">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-12 col-md-4 text-center text-md-start mb-2 mb-md-0">
                <small class="text-muted">&copy; 2025 PT. BIM-PPS. All rights reserved.</small>
            </div>
            <div class="col-12 col-md-4 text-center mb-2 mb-md-0">
                <small class="text-muted">
                    Dibangun dengan <i class="bi bi-heart text-danger mx-1"></i> Cinta |
                    <a href="https://kwadev.my.id/" target="_blank">kwadev.my.id</a>
                </small>
            </div>
            <div class="col-12 col-md-4 text-center text-md-end">
                <small class="text-muted">v1.0.0</small>
                <a href="#" class="ms-3 text-muted" data-bs-toggle="modal" data-bs-target="#aboutModal">
                    <i class="bi bi-info-circle"></i> About
                </a>
            </div>
        </div>
    </div>
</footer>

<!-- About Modal -->
<div class="modal fade" id="aboutModal" tabindex="-1" aria-labelledby="aboutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="aboutModalLabel">Tentang Aplikasi WA-Bot Monitoring</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <h6 class="fw-bold text-primary mb-3"><i class="bi bi-activity"></i> Fungsi Aplikasi</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">
                                    <i class="bi bi-whatsapp"></i> WA-Bot
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Fungsi WA-Bot</div>
                                                <p class="mb-1">Menerima file laporan dari karyawan melalui WhatsApp</p>
                                                <span class="badge bg-info">Validasi Nama File</span>
                                                <span class="badge bg-info ms-1">Cek Duplikasi</span>
                                                <span class="badge bg-info ms-1">Penyimpanan File</span>
                                            </div>
                                        </li>
                                        <li class="list-group-item">
                                            <strong>Format File yang Didukung:</strong>
                                            <ul class="mt-2">
                                                <li>TRB - Tracking Rawat Belanja (TRB-BIM/PPS...)</li>
                                                <li>TU - Transfer Unit (TU-...)</li>
                                                <li>AMANDARB - Amanda Report (AMANDARB_...)</li>
                                                <li class="text-success fw-bold">TO - Transfer Order (TO-...)</li>
                                                <li class="text-success fw-bold">TPN - Tracking Panen (TPN-...)</li>
                                                <li class="text-success fw-bold">TR - Tracking Rawat (TR-...)</li>
                                            </ul>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-success text-white">
                                    <i class="bi bi-window"></i> Panel Web
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Fungsi Panel Web</div>
                                                <p class="mb-1">Menampilkan status WA-Bot dan data laporan</p>
                                                <span class="badge bg-info">Status Koneksi WA-Bot</span>
                                                <span class="badge bg-info ms-1">Monitoring Data</span>
                                                <span class="badge bg-info ms-1">Pengelolaan User</span>
                                            </div>
                                        </li>
                                        <li class="list-group-item">
                                            <strong>Fitur Panel:</strong>
                                            <ul class="mt-2">
                                                <li>Dashboard monitoring status WA-Bot</li>
                                                <li>Daftar kontak dan pengguna</li>
                                                <li>Laporan valid/invalid</li>
                                                <li>Log aktivitas sistem</li>
                                                <li>Manajemen akun dan jabatan</li>
                                            </ul>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mt-4 mb-3"><i class="bi bi-flow"></i> Alur Kerja Sistem</h6>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row text-center mb-4">
                                        <div class="col-3">
                                            <div class="p-3 bg-light rounded">
                                                <i class="bi bi-file-earmark-arrow-up h3 text-primary"></i>
                                                <p class="mb-0">Karyawan Kirim File</p>
                                            </div>
                                        </div>
                                        <div class="col-2 align-self-center">
                                            <i class="bi bi-arrow-right h2 text-muted"></i>
                                        </div>
                                        <div class="col-3">
                                            <div class="p-3 bg-light rounded">
                                                <i class="bi bi-whatsapp h3 text-success"></i>
                                                <p class="mb-0">WA-Bot Terima File</p>
                                            </div>
                                        </div>
                                        <div class="col-2 align-self-center">
                                            <i class="bi bi-arrow-right h2 text-muted"></i>
                                        </div>
                                        <div class="col-2">
                                            <div class="p-3 bg-light rounded">
                                                <i class="bi bi-database h3 text-info"></i>
                                                <p class="mb-0">Simpan ke DB</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row text-center">
                                        <div class="col-3">
                                            <div class="p-3 bg-light rounded">
                                                <i class="bi bi-check-circle h3 text-success"></i>
                                                <p class="mb-0">Validasi Nama File</p>
                                            </div>
                                        </div>
                                        <div class="col-2 align-self-center">
                                            <i class="bi bi-arrow-right h2 text-muted"></i>
                                        </div>
                                        <div class="col-3">
                                            <div class="p-3 bg-light rounded">
                                                <i class="bi bi-shield-check h3 text-warning"></i>
                                                <p class="mb-0">Cek Duplikasi</p>
                                            </div>
                                        </div>
                                        <div class="col-2 align-self-center">
                                            <i class="bi bi-arrow-right h2 text-muted"></i>
                                        </div>
                                        <div class="col-2">
                                            <div class="p-3 bg-light rounded">
                                                <i class="bi bi-eye h3 text-primary"></i>
                                                <p class="mb-0">Monitor via Panel</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mt-4 mb-3"><i class="bi bi-clipboard-check"></i> Changelog & Fitur yang Telah Diterapkan</h6>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="accordion" id="featureAccordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingOne">
                                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                                    <i class="bi bi-whatsapp me-2"></i> WA-Bot Core Features
                                                </button>
                                            </h2>
                                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#featureAccordion">
                                                <div class="accordion-body">
                                                    <ul>
                                                        <li>Validasi nama file sesuai format TRB, TU, AmandaRB, TO, TPN, TR</li>
                                                        <li>Cek duplikasi file berdasarkan nama file</li>
                                                        <li>Penyimpanan otomatis ke direktori sesuai tipe file</li>
                                                        <li>API status untuk monitoring WA-Bot</li>
                                                        <li>Log aktivitas dan error handling</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingTwo">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                                    <i class="bi bi-window me-2"></i> Web Panel Features
                                                </button>
                                            </h2>
                                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#featureAccordion">
                                                <div class="accordion-body">
                                                    <ul>
                                                        <li>Dashboard monitoring status WA-Bot</li>
                                                        <li>Manajemen kontak dan nomor WhatsApp</li>
                                                        <li>Pengelolaan pengguna dan jabatan</li>
                                                        <li>Laporan file valid/invalid</li>
                                                        <li>Log aktivitas sistem</li>
                                                        <li>Login otentikasi pengguna</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingThree">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                                    <i class="bi bi-plus-circle me-2"></i> Fitur Baru: TO, TPN, TR Support
                                                </button>
                                            </h2>
                                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#featureAccordion">
                                                <div class="accordion-body">
                                                    <ul>
                                                        <li>Regex validasi format TO (Transfer Order)</li>
                                                        <li>Regex validasi format TPN (Tracking Panen)</li>
                                                        <li>Regex validasi format TR (Tracking Rawat)</li>
                                                        <li>Penyimpanan dan kategorisasi file sesuai tipe baru</li>
                                                        <li>Ekstraksi data (NPK, Tanggal, Site Code, Afdeling, IMEI)</li>
                                                        <li>Integrasi database untuk mendukung tipe file baru</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingFour">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                                    <i class="bi bi-database me-2"></i> Database & Struktur Data
                                                </button>
                                            </h2>
                                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#featureAccordion">
                                                <div class="accordion-body">
                                                    <ul>
                                                        <li>Struktur tabel file_submissions diperluas</li>
                                                        <li>Dukungan ENUM untuk tipe file baru (TO, TPN, TR)</li>
                                                        <li>Ekstraksi data elemen dari nama file (NPK, Tanggal, dll)</li>
                                                        <li>Penyimpanan log aktivitas sistem</li>
                                                        <li>Manajemen kontak dan pengguna</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="alert alert-light border">
                                <h6 class="text-center mb-3">Deskripsi Format File</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title text-success">TO - Transfer Order</h6>
                                                <p class="card-text">
                                                    <strong>Format:</strong> TO-NPK-Tanggal-SiteCode-IMEI.ext<br>
                                                    <strong>Contoh:</strong> TO-1234567-20251012-BIM1-123456789012345.csv
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title text-success">TPN - Tracking Panen</h6>
                                                <p class="card-text">
                                                    <strong>Format:</strong> TPN-NPK-Tanggal-SiteCode-Afdeling-IMEI.ext<br>
                                                    <strong>Contoh:</strong> TPN-1234567-20251012-BIM1-AA-123456789012345.csv
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title text-success">TR - Tracking Rawat</h6>
                                                <p class="card-text">
                                                    <strong>Format:</strong> TR-NPK-Tanggal-SiteCode-Afdeling-IMEI.ext<br>
                                                    <strong>Contoh:</strong> TR-1234567-20251012-BIM1-AA-123456789012345.csv
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>



<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Flatpickr Date Picker -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>

<!-- Optional: Flash Message Handler -->
<script>
// Handle any flash messages (if implemented)
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>

</body>
</html>
