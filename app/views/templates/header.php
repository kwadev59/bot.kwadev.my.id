<!DOCTYPE html>
<html lang="id" style="height: 100%;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['judul']; ?> - WA Bot Panel</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Flatpickr Date Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL; ?>/../style_v2.css">
    <style>
        /* Override for top navbar layout */
        body {
            padding-top: 60px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
            color: #212529 !important;
        }
        
        .navbar-brand i {
            color: #0d6efd;
        }
        
        .nav-item {
            margin-right: 0.5rem;
        }
        
        .nav-link {
            color: #6c757d !important;
            font-weight: 500;
            padding: 0.6rem 1rem !important;
            border-radius: 0.5rem;
            margin: 0 0.25rem;
            transition: all 0.2s ease-in-out;
        }
        
        .nav-link:hover {
            background-color: #eef2f7;
            color: #0d6efd !important;
        }
        
        .nav-link.active {
            background-color: #0d6efd;
            color: #ffffff !important;
            font-weight: 600;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            border-radius: 0.5rem;
        }
        
        .dropdown-item.active, .dropdown-item:active {
            background-color: #0d6efd;
        }
        
        .page-content {
            flex: 1;
            padding: 2rem 0;
        }
        
        .card {
            border: none;
            border-radius: 0.75rem;
        }
        
        .footer {
            margin-top: auto;
        }
    </style>
</head>
<body>
