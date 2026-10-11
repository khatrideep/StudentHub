document.addEventListener('DOMContentLoaded', () => {
    const storageKey = 'student-hub-profile';
    const defaults = {
        fullName: 'Ram Kumar',
        studentId: '123456',
        email: 'ramkumar@studenthub.edu',
        phone: '+91 1235464546',
        program: 'BSc Computer Science',
        year: '2nd Year',
        dob: '',
        gender: '',
        address: '',
        enrollmentDate: '2026-07-15',
        gpa: '3.8 / 4.0',
        bio: ''
    };

    const form = document.getElementById('profileEditForm');
    const editButton = document.getElementById('editProfileButton');
    const cancelButton = document.getElementById('cancelProfileEdit');
    const message = document.getElementById('profileSaveMessage');

    if (!form || !editButton || !cancelButton || !message) {
        return;
    }

    let profile = { ...defaults };
    let loadError = false;

    try {
        const savedProfile = localStorage.getItem(storageKey);
        if (savedProfile) {
            profile = { ...profile, ...JSON.parse(savedProfile) };
        }
    } catch (error) {
        loadError = true;
        message.textContent = 'Saved profile data could not be read. Editing will not overwrite it.';
        console.error('Unable to load the saved student profile.', error);
    }

    const display = (id, value, fallback = 'Not provided') => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value || fallback;
        }
    };

    const formatDate = (value) => {
        if (!value) {
            return '';
        }
        const date = new Date(`${value}T00:00:00`);
        return Number.isNaN(date.getTime())
            ? value
            : new Intl.DateTimeFormat(undefined, { year: 'numeric', month: 'long', day: 'numeric' }).format(date);
    };

    const renderProfile = () => {
        display('profileName', profile.fullName);
        display('profileFullName', profile.fullName);
        display('profileStudentId', profile.studentId);
        display('profileEmail', profile.email);
        display('profilePhone', profile.phone);
        display('profileProgram', profile.program);
        display('profileYear', profile.year);
        display('profileDob', formatDate(profile.dob));
        display('profileGender', profile.gender);
        display('profileAddress', profile.address);
        display('profileEnrollmentDate', formatDate(profile.enrollmentDate));
        display('profileGpa', profile.gpa);
        display('profileBio', profile.bio, 'Add a short introduction about yourself.');
        display('profileSummary', [profile.studentId, profile.program, profile.year].filter(Boolean).join(' | '), 'Student profile');

        const initials = profile.fullName
            .trim()
            .split(/\s+/)
            .slice(0, 2)
            .map((part) => part.charAt(0))
            .join('')
            .toUpperCase();
        display('profileAvatar', initials, 'SH');
    };

    const populateForm = () => {
        Object.entries({
            fullName: profile.fullName,
            email: profile.email,
            phone: profile.phone,
            dob: profile.dob,
            gender: profile.gender,
            program: profile.program,
            year: profile.year,
            address: profile.address,
            bio: profile.bio
        }).forEach(([name, value]) => {
            const field = form.elements.namedItem(name);
            if (field) {
                field.value = value || '';
            }
        });
    };

    renderProfile();

    editButton.addEventListener('click', () => {
        populateForm();
        message.textContent = loadError ? message.textContent : '';
        form.hidden = false;
        editButton.hidden = true;
        form.querySelector('input')?.focus();
    });

    cancelButton.addEventListener('click', () => {
        form.hidden = true;
        editButton.hidden = false;
        message.textContent = '';
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!form.reportValidity()) {
            return;
        }
        if (loadError) {
            message.textContent = 'Profile cannot be saved because the existing saved data could not be read.';
            return;
        }

        const formData = new FormData(form);
        const updatedProfile = {
            ...profile,
            fullName: String(formData.get('fullName')).trim(),
            email: String(formData.get('email')).trim(),
            phone: String(formData.get('phone')).trim(),
            dob: String(formData.get('dob')),
            gender: String(formData.get('gender')),
            program: String(formData.get('program')).trim(),
            year: String(formData.get('year')),
            address: String(formData.get('address')).trim(),
            bio: String(formData.get('bio')).trim()
        };

        try {
            localStorage.setItem(storageKey, JSON.stringify(updatedProfile));
            profile = updatedProfile;
            renderProfile();
            form.hidden = true;
            editButton.hidden = false;
            message.textContent = 'Profile saved successfully.';
        } catch (error) {
            message.textContent = 'Your changes could not be saved in this browser. Please check available storage and try again.';
            console.error('Unable to save the student profile.', error);
        }
    });
});
