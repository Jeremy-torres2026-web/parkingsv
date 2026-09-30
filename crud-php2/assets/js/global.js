document.addEventListener('DOMContentLoaded', function() {
  // Elementos principales
  const userDropdown = document.querySelector('.user-dropdown');
  const userMenuBtn = document.getElementById('userMenuBtn');
  const navbarToggle = document.getElementById('navbarToggle');
  const navbarLinks = document.querySelector('.navbar-links');

  // Seguridad: verificar existencia
  const hasUser = !!(userDropdown && userMenuBtn);
  const hasNav = !!(navbarToggle && navbarLinks);

  
  // Mostrar/ocultar menú usuario
  if (hasUser) {
    userMenuBtn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      // Toggle clase active en el wrapper .user-dropdown
      userDropdown.classList.toggle('active');

      // Update aria
      const expanded = userDropdown.classList.contains('active');
      userMenuBtn.setAttribute('aria-expanded', expanded ? 'true' : 'false');

      // NOTE: no cerramos el navbar aquí. En mobile el dropdown interno debe ser interactivo.
    });
  }

  // Hamburguesa: abrir/cerrar navbar-links
  if (hasNav) {
    navbarToggle.addEventListener('click', function(e) {
      e.stopPropagation();
      navbarLinks.classList.toggle('show');

      // Set aria-expanded
      const expanded = navbarLinks.classList.contains('show');
      navbarToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');

      // Si abrimos el navbar, cerramos dropdown de usuario (si está abierto)
      if (userDropdown && userDropdown.classList.contains('active')) {
        userDropdown.classList.remove('active');
        userMenuBtn?.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // Cerrar menús al hacer clic fuera
  document.addEventListener('click', function(e) {
    // Cerrar user dropdown si clic fuera
    if (userDropdown && !userDropdown.contains(e.target) && e.target !== userMenuBtn) {
      userDropdown.classList.remove('active');
      userMenuBtn?.setAttribute('aria-expanded', 'false');
    }

    // Cerrar navbar-links si clic fuera (y no se hizo sobre el toggle)
    if (navbarLinks && !navbarLinks.contains(e.target) && e.target !== navbarToggle) {
      navbarLinks.classList.remove('show');
      navbarToggle?.setAttribute('aria-expanded', 'false');
    }
  });

  // Cerrar menús con Escape
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      if (userDropdown && userDropdown.classList.contains('active')) {
        userDropdown.classList.remove('active');
        userMenuBtn?.setAttribute('aria-expanded', 'false');
      }
      if (navbarLinks && navbarLinks.classList.contains('show')) {
        navbarLinks.classList.remove('show');
        navbarToggle?.setAttribute('aria-expanded', 'false');
      }
    }
  });

  // Tabs (si existen)
  const tabs = document.querySelectorAll('.tab');
  const tabContents = document.querySelectorAll('.tab-content');

  if (tabs.length > 0 && tabContents.length > 0) {
    tabs.forEach(tab => {
      tab.addEventListener('click', function() {
        const target = this.getAttribute('data-target');
        tabs.forEach(t => t.classList.remove('active'));
        tabContents.forEach(tc => tc.classList.remove('active'));
        this.classList.add('active');
        document.getElementById(target)?.classList.add('active');
      });
    });
    // activar primera pestaña si existe
    tabs[0]?.click();
  }
});

// Sistema de navegación para el botón de volver universal
document.addEventListener('DOMContentLoaded', function() {
    const backButton = document.getElementById('universalBackButton');
    if (!backButton) return;
    
    // Determinar la página de origen basada en la URL actual
    const currentPage = window.location.pathname.split('/').pop();
    const urlParams = new URLSearchParams(window.location.search);
    
    // Páginas y sus destinos por defecto
    const defaultDestinations = {
        'detalles-parqueo.php': 'parqueos.publicados.php',
        'carpeta.php': 'guardados.php',
        'ver-editar-parqueo.php': 'parqueos.publicados.php'
    };
    
    // Establecer destino por defecto
    let targetPage = defaultDestinations[currentPage] || 'index.php';
    
    // Verificar parámetros de URL para determinar el origen
    if (currentPage === 'detalles-parqueo.php') {
        const fromCarpeta = urlParams.get('fromCarpeta');
        const fromGuardados = urlParams.get('fromGuardados');
        
        if (fromCarpeta) {
            targetPage = `carpeta.php?id=${fromCarpeta}`;
        } else if (fromGuardados) {
            targetPage = 'guardados.php';
        }
    } else if (currentPage === 'carpeta.php') {
        // Si estamos en una carpeta específica, volver a guardados
        targetPage = 'guardados.php';
    }
    
    // Configurar el enlace
    backButton.href = targetPage;
    
    // Manejar clic con verificación de historial
    backButton.addEventListener('click', function(e) {
        // Si hay historial, usar navegación hacia atrás
        if (window.history.length > 1) {
            e.preventDefault();
            window.history.back();
        }
        // Si no hay historial, el href normal funcionará
    });
});

// Session Modal Functionality
document.addEventListener('DOMContentLoaded', function() {
  const sessionModal = document.getElementById('sessionModal');
  const restrictedLinks = document.querySelectorAll('.restricted-link');
  const sessionModalLogin = document.getElementById('sessionModalLogin');
  const sessionModalClose = document.getElementById('sessionModalClose');
  
  // Check if user is logged in using the body class from PHP
  function isUserLoggedIn() {
    return document.body.classList.contains('user-logged-in');
  }
  
  // Show session modal
  function showSessionModal() {
    if (sessionModal) {
      sessionModal.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  }
  
  // Hide session modal
  function hideSessionModal() {
    if (sessionModal) {
      sessionModal.classList.remove('active');
      document.body.style.overflow = '';
    }
  }
  
  // Handle restricted link clicks
  if (restrictedLinks.length > 0) {
    restrictedLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        if (!isUserLoggedIn()) {
          e.preventDefault();
          e.stopPropagation();
          showSessionModal();
          
          // Store the intended destination
          const intendedUrl = this.getAttribute('href');
          if (sessionModalLogin) {
            sessionModalLogin.setAttribute('data-intended-url', intendedUrl);
          }
        }
        // If user is logged in, the normal link behavior continues
      });
    });
  }
  
  // Login button click - open the index login modal
  if (sessionModalLogin) {
    sessionModalLogin.addEventListener('click', function() {
      hideSessionModal();
      // Open the login modal on index page
      const openLoginModalButton = document.getElementById('openLoginModal');
      if (openLoginModalButton) {
        openLoginModalButton.click();
      } else {
        // If not on index page, redirect to index with modal open parameter
        window.location.href = '/crud-php2/index.php?openLoginModal=1';
      }
    });
  }
  
  // Close modal button
  if (sessionModalClose) {
    sessionModalClose.addEventListener('click', hideSessionModal);
  }
  
  // Auto-open login modal if URL parameter is present
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('openLoginModal') === '1') {
    const openLoginModalButton = document.getElementById('openLoginModal');
    if (openLoginModalButton) {
      setTimeout(() => {
        openLoginModalButton.click();
      }, 500);
    }
  }
});

// Session Modal Functionality - Enhanced
document.addEventListener('DOMContentLoaded', function() {
  const sessionModal = document.getElementById('sessionModal');
  const restrictedLinks = document.querySelectorAll('.restricted-link');
  const sessionModalLogin = document.getElementById('sessionModalLogin');
  const sessionModalGuest = document.getElementById('sessionModalGuest'); // Ya está correcto
  const registerLink = document.querySelector('.register-link');
  const featuresScroll = document.querySelector('.features-scroll');
  
  // Check if user is logged in using the body class from PHP
  function isUserLoggedIn() {
    return document.body.classList.contains('user-logged-in');
  }
  
  // Show session modal
  function showSessionModal() {
    if (sessionModal) {
      sessionModal.classList.add('active');
      document.body.style.overflow = 'hidden';
      
      // Reset scroll position
      if (featuresScroll) {
        featuresScroll.style.animation = 'none';
        setTimeout(() => {
          featuresScroll.style.animation = 'scrollFeatures 25s linear infinite';
        }, 10);
      }
    }
  }
  
  // Hide session modal
  function hideSessionModal() {
    if (sessionModal) {
      sessionModal.classList.remove('active');
      document.body.style.overflow = '';
    }
  }
  
  // Handle restricted link clicks
  if (restrictedLinks.length > 0) {
    restrictedLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        if (!isUserLoggedIn()) {
          e.preventDefault();
          e.stopPropagation();
          showSessionModal();
          
          // Store the intended destination
          const intendedUrl = this.getAttribute('href');
          if (sessionModalLogin) {
            sessionModalLogin.setAttribute('data-intended-url', intendedUrl);
          }
        }
      });
    });
  }
  
  // Login button click - open the index login modal
  if (sessionModalLogin) {
    sessionModalLogin.addEventListener('click', function() {
      hideSessionModal();
      // Open the login modal on index page
      const openLoginModalButton = document.getElementById('openLoginModal');
      if (openLoginModalButton) {
        openLoginModalButton.click();
      } else {
        window.location.href = '/crud-php2/index.php?openLoginModal=1';
      }
    });
  }
  
  // Guest button click
  if (sessionModalGuest) {
    sessionModalGuest.addEventListener('click', hideSessionModal);
  }
  
  // Register link click
  if (registerLink) {
    registerLink.addEventListener('click', function() {
      hideSessionModal();
      // Switch to signup tab in index modal
      const openSignupModalButton = document.getElementById('openSignupModal');
      if (openSignupModalButton) {
        openSignupModalButton.click();
      } else {
        window.location.href = '/crud-php2/index.php?openSignupModal=1';
      }
    });
  }
});