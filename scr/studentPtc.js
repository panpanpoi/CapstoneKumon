document.addEventListener('DOMContentLoaded', () => {
    
    // --- 1. Custom Modal Logic (Replaces alert/confirm) ---
    
    /**
     * Shows a custom modal.
     * @param {string} message The message to display.
     * @param {boolean} isConfirm If true, shows "Yes" and "No" buttons. If false, shows "OK".
     * @returns {Promise<boolean>} Resolves true if "Yes" or "OK" is clicked, false if "No" is clicked.
     */
    function showModal(message, isConfirm = false) {
        // Remove any existing modal
        const existingModal = document.getElementById('customModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Create modal elements
        const overlay = document.createElement('div');
        overlay.id = 'customModal';
        overlay.style = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); display: flex; align-items: center;
            justify-content: center; z-index: 1000;
        `;

        const modalBox = document.createElement('div');
        modalBox.style = `
            background: white; padding: 25px; border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2); text-align: center;
            max-width: 400px; width: 90%;
        `;

        const messageP = document.createElement('p');
        messageP.textContent = message;
        messageP.style = 'margin: 0 0 20px; font-size: 1.1em; line-height: 1.5;';
        
        const buttonWrapper = document.createElement('div');
        buttonWrapper.style = 'display: flex; justify-content: center; gap: 10px;';

        const confirmBtn = document.createElement('button');
        confirmBtn.textContent = isConfirm ? 'Yes, Cancel' : 'OK';
        confirmBtn.style = 'padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; background: #d9534f; color: white;';

        const cancelBtn = document.createElement('button');
        cancelBtn.textContent = 'No';
        cancelBtn.style = 'padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; background: #f0f0f0;';

        // Append elements
        modalBox.appendChild(messageP);
        buttonWrapper.appendChild(confirmBtn);
        if (isConfirm) {
            buttonWrapper.appendChild(cancelBtn);
        }
        modalBox.appendChild(buttonWrapper);
        overlay.appendChild(modalBox);
        document.body.appendChild(overlay);

        // Return a promise that resolves based on button clicks
        return new Promise((resolve) => {
            confirmBtn.onclick = () => {
                document.body.removeChild(overlay);
                resolve(true); // Resolves true for OK or Yes
            };
            cancelBtn.onclick = () => {
                document.body.removeChild(overlay);
                resolve(false); // Resolves false for No
            };
            // Don't close confirm modal on overlay click
            if(!isConfirm) {
                 overlay.onclick = () => {
                    document.body.removeChild(overlay);
                    resolve(false);
                };
            }
        });
    }

    // --- 2. Your Existing Code (Alerts & Notes) ---
    
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

    // --- 3. New Cancel Booking Logic ---
    
    // Use event delegation on the document
    document.addEventListener('click', async function(event) {
        
        // Check if a cancel button was clicked
        if (event.target.classList.contains('cancel-booking-btn')) {
            
            const button = event.target;
            const bookingId = button.dataset.bookingId;
            // Assumes the card is the button's closest table row
            const card = button.closest('tr'); 

            if (!bookingId) {
                await showModal('Error: Booking ID not found.', false);
                return;
            }

            // 1. Confirm with the user
            const confirmed = await showModal('Are you sure you want to cancel this booking? This action cannot be undone.', true);
            
            if (!confirmed) {
                return; // User clicked "No"
            }

            // Disable button to prevent double-clicks
            button.disabled = true;
            button.textContent = 'Cancelling...';

            // 2. Call the new API to DELETE the booking
            try {
                const response = await fetch('../api/cancelBooking.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ booking_id: bookingId })
                });
                
                const data = await response.json();

                if (data.success) {
                    await showModal('Booking successfully cancelled.', false);
                    // 3. Remove the booking card (table row) from the UI
                    if (card) {
                        card.remove();
                    }
                    // Optional: You can now reload the page to show available slots
                    window.location.reload();

                } else {
                    await showModal('Error: ' + data.error, false);
                    // Re-enable the button if it failed
                    button.disabled = false;
                    button.textContent = 'Cancel Booking';
                }
            } catch (error) {
                console.error('Error:', error);
                await showModal('An unexpected error occurred. Please try again.', false);
                button.disabled = false;
                button.textContent = 'Cancel Booking';
            }
        }
    });
});