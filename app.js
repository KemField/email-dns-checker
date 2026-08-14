/**
 * app.js - Main Application Controller
 * 
 * Configures event listeners, binds modules together, parses inputs,
 * coordinates rate limiting checks, and syncs AppState to visual triggers.
 */

import { Sanitizer } from './sanitizer.js';
import { DnsService } from './dnsService.js';
import { createRateLimiter } from './useRateLimiter.js';
import { AppState } from './state.js';
import { Renderer } from './render.js';

document.addEventListener('DOMContentLoaded', () => {
    initApp();
});

/**
 * Initializes the main app bindings
 */
function initApp() {
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const rateLimiter = createRateLimiter(3, 20000, 10);

    // Subscribe store changes to update view states
    AppState.subscribe((state) => {
        updateUIState(state, submitBtn, btnText);
    });

    document.getElementById('checkerForm').addEventListener('submit', (e) => {
        handleFormSubmit(e, rateLimiter);
    });
}

/**
 * Syncs the global states with UI
 */
function updateUIState(state, submitBtn, btnText) {
    Renderer.setLoading(state.isLoading);
    
    if (state.errorMessage) {
        Renderer.showError(state.errorMessage);
    } else {
        Renderer.hideError();
    }

    if (state.resultData) {
        Renderer.renderDashboard(state.resultData);
    }

    applyLockoutUI(state, submitBtn, btnText);
}

/**
 * Toggles disabled visual configurations on lockouts
 */
function applyLockoutUI(state, submitBtn, btnText) {
    if (state.isRateLimitLocked) {
        submitBtn.disabled = true;
        btnText.textContent = `Locked (${state.lockSecondsRemaining}s)`;
        submitBtn.classList.add('opacity-80', 'cursor-not-allowed');
    } else if (!state.isLoading) {
        submitBtn.disabled = false;
        btnText.textContent = 'Analyze Domain';
        submitBtn.classList.remove('opacity-80', 'cursor-not-allowed');
    }
}

/**
 * Form submit pipeline orchestrator
 */
async function handleFormSubmit(e, rateLimiter) {
    e.preventDefault();

    const domain = document.getElementById('domainInput').value.trim();
    const selector = document.getElementById('selectorInput').value.trim();

    if (!validateInputs(domain)) {
        return;
    }

    const isAllowed = checkRateLimiter(rateLimiter);
    if (!isAllowed) return;

    AppState.update({ isLoading: true, errorMessage: null, resultData: null });

    try {
        const data = await DnsService.fetchReportWithTimeout(domain, selector);
        AppState.update({ resultData: data, isLoading: false });
    } catch (err) {
        AppState.update({ errorMessage: err.message, isLoading: false });
    }
}

/**
 * Validation guard queries
 */
function validateInputs(domain) {
    if (!domain) {
        AppState.update({ errorMessage: 'Please enter a domain name to continue.' });
        return false;
    }
    if (!Sanitizer.isValidDomain(domain)) {
        AppState.update({ errorMessage: 'Please enter a valid domain name (e.g. google.com).' });
        return false;
    }
    if (Sanitizer.isForbiddenHost(domain)) {
        AppState.update({ errorMessage: 'Security Check: Access to localhost or internal hosts is restricted.' });
        return false;
    }
    return true;
}

/**
 * Triggers rate limiting routines
 */
function checkRateLimiter(rateLimiter) {
    return rateLimiter.requestSearch(
        (remaining) => AppState.update({ isRateLimitLocked: true, lockSecondsRemaining: remaining }),
        (remaining) => AppState.update({ lockSecondsRemaining: remaining }),
        () => AppState.update({ isRateLimitLocked: false, lockSecondsRemaining: 0 })
    );
}

// ========================================================
// Global Window bindings for HTML interface integrations
// ========================================================

window.loadPreset = function(domainName) {
    const domainInput = document.getElementById('domainInput');
    const selectorInput = document.getElementById('selectorInput');
    domainInput.value = domainName;
    selectorInput.value = (domainName === 'demo-critical.net') ? 'sig1' : '';

    const form = document.getElementById('checkerForm');
    form.dispatchEvent(new Event('submit', { cancelable: true }));
};

window.copyRecordText = function(elementId) {
    const rawVal = document.getElementById(elementId).textContent.trim();
    const sanitizedVal = Sanitizer.sanitizeClipboardText(rawVal);
    
    if (sanitizedVal === 'None detected' || sanitizedVal === 'No DKIM public key published' || sanitizedVal === '') {
        Renderer.showToast('Nothing to copy', true);
        return;
    }

    navigator.clipboard.writeText(sanitizedVal)
        .then(() => Renderer.showToast('Record value copied to clipboard!'))
        .catch(err => console.error('Failed to copy: ', err));
};

window.copyFullReport = function() {
    const reportVal = document.getElementById('fullReportArea').value;
    const sanitizedReport = Sanitizer.sanitizeClipboardText(reportVal);

    navigator.clipboard.writeText(sanitizedReport)
        .then(() => {
            const btn = document.getElementById('copyReportBtnText');
            btn.textContent = 'Copied!';
            Renderer.showToast('Full Markdown diagnostics report copied!');
            setTimeout(() => {
                btn.textContent = 'Copy Full Report';
            }, 2500);
        })
        .catch(err => console.error('Failed to copy: ', err));
};
