// Validación de calificaciones
document.addEventListener('DOMContentLoaded', function() {
    // Validar que las calificaciones estén entre 0 y 100
    const gradeInputs = document.querySelectorAll('.form-control-grade');
    
    gradeInputs.forEach(input => {
        input.addEventListener('input', function() {
            let value = parseFloat(this.value);
            
            if (value > 100) {
                this.value = 100;
                showAlert('La calificación máxima es 100', 'warning');
            } else if (value < 0) {
                this.value = 0;
                showAlert('La calificación mínima es 0', 'warning');
            }
            
            // Validación visual
            if (value >= 0 && value <= 100) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
        
        input.addEventListener('blur', function() {
            if (this.value && (parseFloat(this.value) < 0 || parseFloat(this.value) > 100)) {
                this.value = '';
                this.classList.remove('is-valid', 'is-invalid');
            }
        });
    });
    
    // Calcular promedio automático
    const form = document.querySelector('form[method="POST"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            const grades = Array.from(gradeInputs)
                .map(input => parseFloat(input.value))
                .filter(value => !isNaN(value));
            
            if (grades.length === 0) {
                e.preventDefault();
                showAlert('Debes ingresar al menos una calificación', 'warning');
                return false;
            }
            
            const avg = grades.reduce((a, b) => a + b, 0) / grades.length;
            console.log('Promedio calculado:', avg.toFixed(2));
        });
    }
    
    // Atajo de teclado para guardar (Ctrl + S)
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const saveButton = document.querySelector('.btn-save');
            if (saveButton) {
                saveButton.click();
                showAlert('Guardando calificaciones...', 'info');
            }
        }
    });
});

// Función para copiar calificaciones
function copyGrades() {
    const grades = Array.from(document.querySelectorAll('.form-control-grade'))
        .map(input => input.value)
        .filter(value => value !== '');
    
    if (grades.length > 0) {
        navigator.clipboard.writeText(grades.join(', '))
            .then(() => showAlert('Calificaciones copiadas al portapapeles', 'success'))
            .catch(() => showAlert('Error al copiar', 'danger'));
    }
}

// Limpiar todas las calificaciones
function clearAllGrades() {
    if (confirmDelete('¿Estás seguro de que deseas limpiar todas las calificaciones?')) {
        document.querySelectorAll('.form-control-grade').forEach(input => {
            input.value = '';
            input.classList.remove('is-valid', 'is-invalid');
        });
        showAlert('Todas las calificaciones han sido limpiadas', 'info');
    }
}