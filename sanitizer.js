/**
 * sanitizer.js - Zero Trust Client-Side Security Layer
 * 
 * Implements strict domain validators, XSS text encoding, 
 * private network host blocks, and clipboard input filters.
 */

export const Sanitizer = {
    /**
     * Szigorú FQDN Domain Validator (RFC 1035)
     */
    isValidDomain(domain) {
        if (!domain || typeof domain !== 'string') return false;
        const cleanDomain = domain.trim().toLowerCase();
        // RFC 1035 Domain regex
        const domainRegex = /^(?!:\/\/)([a-zA-Z0-9-_]{1,63}\.)+[a-zA-Z]{2,15}$/;
        return domainRegex.test(cleanDomain);
    },

    /**
     * SSRF / Belső hálózat blokkoló lista ellenőrzés kliensoldalon
     */
    isForbiddenHost(domain) {
        if (!domain || typeof domain !== 'string') return true;
        const clean = domain.trim().toLowerCase();
        const forbidden = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];
        return forbidden.includes(clean) || clean.endsWith('.local') || clean.endsWith('.internal');
    },

    /**
     * XSS Sanitizer: Speciális HTML karakterek kódolása
     */
    sanitizeHTML(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

    /**
     * Clipboard Protection: Eltávolítja a vezérlőkaraktereket a beillesztett/másolt adatokból
     */
    sanitizeClipboardText(text) {
        if (!text || typeof text !== 'string') return '';
        // Eltávolítja a veszélyes láthatatlan vezérlőkaraktereket és a javascript: sémát
        const clean = text.replace(/[\x00-\x1F\x7F-\x9F]/g, '').trim();
        if (clean.toLowerCase().startsWith('javascript:')) {
            return '';
        }
        return clean;
    }
};
