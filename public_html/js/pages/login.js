// ============================================
// Страница логина
// ============================================

const LoginPage = {

    render() {
        return `
            <div class="login-page">
                <div class="login-card">
                    <div class="login-logo">
                        <div class="login-logo-icon">N</div>
                        <h1>NerdCRM</h1>
                        <p>Войдите в систему</p>
                    </div>
                    <div class="login-error" id="login-error"></div>
                    <form class="login-form" id="login-form">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="login-email"
                                placeholder="email@example.com" required autofocus>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Пароль</label>
                            <input type="password" class="form-control" id="login-password"
                                placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="login-btn">
                            Войти
                        </button>
                    </form>
                </div>
            </div>
        `;
    },

    afterRender() {
        const form = document.getElementById('login-form');
        const errorEl = document.getElementById('login-error');
        const btn = document.getElementById('login-btn');

        form.onsubmit = async (e) => {
            e.preventDefault();
            errorEl.style.display = 'none';
            btn.disabled = true;
            btn.textContent = 'Вход...';

            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;

            const result = await Auth.signIn(email, password);

            if (!result.success) {
                errorEl.textContent = result.message;
                errorEl.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Войти';
            }
        };
    },
};