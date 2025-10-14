document.addEventListener('DOMContentLoaded', () => {
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const autoHide = setTimeout(() => hideAlert(alert), 5000);
        const closeBtn = alert.querySelector('.alert-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                clearTimeout(autoHide);
                hideAlert(alert);
            });
        }
    });

    function hideAlert(alert) {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-15px)';
        setTimeout(() => alert.remove(), 300);
    }

    // Toggle done PTC notes
    document.querySelectorAll('.btn-view-notes').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.scheduleId;
            const popup = document.getElementById(`notes-${id}`);
            if (popup) popup.style.display = popup.style.display === 'none' ? 'table-row' : 'none';
        });
    });

    document.querySelectorAll('.close-popup').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.scheduleId;
            const popup = document.getElementById(`notes-${id}`);
            if (popup) popup.style.display = 'none';
        });
    });
});
