<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в Nerd</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <h1>Nerd Система</h1>
                <p>Управление заказами</p>
            </div>
            
            <div id="errorMessage" class="error-message"></div>
            
            <form id="loginForm">
                <div class="form-group">
                    <label for="name">Имя пользователя</label>
                    <input type="text" id="name" name="name" required autocomplete="username" autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                
                <button type="submit">Войти в систему</button>
            </form>
            
            <div class="info">
                <p>Демо-доступ: admin / password</p>
                <p><a href="#">Забыли пароль?</a></p>
            </div>
        </div>
    </div>
    
    <script>
        const loginForm = document.getElementById('loginForm');
        const errorMessage = document.getElementById('errorMessage');
        
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            errorMessage.classList.remove('show');
            errorMessage.textContent = '';
            
            const name = document.getElementById('name').value.trim();
            const password = document.getElementById('password').value;
            
            if (!name || !password) {
                showError('Пожалуйста, заполните все поля');
                return;
            }
            
            const submitBtn = loginForm.querySelector('button');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Вход...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ name, password })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = result.data.redirect || '/dashboard';
                } else {
                    showError(result.error || 'Ошибка входа');
                }
            } catch (error) {
                console.error('Ошибка:', error);
                showError('Ошибка подключения к серверу');
            } finally {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });
        
        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.classList.add('show');
        }
        
        async function checkAuth() {
            try {
                const response = await fetch('/api/auth/check');
                const result = await response.json();
                if (result.success && result.data.authenticated) {
                    window.location.href = '/dashboard';
                }
            } catch (error) {
                console.error('Ошибка проверки авторизации:', error);
            }
        }
        
        checkAuth();
    </script>
</body>
</html>