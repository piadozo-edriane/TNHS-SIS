document.addEventListener('DOMContentLoaded', function() {
    // Fetch subjects from the schedule API
    fetch('../api/get_student_schedule.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('grade-table-body');
            if (data.success && data.subjects.length > 0) {
                tbody.innerHTML = data.subjects.map(sub =>
                    `<tr>
                        <td>${sub.subject_name}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>`
                ).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="6">No subjects found.</td></tr>';
            }
        })
        .catch(() => {
            const tbody = document.getElementById('grade-table-body');
            tbody.innerHTML = '<tr><td colspan="6">Error loading subjects.</td></tr>';
        });
}); 