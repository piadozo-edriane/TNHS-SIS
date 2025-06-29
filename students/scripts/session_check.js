// Session management for student pages
function checkStudentSession() {
    const studentData = sessionStorage.getItem('studentData');
    
    if (!studentData) {
        // No student data found, redirect to login
        window.location.href = 'student_login.html';
        return null;
    }
    
    try {
        return JSON.parse(studentData);
    } catch (error) {
        console.error('Error parsing student data:', error);
        sessionStorage.removeItem('studentData');
        window.location.href = 'student_login.html';
        return null;
    }
}

// Function to logout student
function logoutStudent() {
    sessionStorage.removeItem('studentData');
    window.location.href = 'student_login.html';
}

// Function to get current student data
function getCurrentStudent() {
    return checkStudentSession();
}

// Auto-check session on page load (for protected pages)
document.addEventListener('DOMContentLoaded', function() {
    // Check if this is a protected page (not login page)
    if (!window.location.href.includes('student_login.html')) {
        const student = checkStudentSession();
        if (student) {
            // Update page with student name if there's a welcome element
            const welcomeElement = document.getElementById('student-name');
            if (welcomeElement) {
                welcomeElement.textContent = student.first_name + ' ' + student.last_name;
            }
        }
    }
}); 