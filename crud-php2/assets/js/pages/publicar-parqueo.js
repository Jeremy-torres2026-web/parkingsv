// publicar-parqueo.js - Versión mejorada con mayor funcionalidad y corrección en modal de tarifa y horario extendido
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    const form = document.getElementById('parkingForm');
    const imageInput = document.getElementById('parking-images');
    const preview = document.getElementById('image-preview');
    const fileCount = document.getElementById('file-count');
    const removeAllBtn = document.getElementById('remove-all-images');
    const maxImages = 8;
    const maxFileSize = 5 * 1024 * 1024; // 5MB
    const validImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    let currentMainImageIndex = -1;

    // ============== FUNCIONES DE UTILIDAD ==============
    const showError = (field, message) => {
        field.classList.add('input-error');
        if (!field.nextElementSibling?.classList.contains('error-message')) {
            const errorMsg = document.createElement('div');
            errorMsg.className = 'error-message';
            errorMsg.textContent = message;
            field.parentNode.insertBefore(errorMsg, field.nextSibling);
        }
    };

    const removeError = (field) => {
        field.classList.remove('input-error');
        const errorMsg = field.nextElementSibling;
        if (errorMsg?.classList.contains('error-message')) {
            errorMsg.remove();
        }
    };

    const showToast = (message, type = 'success', duration = 3000) => {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }, 100);
    };

    const debounce = (func, delay) => {
        let timeoutId;
        return function(...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    };

    // ============== VALIDACIÓN DEL FORMULARIO MEJORADA ==============
    if (form) {
        // Validación en tiempo real con debounce
        form.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('input', debounce(() => {
                if (field.value.trim()) {
                    removeError(field);

                    // Validación específica para campos numéricos
                    if (field.type === 'number' && field.min && parseFloat(field.value) < parseFloat(field.min)) {
                        showError(field, `El valor mínimo permitido es ${field.min}`);
                    }

                    // Validación específica para emails
                    if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
                        showError(field, 'Por favor ingrese un email válido');
                    }
                }
            }, 500));
        });

        // Validación al enviar el formulario
        form.addEventListener('submit', function(e) {
            let isValid = true;
            let firstError = null;

            // Validar campos requeridos visibles
            document.querySelectorAll('[required]:not([style*="display: none"])').forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    showError(field, 'Este campo es obligatorio');
                    if (!firstError) firstError = field;
                }
            });

            // Validación personalizada para imágenes
            if (imageInput && (!imageInput.files || imageInput.files.length === 0)) {
                isValid = false;
                showToast('Debe subir al menos una imagen', 'error');
                if (!firstError) firstError = imageInput;
            }

            // Validar que se haya seleccionado una categoría
            const categoryInput = document.getElementById('selectedCategory');
            if (categoryInput && !categoryInput.value) {
                isValid = false;
                showToast('Debe seleccionar una categoría', 'error');
                if (!firstError) firstError = document.getElementById('categoryBtn');
            }

            // Validar campos en modales
            const modalRequiredFields = [
                {name: 'capacidad_general', message: 'La capacidad general es requerida'},
                {name: 'municipio', message: 'Debe seleccionar un municipio'},
                {name: 'direccion', message: 'La dirección es requerida'}
            ];

            modalRequiredFields.forEach(({name, message}) => {
                const field = form.querySelector(`[name="${name}"]`);
                if (field && !field.value.trim()) {
                    isValid = false;
                    if (!firstError) firstError = field;
                    showToast(message, 'error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                firstError?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError?.focus();
                showToast('Por favor complete todos los campos requeridos', 'error');
            } else {
                showToast('Formulario enviado correctamente', 'success');
            }
        });
    }

    // ============== SUBIDA DE IMÁGENES MEJORADA ==============
    const updateImagePreview = () => {
        if (!preview) return;

        preview.innerHTML = '';

        if (!imageInput?.files?.length) {
            if (fileCount) fileCount.textContent = 'Ningún archivo seleccionado';
            preview.innerHTML = '<div class="no-images"><i class="fas fa-image"></i><p>No hay imágenes seleccionadas</p></div>';
            if (removeAllBtn) removeAllBtn.style.display = 'none';
            currentMainImageIndex = -1;
            document.querySelector('input[name="imagen_principal"]')?.remove();
            return;
        }

        if (fileCount) {
            fileCount.textContent = `${imageInput.files.length} archivo${imageInput.files.length > 1 ? 's' : ''} seleccionado${imageInput.files.length > 1 ? 's' : ''}`;
        }

        if (removeAllBtn) removeAllBtn.style.display = 'inline-block';

        Array.from(imageInput.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const imgContainer = document.createElement('div');
                imgContainer.className = 'image-preview-item';

                // Destacar imagen principal si existe
                if (index === currentMainImageIndex) {
                    imgContainer.classList.add('main-image');
                }

                imgContainer.innerHTML = `
                    <img src="${e.target.result}" alt="Previsualización ${index + 1}">
                    <div class="image-actions">
                        <button type="button" class="star-btn ${index === currentMainImageIndex ? 'active' : ''}" 
                                data-index="${index}" title="Seleccionar como imagen principal">
                            <i class="${index === currentMainImageIndex ? 'fas' : 'far'} fa-star"></i>
                        </button>
                        <button type="button" class="remove-image-btn" data-index="${index}" title="Eliminar imagen">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;

                preview.appendChild(imgContainer);

                // Configurar eventos para los botones
                const starBtn = imgContainer.querySelector('.star-btn');
                const removeBtn = imgContainer.querySelector('.remove-image-btn');

                starBtn.addEventListener('click', function() {
                    currentMainImageIndex = parseInt(this.dataset.index);
                    updateImagePreview();

                    // Actualizar imagen principal en el formulario
                    document.querySelector('input[name="imagen_principal"]')?.remove();

                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'imagen_principal';
                    hiddenInput.value = currentMainImageIndex;
                    form.appendChild(hiddenInput);

                    showToast('Imagen principal seleccionada', 'success');
                });

                removeBtn.addEventListener('click', function() {
                    const indexToRemove = parseInt(this.dataset.index);
                    const files = Array.from(imageInput.files);

                    // Ajustar el índice de la imagen principal si es necesario
                    if (currentMainImageIndex === indexToRemove) {
                        currentMainImageIndex = -1;
                    } else if (currentMainImageIndex > indexToRemove) {
                        currentMainImageIndex--;
                    }

                    files.splice(indexToRemove, 1);

                    const dataTransfer = new DataTransfer();
                    files.forEach(file => dataTransfer.items.add(file));
                    imageInput.files = dataTransfer.files;

                    updateImagePreview();
                    showToast('Imagen eliminada', 'info');
                });
            };
            reader.readAsDataURL(file);
        });
    };

    if (imageInput) {
        // Drag and drop para imágenes
        const dropArea = imageInput.closest('.image-upload-container') || preview;

        if (dropArea) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, unhighlight, false);
            });

            function highlight() {
                dropArea.classList.add('highlight');
            }

            function unhighlight() {
                dropArea.classList.remove('highlight');
            }

            dropArea.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;

                if (files.length) {
                    // Combinar archivos existentes con nuevos (hasta el máximo permitido)
                    const existingFiles = imageInput.files ? Array.from(imageInput.files) : [];
                    const newFiles = Array.from(files).slice(0, maxImages - existingFiles.length);

                    if (newFiles.length < files.length) {
                        showToast(`Solo puedes subir un máximo de ${maxImages} imágenes`, 'warning');
                    }

                    const allFiles = [...existingFiles, ...newFiles];
                    const dataTransfer = new DataTransfer();

                    allFiles.forEach(file => {
                        if (validImageTypes.includes(file.type) && file.size <= maxFileSize) {
                            dataTransfer.items.add(file);
                        } else if (!validImageTypes.includes(file.type)) {
                            showToast(`El archivo ${file.name} no es una imagen válida`, 'error');
                        } else if (file.size > maxFileSize) {
                            showToast(`La imagen ${file.name} es demasiado grande (máximo 5MB)`, 'error');
                        }
                    });

                    imageInput.files = dataTransfer.files;
                    updateImagePreview();
                }
            }
        }

        imageInput.addEventListener('change', function() {
            if (this.files.length > maxImages) {
                showToast(`Solo puedes subir un máximo de ${maxImages} imágenes`, 'warning');
                this.value = '';
                updateImagePreview();
                return;
            }

            // Validar cada archivo
            let hasInvalidFiles = false;
            Array.from(this.files).forEach(file => {
                if (!validImageTypes.includes(file.type)) {
                    hasInvalidFiles = true;
                    showToast(`El archivo ${file.name} no es una imagen válida`, 'error');
                } else if (file.size > maxFileSize) {
                    hasInvalidFiles = true;
                    showToast(`La imagen "${file.name}" es demasiado grande (máximo 5MB)`, 'error');
                }
            });

            if (hasInvalidFiles) {
                // Mantener solo los archivos válidos
                const validFiles = Array.from(this.files).filter(file =>
                    validImageTypes.includes(file.type) && file.size <= maxFileSize
                );

                const dataTransfer = new DataTransfer();
                validFiles.forEach(file => dataTransfer.items.add(file));
                this.files = dataTransfer.files;

                if (validFiles.length === 0) {
                    this.value = '';
                }
            }

            updateImagePreview();
        });
    }

    if (removeAllBtn) {
        removeAllBtn.addEventListener('click', () => {
            if (imageInput.files && imageInput.files.length > 0) {
                if (confirm('¿Estás seguro de que deseas eliminar todas las imágenes?')) {
                    imageInput.value = '';
                    currentMainImageIndex = -1;
                    document.querySelector('input[name="imagen_principal"]')?.remove();
                    updateImagePreview();
                    showToast('Todas las imágenes han sido eliminadas', 'info');
                }
            } else {
                showToast('No hay imágenes para eliminar', 'warning');
            }
        });
    }

    updateImagePreview();

    // ============== SISTEMA DE MODALES MEJORADO ==============
    const modalSystem = {
        modals: {
            category: { openBtn: 'categoryBtn', modal: 'categoryModal' },
            capacity: { openBtn: 'capacityBtn', modal: 'capacityModal' },
            location: { openBtn: 'locationBtn', modal: 'locationModal' },
            schedule: { openBtn: 'scheduleBtn', modal: 'scheduleModal' },
            rate: { openBtn: 'addRateBtn', modal: 'rateModal' }
        },

        init() {
            Object.values(this.modals).forEach(({openBtn, modal}) => {
                const openButton = document.getElementById(openBtn);
                const modalElement = document.getElementById(modal);

                if (openButton && modalElement) {
                    openButton.addEventListener('click', () => this.openModal(modal));

                    modalElement.querySelectorAll('.close, .btn-save').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            if (e.target.classList.contains('btn-save')) {
                                this.validateModal(modal);
                            } else {
                                this.closeModal(modal);
                            }
                        });
                    });

                    modalElement.addEventListener('click', (e) => {
                        if (e.target === modalElement) this.closeModal(modal);
                    });

                    // Cerrar con ESC
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape' && modalElement.style.display === 'block') {
                            this.closeModal(modal);
                        }
                    });
                }
            });
        },

        openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
                document.body.style.paddingRight = `${window.innerWidth - document.documentElement.clientWidth}px`;
                modal.querySelector('input, select, textarea')?.focus();

                // Inicializar contenido del modal si es necesario
                if (modalId === 'scheduleModal') {
                    initScheduleModal();
                } else if (modalId === 'rateModal') {
                    resetRateModal();
                    initRateModalSelectables();
                }
            }
        },

        closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                document.body.style.paddingRight = '0';
            }
        },

        validateModal(modalId) {
            let isValid = true;
            const modal = document.getElementById(modalId);

            if (!modal) return false;

            // Validación específica para cada modal
            switch(modalId) {
                case 'capacityModal':
                    if (!document.querySelector('#capacityModal input[name="capacidad_general"]').value) {
                        isValid = false;
                        showToast('La capacidad general es requerida', 'error');
                    }
                    break;

                case 'locationModal':
                    if (!departamentoSelect.value) {
                        isValid = false;
                        showToast('Debe seleccionar un departamento', 'error');
                    }
                    if (!municipioSelect.value) {
                        isValid = false;
                        showToast('Debe seleccionar un municipio', 'error');
                    }
                    if (!document.querySelector('#locationModal select[name="municipio"]').value) {
                        isValid = false;
                        showToast('Debe seleccionar un municipio', 'error');
                    }
                    if (!document.querySelector('#locationModal input[name="direccion"]').value) {
                        isValid = false;
                        showToast('La dirección es requerida', 'error');
                    }
                    break;

                case 'rateModal':
                    // Validación se hace con guardar tarifa (saveRateBtn)
                    break;
            }

            if (isValid) {
                this.closeModal(modalId);
                showToast('Cambios guardados correctamente', 'success');

                // Actualizar UI según el modal
                if (modalId === 'categoryModal') {
                    updateCategoryButton();
                }
            }

            return isValid;
        }
    };

    modalSystem.init();

    // ============== MODAL DE TARIFA: Selección y reinicio ==============
    function resetRateModal() {
        // Limpia todas las selecciones y valores del modal de tarifas
        document.querySelectorAll('.rate-vehicle-types .vehicle-type-option.selected').forEach(o => o.classList.remove('selected'));
        document.getElementById('selectedRateVehicleType').value = '';

        document.querySelectorAll('.rate-types .tarifa-type-option.selected').forEach(o => o.classList.remove('selected'));
        document.getElementById('selectedTarifaType').value = '';

        document.querySelectorAll('.time-units-grid .time-unit-option.selected').forEach(o => o.classList.remove('selected'));
        document.getElementById('selectedTimeUnit').value = '';

        document.querySelectorAll('.days-grid .day-option.selected').forEach(o => o.classList.remove('selected'));
        document.getElementById('selectedDays').value = '';

        document.getElementById('tarifaPriceOption').checked = true;
        document.getElementById('tarifaFreeOption').checked = false;
        document.querySelector('input[name="tarifa_precio[]"]').value = '';
        document.querySelector('input[name="tarifa_validez_inicio[]"]').value = '';
        document.querySelector('input[name="tarifa_validez_fin[]"]').value = '';
        document.querySelector('.rate-price-group').style.display = '';
    }

    // Inicializa eventos de selección de las opciones de tarifa
    function initRateModalSelectables() {
        // Tipo de Vehículo
        document.querySelectorAll('.rate-vehicle-types .vehicle-type-option').forEach(opt => {
            opt.onclick = function() {
                document.querySelectorAll('.rate-vehicle-types .vehicle-type-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('selectedRateVehicleType').value = this.dataset.value;
            };
        });
        // Tipo de Tarifa
        document.querySelectorAll('.rate-types .tarifa-type-option').forEach(opt => {
            opt.onclick = function() {
                document.querySelectorAll('.rate-types .tarifa-type-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('selectedTarifaType').value = this.dataset.value;
            };
        });
        // Unidad de tiempo
        document.querySelectorAll('.time-units-grid .time-unit-option').forEach(opt => {
            opt.onclick = function() {
                document.querySelectorAll('.time-units-grid .time-unit-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('selectedTimeUnit').value = this.dataset.value;
            };
        });
        // Días
        document.querySelectorAll('.days-grid .day-option').forEach(opt => {
            opt.onclick = function() {
                document.querySelectorAll('.days-grid .day-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('selectedDays').value = this.dataset.value;
            };
        });
        // Gratis o precio
        const tarifaFreeOption = document.getElementById('tarifaFreeOption');
        const tarifaPriceOption = document.getElementById('tarifaPriceOption');
        const ratePriceGroup = document.querySelector('.rate-price-group');
        if (tarifaFreeOption && tarifaPriceOption && ratePriceGroup) {
            tarifaFreeOption.addEventListener('change', function() {
                if (this.checked) ratePriceGroup.style.display = 'none';
            });
            tarifaPriceOption.addEventListener('change', function() {
                if (this.checked) ratePriceGroup.style.display = '';
            });
            if (tarifaFreeOption.checked) ratePriceGroup.style.display = 'none';
            else ratePriceGroup.style.display = '';
        }
    }

    // ============== SELECCIÓN DE CATEGORÍA MEJORADA ==============
    document.querySelectorAll('.category-item').forEach(item => {
        item.addEventListener('click', function() {
            // Quitar selección previa
            document.querySelectorAll('.category-item.selected').forEach(sel => sel.classList.remove('selected'));
            this.classList.add('selected');
        });
    });

    // Guardar la categoría seleccionada al dar "Confirmar"
    const categoryConfirmBtn = document.querySelector('#categoryModal .btn-save');
    if (categoryConfirmBtn) {
        categoryConfirmBtn.addEventListener('click', function() {
            const selected = document.querySelector('.category-item.selected');
            if (!selected) {
                alert('Selecciona una categoría');
                return;
            }
            document.getElementById('selectedCategory').value = selected.dataset.id;
            // Opcional: cambia el texto del botón principal
            const categoryBtn = document.getElementById('categoryBtn');
            if (categoryBtn) {
                const categoryName = selected.querySelector('.category-info h4').textContent;
                categoryBtn.innerHTML = `<i class="fas fa-tag"></i> ${categoryName}`;
                categoryBtn.classList.add('has-selection');
            }
            // Cerrar el modal
            document.getElementById('categoryModal').style.display = 'none';
        });
    }

    // ======================== MODALES DE SERVICIOS Y RESTRICCIONES ===========================
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    // Servicios Editar
    const editServicesBtn = document.getElementById('editServicesBtn');
    const servicesModal = document.getElementById('servicesModal');
    const saveServicesBtn = document.getElementById('saveServicesBtn');
    const servicesGrid = document.getElementById('servicesGrid');
    if (editServicesBtn && servicesModal && servicesGrid) {
        editServicesBtn.addEventListener('click', () => openModal('servicesModal'));
        servicesModal.querySelector('.close').addEventListener('click', () => closeModal('servicesModal'));
        saveServicesBtn.addEventListener('click', () => {
            const selected = [];
            servicesGrid.querySelectorAll('.service-item.active').forEach(item => selected.push(item.dataset.id));
            document.getElementById('selectedServices').value = selected.join(',');
            updateSummary('services-summary', selected, 'servicio');
            closeModal('servicesModal');
        });
        servicesGrid.querySelectorAll('.service-item').forEach(item => {
            item.addEventListener('click', function() {
                this.classList.toggle('active');
            });
        });
    }

    // Restricciones Editar
    const editRestrictionsBtn = document.getElementById('editRestrictionsBtn');
    const restrictionsModal = document.getElementById('restrictionsModal');
    const saveRestrictionsBtn = document.getElementById('saveRestrictionsBtn');
    const restrictionsGrid = document.getElementById('restrictionsGrid');
    if (editRestrictionsBtn && restrictionsModal && restrictionsGrid) {
        editRestrictionsBtn.addEventListener('click', () => openModal('restrictionsModal'));
        restrictionsModal.querySelector('.close').addEventListener('click', () => closeModal('restrictionsModal'));
        saveRestrictionsBtn.addEventListener('click', () => {
            const selected = [];
            restrictionsGrid.querySelectorAll('.restriction-item.active').forEach(item => selected.push(item.dataset.id));
            document.getElementById('selectedRestrictions').value = selected.join(',');
            updateSummary('restrictions-summary', selected, 'restricción');
            closeModal('restrictionsModal');
        });
        restrictionsGrid.querySelectorAll('.restriction-item').forEach(item => {
            item.addEventListener('click', function() {
                this.classList.toggle('active');
            });
        });
    }

    function updateSummary(summaryId, selected, label) {
        const summary = document.getElementById(summaryId);
        if (summary) {
            summary.innerHTML = selected.length
                ? `<div class="summary-item">${selected.length} ${label}${selected.length>1?'es':''} seleccionad${selected.length>1?'as':'a'}</div>`
                : `<p class="no-info">No se han seleccionado ${label}s</p>`;
        }
    }

    // ============== HORARIOS MEJORADOS (CORREGIDO) ==============
    const initScheduleModal = () => {
        const days = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];

        days.forEach(day => {
            // Mostrar u ocultar los slots según el checkbox
            const checkbox = document.querySelector(`input[name="dias_abierto[]"][value="${day}"]`);
            const timeSlotsContainer = checkbox?.closest('.day-schedule')?.querySelector('.time-slots');
            if (checkbox && timeSlotsContainer) {
                timeSlotsContainer.style.display = checkbox.checked ? 'block' : 'none';
                checkbox.addEventListener('change', function() {
                    timeSlotsContainer.style.display = this.checked ? 'block' : 'none';
                });
            }

            // Botón "+" dinámico
            const plusButtons = (timeSlotsContainer ? timeSlotsContainer.querySelectorAll('.add-slot-btn') : []);
            plusButtons.forEach(btn => {
                btn.onclick = function() {
                    addTimeSlot(day);
                };
            });

            // Botón "-" dinámico
            const minusButtons = (timeSlotsContainer ? timeSlotsContainer.querySelectorAll('.remove-slot-btn') : []);
            minusButtons.forEach(btn => {
                btn.onclick = function() {
                    removeTimeSlot(this);
                };
            });
        });
    };

    // FUNCIONES GLOBALES DISPONIBLES PARA EL HTML
    window.addTimeSlot = function(day, openTime = '', closeTime = '') {
        // Busca el contenedor adecuado
        const container = document.querySelector(`.day-schedule input[value="${day}"]`)?.closest('.day-schedule')?.querySelector('.time-slots');
        if (!container) return;

        // Crea el slot
        const slotDiv = document.createElement('div');
        slotDiv.className = 'time-slot';

        slotDiv.innerHTML = `
            <div class="time-input-group">
                <i class="fas fa-door-open"></i>
                <input type="time" name="apertura_${day}[]" value="${openTime}">
            </div>
            <div class="time-input-group">
                <i class="fas fa-door-closed"></i>
                <input type="time" name="cierre_${day}[]" value="${closeTime}">
            </div>
            <button type="button" class="add-slot-btn" title="Agregar horario"><i class="fas fa-plus"></i></button>
            <button type="button" class="remove-slot-btn" title="Eliminar horario"><i class="fas fa-minus"></i></button>
        `;
        container.appendChild(slotDiv);

        // Asignar eventos a los botones recién agregados
        slotDiv.querySelector('.add-slot-btn').onclick = function() {
            addTimeSlot(day);
        };
        slotDiv.querySelector('.remove-slot-btn').onclick = function() {
            removeTimeSlot(this);
        };
    };

    window.removeTimeSlot = function(button) {
        const slot = button.closest('.time-slot');
        const container = slot.parentNode;
        // Evita borrar el último slot de ese día
        if (container.querySelectorAll('.time-slot').length > 1) {
            slot.remove();
        } else {
            showToast('Debe haber al menos un horario', 'warning');
        }
    };

    // ===================== GUARDAR TARIFA =========================
    const saveRateBtn = document.getElementById('saveRateBtn');
    if (saveRateBtn) {
        saveRateBtn.addEventListener('click', function() {
            const vehicleType = document.getElementById('selectedRateVehicleType').value;
            const tarifaType = document.getElementById('selectedTarifaType').value;
            const timeUnit = document.getElementById('selectedTimeUnit').value;
            const days = document.getElementById('selectedDays').value;
            const isFree = document.getElementById('tarifaFreeOption').checked;
            const precio = document.querySelector('input[name="tarifa_precio[]"]').value;
            const form = document.getElementById('parkingForm');
            const ratesContainer = document.getElementById('rates-container');

            if (!vehicleType || !tarifaType || !timeUnit || !days || (!isFree && !precio)) {
                showToast('Por favor complete todos los campos requeridos de la tarifa', 'error');
                return;
            }
            // Crear el item visual
            const rateDiv = document.createElement('div');
            rateDiv.className = 'rate-item';
            // Asigna un id único para encontrar los inputs después
            rateDiv.dataset.rateId = Math.random().toString(36).substring(2, 10);

            rateDiv.innerHTML = `
                <div class="rate-icon">
                    <i class="fas fa-tag"></i>
                </div>
                <div class="rate-details">
                    <h4>${document.querySelector('.rate-vehicle-types .vehicle-type-option.selected span').textContent}</h4>
                    <p>${isFree ? 'Gratis' : `$${precio} por ${timeUnit}`}</p>
                    <small>Tipo: ${tarifaType} | Aplica: ${days}</small>
                </div>
                <button type="button" class="rate-remove"><i class="fas fa-times"></i></button>
            `;
            // Remove button functionality: elimina también los inputs hidden asociados
            rateDiv.querySelector('.rate-remove').onclick = function() {
                // Elimina los inputs hidden de esta tarifa
                form.querySelectorAll(`[data-rate-id="${rateDiv.dataset.rateId}"]`).forEach(i => i.remove());
                ratesContainer.removeChild(rateDiv);
            };
            ratesContainer.appendChild(rateDiv);

            // Crear y agregar inputs hidden al formulario para esta tarifa
            function addHidden(name, value) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                // Para poder borrarlos después
                input.setAttribute('data-rate-id', rateDiv.dataset.rateId);
                form.appendChild(input);
            }
            addHidden('tarifa_vehicle_type[]', vehicleType);
            addHidden('tarifa_tipo[]', tarifaType);
            addHidden('tarifa_unidad[]', timeUnit);
            addHidden('tarifa_dias[]', days);
            addHidden('tarifa_precio[]', isFree ? 'Gratis' : precio);
            addHidden('tarifa_validez_inicio[]', document.querySelector('input[name="tarifa_validez_inicio[]"]').value || '');
            addHidden('tarifa_validez_fin[]', document.querySelector('input[name="tarifa_validez_fin[]"]').value || '');

            // Limpiar selección del modal
            resetRateModal();
            // Cierra el modal
            closeModal('rateModal');
        });
    }

    // ================ Contacto del Parqueo VALIDACIÓN EXTRA ============
    if (form) {
        form.addEventListener('submit', function(e) {
            const contactoNombre = form.querySelector('input[name="contacto_nombre"]');
            const contactoTelefono = form.querySelector('input[name="contacto_telefono"]');
            const contactoEmail = form.querySelector('input[name="contacto_email"]');
            let valid = true;

            if (!contactoNombre.value.trim()) {
                showError(contactoNombre, 'Nombre de contacto es requerido');
                valid = false;
            }
            if (!contactoTelefono.value.trim()) {
                showError(contactoTelefono, 'Teléfono de contacto es requerido');
                valid = false;
            }
            if (!contactoEmail.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(contactoEmail.value)) {
                showError(contactoEmail, 'Email de contacto no válido');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                showToast('Por favor complete los datos de contacto correctamente', 'error');
                contactoNombre.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    // Manejar checkbox 24/7
    const is247Checkbox = document.getElementById('is_24_7');
    if (is247Checkbox) {
        is247Checkbox.addEventListener('change', function() {
            const scheduleContainer = document.getElementById('schedule-container');
            if (this.checked) {
                scheduleContainer.style.display = 'none';
            } else {
                scheduleContainer.style.display = 'block';
            }
        });
        
        // Inicializar estado al cargar
        if (is247Checkbox.checked) {
            document.getElementById('schedule-container').style.display = 'none';
        }
    }

    // Agregar esto en el DOMContentLoaded
const departamentoSelect = document.getElementById('departamentoSelect');
const municipioSelect = document.getElementById('municipioSelect');

// Actualizar municipios cuando cambie el departamento
if (departamentoSelect && municipioSelect) {
    departamentoSelect.addEventListener('change', function() {
        const deptoSeleccionado = this.value;
        municipioSelect.innerHTML = '<option value="">Seleccione un municipio</option>';
        
        if (deptoSeleccionado && departamentosData[deptoSeleccionado]) {
            departamentosData[deptoSeleccionado].forEach(municipio => {
                const option = document.createElement('option');
                option.value = municipio;
                option.textContent = municipio;
                option.selected = (municipio === municipioPreSeleccionado);
                municipioSelect.appendChild(option);
            });
        }
    });

    // Disparar evento inicial si hay departamento seleccionado
    if (departamentoSelect.value) {
        departamentoSelect.dispatchEvent(new Event('change'));
    }
}
});

// Estilos CSS adicionales para las mejoras
const additionalCSS = `
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    transform: translateX(150%);
    transition: transform 0.3s ease;
    z-index: 10000;
    max-width: 300px;
}
.toast.show {
    transform: translateX(0);
}
.toast-success { background-color: #4CAF50; }
.toast-error { background-color: #E57373; }
.toast-warning { background-color: #FFB74D; }
.toast-info { background-color: #64B5F6; }
.image-preview-item.main-image {
    border: 3px solid var(--amarillo);
    box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.3);
}
.highlight {
    border-color: var(--azul) !important;
    background-color: rgba(124, 204, 255, 0.1) !important;
}
.rate-item {
    display: flex;
    align-items: center;
    padding: 12px;
    background: white;
    border-radius: 10px;
    margin-bottom: 10px;
    box-shadow: var(--sombra);
    transition: all 0.3s ease;
}
.rate-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--sombra-fuerte);
}
.rate-icon {
    margin-right: 15px;
    color: var(--azul);
    font-size: 1.2rem;
}
.rate-info {
    flex: 1;
    display: flex;
    justify-content: space-between;
}
.rate-price {
    font-weight: bold;
    color: var(--verde);
}
.remove-rate-btn {
    background: none;
    border: none;
    color: var(--rojo);
    cursor: pointer;
    margin-left: 15px;
    opacity: 0.7;
    transition: all 0.2s ease;
}
.remove-rate-btn:hover {
    opacity: 1;
    transform: scale(1.1);
}
.no-rates {
    text-align: center;
    padding: 20px;
    color: #777;
}
.no-rates i {
    font-size: 2rem;
    margin-bottom: 10px;
    display: block;
}
.has-selection {
    background: linear-gradient(to right, var(--verde), #5aa35e) !important;
}
@media (max-width: 768px) {
    .toast {
        top: auto;
        bottom: 20px;
        left: 20px;
        right: 20px;
        max-width: none;
    }
}
`;
// Agregar estilos adicionales al documento
const styleElement = document.createElement('style');
styleElement.textContent = additionalCSS;
document.head.appendChild(styleElement);