<?php if (!in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'signup.php', 'test_db.php'])): ?>
            </main>
        </div>
    </div>
    
    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">Confirm Logout</h3>
                    <button onclick="closeModal('logoutModal')" class="text-gray-500 hover:text-gray-700 text-2xl">
                        &times;
                    </button>
                </div>
                <div class="space-y-4">
                    <div class="p-4 bg-yellow-50 rounded-lg">
                        <i class="fas fa-sign-out-alt text-yellow-500 text-2xl mb-2"></i>
                        <p class="text-gray-700">Are you sure you want to log out of your account?</p>
                    </div>
                    <div class="flex justify-end space-x-4">
                        <button onclick="closeModal('logoutModal')" class="px-6 py-2 border rounded-lg hover:bg-gray-100 transition-colors">
                            Cancel
                        </button>
                        <a href="?logout=true" class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg transition-colors">
                            Log Out
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Show logout modal
    function showLogoutModal() {
        openModal('logoutModal');
    }
    
    // Add global modal functions if not already defined
    if (typeof openModal === 'undefined') {
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 300);
        });
    }, 5000);
    
    // Add CSS for modals if not already in header
    if (!document.querySelector('style[data-modals]')) {
        const style = document.createElement('style');
        style.setAttribute('data-modals', 'true');
        style.textContent = `
            .modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 1000;
                align-items: center;
                justify-content: center;
                padding: 1rem;
            }
            .modal.active {
                display: flex;
            }
            .modal-content {
                background: white;
                border-radius: 12px;
                max-width: 500px;
                width: 100%;
                max-height: 90vh;
                overflow-y: auto;
                animation: modalSlideIn 0.3s ease;
            }
            @keyframes modalSlideIn {
                from { transform: translateY(-20px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            @media (max-width: 640px) {
                .modal {
                    padding: 0.5rem;
                }
                .modal-content {
                    max-width: 100%;
                }
            }
        `;
        document.head.appendChild(style);
    }
    </script>
<?php endif; ?>
</body>
</html>