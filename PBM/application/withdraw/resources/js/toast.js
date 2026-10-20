/**
 * Toast notifications - ใช้ได้ทั้งระบบ
 */
const defaultOptions = {
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    width: '22rem',
    padding: '1rem 1.25rem',
    background: '#fff',
    color: '#334155',
    showClass: {
        popup: 'swal2-show',
        backdrop: 'swal2-backdrop-show',
    },
    hideClass: {
        popup: 'swal2-hide',
        backdrop: 'swal2-backdrop-hide',
    },
};

function showToast(icon, message, options = {}) {
    if (typeof Swal === 'undefined') {
        console.warn('SweetAlert2 not loaded. Toast skipped:', message);
        return;
    }
    Swal.fire({
        ...defaultOptions,
        icon,
        title: message,
        ...options,
    });
}

export const toast = {
    success: (message, options = {}) => showToast('success', message, options),
    error: (message, options = {}) => showToast('error', message, options),
    warning: (message, options = {}) => showToast('warning', message, options),
    info: (message, options = {}) => showToast('info', message, options),
};

window.toast = toast;
