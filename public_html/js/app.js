// ============================================
// Точка входа
// ============================================

window.APP_VERSION = 'v2';

(async function () {
    try {
        console.log('NerdCRM', window.APP_VERSION);
        await Auth.init();
        Router.init();
        Router.start();
    } catch (error) {
        console.error('Ошибка запуска:', error);
        document.getElementById('app').innerHTML = `
            <div class="loading-screen">
                <p class="text-danger">Ошибка загрузки</p>
                <p class="text-muted mt-2" style="font-size:12px">${error.message || error}</p>
            </div>
        `;
    }
})();