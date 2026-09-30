        document.addEventListener('DOMContentLoaded', function() {
            // Theme Toggle
            const themeToggle = document.getElementById('theme-toggle');
            themeToggle.addEventListener('change', function() {
                document.body.classList.toggle('dark-theme', this.checked);
                localStorage.setItem('theme', this.checked ? 'dark' : 'light');
            });
            
            // Set initial theme
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-theme');
                themeToggle.checked = true;
            }
            
            // Font Size Buttons
            const fontButtons = document.querySelectorAll('.font-btn');
            fontButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    fontButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Set font size
                    const size = this.getAttribute('data-size');
                    document.body.className = document.body.className.replace(/\bfont-\S+/g, '');
                    document.body.classList.add(`font-${size}`);
                    
                    // Save preference
                    localStorage.setItem('fontSize', size);
                });
            });
            
            // Set initial font size
            const savedSize = localStorage.getItem('fontSize') || 'medium';
            document.querySelector(`.font-btn[data-size="${savedSize}"]`).classList.add('active');
            document.body.classList.add(`font-${savedSize}`);
            
            // Toggles for Preferences
            const toggles = ['location', 'recommendations', 'notifications'];
            toggles.forEach(toggle => {
                const element = document.getElementById(`${toggle}-toggle`);
                
                // Load saved state
                const savedState = localStorage.getItem(toggle);
                if (savedState !== null) {
                    element.checked = savedState === 'true';
                }
                
                element.addEventListener('change', function() {
                    localStorage.setItem(toggle, this.checked);
                });
            });
            
            // Language Modal
            const languageBtn = document.getElementById('language-btn');
            const languageModal = document.getElementById('language-modal');
            const closeBtn = document.querySelector('.close-btn');
            const saveLanguageBtn = document.getElementById('save-language');
            const languageOptions = document.querySelectorAll('.language-option');
            
            // Open modal
            languageBtn.addEventListener('click', () => {
                languageModal.style.display = 'flex';
            });
            
            // Close modal
            closeBtn.addEventListener('click', () => {
                languageModal.style.display = 'none';
            });
            
            // Close when clicking outside modal
            window.addEventListener('click', (e) => {
                if (e.target === languageModal) {
                    languageModal.style.display = 'none';
                }
            });
            
            // Select language option
            languageOptions.forEach(option => {
                option.addEventListener('click', () => {
                    languageOptions.forEach(opt => opt.classList.remove('active'));
                    option.classList.add('active');
                });
            });
            
            // Save language
            saveLanguageBtn.addEventListener('click', () => {
                const selectedLang = document.querySelector('.language-option.active .language-name').textContent;
                alert(`Idioma cambiado a: ${selectedLang}`);
                languageModal.style.display = 'none';
                // Aquí iría la lógica para cambiar el idioma de la aplicación
            });
        });