// 1. Búsqueda en tiempo real
document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM - DEFINIR TODOS AQUÍ
    const filterBtn = document.getElementById('filter-btn');
    const filtersContainer = document.getElementById('filters-container');
    const applyFiltersBtn = document.getElementById('apply-filters');
    const resetFiltersBtn = document.getElementById('reset-filters');
    const departmentFilter = document.getElementById('department-filter');
    const municipalityFilter = document.getElementById('municipality-filter');
    const searchInput = document.getElementById('search-input');
    const priceSlider = document.getElementById('max-price');
    const priceOutput = document.getElementById('current-price');
    const dateFilter = document.getElementById('date-filter');

    // === EFECTO DE HEADER DINÁMICO ===
    const navbar = document.querySelector('.navbar');
    const body = document.body;
    
    if (body.classList.contains('page-parqueos-publicados')) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
        
        // Aplicar estado inicial
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        }
    }

    // 1. Toggle de filtros
    if (filterBtn && filtersContainer) {
        filterBtn.addEventListener('click', function() {
            const isVisible = filtersContainer.style.display === 'block';
            filtersContainer.style.display = isVisible ? 'none' : 'block';
        });
    }

    // 2. Cargar municipios basados en departamento
    if (departmentFilter && municipalityFilter) {
        departmentFilter.addEventListener('change', function() {
            const department = this.value;
            municipalityFilter.disabled = !department;
            
            if (department) {
                fetchMunicipalities(department);
            } else {
                municipalityFilter.innerHTML = '<option value="">Todos los municipios</option>';
            }
        });
    }

    // 3. Controlador de rango de precio
    if (priceSlider && priceOutput) {
        priceSlider.addEventListener('input', function() {
            priceOutput.textContent = `$${parseFloat(this.value).toFixed(2)}`;
        });
    }

    // 4. Manejo de especificaciones del usuario
    document.querySelectorAll('.spec-option').forEach(option => {
        option.addEventListener('click', function(e) {
            if (e.target.tagName !== 'INPUT') {
                const checkbox = this.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
                this.classList.toggle('active', checkbox.checked);
            }
        });
    });

    // 5. Búsqueda en tiempo real
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const parkingCards = document.querySelectorAll('.parking-card');

            parkingCards.forEach(card => {
                const name = card.querySelector('h3').textContent.toLowerCase();
                const location = card.querySelector('.location').textContent.toLowerCase();

                if (name.includes(searchTerm) || location.includes(searchTerm)) {
                    card.closest('.parking-card-container').style.display = 'block';
                } else {
                    card.closest('.parking-card-container').style.display = 'none';
                }
            });
        });
    }

    // 6. Aplicar filtros
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', function() {
            applyFilters();
        });
    }

    // 7. Resetear filtros
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', function() {
            resetFilters();
        });
    }

    // 8. Sistema de favoritos
    const parkingCards = document.querySelectorAll('.parking-card');
    let favorites = JSON.parse(localStorage.getItem('parkingFavorites')) || [];

    parkingCards.forEach(card => {
        const parkingId = card.getAttribute('data-parking-id');
        const saveIcon = card.querySelector('.save-icon');

        if (favorites.includes(parkingId)) {
            saveIcon.classList.add('active');
            saveIcon.querySelector('i').classList.replace('far', 'fas');
        }
    });

    // 9. Evento para clic en tarjetas
    parkingCards.forEach(card => {
        card.addEventListener('click', function(e) {
            if (!e.target.closest('.save-icon')) {
                const parkingId = this.getAttribute('data-parking-id');
                window.location.href = `detalles-parqueo.php?id=${parkingId}`;
            }
        });
    });

    // 10. Evento para guardar/eliminar favoritos
    document.querySelectorAll('.save-icon').forEach(icon => {
        icon.addEventListener('click', function(e) {
            e.stopPropagation();
            const card = this.closest('.parking-card');
            const parkingId = card.getAttribute('data-parking-id');
            const iconElement = this.querySelector('i');
            const isFavorite = this.classList.contains('active');

            if (isFavorite) {
                this.classList.remove('active');
                iconElement.classList.replace('fas', 'far');
                favorites = favorites.filter(id => id !== parkingId);
                showNotification('Parqueo eliminado de favoritos');
            } else {
                this.classList.add('active');
                iconElement.classList.replace('far', 'fas');
                favorites.push(parkingId);
                showNotification('Parqueo guardado en favoritos');
            }

            localStorage.setItem('parkingFavorites', JSON.stringify(favorites));

            const authStatus = document.getElementById('auth-status');
            if (authStatus && authStatus.getAttribute('data-is-logged-in') === 'true') {
                saveFavoriteToDatabase(parkingId, !isFavorite);
            }
        });
    });

    // 11. Cargar valores de filtros desde la URL si existen
    loadFiltersFromURL();

    // 12. Procesar horarios de parqueos
    processAllParkingSchedules();

    // === FUNCIONES ===

    // Función para cargar municipios
    function fetchMunicipalities(department) {
        fetch(`get_municipalities.php?department=${encodeURIComponent(department)}`)
            .then(response => response.json())
            .then(data => {
                let options = '<option value="">Todos los municipios</option>';
                data.forEach(mun => {
                    options += `<option value="${mun}">${mun}</option>`;
                });
                municipalityFilter.innerHTML = options;
                municipalityFilter.disabled = false;
            })
            .catch(error => {
                console.error('Error fetching municipalities:', error);
                municipalityFilter.innerHTML = '<option value="">Error al cargar</option>';
            });
    }

    // Función para aplicar filtros
    function applyFilters() {
        const formData = new FormData(document.getElementById('filters-form'));
        const params = new URLSearchParams();
        
        // Agregar solo los filtros que tienen valor
        for (const [key, value] of formData.entries()) {
            if (value && value !== 'all') {
                // Para arrays (como specs[])
                if (key === 'specs[]') {
                    params.append('specs', value);
                } else {
                    params.append(key, value);
                }
            }
        }
        
        // Redirigir con los parámetros de filtro
        window.location.href = `parqueos-publicados.php?${params.toString()}`;
    }

    // Función para resetear filtros
    function resetFilters() {
        document.getElementById('filters-form').reset();
        if (municipalityFilter) {
            municipalityFilter.innerHTML = '<option value="">Todos los municipios</option>';
            municipalityFilter.disabled = true;
        }
        
        // Resetear clases activas de especificaciones
        document.querySelectorAll('.spec-option').forEach(option => {
            option.classList.remove('active');
        });
        
        // Actualizar el display del precio
        if (priceSlider && priceOutput) {
            priceOutput.textContent = '$50.00';
        }
    }

    // Función para cargar filtros desde URL
    function loadFiltersFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Departamento
        if (urlParams.has('department') && departmentFilter) {
            departmentFilter.value = urlParams.get('department');
            fetchMunicipalities(departmentFilter.value);
        }
        
        // Municipio (debe cargarse después que el departamento)
        setTimeout(() => {
            if (urlParams.has('municipality') && municipalityFilter) {
                municipalityFilter.value = urlParams.get('municipality');
            }
        }, 300);
        
        // Vehículo
        if (urlParams.has('vehicle')) {
            const vehicleValue = urlParams.get('vehicle');
            const vehicleInput = document.querySelector(`input[name="vehicle"][value="${vehicleValue}"]`);
            if (vehicleInput) vehicleInput.checked = true;
        }
        
        // Precio
        if (urlParams.has('max_price') && priceSlider && priceOutput) {
            const maxPrice = urlParams.get('max_price');
            priceSlider.value = maxPrice;
            priceOutput.textContent = `$${parseFloat(maxPrice).toFixed(2)}`;
        }
        
        // Reservable
        if (urlParams.has('reservable')) {
            const reservableValue = urlParams.get('reservable');
            const reservableInput = document.querySelector(`input[name="reservable"][value="${reservableValue}"]`);
            if (reservableInput) reservableInput.checked = true;
        }
        
        // Día
        if (urlParams.has('date') && dateFilter) {
            dateFilter.value = urlParams.get('date');
        }
        
        // Especificaciones
        if (urlParams.has('specs')) {
            const specs = urlParams.getAll('specs');
            specs.forEach(specId => {
                const checkbox = document.querySelector(`input[name="specs[]"][value="${specId}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    checkbox.closest('.spec-option').classList.add('active');
                }
            });
        }
        
        // Favoritos
        if (urlParams.has('favorites')) {
            const favoritesCheckbox = document.querySelector('input[name="favorites"]');
            if (favoritesCheckbox) favoritesCheckbox.checked = true;
        }
    }

    // Función para mostrar notificación
    function showNotification(message) {
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            font-weight: 500;
            animation: fadeIn 0.3s, fadeOut 0.3s 2.7s;
        `;
        
        // Agregar animaciones
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes fadeOut {
                from { opacity: 1; transform: translateY(0); }
                to { opacity: 0; transform: translateY(20px); }
            }
        `;
        document.head.appendChild(style);
        
        document.body.appendChild(notification);
        
        // Eliminar después de 3 segundos
        setTimeout(() => {
            notification.remove();
            style.remove();
        }, 3000);
    }

    // Función para guardar en la base de datos
    function saveFavoriteToDatabase(parkingId, isFavorite) {
        const formData = new FormData();
        formData.append('parking_id', parkingId);
        formData.append('action', isFavorite ? 'add' : 'remove');
        
        fetch('includes/guardar-favorito.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Error al guardar favorito:', data.message);
                // Revertir el cambio visual si falla
                const icon = document.querySelector(`.parking-card[data-parking-id="${parkingId}"] .save-icon`);
                if (icon) {
                    if (isFavorite) {
                        icon.classList.remove('active');
                        icon.querySelector('i').classList.replace('fas', 'far');
                    } else {
                        icon.classList.add('active');
                        icon.querySelector('i').classList.replace('far', 'fas');
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error de conexión:', error);
        });
    }

    // Función para procesar horarios de todos los parqueos
    function processAllParkingSchedules() {
        document.querySelectorAll('.parking-card').forEach(card => {
            const scheduleData = processParkingSchedule(card);
            const intervals = scheduleData.intervals || ["Ver horario"];
            const scheduleText = intervals.join(' y ');
            
            // Actualizar elementos del DOM
            const scheduleSpan = card.querySelector('.schedule-text');
            const statusDiv = card.querySelector('.open-status');
            
            if (scheduleSpan) {
                scheduleSpan.textContent = scheduleText;
            }
            
            if (statusDiv) {
                statusDiv.className = 'open-status'; // Reset clases
                
                // Parqueo 24/7
                if (scheduleData.is24_7) {
                    statusDiv.innerHTML = '<i class="fas fa-infinity"></i> Abierto 24/7';
                    statusDiv.classList.add('status-24-7');
                } 
                // Cierre inminente (menos de 30 minutos)
                else if (scheduleData.closingSoon) {
                    statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ¡Cerrando pronto!';
                    statusDiv.classList.add('status-closing-soon');
                } 
                // Apertura próxima (menos de 60 minutos)
                else if (scheduleData.openingSoon) {
                    statusDiv.innerHTML = '<i class="fas fa-hourglass-start"></i> ¡Abre pronto!';
                    statusDiv.classList.add('status-opening-soon');
                } 
                // Horario normal
                else if (intervals[0] === "Cerrado" || intervals[0] === "Horario no disponible") {
                    statusDiv.innerHTML = '<i class="fas fa-door-closed"></i> Cerrado hoy';
                    statusDiv.classList.add('status-closed-today');
                } else if (scheduleData.open) {
                    statusDiv.innerHTML = '<i class="fas fa-door-open"></i> Abierto ahora';
                    statusDiv.classList.add('status-open');
                } else {
                    statusDiv.innerHTML = '<i class="fas fa-door-closed"></i> ¡Ya Cerró!';
                    statusDiv.classList.add('status-closed-now');
                }
            }
        });
    }

    // Función para procesar horario individual
    function processParkingSchedule(card) {
        try {
            const is24_7 = card.getAttribute('data-is-24-7') === '1';
            
            // Manejo de parqueos 24/7
            if (is24_7) {
                return {
                    intervals: ["¡Abierto siempre!"],
                    open: true,
                    is24_7: true
                };
            }

            const scheduleJSON = card.getAttribute('data-schedule');
            if (!scheduleJSON) return;
            
            const schedule = JSON.parse(scheduleJSON);
            const days = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
            const today = days[new Date().getDay()];
            
            let todaySchedule = schedule[today] || 
                               schedule[today.charAt(0).toUpperCase() + today.slice(1)] || 
                               schedule[today.toUpperCase()];
            
            const slots = todaySchedule ? 
                (Array.isArray(todaySchedule) ? todaySchedule : [todaySchedule]) : 
                [];
            
            const now = new Date();
            const currentMinutes = now.getHours() * 60 + now.getMinutes();
            let isOpen = false;
            let closingSoon = false;
            let openingSoon = false;
            const intervals = [];
            
            slots.forEach(slot => {
                const openTime = slot.apertura || slot.hora_inicio;
                const closeTime = slot.cierre || slot.hora_fin;
                
                if (!openTime || !closeTime || 
                    openTime.toLowerCase() === 'cerrado' || 
                    closeTime.toLowerCase() === 'cerrado') {
                    intervals.push("Cerrado");
                    return;
                }
                
                intervals.push(`${formatHour(openTime)} - ${formatHour(closeTime)}`);
                
                const [openHour, openMinute] = openTime.split(':').map(Number);
                const [closeHour, closeMinute] = closeTime.split(':').map(Number);
                const openTotal = openHour * 60 + openMinute;
                const closeTotal = closeHour * 60 + closeMinute;
                
                // Verificar si está abierto ahora
                if (currentMinutes >= openTotal && currentMinutes < closeTotal) {
                    isOpen = true;
                    
                    // Verificar si está cerca de cerrar (menos de 30 minutos)
                    if ((closeTotal - currentMinutes) <= 30) {
                        closingSoon = true;
                    }
                } 
                // Verificar si está cerca de abrir (mismo día, menos de 60 minutos)
                else if (currentMinutes < openTotal && (openTotal - currentMinutes) <= 60) {
                    openingSoon = true;
                }
            });
            
            return {
                intervals: intervals.length ? intervals : ["Ver horario"],
                open: isOpen,
                closingSoon: closingSoon,
                openingSoon: openingSoon
            };
        } catch (e) {
            console.error("Error procesando horario:", e);
            return {
                intervals: ["Ver horario"],
                open: false,
                closingSoon: false,
                openingSoon: false
            };
        }
    }

    function formatHour(time) {
        if (!time || time.toLowerCase() === "cerrado") return "Cerrado";
        const [h, m] = time.split(':');
        const hour = parseInt(h, 10);
        const period = hour >= 12 ? 'pm' : 'am';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${m} ${period}`;
    }
});