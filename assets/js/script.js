/**
 * WaterCheck / HydroSan - Main JavaScript Library
 * Mengelola interaksi UI, jam realtime, validasi, dan konfirmasi aksi
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Live Realtime Clock
    const clockElement = document.getElementById('liveClock');
    if (clockElement) {
        function updateClock() {
            const now = new Date();
            const options = {
                timeZone: 'Asia/Jakarta',
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            };
            const formatter = new Intl.DateTimeFormat('id-ID', options);
            clockElement.innerHTML = `<i class="bi bi-clock me-1"></i> ${formatter.format(now)} WIB`;
        }
        updateClock();
        setInterval(updateClock, 1000);
    }

    // 2. Responsive Sidebar Toggle for Mobile & Tablet
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const appSidebar = document.querySelector('.app-sidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    if (sidebarToggleBtn && appSidebar) {
        sidebarToggleBtn.addEventListener('click', function () {
            appSidebar.classList.toggle('show');
            if (sidebarBackdrop) {
                sidebarBackdrop.classList.toggle('show');
            }
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', function () {
            if (appSidebar) appSidebar.classList.remove('show');
            sidebarBackdrop.classList.remove('show');
        });
    }

    // 3. Konfirmasi Hapus Data Universal
    const confirmDeleteButtons = document.querySelectorAll('[data-confirm]');
    confirmDeleteButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // 4. Inisialisasi Tooltip Bootstrap jika tersedia
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // 5. Auto dismiss alert setelah 6 detik
    const autoDismissAlerts = document.querySelectorAll('.alert-dismissible');
    autoDismissAlerts.forEach(alert => {
        setTimeout(() => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } else {
                alert.style.display = 'none';
            }
        }, 6000);
    });
});
