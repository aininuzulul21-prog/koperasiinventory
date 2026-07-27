/**
 * Sistem Inventori Barang Koperasi
 * Main JavaScript
 */

// ==================== SIDEBAR TOGGLE ====================
$(document).ready(function() {
    // Toggle sidebar on button click
    $('#sidebarToggleBtn').on('click', function() {
        if ($(window).width() <= 991.98) {
            $('#sidebar').toggleClass('show');
            $('#sidebarOverlay').toggleClass('show');
        } else {
            $('#sidebar').toggleClass('collapsed');
            $('#mainContent').toggleClass('expanded');
        }
    });
    
    // Close sidebar on overlay click
    $('#sidebarOverlay').on('click', function() {
        $('#sidebar').removeClass('show');
        $('#sidebarOverlay').removeClass('show');
    });
    
    // Close sidebar on close button
    $('#sidebarCloseBtn').on('click', function() {
        $('#sidebar').removeClass('show');
        $('#sidebarOverlay').removeClass('show');
    });
    
    // Auto close sidebar on route change (mobile)
    $('.nav-link').on('click', function() {
        if ($(window).width() <= 991.98) {
            $('#sidebar').removeClass('show');
            $('#sidebarOverlay').removeClass('show');
        }
    });
});

// ==================== TOAST NOTIFICATION ====================
function showToast(message, type = 'success', title = '') {
    const iconMap = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };
    
    Swal.fire({
        icon: type,
        title: title || (type === 'success' ? 'Berhasil' : type === 'error' ? 'Gagal' : 'Informasi'),
        text: message,
        timer: 3000,
        timerProgressBar: true,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        customClass: {
            popup: 'animate-slideUp'
        }
    });
}

// ==================== CONFIRM DIALOG ====================
function confirmDialog(message, callback) {
    Swal.fire({
        title: 'Konfirmasi',
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Ya, lanjutkan',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed && callback) {
            callback();
        }
    });
}

// ==================== LOADING ====================
function showLoading() {
    if ($('#loadingOverlay').length === 0) {
        $('body').append('<div id="loadingOverlay" class="loading-overlay show"><div class="spinner-border text-primary" style="width:3rem;height:3rem" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    } else {
        $('#loadingOverlay').addClass('show');
    }
}

function hideLoading() {
    $('#loadingOverlay').removeClass('show');
}

// ==================== FORMAT RUPIAH ====================
function formatRupiah(angka) {
    if (!angka && angka !== 0) return 'Rp 0';
    return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function parseRupiah(str) {
    if (!str) return 0;
    return parseInt(str.replace(/[^0-9]/g, '')) || 0;
}

// ==================== FORMAT DATE ====================
function formatDate(dateStr) {
    if (!dateStr) return '-';
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const date = new Date(dateStr);
    return date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
}

function formatDateTime(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return formatDate(dateStr) + ' ' + date.getHours().toString().padStart(2, '0') + ':' + date.getMinutes().toString().padStart(2, '0');
}

function formatDateInput(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toISOString().split('T')[0];
}

// ==================== DARK MODE ====================
function toggleDarkMode() {
    $('body').toggleClass('dark-mode');
    const isDark = $('body').hasClass('dark-mode');
    localStorage.setItem('darkMode', isDark ? 'true' : 'false');
    
    const icon = $('#darkModeToggle i');
    if (isDark) {
        icon.removeClass('fa-moon').addClass('fa-sun');
    } else {
        icon.removeClass('fa-sun').addClass('fa-moon');
    }
}

// Load dark mode preference
$(document).ready(function() {
    if (localStorage.getItem('darkMode') === 'true') {
        $('body').addClass('dark-mode');
        $('#darkModeToggle i').removeClass('fa-moon').addClass('fa-sun');
    }
});

// ==================== FULLSCREEN ====================
function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
    } else {
        document.exitFullscreen();
    }
}

// ==================== AJAX REQUEST ====================
function apiRequest(url, method = 'GET', data = null, callback = null) {
    const options = {
        url: url,
        method: method,
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Content-Type': 'application/json'
        },
        beforeSend: function() {
            showLoading();
        },
        complete: function() {
            hideLoading();
        },
        success: function(response) {
            if (callback) callback(response);
        },
        error: function(xhr, status, error) {
            let message = 'Terjadi kesalahan';
            try {
                const resp = JSON.parse(xhr.responseText);
                message = resp.message || message;
            } catch(e) {}
            showToast(message, 'error');
        }
    };
    
    if (data) {
        options.data = JSON.stringify(data);
    }
    
    $.ajax(options);
}

// ==================== NOTIFICATIONS ====================
function loadNotifikasi() {
    $.ajax({
        url: BASE_URL + '/api/get_notifikasi.php',
        method: 'GET',
        success: function(response) {
            if (response.success && response.data) {
                renderNotifikasi(response.data, response.unread);
            }
        }
    });
}

function renderNotifikasi(notifs, unread) {
    const list = $('#notifList');
    if (!notifs || notifs.length === 0) {
        list.html('<div class="text-center py-4 text-muted"><i class="fas fa-bell-slash fa-2x mb-2"></i><p class="mb-0">Tidak ada notifikasi</p></div>');
        return;
    }
    
    let html = '';
    notifs.slice(0, 5).forEach(function(n) {
        const iconMap = {
            'info': 'fa-info-circle text-primary',
            'warning': 'fa-exclamation-triangle text-warning',
            'danger': 'fa-times-circle text-danger',
            'success': 'fa-check-circle text-success'
        };
        
        html += `
            <a href="${n.url || '#'}" class="dropdown-item ${n.is_read ? '' : 'fw-bold'}" onclick="markNotifRead(${n.id})">
                <div class="d-flex gap-3 align-items-start">
                    <i class="fas ${iconMap[n.tipe] || iconMap.info} mt-1"></i>
                    <div class="flex-grow-1">
                        <p class="mb-0" style="font-size:0.85rem">${n.judul}</p>
                        <small class="text-muted">${formatDateTime(n.created_at)}</small>
                    </div>
                </div>
            </a>
            <div class="dropdown-divider"></div>
        `;
    });
    
    list.html(html);
}

function markNotifRead(id) {
    $.ajax({
        url: BASE_URL + '/api/mark_notif_read.php',
        method: 'POST',
        data: JSON.stringify({ id: id }),
        contentType: 'application/json',
        success: function() {
            loadNotifikasi();
        }
    });
}

function markAllNotifRead() {
    $.ajax({
        url: BASE_URL + '/api/mark_all_notif_read.php',
        method: 'POST',
        success: function() {
            loadNotifikasi();
            showToast('Semua notifikasi telah dibaca', 'success');
        }
    });
}

// ==================== DATATABLE DEFAULT ====================
$.extend($.fn.dataTable.defaults, {
    language: {
        processing: 'Memproses...',
        search: 'Cari:',
        lengthMenu: 'Tampilkan _MENU_ data',
        info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
        infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
        infoFiltered: '(disaring dari _MAX_ total data)',
        infoPostFix: '',
        loadingRecords: 'Memuat...',
        zeroRecords: 'Tidak ada data yang cocok',
        emptyTable: 'Tidak ada data',
        paginate: {
            first: 'Pertama',
            previous: 'Sebelumnya',
            next: 'Berikutnya',
            last: 'Terakhir'
        },
        aria: {
            sortAscending: ': aktifkan untuk mengurutkan kolom naik',
            sortDescending: ': aktifkan untuk mengurutkan kolom turun'
        }
    },
    responsive: true,
    pageLength: 25,
    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']]
});

// ==================== SELECT2 INIT ====================
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
});

// ==================== TOOLTIP ====================
$(document).ready(function() {
    $('[data-bs-toggle="tooltip"]').tooltip();
});

