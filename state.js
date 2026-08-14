/**
 * state.js - Shared Application State Store
 * 
 * Implements a simple reactive store using the Pub/Sub pattern
 * to coordinate changes between logic layers and rendering systems.
 */

export const AppState = {
    state: {
        isLoading: false,
        currentDomain: '',
        currentSelector: '',
        resultData: null,
        errorMessage: null,
        isRateLimitLocked: false,
        lockSecondsRemaining: 0
    },

    listeners: [],

    /**
     * Reaktivitás feliratkozás (Pub/Sub pattern)
     */
    subscribe(callback) {
        this.listeners.push(callback);
        // Leiratkozási metódust ad vissza
        return () => {
            this.listeners = this.listeners.filter(cb => cb !== callback);
        };
    },

    /**
     * Frissíti a belső állapotot és értesíti a feliratkozókat
     */
    update(changeSet) {
        this.state = { ...this.state, ...changeSet };
        this.notify();
    },

    /**
     * Végigfut az összes regisztrált callbacken
     */
    notify() {
        this.listeners.forEach(callback => callback(this.state));
    },

    /**
     * Alaphelyzetbe állítja az állapotot
     */
    reset() {
        this.update({
            isLoading: false,
            resultData: null,
            errorMessage: null
        });
    }
};
