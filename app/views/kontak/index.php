<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-0">Manajemen Kontak</h1>
        <p class="text-muted mb-0">Kelola daftar kontak WA untuk semua jabatan lapangan.</p>
    </div>

</div>

<?php if (isset($_SESSION['flash'])): ?>
<div class="alert alert-<?= htmlspecialchars($_SESSION['flash']['tipe'] ?? '', ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($_SESSION['flash']['pesan'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    <?php unset($_SESSION['flash']); ?>
</div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4">
                    <label for="searchKontak" class="form-label fw-semibold">Cari kontak</label>
                    <input
                        type="text"
                        class="form-control"
                        id="searchKontak"
                        name="search"
                        placeholder="Cari berdasarkan nama, site, jabatan..."
                        value="<?= htmlspecialchars($data['search'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label for="perPage" class="form-label fw-semibold">Per halaman</label>
                    <select class="form-select" id="perPage" name="per_page">
                        <?php $perPageOptions = $data['per_page_options'] ?? [10, 25, 50, 100]; ?>
                        <?php $selectedPerPage = (int)($data['per_page'] ?? 25); ?>
                        <?php foreach ($perPageOptions as $option): ?>
                            <option value="<?= (int)$option; ?>" <?= $selectedPerPage === (int)$option ? 'selected' : ''; ?>><?= (int)$option; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label fw-semibold">&nbsp;</label>
                    <button type="submit" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-search"></i> Cari
                    </button>
                </div>
                <div class="col-lg-4 col-md-4 text-lg-end">
                    <label class="form-label fw-semibold d-block">Aksi Cepat</label>
                    <div class="d-flex gap-2 justify-content-lg-end">
                        <button type="button" class="btn btn-success flex-grow-1 flex-lg-grow-0" data-bs-toggle="modal" data-bs-target="#importKontakModal">
                            <i class="bi bi-upload"></i> Import CSV
                        </button>
                        <div class="btn-group flex-grow-1 flex-lg-grow-0">
                            <button type="button" class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-download"></i> Template
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL; ?>/KontakController/downloadTemplate/default">
                                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>Template Default
                                        <span class="badge bg-secondary float-end"><?= (int)($data['download_counts']['template'] ?? 0); ?></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL; ?>/KontakController/downloadTemplate/bom">
                                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>Template BOM
                                        <span class="badge bg-secondary float-end"><?= (int)($data['download_counts']['template_dengan_bom'] ?? 0); ?></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL; ?>/KontakController/downloadTemplate/variasi">
                                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>Template Variasi Header
                                        <span class="badge bg-secondary float-end"><?= (int)($data['download_counts']['template_variasi_header'] ?? 0); ?></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL; ?>/KontakController/downloadTemplate/valid">
                                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>Contoh Data Valid
                                        <span class="badge bg-secondary float-end"><?= (int)($data['download_counts']['template_kontak_valid'] ?? 0); ?></span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php $pagination = $data['pagination'] ?? []; ?>
        <?php $currentPage = (int)($pagination['current_page'] ?? 1); ?>
        <?php $totalPages = (int)($pagination['total_pages'] ?? 1); ?>
        <?php $firstItem = (int)($pagination['first_item'] ?? 0); ?>
        <?php $lastItem = (int)($pagination['last_item'] ?? 0); ?>
        <?php $totalItems = (int)($pagination['total_items'] ?? 0); ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">Daftar Kontak</h5>
                <small class="text-muted">
                    <?= $totalItems > 0 ? "Menampilkan {$firstItem}-{$lastItem} dari {$totalItems} kontak" : 'Belum ada kontak yang tersimpan.'; ?>
                </small>
            </div>
            <button
                type="button"
                class="btn btn-primary fw-semibold mt-2 mt-md-0"
                data-bs-toggle="modal"
                data-bs-target="#tambahKontakModal">
                <i class="bi bi-plus-lg me-1"></i> Tambah Kontak
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Site</th>
                        <th scope="col">AFD</th>
                        <th scope="col">Nama</th>
                        <th scope="col">Nomor WA</th>
                        <th scope="col">Jabatan</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $kontakList = $data['kontak_list'] ?? []; ?>
                    <?php if (empty($kontakList)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Tidak ada kontak yang dapat ditampilkan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($kontakList as $kontak): ?>
                            <?php
                                $site = htmlspecialchars($kontak['site'] ?? '-', ENT_QUOTES, 'UTF-8');
                                $afd = htmlspecialchars($kontak['afd'] ?? '-', ENT_QUOTES, 'UTF-8');
                                $nama = htmlspecialchars($kontak['nama'] ?? '', ENT_QUOTES, 'UTF-8');
                                $nomerWaRaw = trim((string)($kontak['nomer_wa'] ?? ''));
                                $nomerWaDigits = preg_replace('/[^0-9]/', '', $nomerWaRaw);
                                $nomerWaDisplay = htmlspecialchars($nomerWaRaw !== '' ? $nomerWaRaw : '-', ENT_QUOTES, 'UTF-8');
                                $kategori = htmlspecialchars($kontak['kategori'] ?? '-', ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr>
                                <td><?= $site !== '' ? $site : '-'; ?></td>
                                <td><?= $afd !== '' ? $afd : '-'; ?></td>
                                <td><?= $nama; ?></td>
                                <td>
                                    <?php if ($nomerWaDigits !== ''): ?>
                                        <a href="https://wa.me/<?= htmlspecialchars($nomerWaDigits, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                            <?= $nomerWaDisplay; ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= $kategori !== '' ? $kategori : '-'; ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button
                                            type="button"
                                            class="btn btn-outline-primary editKontakBtn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editKontakModal"
                                            data-id="<?= (int)($kontak['id'] ?? 0); ?>"
                                            data-site="<?= $site; ?>"
                                            data-afd="<?= $afd; ?>"
                                            data-nama="<?= $nama; ?>"
                                            data-nomer="<?= htmlspecialchars($nomerWaRaw, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-kategori="<?= $kategori; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a
                                            href="<?= BASE_URL; ?>/KontakController/hapus/<?= (int)($kontak['id'] ?? 0); ?>"
                                            class="btn btn-outline-danger"
                                            onclick="return confirm('Yakin ingin menghapus kontak ini?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <?php
                $baseQuery = $data['preserved_query'] ?? '';
                $queryPrefix = $baseQuery !== '' ? $baseQuery . '&' : '';
                $window = 2;
                $startPage = max(1, $currentPage - $window);
                $endPage = min($totalPages, $startPage + ($window * 2));
                $startPage = max(1, $endPage - ($window * 2));
            ?>
            <nav aria-label="Navigasi halaman kontak" class="mt-3">
                <ul class="pagination justify-content-end mb-0">
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?= BASE_URL; ?>/KontakController?<?= $queryPrefix; ?>page=<?= max(1, $currentPage - 1); ?>" aria-label="Sebelumnya">
                            &laquo;
                        </a>
                    </li>
                    <?php for ($page = $startPage; $page <= $endPage; $page++): ?>
                        <li class="page-item <?= $page === $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="<?= BASE_URL; ?>/KontakController?<?= $queryPrefix; ?>page=<?= $page; ?>">
                                <?= $page; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?= BASE_URL; ?>/KontakController?<?= $queryPrefix; ?>page=<?= min($totalPages, $currentPage + 1); ?>" aria-label="Berikutnya">
                            &raquo;
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Import Kontak Modal -->
<div class="modal fade" id="importKontakModal" tabindex="-1" aria-labelledby="importKontakLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importKontakLabel">Import Kontak via CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL; ?>/KontakController/import" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="kontakCsv" class="form-label">Pilih file CSV</label>
                        <input class="form-control" type="file" id="kontakCsv" name="kontak_csv" accept=".csv" required>
                        <div class="form-text">Kolom yang disarankan: <code>site</code>, <code>afd</code>, <code>nama</code>, <code>nomor wa</code>, <code>jabatan</code>. Baris pertama dianggap header.</div>
                    </div>
                    <div class="alert alert-light border-start border-success" role="alert">
                        <div class="d-flex">
                            <i class="bi bi-info-circle me-2 mt-1"></i>
                            <div>
                                <strong>Tips:</strong> Gunakan tombol Template untuk mengunduh format CSV yang sesuai. Sistem akan melewati baris tanpa nama.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Mulai Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tambah Kontak Modal -->
<div class="modal fade" id="tambahKontakModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kontak Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL; ?>/KontakController/tambah" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="kategori" class="form-label">Jabatan</label>
                        <select class="form-select" id="kategori" name="kategori" required>
                            <option value="">Pilih jabatan</option>
                            <?php foreach (($data['kategori_options'] ?? []) as $kategoriOption): ?>
                                <option value="<?= htmlspecialchars($kategoriOption, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?= htmlspecialchars($kategoriOption, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="site" class="form-label">Site</label>
                            <input type="text" class="form-control" id="site" name="site" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="afd" class="form-label">AFD</label>
                            <input type="text" class="form-control" id="afd" name="afd" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama</label>
                        <input type="text" class="form-control" id="nama" name="nama" required>
                    </div>
                    <div class="mb-3">
                        <label for="nomerWa" class="form-label">Nomor WA</label>
                        <input type="text" class="form-control" id="nomerWa" name="nomer_wa" required>
                        <div class="form-text">Gunakan format internasional, contoh: 6281234567890</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Kontak Modal -->
<div class="modal fade" id="editKontakModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Kontak</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL; ?>/KontakController/update" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="editId" name="id" value="">
                    <div class="mb-3">
                        <label for="editKategori" class="form-label">Jabatan</label>
                        <select class="form-select" id="editKategori" name="kategori" required>
                            <option value="">Pilih jabatan</option>
                            <?php foreach (($data['kategori_options'] ?? []) as $kategoriOption): ?>
                                <option value="<?= htmlspecialchars($kategoriOption, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?= htmlspecialchars($kategoriOption, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editSite" class="form-label">Site</label>
                            <input type="text" class="form-control" id="editSite" name="site" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editAfd" class="form-label">AFD</label>
                            <input type="text" class="form-control" id="editAfd" name="afd" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editNama" class="form-label">Nama</label>
                        <input type="text" class="form-control" id="editNama" name="nama" required>
                    </div>
                    <div class="mb-3">
                        <label for="editNomerWa" class="form-label">Nomor WA</label>
                        <input type="text" class="form-control" id="editNomerWa" name="nomer_wa" required>
                        <div class="form-text">Gunakan format internasional, contoh: 6281234567890</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editKontakModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) { return; }

            const id = button.getAttribute('data-id') || '';
            const site = button.getAttribute('data-site') || '';
            const afd = button.getAttribute('data-afd') || '';
            const nama = button.getAttribute('data-nama') || '';
            const nomer = button.getAttribute('data-nomer') || '';
            const kategori = button.getAttribute('data-kategori') || '';

            editModal.querySelector('#editId').value = id;
            editModal.querySelector('#editSite').value = site;
            editModal.querySelector('#editAfd').value = afd;
            editModal.querySelector('#editNama').value = nama;
            editModal.querySelector('#editNomerWa').value = nomer;

            const kategoriSelect = editModal.querySelector('#editKategori');
            if (kategoriSelect) {
                let hasOption = false;
                kategoriSelect.querySelectorAll('option').forEach(function(option) {
                    if (option.value === kategori) {
                        hasOption = true;
                    }
                });
                if (!hasOption && kategori) {
                    kategoriSelect.add(new Option(kategori, kategori, true, true));
                }
                kategoriSelect.value = kategori;
            }
        });
    }

    const perPageSelect = document.getElementById('perPage');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            this.form.submit();
        });
    }
});
</script>
