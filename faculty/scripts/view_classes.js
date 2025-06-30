const API_URL = '../api/view_classes.php';

document.addEventListener('DOMContentLoaded', function() {
    fetch(API_URL)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('classes-container');
            container.innerHTML = '';
            if (data.success && Array.isArray(data.classes) && data.classes.length > 0) {
                const table = document.createElement('table');
                table.className = 'classes-table';
                table.innerHTML = `
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Room Number</th>
                            <th>Number of Students</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.classes.map(cls => `
                            <tr>
                                <td>${cls.class_name}</td>
                                <td>${cls.room_number}</td>
                                <td>${cls.number_of_students}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                `;
                container.appendChild(table);
            } else {
                container.innerHTML = '<div class="no-classes">No classes found.</div>';
            }
        })
        .catch(error => {
            document.getElementById('classes-container').innerHTML = '<div class="error">Error loading classes.</div>';
            console.error('Error fetching classes:', error);
        });
});
