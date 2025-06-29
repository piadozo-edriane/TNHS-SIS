document.addEventListener('DOMContentLoaded', function() {
    const submitButton = document.getElementById('my_button');
    const lrnInput = document.getElementById('lrn_input');
    const monthSelect = document.getElementById('month_input_container');
    const daySelect = document.getElementById('day_input_container');
    const yearSelect = document.getElementById('year_input_container');
    const passwordInput = document.getElementById('my_password');

    const months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    
    months.forEach((month, index) => {
        const option = document.createElement('option');
        option.value = index + 1;
        option.textContent = month;
        monthSelect.appendChild(option);
    });

    // Populate day dropdown (1-31)
    for (let day = 1; day <= 31; day++) {
        const option = document.createElement('option');
        option.value = day;
        option.textContent = day;
        daySelect.appendChild(option);
    }

    // Populate year dropdown (1990-2010)
    for (let year = 2010; year >= 1990; year--) {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        yearSelect.appendChild(option);
    }

    // Add default options
    const defaultMonthOption = document.createElement('option');
    defaultMonthOption.value = '';
    defaultMonthOption.textContent = 'Month';
    defaultMonthOption.selected = true;
    monthSelect.insertBefore(defaultMonthOption, monthSelect.firstChild);

    const defaultDayOption = document.createElement('option');
    defaultDayOption.value = '';
    defaultDayOption.textContent = 'Day';
    defaultDayOption.selected = true;
    daySelect.insertBefore(defaultDayOption, daySelect.firstChild);

    const defaultYearOption = document.createElement('option');
    defaultYearOption.value = '';
    defaultYearOption.textContent = 'Year';
    defaultYearOption.selected = true;
    yearSelect.insertBefore(defaultYearOption, yearSelect.firstChild);

    submitButton.addEventListener('click', function() {
        // Get form values
        const lrn = lrnInput.value.trim();
        const birthMonth = monthSelect.value;
        const birthDay = daySelect.value;
        const birthYear = yearSelect.value;
        const password = passwordInput.value.trim();

        // Client-side validation
        if (!lrn) {
            alert('Please enter your LRN');
            lrnInput.focus();
            return;
        }

        if (!birthMonth || !birthDay || !birthYear) {
            alert('Please select your complete birth date');
            return;
        }

        if (!password) {
            alert('Please enter your password');
            passwordInput.focus();
            return;
        }

        // Validate LRN format (should be numeric and 12 digits)
        if (!/^\d{12}$/.test(lrn)) {
            alert('LRN must be 12 digits');
            lrnInput.focus();
            return;
        }

        // Show loading state
        submitButton.disabled = true;
        submitButton.textContent = 'Logging in...';

        // Prepare data for API
        const loginData = {
            lrn: lrn,
            birthMonth: birthMonth,
            birthDay: birthDay,
            birthYear: birthYear,
            password: password
        };

        // Send request to PHP API
        fetch('../api/log_in_validation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(loginData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Store student data in sessionStorage
                sessionStorage.setItem('studentData', JSON.stringify(data.student));
                
                // Show success message
                alert('Login successful! Welcome, ' + data.student.first_name + ' ' + data.student.last_name);
                
                // Redirect to student dashboard
                window.location.href = 'student_dashboard.html';
            } else {
                alert(data.message || 'Login failed. Please check your credentials.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred during login. Please try again.');
        })
        .finally(() => {
            // Reset button state
            submitButton.disabled = false;
            submitButton.textContent = 'Submit';
        });
    });

    // Add Enter key support
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            submitButton.click();
        }
    });

    // Add input validation feedback
    lrnInput.addEventListener('input', function() {
        const value = this.value.trim();
        if (value && !/^\d{12}$/.test(value)) {
            this.style.borderColor = '#ff4444';
        } else {
            this.style.borderColor = '';
        }
    });

    // Clear validation styling when input is cleared
    lrnInput.addEventListener('blur', function() {
        if (!this.value.trim()) {
            this.style.borderColor = '';
        }
    });
});
