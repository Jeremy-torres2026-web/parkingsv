// Funciones principales para manejar modales
function openModal(modalType) {
    const modalIdMap = {
        'location': 'location-modal',
        'capacity': 'capacity-modal',
        'schedule': 'schedule-modal',
        'share': 'share-modal',
        'google-map': 'google-map-modal',
        'waze-map': 'waze-map-modal',
        'reserve': 'reserve-modal',
        'qr': 'qr-modal'
    };

    const modalId = modalIdMap[modalType];
    if (!modalId) return;
    
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Funciones para compartir
function shareOnFacebook() {
    const url = encodeURIComponent(window.location.href);
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank');
}

function shareOnTwitter() {
    const text = encodeURIComponent(`Mira este parqueo: ${document.querySelector('.parking-title').textContent}`);
    const url = encodeURIComponent(window.location.href);
    window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank');
}

function shareOnWhatsApp() {
    const text = encodeURIComponent(`Mira este parqueo: ${document.querySelector('.parking-title').textContent} - ${window.location.href}`);
    window.open(`https://wa.me/?text=${text}`, '_blank');
}

function copyLink() {
    const tempInput = document.createElement('input');
    tempInput.value = window.location.href;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
    
    alert('Enlace copiado al portapapeles');
}

// Función para copiar enlaces específicos
function copyToClipboard(text, type) {
    const tempInput = document.createElement('input');
    tempInput.value = text;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
    alert(`Enlace de ${type} copiado al portapapeles`);
}

// Función para validar y abrir el modal de reserva
async function validateAndOpenReserveModal() {
    try {
        // Consultar disponibilidad directamente al servidor
        const response = await fetch(`includes/obtener-disponibilidad.php?parking_id=${window.parkingData.parkingId}`);
        const data = await response.json();
        
        if (data.success && data.reservable > 0) {
            openModal('reserve');
        } else {
            alert('Lo sentimos, este parqueo no tiene espacios reservables disponibles en este momento.');
        }
    } catch (error) {
        console.error('Error al verificar disponibilidad:', error);
        // Si hay error, permitir abrir el modal igualmente
        openModal('reserve');
    }
}

// Función para generar código QR
function generateQRCode(qrData) {
    // Limpiar contenedor previo
    document.getElementById('qrcode').innerHTML = '';
    
    // Usar una librería como qrcode.js para generar el QR
    // Nota: Necesitarás incluir la librería en tu proyecto
    new QRCode(document.getElementById('qrcode'), {
        text: qrData,
        width: 200,
        height: 200
    });
}

// Función para descargar QR
function downloadQR() {
    const canvas = document.querySelector('#qrcode canvas');
    if (canvas) {
        const link = document.createElement('a');
        link.download = 'reserva-parqueo.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    }
}

// Abrir modal de compartir (función global)
function openShareModal() {
    openModal('share');
}

// Funciones para la galería fullscreen
function initFullscreenGallery() {
    const fullscreenGallery = document.getElementById('fullscreen-gallery');
    if (!fullscreenGallery) return;
    
    const fullscreenImage = document.getElementById('fullscreen-image');
    const imageCounter = document.querySelector('.image-counter');
    const closeBtn = document.querySelector('.close-gallery');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    
    let currentImageIndex = 0;
    let galleryImages = [];
    let isZoomed = false;

    // Make openFullscreenGallery global
    window.openFullscreenGallery = function(index) {
        currentImageIndex = index;
        galleryImages = Array.from(document.querySelectorAll('.gallery-item img')).map(img => img.src);
        
        if (galleryImages.length === 0) return;
        
        fullscreenImage.src = galleryImages[currentImageIndex];
        fullscreenImage.classList.remove('zoomed');
        isZoomed = false;
        updateCounter();
        fullscreenGallery.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function openFullscreenGallery(index) {
        currentImageIndex = index;
        galleryImages = Array.from(document.querySelectorAll('.gallery-item img')).map(img => img.src);
        
        if (galleryImages.length === 0) return;
        
        fullscreenImage.src = galleryImages[currentImageIndex];
        fullscreenImage.classList.remove('zoomed');
        isZoomed = false;
        updateCounter();
        fullscreenGallery.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function closeFullscreenGallery() {
        fullscreenGallery.style.display = 'none';
        document.body.style.overflow = 'auto';
        fullscreenImage.classList.remove('zoomed');
        isZoomed = false;
    }
    
    function navigate(direction) {
        if (direction === 'prev') {
            currentImageIndex = (currentImageIndex - 1 + galleryImages.length) % galleryImages.length;
        } else {
            currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
        }
        
        fullscreenImage.src = galleryImages[currentImageIndex];
        fullscreenImage.classList.remove('zoomed');
        isZoomed = false;
        updateCounter();
    }
    
    function updateCounter() {
        if (imageCounter) {
            imageCounter.textContent = `${currentImageIndex + 1} / ${galleryImages.length}`;
        }
    }
    
    // Event listeners para la galería
    if (closeBtn) closeBtn.addEventListener('click', closeFullscreenGallery);
    if (prevBtn) prevBtn.addEventListener('click', () => navigate('prev'));
    if (nextBtn) nextBtn.addEventListener('click', () => navigate('next'));
    
    if (fullscreenImage) {
        fullscreenImage.addEventListener('click', function(e) {
            e.stopPropagation();
            isZoomed = !isZoomed;
            this.classList.toggle('zoomed', isZoomed);
        });
    }
    
    document.addEventListener('keydown', function(e) {
        if (fullscreenGallery.style.display === 'flex') {
            if (e.key === 'Escape') {
                closeFullscreenGallery();
            } else if (e.key === 'ArrowLeft') {
                navigate('prev');
            } else if (e.key === 'ArrowRight') {
                navigate('next');
            }
        }
    });
    
    fullscreenGallery.addEventListener('click', function(e) {
        if (e.target === fullscreenGallery) {
            closeFullscreenGallery();
        }
    });
}

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    // Initialize gallery
    initFullscreenGallery();

    // Guardar en favoritos
    const saveBtn = document.querySelector('.save-btn');
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            const parkingId = this.getAttribute('data-parking-id');
            const btn = this;
            
            fetch('guardar-favorito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `parking_id=${parkingId}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    btn.innerHTML = '<i class="fas fa-bookmark"></i> Guardado';
                    btn.classList.add('saved');
                    btn.disabled = true;
                } else {
                    alert('Error al guardar: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al conectar con el servidor');
            });
        });
    }

    // Configurar galería de imágenes
    const gallery = document.querySelector('.image-gallery');
    if (gallery) {
        // Get images from the global variable we set in PHP
        const totalImages = window.parkingData.images.length;
        const visibleImages = Math.min(5, totalImages);
        gallery.setAttribute('data-count', visibleImages);

        if (totalImages > 5) {
            const fifthImage = gallery.querySelector('.gallery-item:nth-child(5)');
            if (fifthImage) {
                const extraCount = totalImages - 5;
                const extraCountDiv = document.createElement('div');
                extraCountDiv.className = 'extra-count';
                extraCountDiv.textContent = `+${extraCount}`;
                fifthImage.appendChild(extraCountDiv);
            }
        }
        
        // Add click handlers to all gallery items
        document.querySelectorAll('.gallery-item').forEach((item, index) => {
            item.addEventListener('click', function(e) {
                if (e.target.classList.contains('extra-count')) {
                    openFullscreenGallery(5); // Start at index 5 when clicking "+X"
                } else {
                    openFullscreenGallery(index);
                }
            });
        });
    }

    // Funcionalidad para estrellas de calificación
    const stars = document.querySelectorAll('.rating-stars .star');
    const ratingInput = document.getElementById('rating-value');
    
    if (stars.length && ratingInput) {
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const value = parseInt(this.getAttribute('data-value'));
                ratingInput.value = value;
                
                stars.forEach(s => {
                    s.classList.remove('selected');
                    if (parseInt(s.getAttribute('data-value')) <= value) {
                        s.classList.add('selected');
                    }
                });
            });
            
            star.addEventListener('mouseover', function() {
                const value = parseInt(this.getAttribute('data-value'));
                stars.forEach(s => {
                    s.classList.remove('hover');
                    if (parseInt(s.getAttribute('data-value')) <= value) {
                        s.classList.add('hover');
                    }
                });
            });
            
            star.addEventListener('mouseout', function() {
                stars.forEach(s => s.classList.remove('hover'));
            });
        });
    }

    // Envío del formulario de reseña con AJAX
    const reviewForm = document.getElementById('review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar que se haya seleccionado una calificación
            if (!document.querySelector('.rating-stars .star.selected')) {
                alert('Por favor selecciona una calificación con las estrellas');
                return;
            }
            
            const formData = new FormData(this);
            
            fetch('procesar_resena.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en el servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // Recargar después de 1.5 segundos para mostrar la nueva reseña
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    alert('Error: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al enviar la reseña. Por favor intenta de nuevo.');
            });
        });
    }

    // Tooltips para tarifas especiales
    document.querySelectorAll('.fee-item').forEach(item => {
        // Añadir clase según tipo de tarifa
        const feeType = item.getAttribute('data-fee-type');
        if (feeType) {
            item.classList.add(feeType);
        }
        
        // Tooltip para tarifas especiales
        item.addEventListener('mouseenter', function() {
            const type = this.getAttribute('data-fee-type');
            let tooltipText = '';
            
            switch(type) {
                case 'premium':
                    tooltipText = 'Tarifa con servicios adicionales';
                    break;
                case 'nocturno':
                    tooltipText = 'Tarifa aplicable durante la noche';
                    break;
                case 'evento':
                    tooltipText = 'Tarifa especial para eventos';
                    break;
            }
            
            if (tooltipText) {
                const tooltip = document.createElement('div');
                tooltip.className = 'fee-tooltip';
                tooltip.textContent = tooltipText;
                this.appendChild(tooltip);
            }
        });
        
        item.addEventListener('mouseleave', function() {
            const tooltip = this.querySelector('.fee-tooltip');
            if (tooltip) {
                tooltip.remove();
            }
        });
    });

    function setupDateTimePickers() {
    // Configurar selectores de hora con formato AM/PM
    const startTimeSelect = document.getElementById('start_time_select');
    const endTimeSelect = document.getElementById('end_time_select');
    
    if (startTimeSelect && endTimeSelect) {
        // Llenar opciones de hora (de 12:00 AM a 11:45 PM)
        for (let h = 0; h < 24; h++) {
            for (let m = 0; m < 60; m += 15) {
                const hour12 = h % 12 || 12;
                const ampm = h < 12 ? 'AM' : 'PM';
                const timeValue = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                const timeDisplay = `${hour12}:${String(m).padStart(2, '0')} ${ampm}`;
                
                const option1 = document.createElement('option');
                option1.value = timeValue;
                option1.textContent = timeDisplay;
                startTimeSelect.appendChild(option1);
                
                const option2 = document.createElement('option');
                option2.value = timeValue;
                option2.textContent = timeDisplay;
                endTimeSelect.appendChild(option2);
            }
        }
    }
}
});