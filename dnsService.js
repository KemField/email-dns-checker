/**
 * dnsService.js - API Service Layer
 * 
 * Manages fetch requests to api.php, handles connection timeouts
 * via AbortController, and wraps error processing cleanly.
 */

export const DnsService = {
    /**
     * Hálózati hívás végrehajtása standard és timeout hibakezeléssel
     */
    async fetchReport(domain, selector = '', signal = null) {
        const encDomain = encodeURIComponent(domain);
        const encSelector = encodeURIComponent(selector || '');
        const url = `api.php?domain=${encDomain}&selector=${encSelector}`;

        const response = await fetch(url, { signal });
        
        if (!response.ok) {
            const errJson = await response.json().catch(() => ({}));
            throw new Error(errJson.message || `HTTP Error ${response.status}`);
        }

        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Verification failed');
        }

        return data;
    },

    /**
     * Wrapper 8 másodperces időtúllépéssel (AbortController)
     */
    async fetchReportWithTimeout(domain, selector = '') {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 8000);

        try {
            return await this.fetchReport(domain, selector, controller.signal);
        } catch (error) {
            if (error.name === 'AbortError') {
                throw new Error('Connection timed out (exceeded 8s limit). Please check your internet connection.');
            }
            throw error;
        } finally {
            clearTimeout(timeoutId);
        }
    }
};
