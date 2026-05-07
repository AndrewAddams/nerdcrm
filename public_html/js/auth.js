// ============================================
// Аутентификация
// ============================================

const Auth = {

    currentUser: null,
    currentProfile: null,

    async init() {
        const { data: { session }, error } = await window.sb.auth.getSession();
        if (error) throw error;

        if (session && session.user) {
            this.currentUser = session.user;
            await this._loadProfile(session.user.id);
        }

        window.sb.auth.onAuthStateChange(async (event, session) => {
            if (event === 'SIGNED_IN' && session) {
                this.currentUser = session.user;
                await this._loadProfile(session.user.id);
                Router.navigate('/orders');
            } else if (event === 'SIGNED_OUT') {
                this.currentUser = null;
                this.currentProfile = null;
                Router.navigate('/login');
            }
        });
    },

    async _loadProfile(userId) {
        const { data, error } = await window.sb
            .from('profiles')
            .select('*')
            .eq('id', userId)
            .single();

        if (error) {
            console.error('Ошибка загрузки профиля:', error);
            this.currentProfile = null;
            return;
        }

        this.currentProfile = data;
    },

    async signIn(email, password) {
        const { error } = await window.sb.auth.signInWithPassword({ email, password });
        if (error) {
            return { success: false, message: 'Неверный email или пароль' };
        }
        return { success: true };
    },

    async signOut() {
        await window.sb.auth.signOut();
    },

    isLoggedIn() {
        return this.currentUser !== null && this.currentProfile !== null;
    },

    isAdmin() {
        return this.currentProfile && this.currentProfile.role === 'admin';
    },

    getProfile() {
        return this.currentProfile;
    },

    getUserId() {
        return this.currentUser ? this.currentUser.id : null;
    },

    getUserName() {
        return this.currentProfile ? this.currentProfile.name : '';
    },
};