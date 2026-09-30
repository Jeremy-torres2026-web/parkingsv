document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const logoutBtn = document.querySelector('[data-logout]');
    const logoutModal = document.getElementById('logoutModal');
    const editVehiclesBtn = document.getElementById('editVehiclesBtn');
    const vehiclesModal = document.getElementById('vehiclesModal');
    const closeModalBtns = document.querySelectorAll('.close-modal, .btn-cancel');
    const editPfpBtn = document.getElementById('editPfpBtn');
    const profilePicture = document.getElementById('profilePicture');
    const confirmLogout = document.getElementById('confirmLogout');
    const saveVehiclesBtn = document.getElementById('saveVehiclesBtn');
    
    // Modales adicionales
    const pfpModal = document.getElementById('pfpModal');
    const uploadOption = document.getElementById('uploadOption');
    const cameraOption = document.getElementById('cameraOption');
    const cameraPreview = document.getElementById('cameraPreview');
    const cameraFeed = document.getElementById('cameraFeed');
    const captureBtn = document.getElementById('captureBtn');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    const retakeBtn = document.getElementById('retakeBtn');
    const savePfpBtn = document.getElementById('savePfpBtn');
    const pfpForm = document.getElementById('pfpForm');
    const pfpFileInput = document.getElementById('pfpFileInput');
    const imageDataInput = document.getElementById('imageDataInput');
    
    let stream = null;

    // =============================================
    // MANEJADORES DE EVENTOS PRINCIPALES
    // =============================================

    // Manejo del modal de logout
    if (logoutBtn && logoutModal) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openModal(logoutModal);
        });
    }

    // Confirmar logout
    if (confirmLogout) {
        confirmLogout.addEventListener('click', function() {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=logout'
            })
            .then(() => {
                window.location.href = '/crud-php2/index.php';
            })
            .catch(error => {
                console.error('Error al cerrar sesión:', error);
                showNotification('Error al cerrar sesión', 'error');
            });
        });
    }

    // Manejo del modal de edición de vehículos
    if (editVehiclesBtn && vehiclesModal) {
        editVehiclesBtn.addEventListener('click', function() {
            openModal(vehiclesModal);
        });
    }

    // Manejo del botón para cambiar foto de perfil
    if (editPfpBtn && pfpModal) {
        editPfpBtn.addEventListener('click', function() {
            // Resetear el modal cada vez que se abre
            document.querySelector('.pfp-options').style.display = 'flex';
            cameraPreview.style.display = 'none';
            imagePreview.style.display = 'none';
            pfpForm.style.display = 'none';
            pfpFileInput.value = '';
            imageDataInput.value = '';
            stopCamera();
            
            openModal(pfpModal);
        });
    }

    // =============================================
    // MANEJO DE FOTO DE PERFIL
    // =============================================

    // Opción: Subir foto
    if (uploadOption) {
        uploadOption.addEventListener('click', function() {
            pfpFileInput.click();
        });
    }

    // Subir archivo seleccionado
    if (pfpFileInput) {
        pfpFileInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const file = e.target.files[0];
                
                // Validar tipo de archivo
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    showNotification('Solo se permiten imágenes (JPEG, PNG, GIF)', 'error');
                    return;
                }
                
                // Validar tamaño
                if (file.size > 2 * 1024 * 1024) { // 2MB
                    showNotification('La imagen debe ser menor a 2MB', 'error');
                    return;
                }
                
                previewImage(file);
            }
        });
    }

    // Opción: Tomar foto con cámara
    if (cameraOption) {
        cameraOption.addEventListener('click', function() {
            document.querySelector('.pfp-options').style.display = 'none';
            cameraPreview.style.display = 'block';
            startCamera();
        });
    }

    // Capturar foto
    if (captureBtn) {
        captureBtn.addEventListener('click', function() {
            const canvas = document.createElement('canvas');
            canvas.width = cameraFeed.videoWidth;
            canvas.height = cameraFeed.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(cameraFeed, 0, 0, canvas.width, canvas.height);
            
            // Convertir a blob para mantener el formato correcto
            canvas.toBlob(function(blob) {
                const file = new File([blob], 'profile_picture.jpg', { type: 'image/jpeg' });
                
                // Crear un nuevo FileList (simulado)
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                pfpFileInput.files = dataTransfer.files;
                
                // Mostrar vista previa
                previewImage(file);
            }, 'image/jpeg', 0.9);
        }, 'image/jpeg');
    }

    // Volver a tomar foto
    if (retakeBtn) {
        retakeBtn.addEventListener('click', function() {
            imagePreview.style.display = 'none';
            cameraPreview.style.display = 'block';
            startCamera();
        });
    }

    // Guardar foto de perfil
    if (savePfpBtn) {
        savePfpBtn.addEventListener('click', function() {
            if (!pfpFileInput.files || pfpFileInput.files.length === 0) {
                showNotification('No hay imagen para guardar', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'update_profile_picture');
            formData.append('profile_picture', pfpFileInput.files[0]);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification('Foto de perfil actualizada', 'success');
                    profilePicture.src = data.newPath + '?' + new Date().getTime(); // Evitar caché
                    closeAllModals();
                    stopCamera();
                } else {
                    showNotification('Error: ' + data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al subir la imagen', 'error');
            });
        });
    }

    // =============================================
    // MANEJO DE VEHÍCULOS
    // =============================================

    if (saveVehiclesBtn) {
        saveVehiclesBtn.addEventListener('click', function() {
            saveVehiclesBtn.disabled = true;
            saveVehiclesBtn.textContent = 'Guardando...';
            
            const selectedVehicles = Array.from(
                document.querySelectorAll('.vehicle-checkbox:checked')
            ).map(checkbox => parseInt(checkbox.value));
            
            const formData = new FormData();
            formData.append('action', 'update_vehicles');
            selectedVehicles.forEach(id => {
                formData.append('vehicles[]', id);
            });

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error(`Respuesta inesperada del servidor: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    updateVehiclesList(data.vehicles);
                    showNotification('Vehículos actualizados correctamente', 'success');
                    closeAllModals();
                } else {
                    throw new Error(data.error || 'Error al actualizar vehículos');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification(error.message || 'Error al comunicarse con el servidor', 'error');
            })
            .finally(() => {
                saveVehiclesBtn.disabled = false;
                saveVehiclesBtn.textContent = 'Guardar';
            });
        });
    }

    // =============================================
    // FUNCIONES AUXILIARES
    // =============================================

    // Manejo de modales
    function setupModalClose() {
        closeModalBtns.forEach(btn => {
            btn.addEventListener('click', closeAllModals);
        });

        [logoutModal, vehiclesModal, pfpModal].forEach(modal => {
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeAllModals();
                    }
                });
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllModals();
            }
        });
    }
    setupModalClose();

    function openModal(modal) {
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.remove('show');
        });
        document.body.style.overflow = '';
        stopCamera();
    }

    // Cámara
    function startCamera() {
        stopCamera(); // Detener cualquier stream existente
        
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
                .then(function(s) {
                    stream = s;
                    cameraFeed.srcObject = stream;
                })
                .catch(function(error) {
                    console.error("Error al acceder a la cámara: ", error);
                    showNotification('No se pudo acceder a la cámara. Asegúrate de permitir el acceso.', 'error');
                    
                    // Volver a mostrar las opciones
                    document.querySelector('.pfp-options').style.display = 'flex';
                    cameraPreview.style.display = 'none';
                });
        } else {
            showNotification('Tu navegador no soporta acceso a la cámara', 'error');
            document.querySelector('.pfp-options').style.display = 'flex';
            cameraPreview.style.display = 'none';
        }
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
    }

    // Vista previa de imagen
    function previewImage(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            document.querySelector('.pfp-options').style.display = 'none';
            cameraPreview.style.display = 'none';
            imagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }

    // Actualizar lista de vehículos en la interfaz
    function updateVehiclesList(vehicles) {
        const vehiclesContainer = document.querySelector('.vehicles-list');
        if (!vehiclesContainer) return;

        // Limpiar contenido existente
        vehiclesContainer.innerHTML = '';
        
        if (vehicles.length === 0) {
            vehiclesContainer.innerHTML = `
                <div class="no-vehicles">
                    <i class="fas fa-info-circle"></i>
                    No has seleccionado ningún vehículo
                </div>
            `;
            return;
        }

        // Crear lista UL
        const ul = document.createElement('ul');
        vehicles.forEach(vehicle => {
            const li = document.createElement('li');
            li.className = 'vehicle-item';
            li.innerHTML = `
                <i class="fas fa-${vehicle.icon}"></i>
                ${vehicle.category_name}
            `;
            ul.appendChild(li);
        });
        
        vehiclesContainer.appendChild(ul);
    }

    // Mostrar notificaciones
    function showNotification(message, type) {
        document.querySelectorAll('.notification').forEach(el => el.remove());
        
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

        // =============================================
    // ACTUALIZACIÓN DE UBICACIÓN
    // =============================================
    
    const updateLocationBtn = document.getElementById('updateLocationBtn');
    if (updateLocationBtn) {
        updateLocationBtn.addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    position => {
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;
                        
                        fetch(window.location.href, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=update_location&latitude=${latitude}&longitude=${longitude}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification('Ubicación actualizada correctamente', 'success');
                                
                                // Actualizar la vista sin recargar toda la página
                                const locationText = document.querySelector('.location-text');
                                if (locationText) {
                                    locationText.textContent = `Lat: ${latitude}, Long: ${longitude}`;
                                }
                            } else {
                                showNotification('Error: ' + data.error, 'error');
                            }
                        })
                        .catch(error => {
                            showNotification('Error en la comunicación: ' + error.message, 'error');
                        });
                    },
                    error => {
                        let errorMessage = 'Error al obtener ubicación: ';
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage += 'Permiso denegado';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage += 'Ubicación no disponible';
                                break;
                            case error.TIMEOUT:
                                errorMessage += 'Tiempo de espera agotado';
                                break;
                            default:
                                errorMessage += error.message;
                        }
                        showNotification(errorMessage, 'error');
                    }
                );
            } else {
                showNotification('Tu navegador no soporta geolocalización', 'error');
            }
        });
    }

  // =============================================
// MANEJO DE ESPECIFICACIONES
// =============================================

// Elementos del DOM para especificaciones
const editSpecsBtn = document.getElementById('editSpecsBtn');
const specsModal = document.getElementById('specsModal');
const saveSpecsBtn = document.getElementById('saveSpecsBtn');

// Manejo del modal de edición de especificaciones
if (editSpecsBtn && specsModal) {
    editSpecsBtn.addEventListener('click', function() {
        openModal(specsModal);
    });
}

// Toggle de checkboxes para mostrar/ocultar inputs de valor
document.querySelectorAll('.spec-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const optionCard = this.closest('.spec-option-card');
        const hasValue = optionCard.dataset.hasValue === 'true';
        const valueInput = optionCard.querySelector('.spec-value-input');
        
        if (hasValue && valueInput) {
            valueInput.style.display = this.checked ? 'block' : 'none';
            
            // Si se desactiva, limpiar el input
            if (!this.checked) {
                valueInput.querySelector('input').value = '';
            }
        }
    });
});

// Guardar especificaciones
if (saveSpecsBtn) {
    saveSpecsBtn.addEventListener('click', function() {
        saveSpecsBtn.disabled = true;
        saveSpecsBtn.textContent = 'Guardando...';
        
        const specifications = [];
        
        document.querySelectorAll('.spec-option-card').forEach(card => {
            const specId = card.dataset.specId;
            const checkbox = card.querySelector('.spec-checkbox');
            const hasValue = card.dataset.hasValue === 'true';
            
            if (checkbox.checked) {
                const specData = {
                    id: parseInt(specId)
                };
                
                if (hasValue) {
                    const input = card.querySelector('.spec-input');
                    specData.value = input.value.trim();
                }
                
                specifications.push(specData);
            }
        });
        
        const formData = new FormData();
        formData.append('action', 'update_specifications');
        formData.append('specifications', JSON.stringify(specifications));

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new Error(`Respuesta inesperada del servidor: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showNotification('Especificaciones actualizadas correctamente', 'success');
                // Cerrar el modal inmediatamente
                closeAllModals();
                // Recargar después de un breve delay para que se vea la notificación
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                throw new Error(data.error || 'Error al actualizar especificaciones');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification(error.message || 'Error al comunicarse con el servidor', 'error');
        })
        .finally(() => {
            saveSpecsBtn.disabled = false;
            saveSpecsBtn.textContent = 'Guardar';
        });
    });
}

// Añadir el modal de especificaciones a la lista de modales para el cierre
if (specsModal) {
    // Añadir evento de clic fuera del modal para cerrarlo
    specsModal.addEventListener('click', function(e) {
        if (e.target === specsModal) {
            closeAllModals();
        }
    });
    
    // Añadir botones de cerrar
    const closeSpecsBtns = specsModal.querySelectorAll('.close-modal, .btn-cancel');
    closeSpecsBtns.forEach(btn => {
        btn.addEventListener('click', closeAllModals);
    });

    // Mejorar el manejo de los toggles de especificaciones
document.querySelectorAll('.spec-checkbox').forEach(checkbox => {
    // Inicializar estado al cargar
    const optionCard = checkbox.closest('.spec-option-card');
    const hasValue = optionCard.dataset.hasValue === 'true';
    const valueInput = optionCard.querySelector('.spec-value-input');
    
    if (hasValue && valueInput) {
        valueInput.style.display = checkbox.checked ? 'block' : 'none';
    }
    
    // Agregar evento change
    checkbox.addEventListener('change', function() {
        if (hasValue && valueInput) {
            valueInput.style.display = this.checked ? 'block' : 'none';
            
            // Si se desactiva, limpiar el input
            if (!this.checked) {
                valueInput.querySelector('input').value = '';
            } else {
                // Si se activa, enfocar el input
                setTimeout(() => {
                    valueInput.querySelector('input').focus();
                }, 100);
            }
        }
        
        // Alternar clase active para estilos
        if (this.checked) {
            optionCard.classList.add('active');
        } else {
            optionCard.classList.remove('active');
        }
    });
    
    // Inicializar clase active
    if (checkbox.checked) {
        optionCard.classList.add('active');
    }
});
}

});

// Actualizar ubicación
document.getElementById('updateLocationBtn')?.addEventListener('click', function() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            position => {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_location&latitude=${latitude}&longitude=${longitude}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Ubicación actualizada correctamente');
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            },
            error => {
                alert('Error al obtener ubicación: ' + error.message);
            }
        );
    } else {
        alert('Tu navegador no soporta geolocalización');
    }
});