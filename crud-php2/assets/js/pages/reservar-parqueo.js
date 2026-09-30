class ReservationManager {
    constructor() {
        this.currentStep = 1;
        this.selectedVehicle = null;
        this.reservationData = window.reservationData;
        this.attemptsLeft = 3;
        this.isProcessing = false;
        
        this.initializeEventListeners();
        this.showStep(1);
        this.initializeTimeSelectors();
    }

    initializeEventListeners() {
        // Selección de vehículos
        document.querySelectorAll('.vehicle-option').forEach(option => {
            option.addEventListener('click', () => this.selectVehicle(option));
        });

        // Fechas y horas
        document.getElementById('start_date').addEventListener('change', () => this.updateEndDateMin());
        document.getElementById('start_date').addEventListener('change', () => this.validateDateTime());
        document.getElementById('start_time').addEventListener('change', () => this.validateDateTime());
        document.getElementById('end_date').addEventListener('change', () => this.validateDateTime());
        document.getElementById('end_time').addEventListener('change', () => this.validateDateTime());

        // Modal de contraseña
        document.getElementById('password-form').addEventListener('submit', (e) => this.handlePasswordSubmit(e));
        document.getElementById('toggle-password').addEventListener('click', () => this.togglePasswordVisibility());

        // Cerrar modales al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModals();
            }
        });
    }

    initializeTimeSelectors() {
        const times = this.generateTimeOptions();
        const startSelect = document.getElementById('start_time');
        const endSelect = document.getElementById('end_time');
        
        times.forEach(time => {
            startSelect.appendChild(new Option(time.display, time.value));
            endSelect.appendChild(new Option(time.display, time.value));
        });
    }

    generateTimeOptions() {
        const times = [];
        for (let h = 0; h < 24; h++) {
            for (let m = 0; m < 60; m += 15) {
                const hour12 = h % 12 || 12;
                const ampm = h < 12 ? 'AM' : 'PM';
                const timeValue = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                const timeDisplay = `${hour12}:${String(m).padStart(2, '0')} ${ampm}`;
                times.push({ value: timeValue, display: timeDisplay });
            }
        }
        return times;
    }

    selectVehicle(vehicleElement) {
        // Remover selección anterior
        document.querySelectorAll('.vehicle-option').forEach(opt => {
            opt.classList.remove('selected');
        });

        // Seleccionar nuevo vehículo
        vehicleElement.classList.add('selected');
        this.selectedVehicle = vehicleElement.dataset.vehicleId;
        document.getElementById('selected_vehicle_id').value = this.selectedVehicle;

        // Animar selección
        vehicleElement.style.transform = 'scale(1.02)';
        setTimeout(() => {
            vehicleElement.style.transform = 'scale(1)';
        }, 200);
    }

    updateEndDateMin() {
        const startDate = document.getElementById('start_date').value;
        if (startDate) {
            document.getElementById('end_date').min = startDate;
            
            // Resetear fecha de fin si es anterior
            const endDate = document.getElementById('end_date').value;
            if (endDate && endDate < startDate) {
                document.getElementById('end_date').value = '';
                document.getElementById('end_time').value = '';
            }
        }
    }

    validateDateTime() {
        const startDate = document.getElementById('start_date').value;
        const startTime = document.getElementById('start_time').value;
        const endDate = document.getElementById('end_date').value;
        const endTime = document.getElementById('end_time').value;

        if (!startDate || !startTime || !endDate || !endTime) {
            return false;
        }

        const startDateTime = new Date(`${startDate}T${startTime}`);
        const endDateTime = new Date(`${endDate}T${endTime}`);
        const now = new Date();

        // Validaciones
        if (startDateTime < now) {
            this.showError('La fecha y hora de inicio no pueden ser en el pasado');
            return false;
        }

        if (endDateTime <= startDateTime) {
            this.showError('La fecha y hora de fin deben ser posteriores a las de inicio');
            return false;
        }

        const minDuration = 30 * 60 * 1000; // 30 minutos
        if ((endDateTime - startDateTime) < minDuration) {
            this.showError('La reserva debe tener una duración mínima de 30 minutos');
            return false;
        }

        // Si pasa todas las validaciones, verificar disponibilidad
        this.checkAvailability();
        return true;
    }

    async checkAvailability() {
        if (!this.validateDateTime() || !this.selectedVehicle) return;

        const formData = new FormData();
        formData.append('parking_id', this.reservationData.parkingId);
        formData.append('vehicle_type_id', this.selectedVehicle);
        formData.append('start_date', document.getElementById('start_date').value);
        formData.append('start_time', document.getElementById('start_time').value);
        formData.append('end_date', document.getElementById('end_date').value);
        formData.append('end_time', document.getElementById('end_time').value);

        try {
            this.showLoading('fees-content', 'Calculando disponibilidad y tarifa...');

            const response = await fetch('includes/verificar-disponibilidad.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showFeesPreview(data);
            } else {
                this.showAvailabilityError(data.message);
            }

        } catch (error) {
            console.error('Error:', error);
            this.showError('Error al verificar disponibilidad. Intenta de nuevo.');
        }
    }

    showFeesPreview(data) {
        const duration = this.calculateDuration();
        const feesContent = document.getElementById('fees-content');
        
        feesContent.innerHTML = `
            <div class="fee-breakdown">
                <div class="fee-item-line">
                    <span>Tarifa por hora:</span>
                    <span>$${data.hourly_rate || '0.00'}</span>
                </div>
                <div class="fee-item-line">
                    <span>Duración:</span>
                    <span>${duration} horas</span>
                </div>
                <div class="fee-item-line">
                    <span>Descuento:</span>
                    <span>-$${data.discount || '0.00'}</span>
                </div>
                <div class="fee-total">
                    <span>Total estimado:</span>
                    <span>$${data.estimated_fee || '0.00'}</span>
                </div>
                <div class="availability-success">
                    <i class="fas fa-check-circle"></i> Espacio disponible para reservar
                </div>
            </div>
        `;

        // Actualizar resumen de confirmación
        this.updateConfirmationSummary(data, duration);
    }

    showAvailabilityError(message) {
        const feesContent = document.getElementById('fees-content');
        feesContent.innerHTML = `
            <div class="availability-error">
                <i class="fas fa-times-circle"></i> ${message || 'No hay disponibilidad para las fechas seleccionadas'}
            </div>
        `;
    }

    showLoading(elementId, message = 'Cargando...') {
        const element = document.getElementById(elementId);
        element.innerHTML = `
            <div class="loading-fees">
                <i class="fas fa-spinner fa-spin"></i> ${message}
            </div>
        `;
    }

    calculateDuration() {
        const start = new Date(`${document.getElementById('start_date').value}T${document.getElementById('start_time').value}`);
        const end = new Date(`${document.getElementById('end_date').value}T${document.getElementById('end_time').value}`);
        const durationMs = end - start;
        return (durationMs / (1000 * 60 * 60)).toFixed(2);
    }

    updateConfirmationSummary(data, duration) {
        const summary = document.getElementById('confirmation-summary');
        const vehicleText = document.querySelector('.vehicle-option.selected .vehicle-info h4').textContent;
        
        summary.innerHTML = `
            <div class="summary-item">
                <span class="summary-label">Vehículo:</span>
                <span class="summary-value">${vehicleText}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Fecha de inicio:</span>
                <span class="summary-value">${document.getElementById('start_date').value} ${document.getElementById('start_time').options[document.getElementById('start_time').selectedIndex].text}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Fecha de fin:</span>
                <span class="summary-value">${document.getElementById('end_date').value} ${document.getElementById('end_time').options[document.getElementById('end_time').selectedIndex].text}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Duración:</span>
                <span class="summary-value">${duration} horas</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Tarifa total:</span>
                <span class="summary-value">$${data.estimated_fee || '0.00'}</span>
            </div>
        `;
    }

    showStep(stepNumber) {
        // Ocultar todos los pasos
        document.querySelectorAll('.form-step').forEach(step => {
            step.classList.remove('active');
        });

        // Mostrar paso actual
        document.querySelector(`.form-step[data-step="${stepNumber}"]`).classList.add('active');

        // Actualizar progreso
        this.updateProgress(stepNumber);
        
        this.currentStep = stepNumber;
    }

    updateProgress(stepNumber) {
        // Actualizar steps
        document.querySelectorAll('.step').forEach((step, index) => {
            if (index + 1 <= stepNumber) {
                step.classList.add('active');
            } else {
                step.classList.remove('active');
            }
        });

        // Actualizar barra de progreso
        const progressBar = document.querySelector('.progress-bar');
        const progress = ((stepNumber - 1) / 2) * 100;
        progressBar.style.width = `${progress}%`;
    }

    nextStep(step) {
        // Validaciones antes de avanzar
        if (step === 2 && !this.selectedVehicle) {
            this.showError('Por favor selecciona un vehículo');
            return;
        }

        if (step === 3 && !this.validateDateTime()) {
            this.showError('Por favor completa correctamente las fechas y horarios');
            return;
        }

        this.showStep(step);
    }

    prevStep(step) {
        this.showStep(step);
    }

    showPasswordModal() {
        this.attemptsLeft = 3;
        this.updateAttemptsDisplay();
        document.getElementById('password-modal').style.display = 'block';
        document.getElementById('user_password').value = '';
        document.getElementById('user_password').focus();
    }

    closePasswordModal() {
        document.getElementById('password-modal').style.display = 'none';
    }

    togglePasswordVisibility() {
        const passwordInput = document.getElementById('user_password');
        const toggleIcon = document.getElementById('toggle-password');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }

    async handlePasswordSubmit(e) {
        e.preventDefault();
        
        if (this.isProcessing) return;
        
        const password = document.getElementById('user_password').value;
        if (!password) {
            this.showError('Por favor ingresa tu contraseña');
            return;
        }

        this.isProcessing = true;
        this.showLoadingInButton('Verificando...');

        try {
            const formData = new FormData();
            formData.append('password', password);
            formData.append('user_id', this.reservationData.userId);

            const response = await fetch('includes/verificar-contrasena.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.processReservation();
            } else {
                this.handlePasswordError(data.message);
            }

        } catch (error) {
            console.error('Error:', error);
            this.showError('Error de conexión. Intenta de nuevo.');
        } finally {
            this.isProcessing = false;
            this.resetButton();
        }
    }

    handlePasswordError(message) {
        this.attemptsLeft--;
        this.updateAttemptsDisplay();

        if (this.attemptsLeft <= 0) {
            this.showError('Demasiados intentos fallidos. Por seguridad, esta función ha sido bloqueada temporalmente.');
            this.closePasswordModal();
            // Aquí podrías redirigir o deshabilitar más acciones
        } else {
            this.showError(message);
            document.getElementById('user_password').value = '';
            document.getElementById('user_password').focus();
            document.getElementById('password-form').classList.add('shake');
            setTimeout(() => {
                document.getElementById('password-form').classList.remove('shake');
            }, 500);
        }
    }

    updateAttemptsDisplay() {
        const attemptsElement = document.getElementById('attempts-count');
        if (this.attemptsLeft === 3) {
            attemptsElement.className = '';
            attemptsElement.textContent = 'Intentos restantes: 3';
        } else if (this.attemptsLeft === 2) {
            attemptsElement.className = 'attempts-warning';
            attemptsElement.textContent = 'Intentos restantes: 2';
        } else if (this.attemptsLeft === 1) {
            attemptsElement.className = 'attempts-error';
            attemptsElement.textContent = '¡Último intento!';
        }
    }

    async processReservation() {
        this.showLoadingInButton('Procesando reserva...');

        try {
            const formData = new FormData(document.getElementById('reservation-form'));
            formData.append('user_id', this.reservationData.userId);

            const response = await fetch('includes/procesar-reserva.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccessModal(data.reservation_id);
            } else {
                this.showError(data.message);
            }

        } catch (error) {
            console.error('Error:', error);
            this.showError('Error al procesar la reserva. Intenta de nuevo.');
        } finally {
            this.resetButton();
        }
    }

    showSuccessModal(reservationId) {
        this.closePasswordModal();
        document.getElementById('success-modal').style.display = 'block';
        // Guardar el ID de reserva para la redirección
        this.reservationId = reservationId;
    }

    showLoadingInButton(text) {
        const button = document.querySelector('#password-form .btn-primary');
        button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${text}`;
        button.disabled = true;
    }

    resetButton() {
        const button = document.querySelector('#password-form .btn-primary');
        button.innerHTML = '<i class="fas fa-check"></i> Verificar y Reservar';
        button.disabled = false;
    }

    showError(message) {
        // Podrías implementar un sistema de notificaciones más elaborado
        const notification = document.createElement('div');
        notification.className = 'error-notification';
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-exclamation-circle"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 5000);
    }

    closeModals() {
        document.getElementById('password-modal').style.display = 'none';
        document.getElementById('success-modal').style.display = 'none';
    }
}

// Funciones globales para los botones
function nextStep(step) {
    reservationManager.nextStep(step);
}

function prevStep(step) {
    reservationManager.prevStep(step);
}

function showPasswordModal() {
    reservationManager.showPasswordModal();
}

function closePasswordModal() {
    reservationManager.closePasswordModal();
}

function redirectToConfirmation() {
    if (reservationManager.reservationId) {
        window.location.href = `confirmacion-reserva.php?id=${reservationManager.reservationId}`;
    } else {
        window.location.href = 'mis-reservas.php';
    }
}

// Inicializar cuando el DOM esté listo
let reservationManager;
document.addEventListener('DOMContentLoaded', function() {
    reservationManager = new ReservationManager();
});