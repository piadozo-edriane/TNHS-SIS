document.addEventListener('DOMContentLoaded', function() {
    const gradesTableBody = document.querySelector('#gradesTableBody');

    fetch('../api/get_grades.php')
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                gradesTableBody.innerHTML = '<tr><td colspan="3" style="color:red;">Failed to load grades.</td></tr>';
                return;
            }
            let rows = '';
            data.grades.forEach(grade => {
                rows += `<tr>
                    <td>${grade.lrn}</td>
                    <td>${grade.class_subject_id}</td>
                    <td>
                        <input type="number" name="grade_${grade.grade_id}" min="60" max="100" value="${grade.general_weighted_average !== null ? grade.general_weighted_average : ''}" placeholder="Enter grade" style="width:100px; padding:6px 10px; font-size:1em;" data-id="${grade.grade_id}" readonly>
                        <button class="save-btn" data-id="${grade.grade_id}" style="background:#43a047;color:#fff;">Save</button>
                        <button class="edit-btn" data-id="${grade.grade_id}" style="background:#fbc02d;color:#333;">Edit</button>
                        <button class="delete-btn" data-id="${grade.grade_id}" style="background:#e53935;color:#fff;">Delete</button>
                    </td>
                </tr>`;
            });
            // Add a row for new grade entry at the top
            rows = `<tr>
                <td><input type="text" id="new_lrn" placeholder="LRN" style="width:100px; padding:6px 10px; font-size:1em;"></td>
                <td><input type="text" id="new_class_subject_id" placeholder="Class Subject ID" style="width:100px; padding:6px 10px; font-size:1em;"></td>
                <td>
                    <input type="number" id="new_gwa" min="60" max="100" placeholder="General Weighted Average" style="width:100px; padding:6px 10px; font-size:1em;">
                    <button id="add-btn" style="background:#1976d2;color:#fff;">Add</button>
                </td>
            </tr>` + rows;
            gradesTableBody.innerHTML = rows;

            // Add button: create new grade
            const addBtn = document.getElementById('add-btn');
            if (addBtn) {
                addBtn.addEventListener('click', function() {
                    const lrn = document.getElementById('new_lrn').value.trim();
                    const class_subject_id = document.getElementById('new_class_subject_id').value.trim();
                    const gwa = document.getElementById('new_gwa').value.trim();
                    if (!lrn || !class_subject_id || !gwa) {
                        alert('Please fill in all fields.');
                        return;
                    }
                    fetch('../api/grade_action.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'create',
                            lrn: lrn,
                            class_subject_id: class_subject_id,
                            general_weighted_average: gwa
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert('Grade added!');
                            location.reload();
                        } else {
                            alert(data.error || 'Add failed.');
                        }
                    });
                });
            }

            // Use event delegation for button actions
            gradesTableBody.addEventListener('click', function(e) {
                const target = e.target;
                if (target.classList.contains('edit-btn')) {
                    const id = target.getAttribute('data-id');
                    const input = gradesTableBody.querySelector(`input[name='grade_${id}']`);
                    input.removeAttribute('readonly');
                    input.focus();
                } else if (target.classList.contains('save-btn')) {
                    const id = target.getAttribute('data-id');
                    const input = gradesTableBody.querySelector(`input[name='grade_${id}']`);
                    if (input.hasAttribute('readonly')) {
                        alert('Click Edit before saving changes.');
                        return;
                    }
                    const value = input.value;
                    fetch('../api/grade_action.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'update', grade_id: id, general_weighted_average: value })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert('Saved!');
                            input.setAttribute('readonly', true);
                        } else {
                            alert(data.error || 'Save failed.');
                        }
                    });
                } else if (target.classList.contains('delete-btn')) {
                    const id = target.getAttribute('data-id');
                    if (!confirm('Delete this grade?')) return;
                    fetch('../api/grade_action.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete', grade_id: id })
                    })
                    .then(res => res.json())
                    .then(data => {
                        alert(data.success ? 'Deleted!' : (data.error || 'Delete failed.'));
                        if (data.success) location.reload();
                    });
                }
            });
        })
        .catch(() => {
            gradesTableBody.innerHTML = '<tr><td colspan="3" style="color:red;">Error loading grades.</td></tr>';
        });
});
