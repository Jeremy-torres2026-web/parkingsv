// Función para confirmar eliminación
function confirmDelete() {
    const parkingId = document.getElementById('parking_id').value;
    
    if (!confirm('¿Estás seguro de eliminar este parqueo permanentemente? Esta acción no se puede deshacer.')) {
        return;
    }
    
    // Crear formulario dinámico
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'includes/delete-parking.php';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'parking_id';
    input.value = parkingId;
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}