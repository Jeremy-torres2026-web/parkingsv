document.addEventListener('DOMContentLoaded', function() {
    // Búsqueda en tiempo real
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const parkingCards = document.querySelectorAll('.parking-card');
            
            parkingCards.forEach(card => {
                const name = card.querySelector('h3').textContent.toLowerCase();
                const location = card.querySelector('.location').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || location.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // Hacer la tarjeta completamente clickeable para editar
    const parkingCards = document.querySelectorAll('.parking-card');
    if (parkingCards.length > 0) {
        parkingCards.forEach(card => {
            card.addEventListener('click', function(e) {
                // Si el click NO fue en un enlace (por si acaso)
                if (!e.target.closest('a')) {
                    const parkingId = this.getAttribute('data-parking-id');
                    window.location.href = `ver-editar-parqueo.php?id=${parkingId}`;
                }
            });
        });
    }

    // Animación para la ilustración (opcional)
    const illo = document.querySelector('.no-parkings-illustration img');
    if (illo) {
        illo.addEventListener('mouseenter', () => {
            illo.style.transform = 'scale(1.05) rotate(-3deg)';
        });
        illo.addEventListener('mouseleave', () => {
            illo.style.transform = '';
        });
    }
});