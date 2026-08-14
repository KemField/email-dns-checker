/**
 * useRateLimiter.js - Client-Side Throttling Logic
 * 
 * Monitors query frequency and enforces button lockout with
 * countdown triggers when thresholds are exceeded.
 */

export function createRateLimiter(maxQueries = 3, windowMs = 15000, lockoutSec = 10) {
    let queryTimestamps = [];
    let activeLock = false;
    let countdownInterval = null;

    return {
        /**
         * Végrehajtja a korlátozás-ellenőrzést
         * @returns {boolean} Engedélyezett-e a keresés
         */
        requestSearch(onLock, onTick, onUnlock) {
            if (activeLock) return false;

            const now = Date.now();
            queryTimestamps = queryTimestamps.filter(t => now - t < windowMs);

            if (queryTimestamps.length >= maxQueries) {
                this.triggerLockout(lockoutSec, onLock, onTick, onUnlock);
                return false;
            }

            queryTimestamps.push(now);
            return true;
        },

        /**
         * Letiltási állapot indítása és visszaszámlálás kezelése
         */
        triggerLockout(duration, onLock, onTick, onUnlock) {
            activeLock = true;
            let secondsLeft = duration;
            onLock(secondsLeft);

            countdownInterval = setInterval(() => {
                secondsLeft--;
                if (secondsLeft <= 0) {
                    clearInterval(countdownInterval);
                    activeLock = false;
                    onUnlock();
                } else {
                    onTick(secondsLeft);
                }
            }, 1000);
        },

        isLocked() {
            return activeLock;
        }
    };
}
