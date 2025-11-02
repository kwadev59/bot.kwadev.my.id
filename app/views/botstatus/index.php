<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Status Bot WhatsApp</h1>
</div>

<?php if (isset($_SESSION['flash'])): ?>
<div class="alert alert-<?= $_SESSION['flash']['tipe']; ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($_SESSION['flash']['pesan'] ?? ''); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    <?php unset($_SESSION['flash']); ?>
</div>
<?php endif; ?>

<!-- Status Card -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Status Koneksi</h5>
                <div class="row">
                    <div class="col-6">
                        <p class="mb-1">Status:</p>
                        <p class="mb-1">Mode:</p>
                        <p class="mb-1">Terakhir Update:</p>
                        <p class="mb-1">Status Detail:</p>
                        <p class="mb-1">Terputus Terakhir:</p>
                        <p class="mb-1">Pesan Error:</p>
                    </div>
                    <div class="col-6 text-end">
                        <p class="mb-1"><span id="connectionStatus" class="badge bg-warning">Menghubungkan...</span></p>
                        <p class="mb-1"><span id="connectionMode">-</span></p>
                        <p class="mb-1"><span id="lastUpdate">-</span></p>
                        <p class="mb-1"><span id="statusDetail">-</span></p>
                        <p class="mb-1"><span id="lastDisconnectAt">-</span></p>
                        <p class="mb-1"><span id="lastDisconnectReason">-</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Status Bot</h5>
                <div class="row">
                    <div class="col-6">
                        <p class="mb-1">Bot Aktif:</p>
                        <p class="mb-1">Sedang Menghubungkan:</p>
                        <p class="mb-1">Ter-logout:</p>
                    </div>
                    <div class="col-6 text-end">
                        <p class="mb-1"><span id="botOnline" class="badge bg-secondary">-</span></p>
                        <p class="mb-1"><span id="botConnecting" class="badge bg-secondary">-</span></p>
                        <p class="mb-1"><span id="botLoggedOut" class="badge bg-secondary">-</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Section -->
<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h5>QR Code Login</h5>
    </div>
    <div class="card-body text-center">
        <div id="qrContainer" class="d-flex flex-column align-items-center">
            <div id="qrCode" class="mb-3"></div>
            <p id="qrMessage" class="text-muted mb-0">Memuat QR Code...</p>
        </div>
        <button id="refreshQrBtn" class="btn btn-outline-primary mt-3">Refresh QR</button>
    </div>
</div>

<!-- Actions -->
<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h5>Aksi</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <button id="refreshStatusBtn" class="btn btn-info w-100">
                    <i class="bi bi-arrow-clockwise"></i> Refresh Status
                </button>
            </div>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="col-md-6 mb-3">
                <button id="restartBotBtn" class="btn btn-warning w-100" style="display: none;">
                    <i class="bi bi-arrow-repeat"></i> Generate QR Baru
                </button>
                <div id="restartBotBtnPlaceholder" class="w-100">
                    <button class="btn btn-warning w-100" disabled>
                        <i class="bi bi-arrow-repeat"></i> Generate QR Baru
                    </button>
                    <small class="text-muted d-block mt-1 text-center">Tersedia saat bot offline</small>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <button id="logoutBtn" class="btn btn-danger w-100">
                    <i class="bi bi-box-arrow-left"></i> Logout Bot
                </button>
            </div>
            <div class="col-md-6 mb-3">
                <button id="checkNumberBtn" class="btn btn-secondary w-100" data-bs-toggle="modal" data-bs-target="#checkNumberModal">
                    <i class="bi bi-telephone"></i> Cek Nomor WA
                </button>
            </div>
        </div>
        <?php else: ?>
        </div>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Fitur aksi hanya tersedia untuk pengguna admin
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal untuk cek nomor WA -->
<div class="modal fade" id="checkNumberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cek Nomor WhatsApp</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="waNumber" class="form-label">Nomor WhatsApp</label>
                    <input type="text" class="form-control" id="waNumber" placeholder="Contoh: 6281234567890">
                    <div class="form-text">Masukkan nomor WhatsApp tanpa tanda + atau spasi</div>
                </div>
                <div id="checkNumberResult" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="checkNumberSubmit">Cek Nomor</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
let qrCodeInstance = null;
let refreshInterval;

// Fungsi untuk memperbarui status
function updateStatus() {
    fetch('<?= BASE_URL; ?>/BotStatusController/getStatus')
        .then(response => response.json())
        .then(data => {
            // Update status koneksi
            const statusBadge = document.getElementById('connectionStatus');
            const statusDetail = document.getElementById('statusDetail');
            const connectionMode = document.getElementById('connectionMode');
            const lastDisconnectAt = document.getElementById('lastDisconnectAt');
            const lastDisconnectReason = document.getElementById('lastDisconnectReason');
            
            if (data.error) {
                statusBadge.className = 'badge bg-danger';
                statusBadge.textContent = 'ERROR API';
                statusDetail.textContent = data.error;
                connectionMode.textContent = '-';
                lastDisconnectAt.textContent = '-';
                lastDisconnectReason.textContent = '-';
                document.getElementById('botOnline').className = 'badge bg-secondary';
                document.getElementById('botOnline').textContent = '-';
                document.getElementById('botConnecting').className = 'badge bg-secondary';
                document.getElementById('botConnecting').textContent = '-';
                document.getElementById('botLoggedOut').className = 'badge bg-secondary';
                document.getElementById('botLoggedOut').textContent = '-';
                updateRestartButtonVisibility(false);
                return;
            }
            
            if (data.isOnline) {
                statusBadge.className = 'badge bg-success';
                statusBadge.textContent = 'TERHUBUNG';
                statusDetail.textContent = 'Koneksi aktif ke WhatsApp';
            } else if (data.isConnecting) {
                statusBadge.className = 'badge bg-warning';
                statusBadge.textContent = 'MENGHUBUNGKAN';
                statusDetail.textContent = 'Sedang mencoba koneksi';
            } else if (data.isLoggedOut) {
                statusBadge.className = 'badge bg-danger';
                statusBadge.textContent = 'TER-LOGOUT';
                statusDetail.textContent = 'Akun telah logout';
            } else {
                statusBadge.className = 'badge bg-secondary';
                statusBadge.textContent = 'OFFLINE';
                statusDetail.textContent = 'Tidak terhubung';
            }

            const connectionText = data.connection ? data.connection.toString().replace(/_/g, ' ').toUpperCase() : '-';
            connectionMode.textContent = connectionText;

            const lastDisconnect = data.lastDisconnect || null;
            let lastDisconnectTime = '-';

            if (lastDisconnect?.date) {
                lastDisconnectTime = new Date(lastDisconnect.date).toLocaleString();
            } else if (lastDisconnect?.timestamp) {
                lastDisconnectTime = new Date(lastDisconnect.timestamp).toLocaleString();
            } else if (lastDisconnect?.error?.output?.payload?.timestamp) {
                const ts = lastDisconnect.error.output.payload.timestamp;
                lastDisconnectTime = new Date(ts).toLocaleString();
            }

            lastDisconnectAt.textContent = lastDisconnectTime;

            let disconnectReason = '-';
            if (lastDisconnect?.error) {
                if (typeof lastDisconnect.error === 'string') {
                    disconnectReason = lastDisconnect.error;
                } else if (lastDisconnect.error.message) {
                    disconnectReason = lastDisconnect.error.message;
                } else if (lastDisconnect.error?.output?.payload?.message) {
                    disconnectReason = lastDisconnect.error.output.payload.message;
                }
            }

            lastDisconnectReason.textContent = disconnectReason;
            
            // Update informasi tambahan
            document.getElementById('lastUpdate').textContent = data.timestamp ? 
                new Date(data.timestamp).toLocaleString() : '-';
            
            document.getElementById('botOnline').className = data.isOnline ? 'badge bg-success' : 'badge bg-danger';
            document.getElementById('botOnline').textContent = data.isOnline ? 'YA' : 'TIDAK';
            
            document.getElementById('botConnecting').className = data.isConnecting ? 'badge bg-warning' : 'badge bg-secondary';
            document.getElementById('botConnecting').textContent = data.isConnecting ? 'YA' : 'TIDAK';
            
            document.getElementById('botLoggedOut').className = data.isLoggedOut ? 'badge bg-danger' : 'badge bg-success';
            document.getElementById('botLoggedOut').textContent = data.isLoggedOut ? 'YA' : 'TIDAK';

            updateRestartButtonVisibility(!!data.isOnline);
        })
        .catch(error => {
            console.error('Error fetching status:', error);
            document.getElementById('connectionStatus').className = 'badge bg-danger';
            document.getElementById('connectionStatus').textContent = 'ERROR';
            document.getElementById('statusDetail').textContent = 'Gagal mengambil data';
            document.getElementById('connectionMode').textContent = '-';
            document.getElementById('lastDisconnectAt').textContent = '-';
            document.getElementById('lastDisconnectReason').textContent = '-';
            document.getElementById('botOnline').className = 'badge bg-secondary';
            document.getElementById('botOnline').textContent = '-';
            document.getElementById('botConnecting').className = 'badge bg-secondary';
            document.getElementById('botConnecting').textContent = '-';
            document.getElementById('botLoggedOut').className = 'badge bg-secondary';
            document.getElementById('botLoggedOut').textContent = '-';
            updateRestartButtonVisibility(false);
        });
}

// Fungsi untuk memperbarui QR Code
function updateQr() {
    fetch('<?= BASE_URL; ?>/BotStatusController/getQr')
        .then(response => response.json())
        .then(data => {
            const qrCodeWrapper = document.getElementById('qrCode');
            const qrMessage = document.getElementById('qrMessage');

            if (data.qr) {
                if (!qrCodeInstance) {
                    qrCodeInstance = new QRCode(qrCodeWrapper, {
                        text: data.qr,
                        width: 220,
                        height: 220,
                        correctLevel: QRCode.CorrectLevel.M
                    });
                } else {
                    qrCodeInstance.clear();
                    qrCodeInstance.makeCode(data.qr);
                }
                qrMessage.className = 'text-muted mb-0';
                qrMessage.innerHTML = '<i class="bi bi-qr-code"></i> Scan QR ini dari aplikasi WhatsApp Anda untuk login kembali.';
            } else {
                if (qrCodeInstance) {
                    qrCodeInstance.clear();
                }
                qrCodeWrapper.innerHTML = '';
                qrMessage.className = 'text-secondary mb-0';
                qrMessage.innerHTML = `<i class="bi bi-qr-code"></i> ${data.message || 'Bot sedang terhubung atau dalam proses koneksi'}`;
            }
        })
        .catch(error => {
            console.error('Error fetching QR:', error);
            if (qrCodeInstance) {
                qrCodeInstance.clear();
            }
            document.getElementById('qrCode').innerHTML = '';
            const qrMessage = document.getElementById('qrMessage');
            qrMessage.className = 'text-danger mb-0';
            qrMessage.textContent = 'Gagal mengambil QR Code';
        });
}

// Event listeners
document.getElementById('refreshStatusBtn').addEventListener('click', updateStatus);
document.getElementById('refreshQrBtn').addEventListener('click', updateQr);

document.getElementById('restartBotBtn').addEventListener('click', function() {
    if (confirm('Apakah Anda yakin ingin generate QR baru? Ini akan memaksa bot untuk logout dan generate QR baru.')) {
        fetch('<?= BASE_URL; ?>/BotStatusController/restart', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('QR baru sedang digenerate. Silakan periksa terminal WA Bot untuk QR terbaru.');
            } else {
                alert(data.message || 'Gagal merestart bot');
            }
            updateStatus();
            updateQr();
        })
        .catch(error => {
            console.error('Error restarting bot:', error);
            alert('Gagal mengirim permintaan restart');
        });
    }
});

document.getElementById('logoutBtn').addEventListener('click', function() {
    if (confirm('Apakah Anda yakin ingin logout bot? Bot akan terputus dari WhatsApp.')) {
        fetch('<?= BASE_URL; ?>/BotStatusController/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Bot berhasil logout');
            } else {
                alert(data.message || 'Gagal logout bot');
            }
            updateStatus();
        })
        .catch(error => {
            console.error('Error logging out bot:', error);
            alert('Gagal mengirim permintaan logout');
        });
    }
});

// Handler untuk cek nomor WhatsApp
document.getElementById('checkNumberSubmit').addEventListener('click', function() {
    const numberInput = document.getElementById('waNumber');
    const resultDiv = document.getElementById('checkNumberResult');
    const number = numberInput.value.trim();
    
    if (!number) {
        resultDiv.innerHTML = '<div class="alert alert-warning">Silakan masukkan nomor WhatsApp</div>';
        return;
    }

    resultDiv.innerHTML = '<div class="alert alert-info">Memeriksa nomor...</div>';

    fetch('<?= BASE_URL; ?>/BotStatusController/checkNumber', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ number: number })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            resultDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        } else {
            if (data.exists) {
                resultDiv.innerHTML = `
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> Nomor ${data.number} terdaftar di WhatsApp<br>
                        JID: ${data.jid}
                    </div>
                `;
            } else {
                resultDiv.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> Nomor ${data.number} tidak terdaftar di WhatsApp
                    </div>
                `;
            }
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class="alert alert-danger">Gagal memeriksa nomor</div>';
        console.error('Error checking number:', error);
    });
});

// Fungsi untuk mengupdate tampilan tombol restart berdasarkan status bot
function updateRestartButtonVisibility(isOnline) {
    const restartBtn = document.getElementById('restartBotBtn');
    const restartBtnPlaceholder = document.getElementById('restartBotBtnPlaceholder');
    
    if (restartBtn && restartBtnPlaceholder) {
        if (isOnline) {
            // Jika bot online, sembunyikan tombol restart dan tampilkan placeholder
            restartBtn.style.display = 'none';
            restartBtnPlaceholder.style.display = 'block';
        } else {
            // Jika bot offline, tampilkan tombol restart dan sembunyikan placeholder
            restartBtn.style.display = 'block';
            restartBtnPlaceholder.style.display = 'none';
        }
    }
}

// Tambahkan handler untuk menonaktifkan tombol jika bukan admin
<?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin'): ?>
document.getElementById('restartBotBtn').disabled = true;
document.getElementById('logoutBtn').disabled = true;
document.getElementById('checkNumberBtn').disabled = true;

// Jika user bukan admin, tambahkan event listener yang menampilkan alert
document.getElementById('restartBotBtn').addEventListener('click', function(e) {
    e.preventDefault();
    alert('Akses ditolak: hanya admin yang dapat menggunakan fitur ini');
});

document.getElementById('logoutBtn').addEventListener('click', function(e) {
    e.preventDefault();
    alert('Akses ditolak: hanya admin yang dapat menggunakan fitur ini');
});

document.getElementById('checkNumberBtn').addEventListener('click', function(e) {
    e.preventDefault();
    alert('Akses ditolak: hanya admin yang dapat menggunakan fitur ini');
});
<?php else: ?>
// Jika admin, update tampilan tombol restart berdasarkan status
updateRestartButtonVisibility(false);
<?php endif; ?>

// Reset form saat modal ditutup
document.getElementById('checkNumberModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('waNumber').value = '';
    document.getElementById('checkNumberResult').innerHTML = '';
});

// Update status setiap 5 detik
updateStatus();
updateQr();
refreshInterval = setInterval(updateStatus, 5000);

// Membersihkan interval saat halaman dimuat ulang
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>
