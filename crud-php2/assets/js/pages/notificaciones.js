        // Función para abrir/cerrar el menú de acciones
        function toggleActionMenu(btn) {
            const menu = btn.nextElementSibling;
            const isActive = menu.classList.contains('active');
            
            // Cerrar todos los menús primero
            document.querySelectorAll('.action-menu').forEach(m => {
                m.classList.remove('active');
            });
            
            // Abrir el menú actual si no estaba activo
            if (!isActive) {
                menu.classList.add('active');
                
                // Cerrar al hacer clic fuera del menú
                setTimeout(() => {
                    document.addEventListener('click', closeMenuOnClick);
                }, 10);
            }
            
            function closeMenuOnClick(e) {
                if (!menu.contains(e.target) && e.target !== btn) {
                    menu.classList.remove('active');
                    document.removeEventListener('click', closeMenuOnClick);
                }
            }
        }
        
        // Función para cambiar el estado de lectura
        function toggleReadStatus(notificationId) {
            const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
            
            // Simular cambio de estado en la interfaz
            if (notificationItem.classList.contains('notification-read')) {
                notificationItem.classList.remove('notification-read');
                notificationItem.classList.add('notification-unread');
                
                // Cambiar icono en el menú
                const icon = notificationItem.querySelector('.action-menu-item i');
                icon.classList.remove('fa-envelope');
                icon.classList.add('fa-envelope-open');
                
                // Cambiar texto en el menú
                notificationItem.querySelector('.action-menu-item').textContent = 'Marcar como leída';
            } else {
                notificationItem.classList.remove('notification-unread');
                notificationItem.classList.add('notification-read');
                
                // Cambiar icono en el menú
                const icon = notificationItem.querySelector('.action-menu-item i');
                icon.classList.remove('fa-envelope-open');
                icon.classList.add('fa-envelope');
                
                // Cambiar texto en el menú
                notificationItem.querySelector('.action-menu-item').textContent = 'Marcar como no leída';
            }
            
            // Cerrar el menú
            notificationItem.querySelector('.action-menu').classList.remove('active');
            
            // En una implementación real, aquí se haría una petición AJAX al servidor
            console.log(`Cambiando estado de lectura para notificación ${notificationId}`);
        }
        
        // Función para eliminar una notificación
        function deleteNotification(notificationId) {
            const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
            
            // Animación de eliminación
            notificationItem.style.opacity = '0';
            notificationItem.style.transform = 'translateX(100px)';
            
            setTimeout(() => {
                notificationItem.remove();
                
                // Mostrar mensaje de "no hay notificaciones" si era la última
                if (document.querySelectorAll('.notification-item').length === 0) {
                    document.querySelector('.notifications-list').innerHTML = `
                        <div class="no-notifications">
                            <i class="fas fa-bell-slash"></i>
                            <h3>No tienes notificaciones</h3>
                            <p>Cuando tengas nuevas notificaciones, aparecerán aquí.</p>
                        </div>
                    `;
                }
            }, 300);
            
            // En una implementación real, aquí se haría una petición AJAX al servidor
            console.log(`Eliminando notificación ${notificationId}`);
        }
        
        // Marcar todas como leídas
        document.getElementById('markAllRead').addEventListener('click', function() {
            const unreadNotifications = document.querySelectorAll('.notification-unread');
            
            unreadNotifications.forEach(notification => {
                notification.classList.remove('notification-unread');
                notification.classList.add('notification-read');
                
                // Actualizar el menú de acciones
                const icon = notification.querySelector('.action-menu-item i');
                if (icon) {
                    icon.classList.remove('fa-envelope-open');
                    icon.classList.add('fa-envelope');
                    notification.querySelector('.action-menu-item').textContent = 'Marcar como no leída';
                }
            });
            
            // En una implementación real, aquí se haría una petición AJAX al servidor
            console.log('Todas las notificaciones marcadas como leídas');
        });