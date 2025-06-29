const API_URL = '../api/login_faculty.php';

document.addEventListener('DOMContentLoaded', function() {
    const loginBtn = document.getElementById('login_button');
    if (!loginBtn) return;
    loginBtn.addEventListener('click', function() {
        const teacherId = document.getElementById('teacher_id')?.value;
        const month = document.getElementById('month_input')?.value;
        const day = document.getElementById('day_input')?.value;
        const year = document.getElementById('year_input')?.value;
        const password = document.getElementById('password')?.value;
        const errorDiv = document.getElementById('error-message');

        errorDiv.style.display = 'none';
        errorDiv.textContent = '';

        if (!teacherId || !month || !day || !year || !password) {
            errorDiv.textContent = 'Please fill in all fields';
            errorDiv.style.display = 'block';
            return;
        }

        const paddedMonth = month.padStart(2, '0');
        const paddedDay = day.padStart(2, '0');
        const birthday = `${year}-${paddedMonth}-${paddedDay}`;
        const formData = new FormData();
        formData.append('teacher_id', teacherId);
        formData.append('birthday', birthday);
        formData.append('password', password);

        fetch(API_URL, {
            method: 'POST',
            body: formData
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
            window.location.href = '/im_website/faculty/faculty_dashboard.html';
        };
    }
});