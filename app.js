/**
 * MailGuard DNS Diagnostics Javascript Client
 * 
 * Handles form submissions, input validations, UI transitions, circular progress animation,
 * dynamic element updates, clipboard operations, and alerts.
 */

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('checkerForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');
    const errorAlert = document.getElementById('errorAlert');
    const errorMessage = document.getElementById('errorMessage');
    const resultDashboard = document.getElementById('resultDashboard');

    // Handle Form Submit
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const domainInput = document.getElementById('domainInput').value.trim();
        const selectorInput = document.getElementById('selectorInput').value.trim();

        // 1. Client-Side Input Validation
        if (!domainInput) {
            showError('Please enter a domain name to continue.');
            return;
        }

        // Hide old outputs & errors before sending request
        hideError();
        setLoadingState(true);

        try {
            // Encode parameters
            const url = `api.php?domain=${encodeURIComponent(domainInput)}&selector=${encodeURIComponent(selectorInput)}`;
            
            const response = await fetch(url);
            const data = await response.json();

            if (response.ok && data.status === 'success') {
                renderDashboard(data);
            } else {
                showError(data.message || 'An error occurred while fetching DNS diagnostic report.');
            }

        } catch (err) {
            console.error(err);
            showError('Unable to connect to the backend checker API. Check your local server stack status.');
        } finally {
            setLoadingState(false);
        }
    });
});

/**
 * Loads a preset demo domain and triggers submission.
 */
window.loadPreset = function(domainName) {
    const domainInput = document.getElementById('domainInput');
    const selectorInput = document.getElementById('selectorInput');
    
    domainInput.value = domainName;
    
    // Clear selector for default demo domains so DKIM operates standard defaults
    selectorInput.value = (domainName === 'demo-critical.net') ? 'sig1' : '';
    
    // Dispatch submit event
    const form = document.getElementById('checkerForm');
    form.dispatchEvent(new Event('submit', { cancelable: true }));
};

/**
 * Toggles loader visual state during network request.
 */
function setLoadingState(isLoading) {
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');

    if (isLoading) {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-80', 'cursor-not-allowed');
        btnSpinner.classList.remove('hidden');
        btnText.textContent = 'Analyzing...';
    } else {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-80', 'cursor-not-allowed');
        btnSpinner.classList.add('hidden');
        btnText.textContent = 'Analyze Domain';
    }
}

/**
 * Displays error box.
 */
function showError(message) {
    const errorAlert = document.getElementById('errorAlert');
    const errorMessage = document.getElementById('errorMessage');
    
    errorMessage.textContent = message;
    errorAlert.classList.remove('hidden');
    errorAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

/**
 * Hides error box.
 */
function hideError() {
    const errorAlert = document.getElementById('errorAlert');
    errorAlert.classList.add('hidden');
}

/**
 * Renders JSON payload contents directly to dashboard nodes.
 */
function renderDashboard(data) {
    const dashboard = document.getElementById('resultDashboard');

    // 1. Text elements
    document.getElementById('scannedDomainName').textContent = data.domain;
    
    const dateFormatted = new Date(data.timestamp).toLocaleString('en-US', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    document.getElementById('diagnosticDate').textContent = dateFormatted;

    // 2. Demo flags
    const demoBadge = document.getElementById('demoModeBadge');
    const offlineSimBadge = document.getElementById('offlineSimBadge');
    
    if (data.is_demo) {
        demoBadge.classList.remove('hidden');
        if (data.is_offline_sim) {
            offlineSimBadge.classList.remove('hidden');
        } else {
            offlineSimBadge.classList.add('hidden');
        }
    } else {
        demoBadge.classList.add('hidden');
        offlineSimBadge.classList.add('hidden');
    }

    // 3. Render Circular Score
    animateScoreGauge(data.score);

    // 4. Update check elements (MX, SPF, DMARC, DKIM, SSL)
    updateCheckPanel('MX', data.checks.mx);
    updateCheckPanel('SPF', data.checks.spf);
    updateCheckPanel('DMARC', data.checks.dmarc);
    updateCheckPanel('DKIM', data.checks.dkim);
    updateCheckPanel('SSL', data.checks.ssl);

    // 5. Structure Copyable Full Report
    generateFullReportMarkdown(data);

    // 6. Visual transition reveal
    dashboard.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-4');
    dashboard.classList.add('opacity-100', 'translate-y-0');
    
    // Smooth scroll down to overview
    dashboard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

/**
 * Animates the radial progress gauge and updates status labels.
 */
function animateScoreGauge(score) {
    const ring = document.getElementById('scoreRing');
    const scoreText = document.getElementById('scoreText');
    const scoreRatingText = document.getElementById('scoreRatingText');
    
    const circumference = 314.16; // 2 * pi * radius (50)
    const offset = circumference - (circumference * (score / 100));

    // Clear old text colors
    ring.classList.remove('text-emerald-500', 'text-amber-500', 'text-rose-500');
    scoreText.classList.remove('text-emerald-400', 'text-amber-400', 'text-rose-400');
    scoreRatingText.classList.remove('text-emerald-400', 'text-amber-400', 'text-rose-400');

    // Score classification rules
    if (score >= 90) {
        ring.classList.add('text-emerald-500');
        scoreText.classList.add('text-emerald-400');
        scoreRatingText.classList.add('text-emerald-400');
        scoreRatingText.textContent = 'Excellent (Secure)';
    } else if (score >= 60) {
        ring.classList.add('text-amber-500');
        scoreText.classList.add('text-amber-400');
        scoreRatingText.classList.add('text-amber-400');
        scoreRatingText.textContent = 'Warning (Check recommended)';
    } else {
        ring.classList.add('text-rose-500');
        scoreText.classList.add('text-rose-400');
        scoreRatingText.classList.add('text-rose-400');
        scoreRatingText.textContent = 'Critical (Action required)';
    }

    // Set SVG animation values
    ring.style.strokeDashoffset = offset;
    
    // Count up animation for text
    let currentScore = 0;
    const interval = setInterval(() => {
        if (currentScore >= score) {
            scoreText.textContent = score;
            clearInterval(interval);
        } else {
            currentScore += Math.ceil((score - currentScore) / 4) || 1;
            scoreText.textContent = currentScore;
        }
    }, 25);
}

/**
 * Dynamically adjusts panel styling classes based on check outputs.
 */
function updateCheckPanel(name, checkData) {
    const statusTextNode = document.getElementById(`status${name}`);
    const statusBadgeNode = document.getElementById(`badge${name}`);
    const msgNode = document.getElementById(`msg${name}`);
    const tipNode = document.getElementById(`tip${name}`);

    // Fetch styling profile
    const style = getStatusStyles(checkData.status);

    // Apply color definitions to top row pills
    statusBadgeNode.className = `h-2.5 w-2.5 rounded-full ${style.indicatorClass}`;

    // Apply color definitions to detail panel badges
    statusTextNode.className = `px-2 py-0.5 rounded-md text-xs font-bold uppercase ${style.badgeClass}`;
    statusTextNode.textContent = style.label;

    // Apply diagnostic text
    msgNode.textContent = checkData.message;
    tipNode.textContent = checkData.tip;

    // Component specific renderers
    if (name === 'MX') {
        const listContainer = document.getElementById('listMX');
        listContainer.innerHTML = '';
        if (checkData.records && checkData.records.length > 0) {
            checkData.records.forEach(r => {
                const item = document.createElement('div');
                item.className = 'flex justify-between items-center text-slate-300';
                item.innerHTML = `<span>Priority: <strong class="text-indigo-400">${r.pri}</strong></span> <span>${r.host}</span>`;
                listContainer.appendChild(item);
            });
        } else {
            listContainer.innerHTML = '<div class="text-slate-500 text-center py-2">No MX records published</div>';
        }
    }

    if (name === 'SPF' || name === 'DMARC') {
        const recordNode = document.getElementById(`recordVal${name}`);
        if (checkData.record) {
            recordNode.textContent = checkData.record;
            recordNode.classList.remove('text-slate-500', 'italic');
            recordNode.classList.add('text-slate-300');
        } else {
            recordNode.textContent = 'None detected';
            recordNode.classList.remove('text-slate-300');
            recordNode.classList.add('text-slate-500', 'italic');
        }
    }

    if (name === 'DKIM') {
        const recordNode = document.getElementById('recordValDKIM');
        const selectorBadge = document.getElementById('badgeSelectorUsed');

        if (checkData.record) {
            recordNode.textContent = checkData.record;
            recordNode.classList.remove('text-slate-500', 'italic');
            recordNode.classList.add('text-slate-300');
        } else {
            recordNode.textContent = 'No DKIM public key published';
            recordNode.classList.remove('text-slate-300');
            recordNode.classList.add('text-slate-500', 'italic');
        }

        if (checkData.selector_used) {
            selectorBadge.textContent = `selector: ${checkData.selector_used}`;
            selectorBadge.classList.remove('hidden');
        } else {
            selectorBadge.classList.add('hidden');
        }
    }

    if (name === 'SSL') {
        document.getElementById('certIssuer').textContent = checkData.issuer || 'N/A';
        document.getElementById('certSubject').textContent = checkData.subject || 'N/A';
        document.getElementById('certExpiry').textContent = checkData.valid_to ? `${checkData.valid_to} (${checkData.days_remaining} days left)` : 'N/A';
    }
}

/**
 * Returns Tailwind css utility styles depending on verification status.
 */
function getStatusStyles(status) {
    switch (status) {
        case 'valid':
            return {
                badgeClass: 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
                indicatorClass: 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.8)]',
                label: 'Pass'
            };
        case 'warning':
            return {
                badgeClass: 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
                indicatorClass: 'bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.8)]',
                label: 'Warning'
            };
        case 'critical':
            return {
                badgeClass: 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
                indicatorClass: 'bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.8)]',
                label: 'Failed'
            };
        case 'info':
        default:
            return {
                badgeClass: 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20',
                indicatorClass: 'bg-indigo-400 shadow-[0_0_8px_rgba(99,102,241,0.8)]',
                label: 'Info'
            };
    }
}

/**
 * Generates a clean markdown assessment summary in the textarea container.
 */
function generateFullReportMarkdown(data) {
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
}

/**
 * Copies record text elements to clipboard.
 */
window.copyRecordText = function(elementId) {
    const val = document.getElementById(elementId).textContent.trim();
    if (val === 'None detected' || val === 'No DKIM public key published' || val === '') {
        showToast('Nothing to copy', true);
        return;
    }
    navigator.clipboard.writeText(val)
        .then(() => showToast('Record value copied to clipboard!'))
        .catch(err => console.error('Failed to copy text: ', err));
};

/**
 * Copies full text assessment report block.
 */
window.copyFullReport = function() {
    const reportText = document.getElementById('fullReportArea').value;
    navigator.clipboard.writeText(reportText)
        .then(() => {
            const btn = document.getElementById('copyReportBtnText');
            btn.textContent = 'Copied!';
            showToast('Full Markdown diagnostics report copied!');
            setTimeout(() => {
                btn.textContent = 'Copy Full Report';
            }, 2500);
        })
        .catch(err => console.error('Failed to copy assessment: ', err));
};

/**
 * Displays overlay toast popups.
 */
function showToast(message, isWarning = false) {
    const toast = document.getElementById('toast');
    const toastMsg = document.getElementById('toastMsg');
    
    toastMsg.textContent = message;
    
    // Change color depending on level
    if (isWarning) {
        toast.className = "fixed bottom-5 right-5 z-50 transform translate-y-0 opacity-100 transition-all duration-300 ease-out border border-amber-500/20 bg-slate-900/90 text-slate-100 px-4 py-3 rounded-xl flex items-center space-x-2 shadow-2xl pointer-events-none";
    } else {
        toast.className = "fixed bottom-5 right-5 z-50 transform translate-y-0 opacity-100 transition-all duration-300 ease-out border border-emerald-500/20 bg-slate-900/90 text-slate-100 px-4 py-3 rounded-xl flex items-center space-x-2 shadow-2xl pointer-events-none";
    }

    setTimeout(() => {
        toast.classList.add('translate-y-20', 'opacity-0');
    }, 2500);
}
