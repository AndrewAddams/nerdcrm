// ============================================
// Настройки Supabase
// ============================================

const SUPABASE_URL = 'https://coelmimtjtcvwmzzxtax.supabase.co';
const SUPABASE_ANON_KEY = 'sb_publishable_0hSLfODUXrtOLcgyv1705g_miEOjzbP';

if (!window.supabase || !window.supabase.createClient) {
    throw new Error('Supabase SDK не загрузился');
}

window.sb = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

if (!window.sb || !window.sb.auth) {
    throw new Error('Не удалось создать Supabase client');
}