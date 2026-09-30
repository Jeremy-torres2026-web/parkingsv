document.addEventListener('DOMContentLoaded', function() {
    // Botón flotante y panel de creación
    const quickFolderBtn = document.getElementById('quick-folder-btn');
    const folderPanel = document.querySelector('.folder-creation-panel');
    
    if (quickFolderBtn) {
        quickFolderBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            folderPanel.classList.toggle('show');
        });
    }

    // Cerrar panel al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!folderPanel.contains(e.target) && e.target !== quickFolderBtn) {
            folderPanel.classList.remove('show');
        }
    });

    // Selección de color
    const colorOptions = document.querySelectorAll('.color-option');
    const colorInput = document.getElementById('quick-folder-color');
    
    colorOptions.forEach(option => {
        option.addEventListener('click', function() {
            colorOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            colorInput.value = this.getAttribute('data-color');
        });
    });

    // Crear carpeta
    const createBtn = document.getElementById('create-folder-confirm');
    
    if (createBtn) {
        createBtn.addEventListener('click', function() {
            const folderName = document.getElementById('quick-folder-name').value.trim();
            const folderColor = document.getElementById('quick-folder-color').value;
            const selectedParkings = [];
            
            document.querySelectorAll('.parking-checkbox:checked').forEach(checkbox => {
                selectedParkings.push(parseInt(checkbox.value));
            });
            
            if (!folderName) {
                showNotification('Por favor, ingresa un nombre para la carpeta');
                return;
            }
            
            fetch('includes/create-folder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'name': folderName,
                    'color': folderColor,
                    'parkings': JSON.stringify(selectedParkings)
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Crear elemento de carpeta nueva
                    const folderCard = document.createElement('div');
                    folderCard.className = 'folder-card';
                    folderCard.style.backgroundColor = data.folderColor;
                    folderCard.setAttribute('data-folder-id', data.folderId);
                    
                    folderCard.innerHTML = `
                        <div class="folder-icon">
                            <i class="fas fa-folder"></i>
                        </div>
                        <h3>${data.folderName}</h3>
                        <div class="folder-actions">
                            <button class="btn-share" data-token="${data.shareToken}">
                                <i class="fas fa-share-alt"></i>
                            </button>
                        </div>
                    `;
                    
                    // Agregar al contenedor de carpetas
                    const foldersContainer = document.getElementById('folders-container');
                    const noFoldersMsg = foldersContainer.querySelector('.no-folders-msg');
                    
                    if (noFoldersMsg) {
                        noFoldersMsg.remove();
                    }
                    
                    foldersContainer.insertBefore(folderCard, foldersContainer.firstChild);
                    
                    // Añadir eventos a la nueva carpeta
                    addFolderEvents(folderCard);
                    
                    // Cerrar panel y resetear
                    folderPanel.classList.remove('show');
                    document.getElementById('quick-folder-name').value = '';
                    colorOptions[0].click();
                    document.querySelectorAll('.parking-checkbox').forEach(cb => cb.checked = false);
                    
                    showNotification('Carpeta creada con éxito', 'success');
                } else {
                    showNotification(data.message || 'Error al crear la carpeta', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al comunicarse con el servidor', 'error');
            });
        });
    }

    // Función para añadir eventos a carpetas
    function addFolderEvents(folderCard) {
        // Abrir carpeta al hacer clic
        folderCard.addEventListener('click', function(e) {
            if (!e.target.closest('.btn-share')) {
                const folderId = this.getAttribute('data-folder-id');
                window.location.href = `carpeta.php?id=${folderId}`;
            }
        });
        
        // Compartir carpeta
        const shareBtn = folderCard.querySelector('.btn-share');
        if (shareBtn) {
            shareBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const token = this.getAttribute('data-token');
                showShareModal(token);
            });
        }
    }

    // Función para mostrar modal de compartir
    function showShareModal(token) {
        const shareUrl = `${window.location.origin}/carpeta-publica.php?token=${token}`;
        
        // Crear modal
        const modal = document.createElement('div');
        modal.className = 'share-modal';
        modal.innerHTML = `
            <div class="share-modal-content">
                <h4>Compartir carpeta</h4>
                <p>Comparte este enlace para permitir que otros vean tu carpeta:</p>
                
                <div class="share-url-container">
                    <input type="text" class="share-url" value="${shareUrl}" readonly>
                    <button class="btn-copy">Copiar</button>
                </div>
                
                <div class="share-options">
                    <div class="share-option" data-platform="whatsapp">
                        <i class="fab fa-whatsapp"></i>
                        <span>WhatsApp</span>
                    </div>
                    <div class="share-option" data-platform="facebook">
                        <i class="fab fa-facebook"></i>
                        <span>Facebook</span>
                    </div>
                    <div class="share-option" data-platform="twitter">
                        <i class="fab fa-twitter"></i>
                        <span>Twitter</span>
                    </div>
                </div>
                
                <button class="btn btn-secondary" id="close-share-modal">Cerrar</button>
            </div>
        `;
        
        document.body.appendChild(modal);
        setTimeout(() => modal.classList.add('show'), 10);
        
        // Eventos del modal
        modal.querySelector('.btn-copy').addEventListener('click', function() {
            navigator.clipboard.writeText(shareUrl)
                .then(() => showNotification('Enlace copiado al portapapeles', 'success'))
                .catch(() => showNotification('Error al copiar el enlace', 'error'));
        });
        
        modal.querySelector('#close-share-modal').addEventListener('click', function() {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
        });
        
        // Compartir en redes sociales
        modal.querySelectorAll('.share-option').forEach(option => {
            option.addEventListener('click', function() {
                const platform = this.getAttribute('data-platform');
                let url = '';
                
                switch(platform) {
                    case 'whatsapp':
                        url = `https://api.whatsapp.com/send?text=Mira esta carpeta de parqueos: ${encodeURIComponent(shareUrl)}`;
                        break;
                    case 'facebook':
                        url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`;
                        break;
                    case 'twitter':
                        url = `https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl)}&text=Mira esta carpeta de parqueos`;
                        break;
                }
                
                window.open(url, '_blank', 'width=600,height=400');
            });
        });
    }
    
    // Función para mostrar notificación
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Inicializar eventos para carpetas existentes
    document.querySelectorAll('.folder-card').forEach(card => {
        addFolderEvents(card);
    });
});