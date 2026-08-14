/**
 * render.js - UI Render Layer
 * 
 * Handles DOM element state modifications, inline SVG insertions, 
 * radial animations, loading skeletons, and WCAG accessibility cues.
 */

import { Sanitizer } from './sanitizer.js';

// WCAG AAA Contrast status designs
const STATUS_STYLES = {
    valid: {
        bg: 'bg-emerald-950/40 text-emerald-400 border border-emerald-500/20',
        dot: 'bg-emerald-500 shadow-[0_0_8px_rgba(34,197,94,0.8)]',
        label: 'Pass',
        icon: `<svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                 <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
               </svg>`
    },
    warning: {
        bg: 'bg-amber-950/40 text-amber-400 border border-amber-500/20',
        dot: 'bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.8)]',
        label: 'Warning',
        icon: `<svg class="w-4 h-4 text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                 <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
               </svg>`
    },
    critical: {
        bg: 'bg-rose-950/40 text-rose-400 border border-rose-500/20',
        dot: 'bg-rose-500 shadow-[0_0_8px_rgba(239,68,68,0.8)]',
        label: 'Failed',
        icon: `<svg class="w-4 h-4 text-rose-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                 <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
               </svg>`
    },
    info: {
        bg: 'bg-indigo-950/40 text-indigo-400 border border-indigo-500/20',
        dot: 'bg-indigo-400 shadow-[0_0_8px_rgba(129,140,248,0.8)]',
        label: 'Info',
        icon: `<svg class="w-4 h-4 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                 <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
               </svg>`
    }
};

export const Renderer = {
    /**
     * Toggles Loading Spinner and Skeleton visual states
     */
    setLoading(isLoading) {
        const btnText = document.getElementById('btnText');
        const btnSpinner = document.getElementById('btnSpinner');
        const submitBtn = document.getElementById('submitBtn');
        const skeleton = document.getElementById('skeletonLoader');
        const dashboard = document.getElementById('resultDashboard');

        submitBtn.disabled = isLoading;
        if (isLoading) {
            btnSpinner.classList.remove('hidden');
            btnText.textContent = 'Analyzing...';
            skeleton.classList.remove('hidden');
            dashboard.classList.add('hidden');
        } else {
            btnSpinner.classList.add('hidden');
            btnText.textContent = 'Analyze Domain';
            skeleton.classList.add('hidden');
        }
    },

    /**
     * Renders API Error Message Banner (WCAG accessible)
     */
    showError(msg) {
        const errorAlert = document.getElementById('errorAlert');
        const errorMessage = document.getElementById('errorMessage');
        errorMessage.textContent = Sanitizer.sanitizeHTML(msg);
        errorAlert.classList.remove('hidden');
        errorAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    },

    /**
     * Hides error alert banner
     */
    hideError() {
        document.getElementById('errorAlert').classList.add('hidden');
    },

    /**
     * Renders assessment data onto the dashboard panel
     */
    renderDashboard(data) {
        const dashboard = document.getElementById('resultDashboard');
        document.getElementById('scannedDomainName').textContent = Sanitizer.sanitizeHTML(data.domain);

        const dateStr = new Date(data.timestamp).toLocaleString('en-US', {
            day: 'numeric', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
        document.getElementById('diagnosticDate').textContent = dateStr;

        this.toggleDemoBadges(data.is_demo, data.is_offline_sim);
        this.animateScoreGauge(data.score);

        // Update each component card
        this.updateCheckPanel('MX', data.checks.mx);
        this.updateCheckPanel('SPF', data.checks.spf);
        this.updateCheckPanel('DMARC', data.checks.dmarc);
        this.updateCheckPanel('DKIM', data.checks.dkim);
        this.updateCheckPanel('SSL', data.checks.ssl);

        this.generateFullReportMarkdown(data);

        dashboard.classList.remove('hidden');
        dashboard.classList.remove('opacity-0', 'translate-y-4');
        dashboard.classList.add('opacity-100', 'translate-y-0');
        dashboard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    },

    /**
     * Toggles demo badge visibilities
     */
    toggleDemoBadges(isDemo, isOffline) {
        const demoBadge = document.getElementById('demoModeBadge');
        const offlineBadge = document.getElementById('offlineSimBadge');

        demoBadge.classList.toggle('hidden', !isDemo);
        offlineBadge.classList.toggle('hidden', !(isDemo && isOffline));
    },

    /**
     * Animates the radial health score gauge
     */
    animateScoreGauge(score) {
        const ring = document.getElementById('scoreRing');
        const scoreText = document.getElementById('scoreText');
        const scoreRatingText = document.getElementById('scoreRatingText');
        
        const circumference = 314.16;
        const offset = circumference - (circumference * (score / 100));

        ring.classList.remove('text-emerald-500', 'text-amber-500', 'text-rose-500');
        scoreText.classList.remove('text-emerald-400', 'text-amber-400', 'text-rose-400');
        scoreRatingText.classList.remove('text-emerald-400', 'text-amber-400', 'text-rose-400');

        let color = score >= 90 ? 'emerald' : (score >= 60 ? 'amber' : 'rose');
        ring.classList.add(`text-${color}-500`);
        scoreText.classList.add(`text-${color}-400`);
        scoreRatingText.classList.add(`text-${color}-400`);
        scoreRatingText.textContent = score >= 90 ? 'Excellent (Secure)' : (score >= 60 ? 'Warning (Check recommended)' : 'Critical (Action required)');

        ring.style.strokeDashoffset = offset;
        this.countUpScore(score, scoreText);
    },

    /**
     * Count-up animation helper
     */
    countUpScore(target, element) {
        let current = 0;
        const interval = setInterval(() => {
            if (current >= target) {
                element.textContent = target;
                clearInterval(interval);
            } else {
                current += Math.ceil((target - current) / 4) || 1;
                element.textContent = current;
            }
        }, 20);
    },

    /**
     * Updates an individual panel card details (MX, SPF, etc.)
     */
    updateCheckPanel(name, checkData) {
        const textNode = document.getElementById(`status${name}`);
        const badgeNode = document.getElementById(`badge${name}`);
        const msgNode = document.getElementById(`msg${name}`);
        const tipNode = document.getElementById(`tip${name}`);

        const style = STATUS_STYLES[checkData.status] || STATUS_STYLES.info;

        badgeNode.className = `h-2.5 w-2.5 rounded-full ${style.dot}`;
        textNode.className = `inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold ${style.bg}`;
        textNode.innerHTML = `${style.icon} <span role="status" aria-live="polite">${style.label}</span>`;

        msgNode.textContent = checkData.message;
        tipNode.textContent = checkData.tip;

        // Custom details injections
        if (name === 'MX') this.renderMXList(checkData.records);
        if (name === 'SPF') this.renderRawRecord('recordValSPF', checkData.record);
        if (name === 'DMARC') this.renderRawRecord('recordValDMARC', checkData.record);
        if (name === 'DKIM') this.renderDKIMRecord(checkData);
        if (name === 'SSL') this.renderSSLMetadata(checkData);
    },

    renderMXList(records) {
        const list = document.getElementById('listMX');
        list.innerHTML = '';
        if (records && records.length > 0) {
            records.forEach(r => {
                const item = document.createElement('div');
                item.className = 'flex justify-between items-center text-slate-300';
                item.innerHTML = `<span>Priority: <strong class="text-indigo-400">${r.pri}</strong></span> <span>${Sanitizer.sanitizeHTML(r.host)}</span>`;
                list.appendChild(item);
            });
        } else {
            list.innerHTML = '<div class="text-slate-500 text-center py-2">No MX records published</div>';
        }
    },

    renderRawRecord(elementId, val) {
        const node = document.getElementById(elementId);
        if (val) {
            node.textContent = Sanitizer.sanitizeHTML(val);
            node.classList.remove('text-slate-500', 'italic');
            node.classList.add('text-slate-300');
        } else {
            node.textContent = 'None detected';
            node.classList.remove('text-slate-300');
            node.classList.add('text-slate-500', 'italic');
        }
    },

    renderDKIMRecord(checkData) {
        const valNode = document.getElementById('recordValDKIM');
        const badge = document.getElementById('badgeSelectorUsed');

        if (checkData.record) {
            valNode.textContent = Sanitizer.sanitizeHTML(checkData.record);
            valNode.classList.remove('text-slate-500', 'italic');
            valNode.classList.add('text-slate-300');
        } else {
            valNode.textContent = 'No DKIM public key published';
            valNode.classList.remove('text-slate-300');
            valNode.classList.add('text-slate-500', 'italic');
        }

        if (checkData.selector_used) {
            badge.textContent = `selector: ${Sanitizer.sanitizeHTML(checkData.selector_used)}`;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    },

    renderSSLMetadata(checkData) {
        document.getElementById('certIssuer').textContent = Sanitizer.sanitizeHTML(checkData.issuer) || 'N/A';
        document.getElementById('certSubject').textContent = Sanitizer.sanitizeHTML(checkData.subject) || 'N/A';
        
        const expiryNode = document.getElementById('certExpiry');
        if (checkData.valid_to) {
            expiryNode.textContent = `${checkData.valid_to} (${checkData.days_remaining} days left)`;
        } else {
            expiryNode.textContent = 'N/A';
        }
    },

    /**
     * Generates clipboard copyable assessment reports
     */
    generateFullReportMarkdown(data) {
        const mxText = data.checks.mx.records.length > 0
            ? data.checks.mx.records.map(r => `  - [Pri: ${r.pri}] ${r.host}`).join('\n')
            : '  - None';

        const report = `=====================================================
MAILGUARD DNS HEALTH CHECKER ASSESSMENT REPORT
=====================================================
Domain Investigated : ${data.domain}
Overall Health Score : ${data.score}/100
Diagnostics Datetime: ${new Date(data.timestamp).toUTCString()}
Diagnostic Context  : ${data.is_demo ? 'Simulation/Mock Check' : 'Live Network Query'}
-----------------------------------------------------

1. MAIL EXCHANGE (MX) RECORDS [Status: ${data.checks.mx.status.toUpperCase()}]
   Result: ${data.checks.mx.message}
   Servers Identified:
${mxText}
   Support Tip: ${data.checks.mx.tip}

2. SENDER POLICY FRAMEWORK (SPF) [Status: ${data.checks.spf.status.toUpperCase()}]
   Result: ${data.checks.spf.message}
   Record: ${data.checks.spf.record || 'No SPF record found'}
   Support Tip: ${data.checks.spf.tip}

3. DMARC POLICY AUTHENTICATION [Status: ${data.checks.dmarc.status.toUpperCase()}]
   Result: ${data.checks.dmarc.message}
   Record: ${data.checks.dmarc.record || 'No DMARC record found'}
   Support Tip: ${data.checks.dmarc.tip}

4. DOMAINKEYS IDENTIFIED MAIL (DKIM) [Status: ${data.checks.dkim.status.toUpperCase()}]
   Result: ${data.checks.dkim.message}
   Selector Investigated: ${data.checks.dkim.selector_used || 'None (auto-checked generic)'}
   Record: ${data.checks.dkim.record ? data.checks.dkim.record.substring(0, 100) + '...' : 'No public DKIM signature verified'}
   Support Tip: ${data.checks.dkim.tip}

5. SSL / HTTPS SECURITY TUNNEL [Status: ${data.checks.ssl.status.toUpperCase()}]
   Result: ${data.checks.ssl.message}
   Issuer: ${data.checks.ssl.issuer || 'N/A'}
   Expires on: ${data.checks.ssl.valid_to || 'N/A'} (${data.checks.ssl.days_remaining || 0} days remaining)
   Support Tip: ${data.checks.ssl.tip}

=====================================================
Generated by MailGuard DNS Check Portfolio Widget
=====================================================`;

        document.getElementById('fullReportArea').value = report;
    },

    /**
     * Show Toast message boxes
     */
    showToast(message, isWarning = false) {
        const toast = document.getElementById('toast');
        const toastMsg = document.getElementById('toastMsg');
        
        toastMsg.textContent = message;
        
        let borderCol = isWarning ? 'border-amber-500/20' : 'border-emerald-500/20';
        toast.className = `fixed bottom-5 right-5 z-50 transform translate-y-0 opacity-100 transition-all duration-300 ease-out border ${borderCol} bg-slate-900/90 text-slate-100 px-4 py-3 rounded-xl flex items-center space-x-2 shadow-2xl pointer-events-none`;

        setTimeout(() => {
            toast.classList.add('translate-y-20', 'opacity-0');
        }, 2500);
    }
};
