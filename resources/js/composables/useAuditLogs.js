import { ref, computed } from 'vue';
import { useMainStore } from '../stores';

export function useAuditLogs() {
    const mainStore = useMainStore();
    const portal = computed(() => mainStore.userPortal.value);
    
    const auditLogs = ref([]);
    const modelTypes = ref([]);
    const eventTypes = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 50,
        total: 0,
    });

    /**
     * Fetch audit logs dengan filters
     */
    const fetchAuditLogs = async (filters = {}) => {
        console.log('fetchAuditLogs called with filters:', filters);
        console.trace('Call stack:'); // Show where this was called from
        
        loading.value = true;
        error.value = null;

        try {
            const portalId = portal.value[0]?.id;
            if (!portalId) {
                throw new Error('Portal ID tidak ditemukan');
            }

            const params = new URLSearchParams();
            params.append('page', filters.page || 1);
            params.append('per_page', filters.per_page || 50);
            
            if (filters.model_type) params.append('model_type', filters.model_type);
            if (filters.event) params.append('event', filters.event);
            if (filters.user_id) params.append('user_id', filters.user_id);
            if (filters.date_from) params.append('date_from', filters.date_from);
            if (filters.date_to) params.append('date_to', filters.date_to);
            if (filters.search) params.append('search', filters.search);

            const response = await mainStore.useAPI(
                `${portalId}/api/audit-logs?${params.toString()}`,
                { method: 'GET' },
                true // useAuth
            );

            // Response structure: response.data (bukan response.data.data)
            const rawLogs = response.data || [];

            auditLogs.value = rawLogs.filter((log) => {
                // Sembunyikan update Candidate yang hanya menambah berkas
                if (
                    log.model_type === 'Candidate' &&
                    log.changes_summary === 'Menambah berkas kandidat'
                ) {
                    return false;
                }

                // Sembunyikan log Interview yang tidak punya perubahan nilai sama sekali
                const hasOld = log.old_values && Object.keys(log.old_values).length > 0;
                const hasNew = log.new_values && Object.keys(log.new_values).length > 0;

                if (log.model_type === 'Interview' && !hasOld && !hasNew) {
                    return false;
                }

                return true;
            });
            pagination.value = {
                current_page: response.meta?.current_page || 1,
                last_page: response.meta?.last_page || 1,
                per_page: response.meta?.per_page || 15,
                total: response.meta?.total || 0,
                from: response.meta?.from || 0,
                to: response.meta?.to || 0,
            };

            return response;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal memuat audit logs';
            console.error('Error fetching audit logs:', err);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Fetch available model types
     */
    const fetchModelTypes = async () => {
        try {
            const portalId = portal.value[0]?.id;
            if (!portalId) {
                throw new Error('Portal ID tidak ditemukan');
            }

            const response = await mainStore.useAPI(
                `${portalId}/api/audit-logs/model-types`,
                { method: 'GET' },
                true // useAuth
            );
            
            modelTypes.value = response.data || [];
            return response.data.data;
        } catch (err) {
            console.error('Error fetching model types:', err);
            throw err;
        }
    };

    /**
     * Fetch available event types
     */
    const fetchEventTypes = async () => {
        try {
            const portalId = portal.value[0]?.id;
            if (!portalId) {
                throw new Error('Portal ID tidak ditemukan');
            }

            const response = await mainStore.useAPI(
                `${portalId}/api/audit-logs/event-types`,
                { method: 'GET' },
                true // useAuth
            );
            
            eventTypes.value = response.data || [];
            return response.data.data;
        } catch (err) {
            console.error('Error fetching event types:', err);
            throw err;
        }
    };

    /**
     * Export audit logs to CSV
     */
    const exportAuditLogs = async (filters = {}) => {
        try {
            const params = {
                ...(filters.model_type && { model_type: filters.model_type }),
                ...(filters.event && { event: filters.event }),
                ...(filters.user_id && { user_id: filters.user_id }),
                ...(filters.date_from && { date_from: filters.date_from }),
                ...(filters.date_to && { date_to: filters.date_to }),
                ...(filters.search && { search: filters.search }),
            };

            const response = await axios.get('/api/audit-logs/export', {
                params,
                responseType: 'blob',
            });

            // Create download link
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `audit-logs-${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
        } catch (err) {
            console.error('Error exporting audit logs:', err);
            throw err;
        }
    };

    return {
        auditLogs,
        modelTypes,
        eventTypes,
        loading,
        error,
        pagination,
        fetchAuditLogs,
        fetchModelTypes,
        fetchEventTypes,
        exportAuditLogs,
    };
}
