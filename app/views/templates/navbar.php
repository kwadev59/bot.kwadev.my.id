<?php
$activePage = $data['judul'] ?? '';
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

$menuItems = [
    [
        'label'    => 'Laporan',
        'icon'     => 'bi-files',
        'isActive' => strpos($activePage, 'Laporan') !== false,
        'children' => [
            [
                'label'    => 'Laporan Valid',
                'icon'     => 'bi-check-circle',
                'url'      => BASE_URL . '/LaporanController/valid',
                'isActive' => $activePage === 'Laporan Valid'
            ],
            [
                'label'    => 'Laporan Invalid',
                'icon'     => 'bi-exclamation-triangle',
                'url'      => BASE_URL . '/LaporanController/invalid',
                'isActive' => $activePage === 'Laporan Invalid'
            ],
            [
                'label'    => 'Monitoring TU',
                'icon'     => 'bi-truck',
                'url'      => BASE_URL . '/TuMonitoringController',
                'isActive' => $activePage === 'Monitoring File TU'
            ],
        ]
    ],

    [
        'label'    => 'Log',
        'icon'     => 'bi-journal-text',
        'isActive' => strpos($activePage, 'Log') !== false,
        'children' => [
            [
                'label'    => 'Log Duplikat',
                'icon'     => 'bi-card-checklist',
                'url'      => BASE_URL . '/LogController/duplikat',
                'isActive' => $activePage === 'Log File Duplikat'
            ],
            [
                'label'    => 'Log Aktivitas',
                'icon'     => 'bi-activity',
                'url'      => BASE_URL . '/LogController/aktivitas',
                'isActive' => $activePage === 'Log Aktivitas Bot'
            ]
        ]
    ],
    [
        'label'    => 'Status Bot',
        'icon'     => 'bi-power',
        'url'      => BASE_URL . '/BotStatusController',
        'isActive' => strpos($activePage, 'Status Bot') !== false
    ]
];

if ($isAdmin) {
    $menuItems[] = [
        'label'    => 'Pengaturan',
        'icon'     => 'bi-gear',
        'children' => [
            [
                'label'    => 'Karyawan BIM',
                'icon'     => 'bi-people-fill',
                'url'      => BASE_URL . '/EmployeeBimController',
                'isActive' => $activePage === 'Karyawan BIM1'
            ],
            [
                'label'    => 'Karyawan PPS',
                'icon'     => 'bi-people-fill',
                'url'      => BASE_URL . '/EmployeePpsController',
                'isActive' => $activePage === 'Karyawan PPS1'
            ],
            [
                'label'    => 'Manajemen User',
                'icon'     => 'bi-people-fill',
                'url'      => BASE_URL . '/UserController',
                'isActive' => $activePage === 'Manajemen User'
            ],
            [
                'label'    => 'Gadget BIM1',
                'icon'     => 'bi-phone',
                'url'      => BASE_URL . '/GadgetController/bim1',
                'isActive' => $activePage === 'Data Gadget BIM1'
            ],
            [
                'label'    => 'Gadget PPS1',
                'icon'     => 'bi-phone-fill',
                'url'      => BASE_URL . '/GadgetController/pps1',
                'isActive' => $activePage === 'Data Gadget PPS1'
            ],
            [
                'label'    => 'Status Gadget',
                'icon'     => 'bi-phone-vibrate',
                'url'      => BASE_URL . '/GadgetStatusController',
                'isActive' => $activePage === 'Status Gadget Driver'
            ],
            
            [
                'label'    => 'Kontak',
                'icon'     => 'bi-people',
                'url'      => BASE_URL . '/KontakController',
                'isActive' => $activePage === 'Manajemen Kontak'
            ]
        ]
    ];
}
?>
<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="<?= BASE_URL; ?>/DashboardController">
            <i class="bi bi-robot me-2"></i>
            WA Bot Panel
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php foreach ($menuItems as $item): ?>
                    <?php
                        $hasChildren = !empty($item['children']);
                        $isActive = !empty($item['isActive']);
                        if ($hasChildren) {
                            foreach ($item['children'] as $child) {
                                if (!empty($child['isActive'])) {
                                    $isActive = true;
                                    break;
                                }
                            }
                        }
                        $icon = $item['icon'] ?? null;
                    ?>
                    <?php if ($hasChildren): ?>
                        <?php $dropdownId = 'dropdown-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($item['label'])); ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= $isActive ? 'active' : ''; ?>" href="#" id="<?= $dropdownId; ?>" role="button" data-bs-toggle="dropdown" aria-expanded="<?= $isActive ? 'true' : 'false'; ?>">
                                <?php if ($icon): ?>
                                    <i class="bi <?= htmlspecialchars($icon); ?> me-1"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($item['label']); ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="<?= $dropdownId; ?>">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li>
                                        <a class="dropdown-item <?= !empty($child['isActive']) ? 'active' : ''; ?>" href="<?= htmlspecialchars($child['url']); ?>">
                                            <?php if (!empty($child['icon'])): ?>
                                                <i class="bi <?= htmlspecialchars($child['icon']); ?> me-2"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($child['label']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $isActive ? 'active' : ''; ?>" href="<?= htmlspecialchars($item['url']); ?>">
                                <?php if ($icon): ?>
                                    <i class="bi <?= htmlspecialchars($icon); ?> me-1"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($item['label']); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <ul class="navbar-nav d-flex align-items-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center py-1" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-2"></i>
                        <span><?= htmlspecialchars($data['nama_user'] ?? $_SESSION['nama_lengkap'] ?? 'User'); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL; ?>/AuthController/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<main class="page-content">
    <div class="container-fluid px-4">
        <div class="row">
            <div class="col-12">
