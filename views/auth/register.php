<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Exam Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="auth.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@4/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-inner">
                <div class="card-front">
                    <h2>Créer un compte</h2>
                    <form id="registerForm" class="register-form">
                        <div class="form-group">
                            <i class="fas fa-user"></i>
                            <input type="text" name="name" placeholder="Nom complet" required 
                                   pattern=".{3,}" title="Le nom doit contenir au moins 3 caractères">
                        </div>
                        <div class="form-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" placeholder="Email" required>
                        </div>
                        <div class="form-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" placeholder="Mot de passe" required
                                   pattern=".{6,}" title="Le mot de passe doit contenir au moins 6 caractères">
                        </div>
                        <div class="form-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required>
                        </div>
                        <div class="terms">
                            <label>
                                <input type="checkbox" name="terms" required>
                                J'accepte les conditions d'utilisation
                            </label>
                        </div>
                        <button type="submit" class="btn-submit">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                            S'inscrire
                        </button>
                    </form>
                    <div class="social-login">
                        <p>Ou inscrivez-vous avec</p>
                        <div class="social-icons">
                            <a href="#" class="social-icon" data-original-icon='<i class="fab fa-google"></i>'>
                                <i class="fab fa-google"></i>
                            </a>
                            <a href="#" class="social-icon" data-original-icon='<i class="fab fa-facebook-f"></i>'>
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-icon" data-original-icon='<i class="fab fa-twitter"></i>'>
                                <i class="fab fa-twitter"></i>
                            </a>
                        </div>
                    </div>
                    <p class="login-link">
                        Déjà un compte? 
                        <a href="login.php">Se connecter</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const password = formData.get('password');
            const confirmPassword = formData.get('confirm_password');
            
            if (password !== confirmPassword) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur!',
                    text: 'Les mots de passe ne correspondent pas',
                    confirmButtonColor: '#2196F3',
                    background: '#1a1a1a',
                    color: '#fff'
                });
                return;
            }
            
            const submitBtn = this.querySelector('.btn-submit');
            const originalText = submitBtn.textContent;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Inscription...';
            submitBtn.disabled = true;

            fetch('process_register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 2000,
                        background: '#1a1a1a',
                        color: '#fff',
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    }).then(() => {
                        window.location.href = data.redirect;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur!',
                        text: data.message,
                        confirmButtonColor: '#2196F3',
                        background: '#1a1a1a',
                        color: '#fff'
                    });
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur!',
                    text: 'Une erreur est survenue. Veuillez réessayer.',
                    confirmButtonColor: '#2196F3',
                    background: '#1a1a1a',
                    color: '#fff'
                });
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Password strength indicator
        document.querySelector('input[name="password"]').addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            const borderColor = strength < 2 ? '#ff4444' : 
                              strength < 3 ? '#ffbb33' : 
                              strength < 4 ? '#00C851' : '#2196F3';
            
            this.style.borderColor = borderColor;
            this.style.boxShadow = `0 0 10px ${borderColor}40`;
        });

        // Social login buttons
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