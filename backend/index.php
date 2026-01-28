<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VMC Informática</title>
    <meta name="description" content="Sistema de Gestión de Clientes e Incidencias VMC">
    <meta name="author" content="VMC Informática">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <link rel="apple-touch-icon" href="images/favicon-180.svg">
    <link rel="shortcut icon" href="images/favicon.svg">
</head>
<body>
    <div id="app">
        <!-- Loading Screen -->
        <div id="loading-screen" class="loading-overlay">
            <div class="text-center">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <h4 class="mt-3 text-muted">Iniciando VMC Informática</h4>
                <p class="text-muted">Verificando autenticación...</p>
            </div>
        </div>

        <!-- Login Form -->
        <div id="login-container" class="d-none">
            <div class="container-fluid vh-100 d-flex align-items-center justify-content-center bg-gradient">
                <div class="card shadow-lg border-0" style="max-width: 420px; width: 100%; border-radius: 1rem;">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="bi bi-building text-white" style="font-size: 2.5rem;"></i>
                            </div>
                            <h2 class="mt-3 fw-bold">VMC Informática</h2>
                            <p class="text-muted mb-0">Sistema de Gestión de Clientes e Incidencias</p>
                        </div>

                        <form id="login-form">
                            <div class="mb-3">
                                <label for="username" class="form-label fw-semibold">Usuario</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-person text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" id="username" name="user" required autocomplete="username">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-lock text-muted"></i>
                                    </span>
                                    <input type="password" class="form-control border-start-0" id="password" name="pass" required autocomplete="current-password">
                                    <button type="button" class="btn btn-outline-secondary border-start-0" id="toggle-password">
                                        <i class="bi bi-eye" id="password-icon"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg fw-semibold" id="login-btn">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                                </button>
                            </div>
                        </form>

                        <div id="login-alert" class="alert d-none" role="alert"></div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Main Application -->
        <div id="main-app" class="d-none">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
                <div class="container-fluid">
                    <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <a class="navbar-brand d-flex align-items-center" href="#" onclick="showSection('dashboard')">
                        <i class="bi bi-building me-2"></i>
                        <span class="fw-bold">VMC Informática</span>
                    </a>

                    <div class="navbar-nav ms-auto">
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-2"></i>
                                <span id="user-name" class="d-none d-sm-inline">Usuario</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header">
                                    <i class="bi bi-person me-1"></i>
                                    <span id="user-name-menu">Usuario</span>
                                </h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="logout()">
                                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Mobile Sidebar -->
            <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
                <div class="offcanvas-header bg-primary text-white">
                    <h5 class="offcanvas-title" id="sidebarLabel">
                        <i class="bi bi-list me-2"></i>Navegación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body p-0">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link py-3" href="#" data-section="dashboard" onclick="showSection('dashboard')" data-bs-dismiss="offcanvas">
                                <i class="bi bi-speedometer2 me-3"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link py-3" href="#" data-section="clientes" onclick="showSection('clientes')" data-bs-dismiss="offcanvas">
                                <i class="bi bi-people me-3"></i>Clientes
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="container-fluid">
                <div class="row">
                    <!-- Desktop Sidebar -->
                    <nav class="col-lg-2 d-none d-lg-block bg-light sidebar">
                        <div class="position-sticky pt-3">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link py-2" href="#" data-section="dashboard" onclick="showSection('dashboard')">
                                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-2" href="#" data-section="clientes" onclick="showSection('clientes')">
                                        <i class="bi bi-people me-2"></i>Clientes
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </nav>

                    <!-- Main Content -->
                    <main class="col-lg-10 ms-sm-auto px-md-4">
                        <!-- Dashboard Section -->
                        <div id="dashboard-section" class="content-section fade-in">
                            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                                <h1 class="h2 fw-bold">
                                    <i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard
                                </h1>
                                <div class="btn-toolbar mb-2 mb-md-0">
                                    <button class="btn btn-outline-primary btn-sm" onclick="loadDashboard()" id="refresh-dashboard">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
                                    </button>
                                </div>
                            </div>

                            <!-- Stats Cards -->
                            <div class="row mb-4" id="stats-cards">
                                <!-- Cards will be loaded here dynamically -->
                            </div>

                            <!-- Charts -->
                            <div class="row" id="charts-container">
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="bi bi-bar-chart me-2"></i>Estados de Incidencias
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="incidenciasChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="bi bi-pie-chart me-2"></i>Realizadas
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="distributionChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="bi bi-pie-chart-fill me-2"></i>Cobradas
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="paymentChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Clientes Section -->
                        <div id="clientes-section" class="content-section d-none">
                            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                                <h1 class="h2 fw-bold">
                                    <i class="bi bi-people me-2 text-primary"></i>Gestión de Clientes
                                </h1>
                                <div class="btn-toolbar mb-2 mb-md-0">
                                    <button class="btn btn-primary" onclick="openClienteModal()">
                                        <i class="bi bi-plus-lg me-2"></i>Nuevo Cliente
                                    </button>
                                </div>
                            </div>

                            <!-- Search and Filters -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Buscar clientes</label>
                                            <input type="text" class="form-control" id="search-clientes" placeholder="Nombre, razón social, DNI/CIF o teléfono...">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Estado de incidencias</label>
                                            <select class="form-select" id="filter-estado-incidencias" onchange="handleEstadoIncidenciasChange(this.value)">
                                                <option value="">Todos los clientes</option>
                                                <option value="sin_cobrar">Con incidencias sin cobrar</option>
                                                <option value="sin_realizar">Con incidencias sin realizar</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">Por página</label>
                                            <select class="form-select" id="page-size">
                                                <option value="20">20</option>
                                                <option value="30">30</option>
                                                <option value="50">50</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">&nbsp;</label>
                                            <button class="btn btn-outline-secondary d-block w-100" onclick="clearClientesFilters()">
                                                <i class="bi bi-arrow-clockwise me-1"></i>Limpiar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Clientes Table -->
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="fw-semibold sortable-header" data-sort="nombre" style="cursor: pointer; user-select: none;">
                                                        <i class="bi bi-person me-1"></i>Nombre
                                                        <i class="bi bi-chevron-expand sort-icon" id="sort-icon-nombre"></i>
                                                    </th>
                                                    <th class="fw-semibold sortable-header" data-sort="razon_social" style="cursor: pointer; user-select: none;">
                                                        <i class="bi bi-building me-1"></i>Razón Social
                                                        <i class="bi bi-chevron-expand sort-icon" id="sort-icon-razon_social"></i>
                                                    </th>
                                                    <th class="fw-semibold sortable-header" data-sort="dni" style="cursor: pointer; user-select: none;">
                                                        DNI/CIF
                                                        <i class="bi bi-chevron-expand sort-icon" id="sort-icon-dni"></i>
                                                    </th>
                                                    <th class="fw-semibold">
                                                        <i class="bi bi-telephone me-1"></i>Teléfono
                                                    </th>
                                                    <th class="fw-semibold">Observaciones</th>
                                                    <th class="fw-semibold text-center" width="180">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="clientes-table">
                                                <tr>
                                                    <td colspan="6" class="text-center py-4">
                                                        <div class="spinner-border text-primary" role="status">
                                                            <span class="visually-hidden">Cargando...</span>
                                                        </div>
                                                        <p class="mt-2 text-muted mb-0">Cargando clientes...</p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination -->
                                    <nav aria-label="Paginación de clientes" class="mt-3">
                                        <ul class="pagination justify-content-center mb-0" id="pagination-clientes">
                                            <!-- Pagination will be loaded here -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>

                        <!-- Incidencias Section -->
                        <div id="incidencias-section" class="content-section d-none">
                            <div class="pt-3 pb-2 mb-3">
                                <nav aria-label="breadcrumb" class="mb-3">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item">
                                            <a href="#" onclick="showSection('clientes')" class="text-decoration-none">
                                                <i class="bi bi-people me-1"></i>Clientes
                                            </a>
                                        </li>
                                        <li class="breadcrumb-item active" aria-current="page" id="cliente-breadcrumb">
                                            <i class="bi bi-clipboard-check me-1"></i>Incidencias
                                        </li>
                                    </ol>
                                </nav>

                                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center border-bottom pb-3">
                                    <h1 class="h2 fw-bold">
                                        <i class="bi bi-clipboard-check me-2 text-primary"></i>
                                        Incidencias - <span id="cliente-name" class="text-secondary">Cliente</span>
                                    </h1>
                                    <div class="btn-toolbar mb-2 mb-md-0">
                                        <button class="btn btn-outline-secondary me-2" onclick="showSection('clientes')">
                                            <i class="bi bi-arrow-left me-2"></i>Volver
                                        </button>
                                        <button class="btn btn-primary" onclick="openIncidenciaModal()">
                                            <i class="bi bi-plus-lg me-2"></i>Nueva Incidencia
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Filters -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">Realizado</label>
                                            <select class="form-select" id="filter-realizado">
                                                <option value="">Todos</option>
                                                <option value="1">Sí</option>
                                                <option value="0">No</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">Cobrado</label>
                                            <select class="form-select" id="filter-cobrado">
                                                <option value="">Todos</option>
                                                <option value="1">Sí</option>
                                                <option value="0">No</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">Por página</label>
                                            <select class="form-select" id="incidencias-page-size">
                                                <option value="20">20</option>
                                                <option value="30">30</option>
                                                <option value="50">50</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">&nbsp;</label>
                                            <button class="btn btn-outline-secondary d-block w-100" onclick="clearIncidenciasFilters()">
                                                <i class="bi bi-arrow-clockwise me-1"></i>Limpiar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Incidencias Table -->
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="fw-semibold" width="10%">
                                                        <i class="bi bi-calendar me-1"></i>Fecha
                                                    </th>
                                                    <th class="fw-semibold" width="30%">Incidencia</th>
                                                    <th class="fw-semibold" width="30%">Respuesta</th>
                                                    <th class="fw-semibold text-center" width="10%">Realizado</th>
                                                    <th class="fw-semibold text-center" width="10%">Cobrado</th>
                                                    <th class="fw-semibold text-center" width="10%">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="incidencias-table">
                                                <tr>
                                                    <td colspan="6" class="text-center py-4">
                                                        <div class="spinner-border text-primary" role="status">
                                                            <span class="visually-hidden">Cargando...</span>
                                                        </div>
                                                        <p class="mt-2 text-muted mb-0">Cargando incidencias...</p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination -->
                                    <nav aria-label="Paginación de incidencias" class="mt-3">
                                        <ul class="pagination justify-content-center mb-0" id="pagination-incidencias">
                                            <!-- Pagination will be loaded here -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </div>

        <!-- Modals will be included from external file -->
        <div id="modals-container">
            <!-- Cliente and Incidencia modals will be loaded here -->
        </div>

        <!-- Toast Container -->
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1070;">
            <!-- Toasts will be added here dynamically -->
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

    <!-- Chart.js Fallback -->
    <script>
        // Verificar si Chart.js se cargó correctamente
        function checkChartJS() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js no se cargó desde CDN principal, cargando CDN alternativo...');

                // Cargar Chart.js desde CDN alternativo
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/chart.js/4.4.0/chart.umd.js';
                script.onload = function() {
                    console.log('Chart.js cargado desde CDN alternativo');
                };
                script.onerror = function() {
                    console.error('Error cargando Chart.js desde CDN alternativo');
                };
                document.head.appendChild(script);
            } else {
                console.log('Chart.js cargado exitosamente desde CDN principal');
            }
        }

        // Verificar después de que la página se cargue
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', checkChartJS);
        } else {
            checkChartJS();
        }
    </script>

    <!-- Custom JS -->
    <script src="assets/js/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>
