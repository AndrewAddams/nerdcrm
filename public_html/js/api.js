// ============================================
// API — все запросы к Supabase
// ============================================

const API = {

    // Таймаут для запросов
    async withTimeout(promise, label = '', ms = 15000) {
        return Promise.race([
            promise,
            new Promise((_, reject) =>
                setTimeout(() => reject(new Error(`${label}: таймаут`)), ms)
            ),
        ]);
    },

    // ЗАКАЗЫ
    async getOrders(filters = {}) {
        try {
            let query = window.sb
                .from('orders')
                .select(`
                    *,
                    status_order:statuses!orders_status_order_id_fkey(id, name, color, sort_order),
                    status_payment:statuses!orders_status_payment_id_fkey(id, name, color),
                    source:sources!orders_source_id_fkey(id, name),
                    shipping_method:shipping_methods!orders_shipping_method_id_fkey(id, name),
                    manager:profiles!orders_user_id_fkey(id, name)
                `)
                .is('deleted_at', null)
                .order('date_created', { ascending: false })
                .order('id', { ascending: false });

            if (filters.year) {
                query = query.gte('date_created', `${filters.year}-01-01`)
                             .lte('date_created', `${filters.year}-12-31`);
            }

            if (filters.year && filters.month) {
                const m = String(filters.month).padStart(2, '0');
                const lastDay = new Date(filters.year, filters.month, 0).getDate();
                query = query.gte('date_created', `${filters.year}-${m}-01`)
                             .lte('date_created', `${filters.year}-${m}-${lastDay}`);
            }

            if (filters.statusOrderId) {
                query = query.eq('status_order_id', filters.statusOrderId);
            }

            if (filters.statusPaymentId) {
                query = query.eq('status_payment_id', filters.statusPaymentId);
            }

            if (filters.limit) {
                query = query.limit(filters.limit);
            }

            if (filters.offset) {
                query = query.range(filters.offset, filters.offset + (filters.limit || 20) - 1);
            }

            const { data, error } = await this.withTimeout(query, 'Заказы');
            if (error) { console.error('getOrders:', error); return []; }
            return data || [];
        } catch (e) { console.error('getOrders:', e); return []; }
    },

    async getOrder(id) {
        try {
            const { data, error } = await this.withTimeout(
                window.sb.from('orders').select(`
                    *,
                    status_order:statuses!orders_status_order_id_fkey(id, name, color),
                    status_payment:statuses!orders_status_payment_id_fkey(id, name, color),
                    source:sources!orders_source_id_fkey(id, name),
                    shipping_method:shipping_methods!orders_shipping_method_id_fkey(id, name),
                    manager:profiles!orders_user_id_fkey(id, name)
                `).eq('id', id).single(),
                'Заказ'
            );
            if (error) { console.error('getOrder:', error); return null; }
            return data;
        } catch (e) { console.error('getOrder:', e); return null; }
    },

    async createOrder(data) {
        try {
            const { data: row, error } = await this.withTimeout(
                window.sb.from('orders').insert(data).select().single(), 'Создание заказа');
            if (error) return { success: false, error: error.message };
            return { success: true, data: row };
        } catch (e) { return { success: false, error: e.message }; }
    },

    async updateOrder(id, data) {
        try {
            const { error } = await this.withTimeout(
                window.sb.from('orders').update(data).eq('id', id), 'Обновление заказа');
            if (error) return { success: false, error: error.message };
            return { success: true };
        } catch (e) { return { success: false, error: e.message }; }
    },

    async updateOrderField(id, field, value) {
        try {
            const { error } = await this.withTimeout(
                window.sb.from('orders').update({ [field]: value }).eq('id', id), 'Обновление поля');
            if (error) return false;
            return true;
        } catch (e) { return false; }
    },

    async deleteOrder(id) {
        return this.updateOrderField(id, 'deleted_at', new Date().toISOString());
    },

    async toggleCollapsed(id, collapsed) {
        return this.updateOrderField(id, 'is_collapsed', collapsed ? 1 : 0);
    },

    async generateOrderNumber() {
        try {
            const { count } = await this.withTimeout(
                window.sb.from('orders').select('id', { count: 'exact', head: true }), 'Счётчик');
            return `Заказ №${(count || 0) + 1}`;
        } catch (e) { return `Заказ №${Date.now()}`; }
    },

    // ПОЗИЦИИ ЗАКАЗА
    async getOrderItems(orderId) {
        try {
            const { data, error } = await this.withTimeout(
                window.sb.from('order_items').select(`
                    *,
                    product:products!order_items_product_id_fkey(id, name),
                    format:formats!order_items_format_id_fkey(id, name, price),
                    status:statuses!order_items_status_order_item_id_fkey(id, name, color)
                `).eq('order_id', orderId).order('id'),
                'Товары заказа'
            );
            if (error) { console.error('getOrderItems:', error); return []; }
            return data || [];
        } catch (e) { console.error('getOrderItems:', e); return []; }
    },

    async createOrderItem(data) {
        try {
            const { data: row, error } = await this.withTimeout(
                window.sb.from('order_items').insert(data).select().single(), 'Добавление товара');
            if (error) return { success: false, error: error.message };
            return { success: true, data: row };
        } catch (e) { return { success: false, error: e.message }; }
    },

    async updateOrderItemField(id, field, value) {
        try {
            const { error } = await this.withTimeout(
                window.sb.from('order_items').update({ [field]: value }).eq('id', id), 'Обновление товара');
            return !error;
        } catch (e) { return false; }
    },

    async deleteOrderItem(id) {
        try {
            const { error } = await this.withTimeout(
                window.sb.from('order_items').delete().eq('id', id), 'Удаление товара');
            return !error;
        } catch (e) { return false; }
    },

    // СПРАВОЧНИКИ
    async getStatuses(type) {
        try {
            let q = window.sb.from('statuses').select('*').order('sort_order');
            if (type) q = q.eq('type', type);
            const { data, error } = await this.withTimeout(q, 'Статусы');
            if (error) return [];
            return data || [];
        } catch (e) { return []; }
    },

    async getDefaultStatus(type) {
        try {
            const { data } = await this.withTimeout(
                window.sb.from('statuses').select('id').eq('type', type).eq('is_default', 1).limit(1).single(), '');
            if (data) return data.id;
            const { data: f } = await this.withTimeout(
                window.sb.from('statuses').select('id').eq('type', type).order('sort_order').limit(1).single(), '');
            return f ? f.id : null;
        } catch (e) { return null; }
    },

    async getSources() {
        try {
            const { data } = await this.withTimeout(
                window.sb.from('sources').select('*').is('deleted_at', null).order('name'), 'Источники');
            return data || [];
        } catch (e) { return []; }
    },

    async getShippingMethods() {
        try {
            const { data } = await this.withTimeout(
                window.sb.from('shipping_methods').select('*').is('deleted_at', null).order('name'), 'Доставка');
            return data || [];
        } catch (e) { return []; }
    },

    async getProducts() {
        try {
            const { data } = await this.withTimeout(
                window.sb.from('products').select('*').is('deleted_at', null).order('name'), 'Товары');
            return data || [];
        } catch (e) { return []; }
    },

    async getFormats() {
        try {
            const { data } = await this.withTimeout(
                window.sb.from('formats').select('*').is('deleted_at', null).order('name'), 'Форматы');
            return data || [];
        } catch (e) { return []; }
    },

    async getProfiles() {
        try {
            const { data } = await this.withTimeout(
                window.sb.from('profiles').select('*').order('name'), 'Профили');
            return data || [];
        } catch (e) { return []; }
    },

    // ПОЛЬЗОВАТЕЛЬСКИЕ НАСТРОЙКИ
    async getUserSettings() {
        try {
            const userId = Auth.getUserId();
            if (!userId) return null;
            const { data } = await this.withTimeout(
                window.sb.from('user_settings').select('*').eq('user_id', userId).single(), 'Настройки');
            return data;
        } catch (e) { return null; }
    },

    async saveUserSettings(settings) {
        try {
            const userId = Auth.getUserId();
            if (!userId) return false;
            const { error } = await this.withTimeout(
                window.sb.from('user_settings').upsert({
                    user_id: userId,
                    ...settings,
                    updated_at: new Date().toISOString(),
                }), 'Сохранение настроек');
            return !error;
        } catch (e) { return false; }
    },

    // ДЕРЕВО ПАПОК (год/месяц)
    async getOrderDateTree() {
        try {
            const { data, error } = await this.withTimeout(
                window.sb.from('orders').select('date_created').is('deleted_at', null), 'Дерево дат');
            if (error || !data) return {};

            const tree = {};
            data.forEach(order => {
                if (!order.date_created) return;
                const d = new Date(order.date_created);
                const year = d.getFullYear();
                const month = d.getMonth() + 1;
                if (!tree[year]) tree[year] = {};
                if (!tree[year][month]) tree[year][month] = 0;
                tree[year][month]++;
            });
            return tree;
        } catch (e) { return {}; }
    },
};