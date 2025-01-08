<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Exam Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./public/css/auth.css">
    <!-- Add SweetAlert2 for beautiful alerts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@4/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-inner">
                <div class="card-front">
                    <h2>Se Connecter</h2>
                    <form id="loginForm" class="login-form">
                        <div class="form-group">
                            <i class="fas fa-user"></i>
                            <input type="email" name="email" placeholder="Email" required>
                        </div>
                        <div class="form-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" placeholder="Mot de passe" required>
                        </div>
                        <div class="remember-forgot">
                            <label>
                                <input type="checkbox" name="remember">
                                Se souvenir de moi
                            </label>
                            <a href="#" class="forgot-password">Mot de passe oublié?</a>
                        </div>
                        <button type="submit" class="btn-submit">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                            Connexion
                        </button>
                    </form>
                    <div class="social-login">
                        <p>Ou connectez-vous avec</p>
                        <div class="social-icons">
                            <a href="#" class="social-icon">
                                <i class="fab fa-google"></i>
                            </a>
                            <a href="#" class="social-icon">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-icon">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </div>
                    </div>
                    <p class="register-link">
                        Pas encore de compte? 
                        <a href="register.php">S'inscrire</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Show loading state
            const submitBtn = this.querySelector('.btn-submit');
            const originalText = submitBtn.textContent;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connexion...';
            submitBtn.disabled = true;

            fetch('process_login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Show success message with animation
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500,
                        background: '#1a1a1a',
                        color: '#fff',
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    }).then(() => {
                        // Redirect to dashboard
                        window.location.href = data.redirect;
                    });
                } else {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur!',
                        text: data.message,
                        confirmButtonColor: '#2196F3',
                        background: '#1a1a1a',
                        color: '#fff'
                    });
                    
                    // Reset button state
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                // Show error message
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur!',
                    text: 'Une erreur est survenue. Veuillez réessayer.',
                    confirmButtonColor: '#2196F3',
                    background: '#1a1a1a',
                    color: '#fff'
                });
                
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Add loading animation for social login buttons
        document.querySelectorAll('.social-icon').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                this.classList.add('loading');
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                setTimeout(() => {
                    this.classList.remove('loading');
                    this.innerHTML = this.getAttribute('data-original-icon');
                    Swal.fire({
                        icon: 'info',
                        title: 'En développement',
                        text: 'Cette fonctionnalité sera bientôt disponible!',
                        confirmButtonColor: '#2196F3',
                        background: '#1a1a1a',
                        color: '#fff'
                    });
                }, 1500);
            });
        });
    </script>
</body>
</html> 