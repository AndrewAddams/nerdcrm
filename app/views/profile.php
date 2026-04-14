<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой профиль - Nerd</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="header">
        <div class="logo">
            <h2>Nerd Заказы</h2>
        </div>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
            <span class="role-badge"><?php echo $user['role'] === 'admin' ? 'Администратор' : 'Менеджер'; ?></span>
            <a href="/dashboard" class="btn btn-secondary" style="text-decoration: none;">← К заказам</a>
            <button class="logout-btn" id="logoutBtn">Выйти</button>
        </div>
    </div>
    
    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo mb_substr($user['name'], 0, 1, 'UTF-8'); ?>
                </div>
                <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
                <div class="profile-role"><?php echo $user['role'] === 'admin' ? 'Администратор' : 'Менеджер'; ?></div>
            </div>
            
            <div class="profile-body">
                <div id="alertMessage" class="alert"></div>
                
                <div class="form-section">
                    <h3 class="form-section-title">📝 Основная информация</h3>
                    <form id="profileForm">
                        <div class="form-group">
                            <label for="name">Имя пользователя</label>
                            <input type="text" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                        </div>
                    </form>
                </div>
                
                <div class="form-section">
                    <h3 class="form-section-title">🔒 Смена пароля</h3>
                    <form id="passwordForm">
                        <div class="form-group">
                            <label for="currentPassword">Текущий пароль</label>
                            <input type="password" id="currentPassword" required>
                        </div>
                        <div class="form-group">
                            <label for="newPassword">Новый пароль</label>
                            <input type="password" id="newPassword" required minlength="4">
                            <div class="form-text">Минимум 4 символа</div>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Подтверждение пароля</label>
                            <input type="password" id="confirmPassword" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Сменить пароль</button>
                        </div>
                    </form>
                </div>
                
                <a href="/dashboard" class="back-link">← Вернуться к заказам</a>
            </div>
        </div>
    </div>
    
    <script>
        const alertMessage = document.getElementById('alertMessage');
        
        function showAlert(message, type = 'success') {
            alertMessage.className = 'alert alert-' + type;
            alertMessage.textContent = message;
            alertMessage.classList.add('show');
            setTimeout(() => {
                alertMessage.classList.remove('show');
            }, 3000);
        }
        
        document.getElementById('profileForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const name = document.getElementById('name').value.trim();
            if (!name) {
                showAlert('Имя не может быть пустым', 'danger');
                return;
            }
            try {
                const response = await fetch('/api/users/update-profile', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name })
                });
                const result = await response.json();
                if (result.success) {
                    showAlert(result.message, 'success');
                    document.querySelector('.user-name').textContent = name;
                    document.querySelector('.profile-name').textContent = name;
                    document.querySelector('.profile-avatar').textContent = name.charAt(0);
                } else {
                    showAlert(result.error, 'danger');
                }
            } catch (error) {
                showAlert('Ошибка сохранения', 'danger');
            }
        });
        
        document.getElementById('passwordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            if (!currentPassword || !newPassword) {
                showAlert('Заполните все поля', 'danger');
                return;
            }
            if (newPassword !== confirmPassword) {
                showAlert('Новые пароли не совпадают', 'danger');
                return;
            }
            if (newPassword.length < 4) {
                showAlert('Пароль должен содержать минимум 4 символа', 'danger');
                return;
            }
            try {
                const response = await fetch('/api/users/change-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        current_password: currentPassword, 
                        new_password: newPassword 
                    })
                });
                const result = await response.json();
                if (result.success) {
                    showAlert(result.message, 'success');
                    document.getElementById('currentPassword').value = '';
                    document.getElementById('newPassword').value = '';
                    document.getElementById('confirmPassword').value = '';
                } else {
                    showAlert(result.error, 'danger');
                }
            } catch (error) {
                showAlert('Ошибка смены пароля', 'danger');
            }
        });
        
        document.getElementById('logoutBtn').addEventListener('click', async () => {
            await fetch('/api/auth/logout', { method: 'POST' });
            window.location.href = '/login';
        });
    </script>
</body>
</html>