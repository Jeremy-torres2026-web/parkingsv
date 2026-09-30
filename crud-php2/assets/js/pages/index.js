document.addEventListener('DOMContentLoaded', function() {

  // MODAL LOGIN/SIGNUP funcional y bonito
  const loginSignupModal = document.getElementById('loginSignupModal');
  const closeModalBtn = document.getElementById('closeModalBtn');
  const switchLogin = document.getElementById('switchLogin');
  const switchSignup = document.getElementById('switchSignup');
  const loginForm = document.getElementById('loginForm');
  const signupForm = document.getElementById('signupForm');
  const userTypeSelect = document.getElementById('userTypeSelect');
  const birthDateGroup = document.getElementById('birthDateGroup');
  const businessNameGroup = document.getElementById('businessNameGroup');

  // Helper para mostrar solo el form correcto
  function showLoginForm() {
    switchLogin.classList.add('active');
    switchSignup.classList.remove('active');
    loginForm.style.display = 'block';
    signupForm.style.display = 'none';
  }
  function showSignupForm() {
    switchSignup.classList.add('active');
    switchLogin.classList.remove('active');
    loginForm.style.display = 'none';
    signupForm.style.display = 'block';
  }

  // Abrir modal desde botones principales
  const openLoginModalBtn = document.getElementById('openLoginModal');
  const openSignupModalBtn = document.getElementById('openSignupModal');
  if (openLoginModalBtn) {
    openLoginModalBtn.addEventListener('click', function(e) {
      e.preventDefault();
      loginSignupModal.style.display = 'block';
      document.body.style.overflow = 'hidden';
      showLoginForm();
    });
  }
  if (openSignupModalBtn) {
    openSignupModalBtn.addEventListener('click', function(e) {
      e.preventDefault();
      loginSignupModal.style.display = 'block';
      document.body.style.overflow = 'hidden';
      showSignupForm();
    });
  }

  // Cambiar entre login y signup
  switchLogin.addEventListener('click', function(e) {
    e.preventDefault();
    showLoginForm();
  });
  switchSignup.addEventListener('click', function(e) {
    e.preventDefault();
    showSignupForm();
  });

  // Cerrar modal
  closeModalBtn.addEventListener('click', function() {
    loginSignupModal.style.display = 'none';
    document.body.style.overflow = '';
  });
  window.addEventListener('click', function(e) {
    if (e.target === loginSignupModal) {
      loginSignupModal.style.display = 'none';
      document.body.style.overflow = '';
    }
  });

  // Mostrar/ocultar campos según tipo de usuario
  if (userTypeSelect) {
    userTypeSelect.addEventListener('change', function() {
      if (this.value === 'customer') {
        birthDateGroup.style.display = '';
        businessNameGroup.style.display = 'none';
      } else if (this.value === 'owner') {
        birthDateGroup.style.display = 'none';
        businessNameGroup.style.display = '';
      } else {
        birthDateGroup.style.display = 'none';
        businessNameGroup.style.display = 'none';
      }
    });
    // Mostrar correcto al abrir si ya hay valor seleccionado
    if (userTypeSelect.value === 'customer') {
      birthDateGroup.style.display = '';
      businessNameGroup.style.display = 'none';
    } else if (userTypeSelect.value === 'owner') {
      birthDateGroup.style.display = 'none';
      businessNameGroup.style.display = '';
    } else {
      birthDateGroup.style.display = 'none';
      businessNameGroup.style.display = 'none';
    }
  }

// Modal de Mensajes
const messageModal = document.getElementById('messageModal');
const messageContent = document.getElementById('messageContent');
const closeMessageModal = document.querySelector('.close-message-modal');

// Mostrar modal solo si hay mensaje
if (messageContent.innerHTML.trim() !== '') {
  messageModal.style.display = 'block';

  // Cerrar automáticamente después de 5 segundos
  setTimeout(() => {
    messageModal.style.display = 'none';
  }, 5000);
}

// Cerrar manualmente
if (closeMessageModal) {
  closeMessageModal.addEventListener('click', () => {
    messageModal.style.display = 'none';
  });
}

window.addEventListener('click', (e) => {
  if (e.target === messageModal) {
    messageModal.style.display = 'none';
  }
});
});