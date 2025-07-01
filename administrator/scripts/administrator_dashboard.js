const API_URL = '../api/administrator_dashboard.php';

document.addEventListener('DOMContentLoaded', function() {
    console.log('Administrator Dashboard JS loaded.');
    loadDashboardCounts();

    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            // Redirect to the login page (or logout API if session management is re-enabled)
            window.location.href = 'administrator_login.html';
        });
    }
});

function loadDashboardCounts() {
    fetch(API_URL)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('facultyCount').textContent = data.facultyCount;
                document.getElementById('studentCount').textContent = data.studentCount;
                document.getElementById('reportCount').textContent = data.reportCount;
            } else {
                console.error('Error fetching dashboard data:', data.message);
                document.getElementById('facultyCount').textContent = 'Error';
                document.getElementById('studentCount').textContent = 'Error';
                document.getElementById('reportCount').textContent = 'Error';
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            document.getElementById('facultyCount').textContent = 'Error';
            document.getElementById('studentCount').textContent = 'Error';
            document.getElementById('reportCount').textContent = 'Error';
        });
} 