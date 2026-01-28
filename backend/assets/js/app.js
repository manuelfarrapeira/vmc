/**
 * VMC Sistema de Gestión - JavaScript Application
 * Frontend PHP con Bootstrap y JavaScript vanilla
 */

class VMCApp {
    constructor() {
        // Configuración de la aplicación
        this.apiUrl = '/api'; // Usando rutas relativas del VirtualHost
        this.token = null;
        this.currentUser = null;
        this.currentClienteId = null;
        this.currentCliente = null;
        this.currentIncidencias = []; // Almacenar incidencias cargadas

        // Estado de paginación
        this.pagination = {
            clientes: { page: 1, limit: 20, total: 0, pages: 0 },
            incidencias: { page: 1, limit: 20, total: 0, pages: 0 }
        };

        // Filtros de búsqueda
        this.filters = {
            clientes: {
                search: '',
                orderBy: 'nombre',
                orderDir: 'ASC',
                estadoIncidencias: ''
            },
            incidencias: {
                realizado: '',
                cobrado: '',
                finalizada: ''
            }
        };

        // Charts instances
        this.charts = {};

        // Flag para saber si los gráficos están pendientes de cargar
        this.chartsNeedRetry = false;
        this.lastDashboardStats = null;

        // Timeouts para debounce
        this.timeouts = {};

        this.init();
    }

    /**
     * Inicialización de la aplicación
     */
    async init() {
        try {
            this.setupEventListeners();
            await this.loadModals();
            await this.checkAuth();
        } catch (error) {
            this.showLogin();
        }
    }

    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Login form
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.login();
            });
        }

        // Password toggle
        const togglePassword = document.getElementById('toggle-password');
        if (togglePassword) {
            togglePassword.addEventListener('click', this.togglePasswordVisibility);
        }

        // Navigation links
        document.querySelectorAll('[data-section]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.showSection(link.dataset.section);
            });
        });

        // Search debounce for clientes
        const searchInput = document.getElementById('search-clientes');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(this.timeouts.search);
                this.timeouts.search = setTimeout(() => {
                    this.filters.clientes.search = e.target.value;
                    this.pagination.clientes.page = 1;
                    this.loadClientes();
                }, 300);
            });
        }

        // Filter by estado de incidencias
        const filterEstadoIncidencias = document.getElementById('filter-estado-incidencias');
        if (filterEstadoIncidencias) {
            filterEstadoIncidencias.addEventListener('change', (e) => {
                this.filters.clientes.estadoIncidencias = e.target.value;
                this.pagination.clientes.page = 1;
                this.loadClientes();
            });
        }

        // Page size changes
        const pageSizeElement = document.getElementById('page-size');
        if (pageSizeElement) {
            pageSizeElement.addEventListener('change', () => {
                this.updateClientesFilters();
            });
        }

        // Sortable headers
        document.querySelectorAll('.sortable-header').forEach(header => {
            header.addEventListener('click', () => {
                const sortField = header.dataset.sort;
                this.toggleSort(sortField);
            });
        });

        // Incidencias filters
        ['filter-realizado', 'filter-cobrado', 'filter-finalizada', 'incidencias-page-size'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', () => {
                    this.updateIncidenciasFilters();
                });
            }
        });

        // Window resize for charts
        window.addEventListener('resize', () => {
            Object.values(this.charts).forEach(chart => {
                if (chart) chart.resize();
            });
        });
    }

    /**
     * Toggle password visibility
     */
    togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const passwordIcon = document.getElementById('password-icon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            passwordIcon.className = 'bi bi-eye-slash';
        } else {
            passwordInput.type = 'password';
            passwordIcon.className = 'bi bi-eye';
        }
    }

    /**
     * Load modals HTML content
     */
    async loadModals() {
        const modalsContainer = document.getElementById('modals-container');
        if (modalsContainer) {
            modalsContainer.innerHTML = this.getModalsHTML();
        }
    }
    /**
     * Get modals HTML
     */
    getModalsHTML() {
        return `
            <!-- Cliente Modal -->
            <div class="modal fade" id="clienteModal" tabindex="-1" aria-labelledby="clienteModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="clienteModalLabel">
                                <i class="bi bi-person-plus me-2"></i><span id="cliente-modal-title">Nuevo Cliente</span>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="cliente-form" novalidate>
                            <div class="modal-body">
                                <input type="hidden" id="cliente-id">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="cliente-nombre" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="cliente-nombre" name="nombre" required>
                                        <div class="invalid-feedback">El nombre es obligatorio.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cliente-razon" class="form-label fw-semibold">Razón Social</label>
                                        <input type="text" class="form-control" id="cliente-razon" name="razon_social">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cliente-codigo" class="form-label fw-semibold">Código</label>
                                        <input type="text" class="form-control" id="cliente-codigo" name="codigo">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cliente-telefono" class="form-label fw-semibold">Teléfono</label>
                                        <input type="tel" class="form-control" id="cliente-telefono" name="tlf" pattern="[0-9]{9}">
                                        <div class="form-text">Formato: 9 dígitos sin espacios</div>
                                    </div>
                                    <div class="col-12">
                                        <label for="cliente-observaciones" class="form-label fw-semibold">Observaciones</label>
                                        <textarea class="form-control" id="cliente-observaciones" name="observaciones" rows="3" maxlength="200"></textarea>
                                        <div class="form-text">Máximo 200 caracteres</div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-lg me-1"></i>Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i><span id="cliente-submit-text">Crear Cliente</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Incidencia Modal -->
            <div class="modal fade" id="incidenciaModal" tabindex="-1" aria-labelledby="incidenciaModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="incidenciaModalLabel">
                                <i class="bi bi-clipboard-plus me-2"></i><span id="incidencia-modal-title">Nueva Incidencia</span>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="incidencia-form" novalidate>
                            <div class="modal-body">
                                <input type="hidden" id="incidencia-id">
                                <input type="hidden" id="incidencia-cliente-id">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="incidencia-fecha" class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="incidencia-fecha" name="fecha" placeholder="DD/MM/YYYY" pattern="\\d{2}/\\d{2}/\\d{4}" required>
                                        <div class="form-text">Formato: DD/MM/YYYY</div>
                                        <div class="invalid-feedback">La fecha es obligatoria (formato DD/MM/YYYY).</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="incidencia-realizado" class="form-label fw-semibold">Realizado</label>
                                        <select class="form-select" id="incidencia-realizado" name="realizado">
                                            <option value="0">No</option>
                                            <option value="1">Sí</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="incidencia-descripcion" class="form-label fw-semibold">Descripción de la Incidencia <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="incidencia-descripcion" name="incidencia" rows="4" required></textarea>
                                        <div class="invalid-feedback">La descripción de la incidencia es obligatoria.</div>
                                    </div>
                                    <div class="col-12">
                                        <label for="incidencia-respuesta" class="form-label fw-semibold">Respuesta</label>
                                        <textarea class="form-control" id="incidencia-respuesta" name="respuesta" rows="3"></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="incidencia-cobrado" class="form-label fw-semibold">Cobrado</label>
                                        <select class="form-select" id="incidencia-cobrado" name="cobrado">
                                            <option value="0">No</option>
                                            <option value="1">Sí</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="incidencia-finalizada" class="form-label fw-semibold">Finalizada</label>
                                        <select class="form-select" id="incidencia-finalizada" name="finalizada">
                                            <option value="0">No</option>
                                            <option value="1">Sí</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-lg me-1"></i>Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i><span id="incidencia-submit-text">Crear Incidencia</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Confirmation Modal -->
            <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmModalLabel">
                                <i class="bi bi-exclamation-triangle text-warning me-2"></i>Confirmar Acción
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="confirm-message">
                            ¿Estás seguro de realizar esta acción?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-lg me-1"></i>Cancelar
                            </button>
                            <button type="button" class="btn btn-danger" id="confirm-action-btn">
                                <i class="bi bi-check-lg me-1"></i>Confirmar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Verificar autenticación
     */
    async checkAuth() {
        this.token = sessionStorage.getItem('vmc_token');
        const userData = sessionStorage.getItem('vmc_user');

        if (this.token && userData) {
            try {
                this.currentUser = JSON.parse(userData);
                const isValid = await this.verifyToken();
                if (isValid) {
                    this.showMainApp();
                    return;
                }
            } catch (error) {
                console.warn('❌ Error en verificación de token:', error);
            }
        }
        this.clearAuthData();
        this.showLogin();
    }

    /**
     * Verify token with server
     */
    async verifyToken() {
        if (!this.token) {
            console.log('❌ No hay token para verificar');
            return false;
        }
        try {
            const response = await this.apiCall('verify_token.php', 'GET');
            if (response.valid) {
                return true;
            } else {
                console.log('❌ Token inválido:', response.message);
                return false;
            }
        } catch (error) {
            console.log('❌ Error verificando token:', error.toString());
            return false;
        }
    }

    /**
     * Clear authentication data
     */
    clearAuthData() {
        this.token = null;
        this.currentUser = null;
        sessionStorage.removeItem('vmc_token');
        sessionStorage.removeItem('vmc_user');
    }

    /**
     * Realizar login
     */
    async login() {
        const loginBtn = document.getElementById('login-btn');
        const originalText = loginBtn.innerHTML;

        try {
            // Show loading state
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div>Iniciando...';

            const formData = new FormData(document.getElementById('login-form'));
            const credentials = Object.fromEntries(formData);

            const response = await this.apiCall('auth/login.php', 'POST', credentials);

            this.token = response.token;
            this.currentUser = response.user;

            // Save to session storage
            sessionStorage.setItem('vmc_token', this.token);
            sessionStorage.setItem('vmc_user', JSON.stringify(this.currentUser));

            this.showToast('¡Bienvenido al sistema VMC!', 'success');


            this.showMainApp();

        } catch (error) {
            console.error('Login error:', error);
            this.showAlert('login-alert', 'danger', error);
        } finally {
            // Reset button state
            loginBtn.disabled = false;
            loginBtn.innerHTML = originalText;
        }
    }

    /**
     * Realizar logout
     */
    logout() {
        this.clearAuthData();
        this.showToast('Sesión cerrada correctamente', 'info');
        this.showLogin();
    }

    /**
     * Mostrar pantalla de login
     */
    showLogin() {
        document.getElementById('loading-screen').classList.add('d-none');
        document.getElementById('login-container').classList.remove('d-none');
        document.getElementById('main-app').classList.add('d-none');

        // Clear form
        document.getElementById('login-form').reset();

        // Hide any alerts
        document.getElementById('login-alert').classList.add('d-none');
    }

    /**
     * Mostrar aplicación principal
     */
    showMainApp() {
        document.getElementById('loading-screen').classList.add('d-none');
        document.getElementById('login-container').classList.add('d-none');
        document.getElementById('main-app').classList.remove('d-none');

        // Update user info
        document.getElementById('user-name').textContent = this.currentUser.user;
        document.getElementById('user-name-menu').textContent = this.currentUser.user;

        // Show dashboard by default
        this.showSection('dashboard');
    }

    /**
     * Mostrar sección específica
     */
    showSection(section) {
        // Hide all sections
        document.querySelectorAll('.content-section').forEach(s => {
            s.classList.add('d-none');
            s.classList.remove('fade-in');
        });

        // Update navigation
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        document.querySelectorAll(`[data-section="${section}"]`).forEach(l => l.classList.add('active'));

        // Show selected section
        const sectionElement = document.getElementById(`${section}-section`);
        if (sectionElement) {
            sectionElement.classList.remove('d-none');
            setTimeout(() => sectionElement.classList.add('fade-in'), 50);
        }

        // Load section data
        switch (section) {
            case 'dashboard':
                this.loadDashboard();
                break;
            case 'clientes':
                this.setupSortableHeaders(); // Configurar cabeceras ordenables
                this.setupClientesFilters(); // Configurar filtros de clientes
                this.loadClientes();
                break;
            case 'incidencias':
                if (this.currentClienteId) {
                    this.loadIncidencias();
                }
                break;
        }
    }

    /**
     * Show incidencias for specific client
     */
    showIncidencias(clienteId, clienteNombre) {
        this.currentClienteId = clienteId;
        this.currentCliente = { id: clienteId, nombre: clienteNombre };

        // Update UI
        document.getElementById('cliente-name').textContent = clienteNombre;
        document.getElementById('cliente-breadcrumb').textContent = `Incidencias de ${clienteNombre}`;

        // Reset pagination and filters
        this.pagination.incidencias = { page: 1, limit: 20, total: 0, pages: 0 };
        this.filters.incidencias = { realizado: '', cobrado: '', finalizada: '' };

        this.showSection('incidencias');
    }

    /**
     * API call helper
     */
    async apiCall(endpoint, method = 'GET', data = null, showLoading = false) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
            }
        };

        if (this.token) {
            options.headers.Authorization = `Bearer ${this.token}`;
        }

        if (data) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(`${this.apiUrl}/${endpoint}`, options);
            const contentType = response.headers.get('content-type');

            let result;

            const text = await response.text();

            if (contentType && contentType.includes('application/json')) {
                try {
                    result = JSON.parse(text);
                } catch (jsonError) {
                    console.error('JSON Parse Error:', jsonError);
                    console.error('Response text:', text);
                    throw new Error(`Invalid JSON response: ${text.substring(0, 200)}`);
                }
            } else {
                throw new Error(`Server error (not JSON): ${text}`);
            }

            if (!response.ok) {
                throw new Error(result.message || `HTTP ${response.status}: ${response.statusText}`);
            }

            return result;
        } catch (error) {
            console.error(`API Error [${method} ${endpoint}]:`, error);
            throw error.message || error;
        }
    }

    /**
     * Continue in next part...
     */

    /**
     * Show alert message
     */
    showAlert(containerId, type, message) {
        const container = document.getElementById(containerId);
        if (container) {
            container.className = `alert alert-${type}`;
            container.textContent = message;
            container.classList.remove('d-none');

            setTimeout(() => {
                container.classList.add('d-none');
            }, 5000);
        }
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'success', duration = 4000) {
        const toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) return;

        const toastId = 'toast-' + Date.now();
        const iconMap = {
            success: 'bi-check-circle-fill text-success',
            error: 'bi-exclamation-circle-fill text-danger',
            warning: 'bi-exclamation-triangle-fill text-warning',
            info: 'bi-info-circle-fill text-info'
        };

        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center">
                        <i class="bi ${iconMap[type] || iconMap.info} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHTML);

        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: duration });
        toast.show();

        // Remove toast from DOM after it's hidden
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }

    /**
     * Toggle sort for a given field
     */
    toggleSort(field) {
        if (this.filters.clientes.orderBy === field) {
            // Si ya está ordenando por este campo, cambiar dirección
            this.filters.clientes.orderDir = this.filters.clientes.orderDir === 'ASC' ? 'DESC' : 'ASC';
        } else {
            // Si es un campo nuevo, ordenar ascendente
            this.filters.clientes.orderBy = field;
            this.filters.clientes.orderDir = 'ASC';
        }

        // Reset pagination and reload
        this.pagination.clientes.page = 1;
        this.updateSortIcons();
        this.loadClientes();
    }

    /**
     * Update sort icons in table headers
     */
    updateSortIcons() {
        // Reset all icons
        document.querySelectorAll('.sort-icon').forEach(icon => {
            icon.className = 'bi bi-chevron-expand sort-icon';
            icon.parentElement.classList.remove('active');
        });

        // Set active icon
        const activeIcon = document.getElementById(`sort-icon-${this.filters.clientes.orderBy}`);
        if (activeIcon) {
            activeIcon.parentElement.classList.add('active');
            if (this.filters.clientes.orderDir === 'ASC') {
                activeIcon.className = 'bi bi-chevron-up sort-icon asc';
            } else {
                activeIcon.className = 'bi bi-chevron-down sort-icon desc';
            }
        }
    }

    /**
     * Update clientes filters from form controls
     */
    updateClientesFilters() {
        const pageSizeElement = document.getElementById('page-size');

        if (pageSizeElement) {
            this.pagination.clientes.limit = parseInt(pageSizeElement.value);
        }

        // Reset to first page when filters change
        this.pagination.clientes.page = 1;
        this.loadClientes();
    }

    /**
     * Setup sortable headers event listeners
     */
    setupSortableHeaders() {
        // Remove previous listeners to avoid duplicates
        document.querySelectorAll('.sortable-header').forEach(header => {
            const newHeader = header.cloneNode(true);
            header.parentNode.replaceChild(newHeader, header);
        });

        // Add new listeners
        document.querySelectorAll('.sortable-header').forEach(header => {
            header.addEventListener('click', () => {
                const sortField = header.dataset.sort;
                this.toggleSort(sortField);
            });
        });
    }

    /**
     * Setup clientes filters
     */
    setupClientesFilters() {
        // Setup filter by estado de incidencias
        const filterEstadoIncidencias = document.getElementById('filter-estado-incidencias');
        if (filterEstadoIncidencias) {
            // Remove all previous event listeners by cloning
            const newFilter = filterEstadoIncidencias.cloneNode(true);
            filterEstadoIncidencias.parentNode.replaceChild(newFilter, filterEstadoIncidencias);

            // Add new listener
            newFilter.addEventListener('change', (e) => {
                this.filters.clientes.estadoIncidencias = e.target.value;
                this.pagination.clientes.page = 1;
                this.loadClientes();
            });
        }

        // Setup search input
        const searchInput = document.getElementById('search-clientes');
        if (searchInput) {
            const newSearch = searchInput.cloneNode(true);
            searchInput.parentNode.replaceChild(newSearch, searchInput);

            newSearch.addEventListener('input', (e) => {
                clearTimeout(this.timeouts.search);
                this.timeouts.search = setTimeout(() => {
                    this.filters.clientes.search = e.target.value;
                    this.pagination.clientes.page = 1;
                    this.loadClientes();
                }, 300);
            });
        }

        // Setup page size selector
        const pageSizeElement = document.getElementById('page-size');
        if (pageSizeElement) {
            const newPageSize = pageSizeElement.cloneNode(true);
            pageSizeElement.parentNode.replaceChild(newPageSize, pageSizeElement);

            newPageSize.addEventListener('change', () => {
                this.updateClientesFilters();
            });
        }
    }

    /**
     * Load dashboard data and render
     */
    async loadDashboard() {
        try {
            const refreshBtn = document.getElementById('refresh-dashboard');
            if (refreshBtn) {
                refreshBtn.disabled = true;
                refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i><div class="spinner-border spinner-border-sm ms-1" role="status"></div>';
            }

            const stats = await this.apiCall('dashboard/stats.php');
            this.renderDashboard(stats);

        } catch (error) {
            console.error('Error loading dashboard:', error);
            this.showToast('Error al cargar el dashboard: ' + error, 'error');
        } finally {
            const refreshBtn = document.getElementById('refresh-dashboard');
            if (refreshBtn) {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Actualizar';
            }
        }
    }

    /**
     * Render dashboard with stats and charts
     */
    renderDashboard(stats) {
        this.renderStatsCards(stats);
        this.renderCharts(stats);
    }

    /**
     * Render statistics cards
     */
    renderStatsCards(stats) {
        const container = document.getElementById('stats-cards');
        if (!container) return;

        const cards = [
            {
                title: 'Total Clientes',
                value: stats.clientes?.total || 0,
                icon: 'bi-people',
                color: 'primary',
                description: 'Clientes registrados'
            },
            {
                title: 'Total Incidencias',
                value: stats.incidencias?.total || 0,
                icon: 'bi-clipboard-check',
                color: 'info',
                description: 'Incidencias en el sistema'
            },
            {
                title: 'Incidencias Finalizadas',
                value: stats.incidencias?.finalizadas || 0,
                icon: 'bi-check-circle',
                color: 'success',
                description: 'Incidencias completadas'
            },
            {
                title: 'Incidencias Pendientes',
                value: stats.incidencias?.pendientes || 0,
                icon: 'bi-clock',
                color: 'warning',
                description: 'Incidencias por resolver'
            }
        ];

        const cardsHTML = cards.map(card => `
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card stats-card ${card.color} h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bi ${card.icon} text-${card.color}" style="font-size: 2rem;"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="card-title text-muted mb-1">${card.title}</h6>
                                <h2 class="stats-number text-${card.color} mb-1">${this.formatNumber(card.value)}</h2>
                                <small class="text-muted">${card.description}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = cardsHTML;
    }

    /**
     * Render charts
     */
    renderCharts(stats) {
        // Verificar que Chart.js esté disponible
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js no está disponible, guardando datos para retry...');

            // Guardar stats para retry posterior
            this.chartsNeedRetry = true;
            this.lastDashboardStats = stats;

            // Mostrar mensaje de carga en los contenedores de gráficos
            const incidenciasChart = document.getElementById('incidenciasChart');
            const distributionChart = document.getElementById('distributionChart');

            if (incidenciasChart) {
                incidenciasChart.innerHTML = '<div class="text-center p-4"><div class="spinner-border spinner-border-sm text-primary"></div><br><small class="text-muted mt-2">Cargando gráficos...</small></div>';
            }
            if (distributionChart) {
                distributionChart.innerHTML = '<div class="text-center p-4"><div class="spinner-border spinner-border-sm text-primary"></div><br><small class="text-muted mt-2">Cargando gráficos...</small></div>';
            }

            // Reintentar cada 500ms hasta 10 veces
            this.retryChartsLoad(0);
            return;
        }

        this.renderIncidenciasChart(stats);
        this.renderDistributionChart(stats);
    }

    /**
     * Retry loading charts when Chart.js becomes available
     */
    retryChartsLoad(attempt) {
        if (attempt >= 20) { // Máximo 20 intentos (10 segundos)
            console.error('Chart.js no pudo cargarse después de múltiples intentos');
            const incidenciasChart = document.getElementById('incidenciasChart');
            const distributionChart = document.getElementById('distributionChart');

            if (incidenciasChart) {
                incidenciasChart.innerHTML = '<div class="text-center p-4"><i class="bi bi-exclamation-triangle text-warning"></i><br><small>Error cargando gráficos</small></div>';
            }
            if (distributionChart) {
                distributionChart.innerHTML = '<div class="text-center p-4"><i class="bi bi-exclamation-triangle text-warning"></i><br><small>Error cargando gráficos</small></div>';
            }
            return;
        }

        setTimeout(() => {
            if (typeof Chart !== 'undefined' && this.chartsNeedRetry && this.lastDashboardStats) {
                console.log('Chart.js ahora disponible, renderizando gráficos...');
                this.chartsNeedRetry = false;
                this.renderIncidenciasChart(this.lastDashboardStats);
                this.renderDistributionChart(this.lastDashboardStats);
            } else {
                this.retryChartsLoad(attempt + 1);
            }
        }, 500);
    }

    /**
     * Render incidencias bar chart
     */
    renderIncidenciasChart(stats) {
        const ctx = document.getElementById('incidenciasChart');
        if (!ctx) return;

        // Verificar que Chart.js esté disponible
        if (typeof Chart === 'undefined') {
            ctx.innerHTML = '<div class="text-center p-4"><i class="bi bi-exclamation-triangle text-warning"></i><br>Chart.js no disponible</div>';
            return;
        }

        // Destroy existing chart
        if (this.charts.incidencias) {
            this.charts.incidencias.destroy();
        }

        const data = {
            labels: ['Total', 'Realizadas', 'Cobradas', 'Finalizadas', 'Pendientes'],
            datasets: [{
                label: 'Cantidad',
                data: [
                    stats.incidencias?.total || 0,
                    stats.incidencias?.realizadas || 0,
                    stats.incidencias?.cobradas || 0,
                    stats.incidencias?.finalizadas || 0,
                    stats.incidencias?.pendientes || 0
                ],
                backgroundColor: [
                    '#0d6efd',
                    '#198754',
                    '#20c997',
                    '#0dcaf0',
                    '#ffc107'
                ],
                borderColor: [
                    '#0a58ca',
                    '#146c43',
                    '#1aa179',
                    '#087990',
                    '#cc9a00'
                ],
                borderWidth: 1,
                borderRadius: 4
            }]
        };

        this.charts.incidencias = new Chart(ctx, {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    /**
     * Render distribution pie chart
     */
    renderDistributionChart(stats) {
        const ctx = document.getElementById('distributionChart');
        if (!ctx) return;

        // Verificar que Chart.js esté disponible
        if (typeof Chart === 'undefined') {
            ctx.innerHTML = '<div class="text-center p-4"><i class="bi bi-exclamation-triangle text-warning"></i><br>Chart.js no disponible</div>';
            return;
        }

        // Destroy existing chart
        if (this.charts.distribution) {
            this.charts.distribution.destroy();
        }

        const data = {
            labels: ['Finalizadas', 'Pendientes'],
            datasets: [{
                data: [
                    stats.incidencias?.finalizadas || 0,
                    stats.incidencias?.pendientes || 0
                ],
                backgroundColor: ['#198754', '#ffc107'],
                borderColor: ['#146c43', '#cc9a00'],
                borderWidth: 2
            }]
        };

        this.charts.distribution = new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    /**
     * Load clientes data
     */
    async loadClientes() {
        try {
            const params = new URLSearchParams({
                page: this.pagination.clientes.page,
                limit: this.pagination.clientes.limit,
                search: this.filters.clientes.search,
                orderBy: this.filters.clientes.orderBy,
                orderDir: this.filters.clientes.orderDir,
                estadoIncidencias: this.filters.clientes.estadoIncidencias
            });

            const response = await this.apiCall(`clientes/index.php?${params}`);
            this.renderClientes(response);
            this.updateSortIcons(); // Actualizar iconos de ordenación

        } catch (error) {
            console.error('Error loading clientes:', error);
            this.showToast('Error al cargar clientes: ' + error, 'error');
            this.renderClientesError(error);
        }
    }

    /**
     * Render clientes table
     */
    renderClientes(data) {
        const tbody = document.getElementById('clientes-table');
        if (!tbody) return;

        this.pagination.clientes.total = data.total || 0;
        this.pagination.clientes.pages = data.pages || 0;

        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mb-0 mt-2">No se encontraron clientes</p>
                        <button class="btn btn-outline-primary btn-sm mt-2" onclick="clearClientesFilters()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Limpiar filtros
                        </button>
                    </td>
                </tr>
            `;
        } else {
            const rows = data.data.map(cliente => `
                <tr>
                    <td>
                        <div class="fw-semibold">${this.escapeHtml(cliente.nombre)}</div>
                    </td>
                    <td>${this.escapeHtml(cliente.razon_social || '')}</td>
                    <td>
                        <code class="text-muted">${this.escapeHtml(cliente.codigo || '')}</code>
                    </td>
                    <td>
                        ${cliente.tlf ? `<a href="tel:${cliente.tlf}" class="text-decoration-none">${cliente.tlf}</a>` : ''}
                    </td>
                    <td>
                        <span class="text-truncate d-block" style="max-width: 200px;" title="${this.escapeHtml(cliente.observaciones || '')}">
                            ${this.escapeHtml(cliente.observaciones || '')}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary btn-action" 
                                    onclick="showIncidencias(${cliente.id}, '${this.escapeHtml(cliente.nombre)}')"
                                    title="Ver incidencias">
                                <i class="bi bi-clipboard-check"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-action"
                                    onclick="openClienteModal(${JSON.stringify(cliente).replace(/"/g, '&quot;')})"
                                    title="Editar cliente">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-action"
                                    onclick="confirmDeleteCliente(${cliente.id}, '${this.escapeHtml(cliente.nombre)}')"
                                    title="Eliminar cliente">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');

            tbody.innerHTML = rows;
        }

        this.renderClientesPagination();
    }

    /**
     * Render clientes error state
     */
    renderClientesError(error) {
        const tbody = document.getElementById('clientes-table');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-danger">
                        <i class="bi bi-exclamation-circle" style="font-size: 3rem;"></i>
                        <p class="mb-0 mt-2">Error al cargar clientes</p>
                        <small class="text-muted">${error}</small>
                        <br>
                        <button class="btn btn-outline-primary btn-sm mt-2" onclick="vmcApp.loadClientes()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Reintentar
                        </button>
                    </td>
                </tr>
            `;
        }
    }

    /**
     * Render clientes pagination
     */
    renderClientesPagination() {
        const container = document.getElementById('pagination-clientes');
        if (!container) return;

        const { page, pages, total } = this.pagination.clientes;

        if (pages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';

        // Previous button
        html += `
            <li class="page-item ${page <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="vmcApp.goToClientesPage(${page - 1})" aria-label="Anterior">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
        `;

        // Page numbers
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(pages, page + 2);

        if (startPage > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="vmcApp.goToClientesPage(1)">1</a></li>`;
            if (startPage > 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `
                <li class="page-item ${i === page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="vmcApp.goToClientesPage(${i})">${i}</a>
                </li>
            `;
        }

        if (endPage < pages) {
            if (endPage < pages - 1) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            html += `<li class="page-item"><a class="page-link" href="#" onclick="vmcApp.goToClientesPage(${pages})">${pages}</a></li>`;
        }

        // Next button
        html += `
            <li class="page-item ${page >= pages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="vmcApp.goToClientesPage(${page + 1})" aria-label="Siguiente">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        `;

        container.innerHTML = html;

        // Show pagination info
        const info = `Mostrando ${((page - 1) * this.pagination.clientes.limit) + 1} a ${Math.min(page * this.pagination.clientes.limit, total)} de ${total} clientes`;
        container.insertAdjacentHTML('afterend', `<div class="text-center text-muted mt-2">${info}</div>`);
    }

    /**
     * Go to specific page for clientes
     */
    goToClientesPage(newPage) {
        if (newPage >= 1 && newPage <= this.pagination.clientes.pages) {
            this.pagination.clientes.page = newPage;
            this.loadClientes();
        }
    }

    /**
     * Update clientes filters
     */
    updateClientesFilters() {
        const sortField = document.getElementById('sort-field')?.value || 'nombre';
        const sortDirection = document.getElementById('sort-direction')?.value || 'ASC';
        const pageSize = parseInt(document.getElementById('page-size')?.value) || 20;

        this.filters.clientes.orderBy = sortField;
        this.filters.clientes.orderDir = sortDirection;
        this.pagination.clientes.limit = pageSize;
        this.pagination.clientes.page = 1;

        this.loadClientes();
    }

    /**
     * Clear clientes filters
     */
    clearClientesFilters() {
        document.getElementById('search-clientes').value = '';
        document.getElementById('sort-field').value = 'nombre';
        document.getElementById('sort-direction').value = 'ASC';
        document.getElementById('page-size').value = '20';
        const filterEstado = document.getElementById('filter-estado-incidencias');
        if (filterEstado) {
            filterEstado.value = '';
        }

        this.filters.clientes = { search: '', orderBy: 'nombre', orderDir: 'ASC', estadoIncidencias: '' };
        this.pagination.clientes = { page: 1, limit: 20, total: 0, pages: 0 };

        this.loadClientes();
    }

    /**
     * Open cliente modal
     */
    openClienteModal(cliente = null) {
        const modal = new bootstrap.Modal(document.getElementById('clienteModal'));
        const form = document.getElementById('cliente-form');

        // Reset form
        form.reset();
        form.classList.remove('was-validated');

        if (cliente) {
            // Edit mode
            document.getElementById('cliente-modal-title').textContent = 'Editar Cliente';
            document.getElementById('cliente-submit-text').textContent = 'Actualizar';
            document.getElementById('cliente-id').value = cliente.id;
            document.getElementById('cliente-nombre').value = cliente.nombre || '';
            document.getElementById('cliente-razon').value = cliente.razon_social || '';
            document.getElementById('cliente-codigo').value = cliente.codigo || '';
            document.getElementById('cliente-telefono').value = cliente.tlf || '';
            document.getElementById('cliente-observaciones').value = cliente.observaciones || '';
        } else {
            // Create mode
            document.getElementById('cliente-modal-title').textContent = 'Nuevo Cliente';
            document.getElementById('cliente-submit-text').textContent = 'Crear Cliente';
            document.getElementById('cliente-id').value = '';
        }

        // Setup form submission
        form.onsubmit = (e) => this.handleClienteSubmit(e);

        modal.show();
    }

    /**
     * Handle cliente form submission
     */
    async handleClienteSubmit(e) {
        e.preventDefault();

        const form = e.target;

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-1" role="status"></div>Guardando...';

            const formData = new FormData(form);
            const data = Object.fromEntries(formData);

            const clienteId = document.getElementById('cliente-id').value;

            if (clienteId) {
                // Update
                data.id = clienteId;
                await this.apiCall('clientes/index.php', 'PUT', data);
                this.showToast('Cliente actualizado exitosamente', 'success');
            } else {
                // Create
                await this.apiCall('clientes/index.php', 'POST', data);
                this.showToast('Cliente creado exitosamente', 'success');
            }

            bootstrap.Modal.getInstance(document.getElementById('clienteModal')).hide();
            this.loadClientes();

        } catch (error) {
            console.error('Error saving cliente:', error);
            this.showToast('Error al guardar cliente: ' + error, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    /**
     * Format number for display
     */
    formatNumber(number) {
        return new Intl.NumberFormat('es-ES').format(number || 0);
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return (text || '').replace(/[&<>"']/g, (m) => map[m]);
    }

    /**
     * Get current date in DD/MM/YYYY format
     */
    getCurrentDate() {
        const date = new Date();
        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    }

    /**
     * Load incidencias data
     */
    async loadIncidencias() {
        if (!this.currentClienteId) {
            console.error('loadIncidencias: No currentClienteId set');
            return;
        }

        try {
            const params = new URLSearchParams({
                page: this.pagination.incidencias.page,
                limit: this.pagination.incidencias.limit,
                idcliente: this.currentClienteId
            });

            // Add filters
            Object.entries(this.filters.incidencias).forEach(([key, value]) => {
                if (value !== '') {
                    params.append(key, value);
                }
            });
            const response = await this.apiCall(`incidencias/index.php?${params}`);

            this.renderIncidencias(response);

        } catch (error) {
            console.error('Error loading incidencias:', error);
            this.showToast('Error al cargar incidencias: ' + error, 'error');
            this.renderIncidenciasError(error);
        }
    }

    /**
     * Render incidencias table
     */
    renderIncidencias(data) {
        const tbody = document.getElementById('incidencias-table');
        if (!tbody) return;

        this.pagination.incidencias.total = data.total || 0;
        this.pagination.incidencias.pages = data.pages || 0;

        // Guardar los datos de incidencias para poder acceder a ellos al editar
        this.currentIncidencias = data.data || [];

        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <i class="bi bi-clipboard-x text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mb-0 mt-2">No se encontraron incidencias</p>
                        <button class="btn btn-outline-primary btn-sm mt-2" onclick="clearIncidenciasFilters()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Limpiar filtros
                        </button>
                    </td>
                </tr>
            `;
        } else {
            const rows = data.data.map(incidencia => `
                <tr>
                    <td style="vertical-align: top;">
                        <small class="text-muted">${this.escapeHtml(incidencia.fecha || '')}</small>
                    </td>
                    <td style="vertical-align: top;">
                        <div style="max-height: 150px; overflow-y: auto; word-wrap: break-word; padding-right: 10px;">
                            ${this.escapeHtml(incidencia.incidencia || '')}
                        </div>
                    </td>
                    <td style="vertical-align: top;">
                        <div style="max-height: 150px; overflow-y: auto; word-wrap: break-word; padding-right: 10px;">
                            ${incidencia.respuesta ? this.escapeHtml(incidencia.respuesta) : '<em class="text-muted">Sin respuesta</em>'}
                        </div>
                    </td>
                    <td class="text-center" style="vertical-align: top;">
                        ${this.getStatusBadge(incidencia.realizado)}
                    </td>
                    <td class="text-center" style="vertical-align: top;">
                        ${this.getStatusBadge(incidencia.cobrado)}
                    </td>
                    <td class="text-center" style="vertical-align: top;">
                        ${this.getStatusBadge(incidencia.finalizada)}
                    </td>
                    <td class="text-center" style="vertical-align: top;">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-action"
                                    onclick="editIncidencia(${incidencia.id})"
                                    title="Editar incidencia">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-action"
                                    onclick="confirmDeleteIncidencia(${incidencia.id})"
                                    title="Eliminar incidencia">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');

            tbody.innerHTML = rows;
        }

        this.renderIncidenciasPagination();
    }


    /**
     * Render incidencias error state
     */
    renderIncidenciasError(error) {
        const tbody = document.getElementById('incidencias-table');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-danger">
                        <i class="bi bi-exclamation-circle" style="font-size: 3rem;"></i>
                        <p class="mb-0 mt-2">Error al cargar incidencias</p>
                        <small class="text-muted">${error}</small>
                        <br>
                        <button class="btn btn-outline-primary btn-sm mt-2" onclick="vmcApp.loadIncidencias()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Reintentar
                        </button>
                    </td>
                </tr>
            `;
        }
    }

    /**
     * Render incidencias pagination
     */
    renderIncidenciasPagination() {
        const container = document.getElementById('pagination-incidencias');
        if (!container) return;

        const { page, pages, total } = this.pagination.incidencias;

        if (pages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';

        // Previous button
        html += `
            <li class="page-item ${page <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="vmcApp.goToIncidenciasPage(${page - 1})" aria-label="Anterior">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
        `;

        // Page numbers
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(pages, page + 2);

        if (startPage > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="vmcApp.goToIncidenciasPage(1)">1</a></li>`;
            if (startPage > 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `
                <li class="page-item ${i === page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="vmcApp.goToIncidenciasPage(${i})">${i}</a>
                </li>
            `;
        }

        if (endPage < pages) {
            if (endPage < pages - 1) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            html += `<li class="page-item"><a class="page-link" href="#" onclick="vmcApp.goToIncidenciasPage(${pages})">${pages}</a></li>`;
        }

        // Next button
        html += `
            <li class="page-item ${page >= pages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="vmcApp.goToIncidenciasPage(${page + 1})" aria-label="Siguiente">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        `;

        container.innerHTML = html;
    }

    /**
     * Go to specific page for incidencias
     */
    goToIncidenciasPage(newPage) {
        if (newPage >= 1 && newPage <= this.pagination.incidencias.pages) {
            this.pagination.incidencias.page = newPage;
            this.loadIncidencias();
        }
    }

    /**
     * Update incidencias filters
     */
    updateIncidenciasFilters() {
        const realizado = document.getElementById('filter-realizado')?.value || '';
        const cobrado = document.getElementById('filter-cobrado')?.value || '';
        const finalizada = document.getElementById('filter-finalizada')?.value || '';
        const pageSize = parseInt(document.getElementById('incidencias-page-size')?.value) || 20;

        this.filters.incidencias = { realizado, cobrado, finalizada };
        this.pagination.incidencias.limit = pageSize;
        this.pagination.incidencias.page = 1;

        this.loadIncidencias();
    }

    /**
     * Clear incidencias filters
     */
    clearIncidenciasFilters() {
        document.getElementById('filter-realizado').value = '';
        document.getElementById('filter-cobrado').value = '';
        document.getElementById('filter-finalizada').value = '';
        document.getElementById('incidencias-page-size').value = '20';

        this.filters.incidencias = { realizado: '', cobrado: '', finalizada: '' };
        this.pagination.incidencias = { page: 1, limit: 20, total: 0, pages: 0 };

        this.loadIncidencias();
    }

    /**
     * Get status badge for incidencia states
     */
    getStatusBadge(value) {
        if (value === 1 || value === '1') {
            return '<span class="badge bg-success">Sí</span>';
        } else if (value === 0 || value === '0') {
            return '<span class="badge bg-secondary">No</span>';
        }
        return '<span class="badge bg-light text-muted">N/A</span>';
    }

    /**
     * Open incidencia modal
     */
    openIncidenciaModal(incidencia = null) {
        const modal = new bootstrap.Modal(document.getElementById('incidenciaModal'));
        const form = document.getElementById('incidencia-form');

        // Reset form
        form.reset();
        form.classList.remove('was-validated');

        if (incidencia) {
            // Edit mode
            document.getElementById('incidencia-modal-title').textContent = 'Editar Incidencia';
            document.getElementById('incidencia-submit-text').textContent = 'Actualizar';
            document.getElementById('incidencia-id').value = incidencia.id;
            document.getElementById('incidencia-cliente-id').value = incidencia.idcliente || this.currentClienteId;
            document.getElementById('incidencia-fecha').value = incidencia.fecha || this.getCurrentDate();
            document.getElementById('incidencia-descripcion').value = incidencia.incidencia || '';
            document.getElementById('incidencia-respuesta').value = incidencia.respuesta || '';
            document.getElementById('incidencia-realizado').value = incidencia.realizado || '0';
            document.getElementById('incidencia-cobrado').value = incidencia.cobrado || '0';
            document.getElementById('incidencia-finalizada').value = incidencia.finalizada || '0';
        } else {
            // Create mode
            document.getElementById('incidencia-modal-title').textContent = 'Nueva Incidencia';
            document.getElementById('incidencia-submit-text').textContent = 'Crear Incidencia';
            document.getElementById('incidencia-id').value = '';
            document.getElementById('incidencia-cliente-id').value = this.currentClienteId;
            document.getElementById('incidencia-fecha').value = this.getCurrentDate();
            document.getElementById('incidencia-realizado').value = '0';
            document.getElementById('incidencia-cobrado').value = '0';
            document.getElementById('incidencia-finalizada').value = '0';
        }

        // Setup form submission
        form.onsubmit = (e) => this.handleIncidenciaSubmit(e);

        modal.show();
    }

    /**
     * Handle incidencia form submission
     */
    async handleIncidenciaSubmit(e) {
        e.preventDefault();

        const form = e.target;

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-1" role="status"></div>Guardando...';

            const formData = new FormData(form);
            const data = Object.fromEntries(formData);

            // Ensure client ID is set
            data.idcliente = document.getElementById('incidencia-cliente-id').value;

            const incidenciaId = document.getElementById('incidencia-id').value;

            if (incidenciaId) {
                // Update
                data.id = incidenciaId;
                await this.apiCall('incidencias/index.php', 'PUT', data);
                this.showToast('Incidencia actualizada exitosamente', 'success');
            } else {
                // Create
                await this.apiCall('incidencias/index.php', 'POST', data);
                this.showToast('Incidencia creada exitosamente', 'success');
            }

            bootstrap.Modal.getInstance(document.getElementById('incidenciaModal')).hide();
            this.loadIncidencias();

        } catch (error) {
            console.error('Error saving incidencia:', error);
            this.showToast('Error al guardar incidencia: ' + error, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    /**
     * Show confirmation dialog
     */
    showConfirmDialog(message, onConfirm) {
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        const messageElement = document.getElementById('confirm-message');
        const confirmBtn = document.getElementById('confirm-action-btn');

        messageElement.innerHTML = message;

        // Remove previous event listeners
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        // Add new event listener
        newConfirmBtn.addEventListener('click', () => {
            modal.hide();
            onConfirm();
        });

        modal.show();
    }

    /**
     * Confirm delete cliente
     */
    confirmDeleteCliente(clienteId, clienteNombre) {
        const message = `
            <p>¿Estás seguro de que quieres eliminar el cliente <strong>"${this.escapeHtml(clienteNombre)}"</strong>?</p>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Esta acción no se puede deshacer y eliminará también todas sus incidencias asociadas.
            </div>
        `;

        this.showConfirmDialog(message, async () => {
            try {
                await this.apiCall('clientes/index.php', 'DELETE', { id: clienteId });
                this.showToast('Cliente eliminado exitosamente', 'success');
                this.loadClientes();
            } catch (error) {
                console.error('Error deleting cliente:', error);
                this.showToast('Error al eliminar cliente: ' + error, 'error');
            }
        });
    }

    /**
     * Confirm delete incidencia
     */
    confirmDeleteIncidencia(incidenciaId) {
        const message = `
            <p>¿Estás seguro de que quieres eliminar esta incidencia?</p>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Esta acción no se puede deshacer.
            </div>
        `;

        this.showConfirmDialog(message, async () => {
            try {
                await this.apiCall('incidencias/index.php', 'DELETE', { id: incidenciaId });
                this.showToast('Incidencia eliminada exitosamente', 'success');
                this.loadIncidencias();
            } catch (error) {
                console.error('Error deleting incidencia:', error);
                this.showToast('Error al eliminar incidencia: ' + error, 'error');
            }
        });
    }

    /**
     * Clear clientes filters
     */
    clearClientesFilters() {
        // Reset search
        const searchInput = document.getElementById('search-clientes');
        if (searchInput) {
            searchInput.value = '';
        }

        // Reset page size
        const pageSizeElement = document.getElementById('page-size');
        if (pageSizeElement) {
            pageSizeElement.value = '20';
        }

        // Reset estado incidencias filter
        const filterEstado = document.getElementById('filter-estado-incidencias');
        if (filterEstado) {
            filterEstado.value = '';
        }

        // Reset filters
        this.filters.clientes = {
            search: '',
            orderBy: 'nombre',
            orderDir: 'ASC',
            estadoIncidencias: ''
        };

        // Reset pagination
        this.pagination.clientes = { page: 1, limit: 20, total: 0, pages: 0 };

        // Update UI and reload
        this.updateSortIcons();
        this.loadClientes();
    }

    /**
     * Update incidencias filters
     */
    updateIncidenciasFilters() {
        // Get filter values
        const realizadoFilter = document.getElementById('filter-realizado');
        const cobradoFilter = document.getElementById('filter-cobrado');
        const finalizadaFilter = document.getElementById('filter-finalizada');
        const pageSizeElement = document.getElementById('incidencias-page-size');

        // Update filters
        if (realizadoFilter) {
            this.filters.incidencias.realizado = realizadoFilter.value;
        }
        if (cobradoFilter) {
            this.filters.incidencias.cobrado = cobradoFilter.value;
        }
        if (finalizadaFilter) {
            this.filters.incidencias.finalizada = finalizadaFilter.value;
        }
        if (pageSizeElement) {
            this.pagination.incidencias.limit = parseInt(pageSizeElement.value);
        }

        // Reset to first page and reload
        this.pagination.incidencias.page = 1;
        this.loadIncidencias();
    }

    /**
     * Clear incidencias filters
     */
    clearIncidenciasFilters() {
        // Reset filter selects
        ['filter-realizado', 'filter-cobrado', 'filter-finalizada'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.value = '';
            }
        });

        // Reset page size
        const pageSizeElement = document.getElementById('incidencias-page-size');
        if (pageSizeElement) {
            pageSizeElement.value = '20';
        }

        // Reset filters
        this.filters.incidencias = {
            realizado: '',
            cobrado: '',
            finalizada: ''
        };

        // Reset pagination
        this.pagination.incidencias = { page: 1, limit: 20, total: 0, pages: 0 };

        // Reload
        this.loadIncidencias();
    }
}

// Global instance
let vmcApp;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    vmcApp = new VMCApp();
});

// Global functions for onclick handlers
function showSection(section) {
    vmcApp?.showSection(section);
}

function logout() {
    vmcApp?.logout();
}

function loadDashboard() {
    vmcApp?.loadDashboard();
}

function openClienteModal(cliente = null) {
    vmcApp?.openClienteModal(cliente);
}

function openIncidenciaModal(incidencia = null) {
    vmcApp?.openIncidenciaModal(incidencia);
}

function editIncidencia(incidenciaId) {
    if (vmcApp && vmcApp.currentIncidencias) {
        const incidencia = vmcApp.currentIncidencias.find(inc => inc.id == incidenciaId);
        if (incidencia) {
            vmcApp.openIncidenciaModal(incidencia);
        } else {
            console.error('Incidencia no encontrada:', incidenciaId);
        }
    }
}

function showIncidencias(clienteId, clienteNombre) {
    vmcApp?.showIncidencias(clienteId, clienteNombre);
}

function clearClientesFilters() {
    vmcApp?.clearClientesFilters();
}

function clearIncidenciasFilters() {
    vmcApp?.clearIncidenciasFilters();
}

// Handle estado incidencias filter change
function handleEstadoIncidenciasChange(value) {
    if (vmcApp) {
        vmcApp.filters.clientes.estadoIncidencias = value;
        vmcApp.pagination.clientes.page = 1;
        vmcApp.loadClientes();
    }
}

// Additional global functions for the onclick handlers
function confirmDeleteCliente(clienteId, clienteNombre) {
    vmcApp?.confirmDeleteCliente(clienteId, clienteNombre);
}

function confirmDeleteIncidencia(incidenciaId) {
    vmcApp?.confirmDeleteIncidencia(incidenciaId);
}

// Utility functions for debugging and development
window.vmcDebug = {
    app: () => vmcApp,
    token: () => vmcApp?.token,
    user: () => vmcApp?.currentUser,
    pagination: () => vmcApp?.pagination,
    filters: () => vmcApp?.filters
};

