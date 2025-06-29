document.addEventListener('DOMContentLoaded', function() {
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

    // Form submission
    document.getElementById('submit_button').addEventListener('click', function() {
        const formData = {
            school_year: document.getElementById('school_year').value,
            grade_level: document.getElementById('grade_level').value,
            lrn_status: document.querySelector('input[name="lrn_status"]:checked')?.value,
            returning_status: document.querySelector('input[name="returning_status"]:checked')?.value,
            psa_birth_cert: document.getElementById('psa_birth_cert').value,
            lrn: document.getElementById('lrn').value,
            last_name: document.getElementById('last_name').value,
            first_name: document.getElementById('first_name').value,
            middle_name: document.getElementById('middle_name').value,
            extension_name: document.getElementById('extension_name').value,
            birthdate: document.getElementById('birthdate').value,
            sex: document.getElementById('sex').value,
            age: document.getElementById('age').value,
            place_of_birth: document.getElementById('place_of_birth').value,
            mother_tongue: document.getElementById('mother_tongue').value,
            ip_community: document.querySelector('input[name="ip_community"]:checked')?.value,
            ip_specify: document.getElementById('ip_specify')?.value || '',
            four_ps: document.querySelector('input[name="four_ps"]:checked')?.value,
            four_ps_id: document.getElementById('four_ps_id')?.value || '',
            disability: document.querySelector('input[name="disability"]:checked')?.value,
            disability_types: Array.from(document.querySelectorAll('input[name="disability_type"]:checked')).map(cb => cb.value),
            current_address: {
                house_no: document.getElementById('current_house_no').value,
                sitio_street: document.getElementById('current_sitio_street').value,
                barangay: document.getElementById('current_barangay').value,
                municipality: document.getElementById('current_municipality').value,
                province: document.getElementById('current_province').value,
                country: document.getElementById('current_country').value,
                zip_code: document.getElementById('current_zip_code').value
            },
            same_address: document.querySelector('input[name="same_address"]:checked')?.value,
            permanent_address: {
                house_no: document.getElementById('permanent_house_no')?.value || '',
                sitio_street: document.getElementById('permanent_sitio_street')?.value || '',
                barangay: document.getElementById('permanent_barangay')?.value || '',
                municipality: document.getElementById('permanent_municipality')?.value || '',
                province: document.getElementById('permanent_province')?.value || '',
                country: document.getElementById('permanent_country')?.value || '',
                zip_code: document.getElementById('permanent_zip_code')?.value || ''
            },
            father: {
                last_name: document.getElementById('father_last_name').value,
                first_name: document.getElementById('father_first_name').value,
                middle_name: document.getElementById('father_middle_name').value,
                contact: document.getElementById('father_contact').value
            },
            mother: {
                last_name: document.getElementById('mother_last_name').value,
                first_name: document.getElementById('mother_first_name').value,
                middle_name: document.getElementById('mother_middle_name').value,
                contact: document.getElementById('mother_contact').value
            },
            guardian: {
                last_name: document.getElementById('guardian_last_name').value,
                first_name: document.getElementById('guardian_first_name').value,
                middle_name: document.getElementById('guardian_middle_name').value,
                contact: document.getElementById('guardian_contact').value
            },
            last_grade_level: document.getElementById('last_grade_level').value,
            last_school_attended: document.getElementById('last_school_attended').value,
            last_school_year: document.getElementById('last_school_year').value,
            school_id: document.getElementById('school_id').value,
            semester: document.querySelector('input[name="semester"]:checked')?.value,
            track: document.getElementById('track').value,
            strand: document.getElementById('strand').value,
            modalities: {
                modular_print: document.getElementById('modular_print').checked,
                modular_digital: document.getElementById('modular_digital').checked,
                online: document.getElementById('online').checked,
                radio_based: document.getElementById('radio_based').checked,
                blended: document.getElementById('blended').checked,
                educational_tv: document.getElementById('educational_tv').checked,
                homeschooling: document.getElementById('homeschooling').checked
            },
            signature: document.getElementById('signature').files[0]
        };

        // Validation
        if (!formData.school_year || !formData.grade_level) {
            alert('Please fill in School Year and Grade Level.');
            return;
        }

        if (!formData.lrn_status || !formData.returning_status) {
            alert('Please select LRN and Returning status.');
            return;
        }

        if (!formData.last_name || !formData.first_name || !formData.birthdate || !formData.sex || !formData.age) {
            alert('Please complete required learner information (Last Name, First Name, Birthdate, Sex, Age).');
            return;
        }

        if (formData.ip_community === 'yes' && !formData.ip_specify) {
            alert('Please specify the Indigenous Community.');
            return;
        }

        if (formData.four_ps === 'yes' && !formData.four_ps_id) {
            alert('Please provide the 4Ps Household ID Number.');
            return;
        }

        if (formData.disability === 'yes' && formData.disability_types.length === 0) {
            alert('Please select at least one disability type.');
            return;
        }

        if (!formData.current_address.house_no || !formData.current_address.barangay || !formData.current_address.municipality) {
            alert('Please complete required current address fields (House No., Barangay, Municipality/City).');
            return;
        }

        if (formData.same_address === 'no' && (!formData.permanent_address.house_no || !formData.permanent_address.barangay || !formData.permanent_address.municipality)) {
            alert('Please complete required permanent address fields (House No., Barangay, Municipality/City).');
            return;
        }

        if (formData.returning_status === 'yes' && (!formData.last_grade_level || !formData.last_school_attended || !formData.last_school_year || !formData.school_id)) {
            alert('Please complete all fields for Returning Learner/Transfer.');
            return;
        }

        if (formData.semester && (!formData.track || !formData.strand)) {
            alert('Please complete all Senior High School fields or leave them all blank.');
            return;
        }

        if (!Object.values(formData.modalities).some(v => v)) {
            alert('Please select at least one distance learning modality.');
            return;
        }

        if (!formData.signature) {
            alert('Please upload a signature file.');
            return;
        }

        console.log('Form Data:', formData);
        alert('Enrollment form submitted successfully!');
        document.querySelectorAll('input, select').forEach(input => input.value = '');
        document.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach(input => input.checked = false);
        document.getElementById('signature').value = '';
        document.querySelectorAll('.conditional').forEach(container => container.style.display = 'none');
    });
});