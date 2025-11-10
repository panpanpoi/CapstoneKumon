document.addEventListener('DOMContentLoaded', () => {
    
    // --- 1. Custom Modal Logic (Replaces alert/confirm) ---
    
    /**
     * Shows a custom modal.
     * @param {string} contentHTML The HTML content to display.
     * @param {boolean} isConfirm If true, shows "Yes" and "No" buttons. If false, shows "OK".
     * @returns {Promise<boolean>} Resolves true if "Yes" or "OK" is clicked, false if "No" is clicked.
     */
    function showModal(contentHTML, isConfirm = false) {
        // Remove any existing modal
        const existingModal = document.getElementById('customModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Create modal elements
        const overlay = document.createElement('div');
        overlay.id = 'customModal';
        overlay.className = 'custom-modal-overlay'; // For styling
        
        const modalBox = document.createElement('div');
        modalBox.className = 'custom-modal-box'; // For styling

        const contentDiv = document.createElement('div');
        contentDiv.className = 'custom-modal-content';
        contentDiv.innerHTML = contentHTML; // Set inner HTML
        
        const buttonWrapper = document.createElement('div');
        buttonWrapper.className = 'custom-modal-buttons';

        const confirmBtn = document.createElement('button');
        confirmBtn.textContent = isConfirm ? 'Yes, Cancel' : 'OK';
        confirmBtn.className = 'modal-btn-confirm';

        const cancelBtn = document.createElement('button');
        cancelBtn.textContent = 'No';
        cancelBtn.className = 'modal-btn-cancel';

        // Append elements
        modalBox.appendChild(contentDiv);
        buttonWrapper.appendChild(confirmBtn);
        if (isConfirm) {
            buttonWrapper.appendChild(cancelBtn);
        }
        modalBox.appendChild(buttonWrapper);
        overlay.appendChild(modalBox);
        document.body.appendChild(overlay);
        
        // Add styles (you can move this to your CSS file)
        if (!document.getElementById('customModalStyles')) {
            const style = document.createElement('style');
            style.id = 'customModalStyles';
            style.innerHTML = `
                .custom-modal-overlay {
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                    background: rgba(0,0,0,0.6); display: flex; align-items: center;
                    justify-content: center; z-index: 1000;
                    opacity: 0; transition: opacity 0.2s ease-in-out;
                }
                .custom-modal-box {
                    background: white; padding: 20px; border-radius: 8px;
                    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
                    max-width: 500px; width: 90%;
                    transform: scale(0.9); transition: transform 0.2s ease-in-out;
                }
                .custom-modal-content {
                    margin-bottom: 20px; font-size: 1.1em; line-height: 1.5;
                    max-height: 60vh; overflow-y: auto;
                }
                .custom-modal-content p:first-child { margin-top: 0; }
                .custom-modal-content p:last-child { margin-bottom: 0; }
                .custom-modal-buttons {
                    display: flex; justify-content: flex-end; gap: 10px;
                }
                .modal-btn-confirm, .modal-btn-cancel {
                    padding: 10px 18px; border: none; border-radius: 5px; cursor: pointer;
                    font-weight: bold; font-size: 0.9em;
                }
                .modal-btn-confirm { background: #d9534f; color: white; }
                .modal-btn-cancel { background: #f0f0f0; color: #333; }
                .modal-btn-confirm:hover { background: #c9302c; }
                .modal-btn-cancel:hover { background: #e0e0e0; }
                .custom-modal-content .notes-list { list-style-type: none; padding-left: 0; }
                .custom-modal-content .notes-list li { background: #f9f9f9; border: 1px solid #eee; border-radius: 4px; padding: 10px; margin-bottom: 10px; }
                .custom-modal-content .notes-list p { margin: 0 0 5px 0; }
                .custom-modal-content .notes-list small { color: #777; }
            `;
            document.head.appendChild(style);
        }
        
        // Trigger animations
        setTimeout(() => {
            overlay.style.opacity = '1';
            modalBox.style.transform = 'scale(1)';
        }, 10);

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
            overlay.onclick = (e) => {
                 if (e.target === overlay && !isConfirm) {
                    document.body.removeChild(overlay);
                    resolve(false);
                 }
            };
        });
    }

    // --- 2. Your Existing Alert-Hiding Code ---
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

    // --- 3. [NEW] "View Notes" Modal Logic ---
    document.querySelectorAll('.btn-view-notes').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.scheduleId;
            const notesContentDiv = document.getElementById(`notes-content-${id}`);
            
            if (notesContentDiv) {
                // Get the HTML content from your hidden notes div
                const notesHtmlContent = notesContentDiv.innerHTML;
                // Show it in a clean modal (isConfirm = false)
                showModal(notesHtmlContent, false); 
            } else {
                showModal('<p>No notes found for this meeting.</p>', false);
            }
        });
    });

    // --- 4. [NEW] "Cancel Booking" API Logic ---
    document.addEventListener('click', async function(event) {
        
        // Check if a cancel button was clicked
        if (event.target.classList.contains('cancel-booking-btn')) {
            
            const button = event.target;
            const bookingId = button.dataset.bookingId;

            if (!bookingId) {
                await showModal('<p>Error: Booking ID not found.</p>', false);
                return;
            }

            // 1. Confirm with the user
            const confirmed = await showModal('<p>Are you sure you want to cancel this booking? This action cannot be undone.</p>', true);
            
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
                    await showModal('<p>Booking successfully cancelled.</p>', false);
                    // Reload the page to show available slots
                    window.location.reload();

                } else {
                    await showModal('<p>Error: ' + data.error + '</p>', false);
                    // Re-enable the button if it failed
                    button.disabled = false;
                    button.textContent = 'Cancel Booking';
                }
            } catch (error) {
                console.error('Error:', error);
                await showModal('<p>An unexpected error occurred. Please try again.</p>', false);
                button.disabled = false;
                button.textContent = 'Cancel Booking';
            }
        }
    });
});