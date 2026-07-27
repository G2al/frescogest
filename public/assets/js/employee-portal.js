document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);

        if (!input) {
            return;
        }

        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.textContent = show ? 'Nascondi' : 'Mostra';
    });
});

const attendanceTimes = document.querySelector('[data-attendance-times]');
const statusInputs = document.querySelectorAll('input[name="status"]');

function updateAttendanceFields() {
    if (!attendanceTimes) {
        return;
    }

    const absent = document.querySelector('input[name="status"]:checked')?.value === 'absent';
    attendanceTimes.hidden = absent;
    attendanceTimes.querySelectorAll('input').forEach((input) => {
        input.disabled = absent;
    });
}

statusInputs.forEach((input) => input.addEventListener('change', updateAttendanceFields));
updateAttendanceFields();
