const API_URL = '../api/enrollment.php';

document.addEventListener('DOMContentLoaded', function() {
    // Sample data for municipalities and barangays (in production, fetch from server)
    const locationData = {
        'Cebu': {
            municipalities: ['Cebu City', 'Mandaue City'],
            barangays: {
                'Cebu City': ['Lahug', 'Mabolo'],
                'Mandaue City': ['Banilad']
            }
        },
        'Davao del Sur': {
            municipalities: ['Davao City'],
            barangays: {
                'Davao City': ['Buhangin']
            }
        },
        'Laguna': {
            municipalities: ['Santa Rosa'],
            barangays: {
                'Santa Rosa': ['Balibago']
            }
        }
    };

    // Function to populate dropdown
    function populateDropdown(selectElement, options, defaultOption) {
        selectElement.innerHTML = `<option value="">${defaultOption}</option>`;
        options.forEach(option => {
            const opt = document.createElement('option');
            opt.value = option;
            opt.textContent = option;
            selectElement.appendChild(opt);
        });
    }

    // Current address dropdowns
    const currentProvince = document.getElementById('current_province');
    const currentMunicipality = document.getElementById('current_municipality');
    const currentBarangay = document.getElementById('current_barangay');

    // Permanent address dropdowns
    const permanentProvince = document.getElementById('permanent_province');
    const permanentMunicipality = document.getElementById('permanent_municipality');
    const permanentBarangay = document.getElementById('permanent_barangay');

    // Update municipalities based on province selection
    function updateMunicipalities(province, municipalitySelect) {
        if (province && locationData[province]) {
            populateDropdown(municipalitySelect, locationData[province].municipalities, 'Select Municipality/City');
            municipalitySelect.disabled = false;
        } else {
            municipalitySelect.innerHTML = '<option value="">Select Municipality/City</option>';
            municipalitySelect.disabled = true;
        }
    }

    // Update barangays based on municipality selection
    function updateBarangays(province, municipality, barangaySelect) {
        if (province && municipality && locationData[province] && locationData[province].barangays[municipality]) {
            populateDropdown(barangaySelect, locationData[province].barangays[municipality], 'Select Barangay');
            barangaySelect.disabled = false;
        } else {
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            barangaySelect.disabled = true;
        }
    }

    // Current address province change
    currentProvince.addEventListener('change', function() {
        const province = this.value;
        updateMunicipalities(province, currentMunicipality);
        updateBarangays(province, currentMunicipality.value, currentBarangay);
    });

    // Current address municipality change
    currentMunicipality.addEventListener('change', function() {
        updateBarangays(currentProvince.value, this.value, currentBarangay);
    });

    // Permanent address province change
    permanentProvince.addEventListener('change', function() {
        const province = this.value;
        updateMunicipalities(province, permanentMunicipality);
        updateBarangays(province, permanentMunicipality.value, permanentBarangay);
    });

    // Permanent address municipality change
    permanentMunicipality.addEventListener('change', function() {
        updateBarangays(permanentProvince.value, this.value, permanentBarangay);
    });

    // Conditional field display logic
    document.querySelectorAll('input[name="ip_community"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('ip_specify_container').style.display = this.value === 'yes' ? 'block' : 'none';
        });
    });

    document.querySelectorAll('input[name="four_ps"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('four_ps_id_container').style.display = this.value === 'yes' ? 'block' : 'none';
        });
    });

    document.querySelectorAll('input[name="disability"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('disability_types_container').style.display = this.value === 'yes' ? 'block' : 'none';
        });
    });

    document.querySelectorAll('input[name="same_address"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('permanent_address_container').style.display = this.value === 'no' ? 'block' : 'none';
        });
    });

    document.querySelectorAll('input[name="returning_transfer_status"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('returning_transfer_container').style.display = this.value === 'yes' ? 'block' : 'none';
        });
    });

    document.querySelectorAll('input[name="senior_high_status"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('senior_high_container').style.display = this.value === 'yes' ? 'block' : 'none';
        });
    });

    // Form submission with AJAX
    document.getElementById('enrollment_form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const errorMessages = document.getElementById('error-messages');

        fetch(API_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            errorMessages.style.display = 'none';
            errorMessages.innerHTML = '';

            if (data.errors && data.errors.length > 0) {
                errorMessages.style.display = 'block';
                const ul = document.createElement('ul');
                data.errors.forEach(error => {
                    const li = document.createElement('li');
                    li.textContent = error;
                    ul.appendChild(li);
                });
                errorMessages.appendChild(ul);
            } else if (data.success) {
                alert(data.success);
                document.getElementById('enrollment_form').reset();
                document.querySelectorAll('.conditional').forEach(container => container.style.display = 'none');
                // Reset dropdowns
                currentMunicipality.innerHTML = '<option value="">Select Municipality/City</option>';
                currentBarangay.innerHTML = '<option value="">Select Barangay</option>';
                permanentMunicipality.innerHTML = '<option value="">Select Municipality/City</option>';
                permanentBarangay.innerHTML = '<option value="">Select Barangay</option>';
                currentMunicipality.disabled = true;
                currentBarangay.disabled = true;
                permanentMunicipality.disabled = true;
                permanentBarangay.disabled = true;
            } else {
                errorMessages.style.display = 'block';
                errorMessages.textContent = data.error || 'An unexpected error occurred.';
            }
        })
        .catch(error => {
            errorMessages.style.display = 'block';
            errorMessages.textContent = 'Failed to submit form. Please try again.';
            console.error('Error:', error);
        });
    });
});