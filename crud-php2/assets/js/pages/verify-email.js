// Verify Email Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('verifyForm');
    const submitBtn = document.getElementById('verifyBtn');
    const btnText = submitBtn?.querySelector('.btn-text');
    const btnLoader = submitBtn?.querySelector('.btn-loader');
    const codeDigits = document.querySelectorAll('.code-digit');
    const codeHidden = document.getElementById('codeHidden');
    const resendBtn = document.getElementById('resendBtn');

    // Focus en el primer input al cargar
    if (codeDigits.length > 0) {
        codeDigits[0].focus();
    }

    // Función para actualizar el campo oculto
    function updateHiddenCode() {
        let code = '';
        codeDigits.forEach(input => {
            code += input.value;
        });
        codeHidden.value = code;
        
        // Habilitar/deshabilitar botón de verificación
        if (submitBtn) {
            submitBtn.disabled = code.length !== 6;
        }
    }

    // Manejar entrada de código
    codeDigits.forEach((input, index) => {
        // Solo permitir números
        input.addEventListener('input', function(e) {
            // Remover cualquier caracter que no sea número
            this.value = this.value.replace(/[^0-9]/g, '');

            // Si se ingresó un número
            if (this.value.length === 1) {
                // Marcar como lleno
                this.classList.add('filled');
                
                // Mover al siguiente input
                if (index < codeDigits.length - 1) {
                    codeDigits[index + 1].focus();
                }
            } else if (this.value.length === 0) {
                // Si se borró, quitar la clase filled
                this.classList.remove('filled');
            }

            // Actualizar el campo oculto
            updateHiddenCode();
        });

        // Manejar teclas especiales
        input.addEventListener('keydown', function(e) {
            // Backspace: si está vacío, ir al anterior
            if (e.key === 'Backspace' && this.value.length === 0 && index > 0) {
                codeDigits[index - 1].focus();
            }
            // Flecha izquierda
            else if (e.key === 'ArrowLeft' && index > 0) {
                codeDigits[index - 1].focus();
                e.preventDefault();
            }
            // Flecha derecha
            else if (e.key === 'ArrowRight' && index < codeDigits.length - 1) {
                codeDigits[index + 1].focus();
                e.preventDefault();
            }
        });

        // Pegar código completo
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pasteData = e.clipboardData.getData('text');
            const numbers = pasteData.replace(/[^0-9]/g, '').split('');
            
            if (numbers.length === 6) {
                numbers.forEach((num, i) => {
                    if (codeDigits[i]) {
                        codeDigits[i].value = num;
                        codeDigits[i].classList.add('filled');
                    }
                });
                updateHiddenCode();
                codeDigits[5].focus();
            }
        });
    });

    // Manejar envío del formulario
    if (form) {
        form.addEventListener('submit', function(e) {
            const code = codeHidden.value;
            
            if (code.length !== 6) {
                e.preventDefault();
                alert('Por favor, ingresa el código completo de 6 dígitos.');
                return;
            }

            // Mostrar loader y deshabilitar botón
            if (submitBtn && btnText && btnLoader) {
                btnText.style.display = 'none';
                btnLoader.style.display = 'inline-block';
                submitBtn.disabled = true;
            }
        });
    }

    // Manejar reenvío de código
    if (resendBtn) {
        resendBtn.addEventListener('click', function(e) {
            // Mostrar indicador de carga
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            this.disabled = true;

            // El formulario se enviará automáticamente
            // El estado se restaurá con la recarga de la página
        });
    }

    // Auto-enfocar y seleccionar el primer dígito al cargar
    setTimeout(() => {
        if (codeDigits[0]) {
            codeDigits[0].focus();
            codeDigits[0].select();
        }
    }, 100);

    // Validación inicial
    updateHiddenCode();
});