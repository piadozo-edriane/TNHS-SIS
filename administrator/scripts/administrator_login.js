document.addEventListener('DOMContentLoaded', function() {
    const loginBtn = document.getElementById('login_button');
    if (!loginBtn) return;
    loginBtn.addEventListener('click', function() {
        const adminId = document.getElementById('administrator_id')?.value;
        const password = document.getElementById('password')?.value;
        const errorDiv = document.getElementById('error-message');

        errorDiv.style.display = 'none';
        errorDiv.textContent = '';

        if (!adminId || !password) {
            errorDiv.textContent = 'Please fill in all fields';
            errorDiv.style.display = 'block';
            return;
        }

        // Hardcoded credentials
        const validId = '0098765';
        const validPassword = 'admin123';

        if (adminId === validId && password === validPassword) {
            // Directly show success modal, bypassing session setup
            showSuccessModal();
        } else {
            errorDiv.textContent = 'Invalid Administrator ID or Password.';
            errorDiv.style.display = 'block';
        }
    });

    function showSuccessModal() {
        let modal = document.getElementById('successModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'successModal';
            modal.className = 'success-modal';
            modal.innerHTML = `
                <div class="success-modal-content">
                    <h3>Login Successful!</h3>
                    <p>Redirecting to dashboard...</p>
                    <button id="okButton">OK</button>
                </div>
            `;
            document.body.appendChild(modal);
        } else {
            modal.style.display = 'block';
        }
        document.getElementById('okButton').onclick = function() {
            // Redirect to admin dashboard (update path as needed)
            window.location.href = 'administrator_dashboard.html';
        };
    }
}); 