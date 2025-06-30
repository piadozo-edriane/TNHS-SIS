const API_URL = '../api/faculty_dashboard.php';

document.addEventListener('DOMContentLoaded', function() {
    loadTeacherInfo();
});

function loadTeacherInfo() {
    fetch(API_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_teacher_info'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const teacherNameElement = document.getElementById('teacherName');
            const fullName = `${data.teacher.last_name}, ${data.teacher.first_name} ${data.teacher.middle_name} ${data.teacher.extension_name}`.replace(/\s+/g, ' ').trim();
            teacherNameElement.textContent = `${fullName} (${data.teacher.teacher_id})`;
        } else {
            if (data.error === 'not_logged_in') {
                window.location.href = 'login.html';
            } else {
                document.getElementById('teacherName').textContent = 'Error loading teacher info';
                console.error('Error:', data.error);
            }
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        document.getElementById('teacherName').textContent = 'Error loading teacher info';
    });
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        fetch('teacher_functions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=logout'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'login.html';
            } else {
                alert('Error logging out. Please try again.');
            }
        })
        .catch(error => {
            console.error('Logout error:', error);
            alert('Error logging out. Please try again.');
        });
    }
}