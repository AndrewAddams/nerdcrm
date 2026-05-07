// ============================================
// Роутер — красивые URL через History API
// ============================================

const Router = {

    routes: {
        '/login':      () => LoginPage.render(),
        '/orders':     () => OrdersPage.render(),
        '/production': () => ProductionPage.render(),
    },

    currentPath: '/',

    init() {
        // Перехватываем клики по ссылкам
        document.addEventListener('click', (e) => {
            const link = e.target.closest('[data-nav]');
            if (link) {
                e.preventDefault();
                this.navigate(link.getAttribute('href'));
            }
        });

        // Кнопка «назад» в браузере
        window.addEventListener('popstate', () => {
            this._render(this._getCurrentPath());
        });
    },

    navigate(path) {
        if (path === this.currentPath) return;
        history.pushState(null, '', path);
        this._render(path);
    },

    _getCurrentPath() {
        return window.location.pathname || '/';
    },

    _render(path) {
        this.currentPath = path;
        const app = document.getElementById('app');

        // Не залогинен — на логин
        if (!Auth.isLoggedIn()) {
            if (path !== '/login') {
                history.replaceState(null, '', '/login');
                this.currentPath = '/login';
            }
            app.innerHTML = LoginPage.render();
            if (LoginPage.afterRender) LoginPage.afterRender();
            return;
        }

        // Залогинен, но на /login — редирект
        if (path === '/login' || path === '/') {
            this.navigate('/orders');
            return;
        }

        // Ищем маршрут
        const renderFn = this.routes[path];
        if (!renderFn) {
            this.navigate('/orders');
            return;
        }

        // Рендерим
        app.innerHTML = renderFn();

        // afterRender
        const map = {
            '/orders':     () => OrdersPage.afterRender && OrdersPage.afterRender(),
            '/production': () => ProductionPage.afterRender && ProductionPage.afterRender(),
        };

        const fn = map[path];
        if (fn) {
            try { fn(); } catch (err) { console.error('afterRender error:', err); }
        }
    },

    start() {
        this._render(this._getCurrentPath());
    },
};