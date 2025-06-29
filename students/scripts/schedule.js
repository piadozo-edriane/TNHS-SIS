document.addEventListener('DOMContentLoaded', function() {
    fetch('../api/get_class_schedule.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('schedule-table-body');
            if (data.success && data.schedule.length > 0) {
                tbody.innerHTML = data.schedule.map(row =>
                    `<tr>
                        <td>${row.time}</td>
                        <td>${row.day}</td>
                        <td>${row.minutes}</td>
                        <td>${row.subject_name}</td>
                        <td>${row.room || ''}</td>
                    </tr>`
                ).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="5">No schedule found.</td></tr>';
            }
        })
        .catch(() => {
            const tbody = document.getElementById('schedule-table-body');
            tbody.innerHTML = '<tr><td colspan="5">Error loading schedule.</td></tr>';
        });
}); 