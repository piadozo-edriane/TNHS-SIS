const API_URL = '../api/login_faculty.php';

document.addEventListener('DOMContentLoaded', function() {
    const loginBtn = document.getElementById('login_button');
    if (!loginBtn) return;
    loginBtn.addEventListener('click', function() {
        const teacherId = document.getElementById('teacher_id')?.value;
        const password = document.getElementById('password')?.value;
        const errorDiv = document.getElementById('error-message');

        errorDiv.style.display = 'none';
        errorDiv.textContent = '';

        if (!teacherId || !password) {
            errorDiv.textContent = 'Teacher ID and password are required.';
            errorDiv.style.display = 'block';
            return;
        }

        fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ teacher_id: teacherId, password: password })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessModal();
            } else {
                errorDiv.textContent = data.message || 'Login failed.';
                errorDiv.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorDiv.textContent = 'An error occurred. Please try again.';
            errorDiv.style.display = 'block';
        });
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
            window.location.href = 'faculty_dashboard.php';
        };
    }
});