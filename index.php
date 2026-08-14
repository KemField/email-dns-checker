<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Deliverability & DNS Health Checker</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        slate: {
                            950: '#0A0A0F',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .custom-glow:hover {
            box-shadow: 0 0 25px rgba(99, 102, 241, 0.4);
            border-color: rgba(99, 102, 241, 0.6);
        }
        .progress-ring__circle {
            transition: stroke-dashoffset 0.6s ease-in-out;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }
    </style>
</head>
<body class="bg-[#0A0A0F] text-slate-100 min-h-screen font-sans flex flex-col selection:bg-indigo-500/30 selection:text-indigo-200">

    <!-- Header Section -->
    <header class="border-b border-white/[0.08] bg-[#0A0A0F]/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="bg-indigo-600/20 p-2 rounded-xl border border-indigo-500/30">
                    <svg class="h-6 w-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 via-sky-400 to-emerald-400">
                        MailGuard DNS
                    </h1>
                    <p class="text-xs text-slate-300">Email Deliverability & DNS Diagnostics</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <span class="hidden md:inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-900 text-slate-200 border border-white/[0.08]">
                    PHP 8.2 + ES6
                </span>
                <a href="https://github.com" target="_blank" class="text-slate-400 hover:text-white transition-colors" title="GitHub Repository">
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.462-1.11-1.462-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.579.688.481C19.137 20.162 22 16.418 22 12c0-5.523-4.477-10-10-10z" />
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 flex flex-col space-y-8">
        
        <!-- Intro Hero Panel -->
        <div class="text-center max-w-3xl mx-auto space-y-4">
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white sm:text-5xl">
                Is your domain configuration blocking outgoing emails?
            </h2>
            <p class="text-lg text-slate-300">
                Spam filters are stricter than ever. Instantly inspect your domain's <span class="text-indigo-400 font-semibold">SPF</span>, <span class="text-sky-400 font-semibold">DMARC</span>, <span class="text-emerald-400 font-semibold">DKIM</span>, <span class="text-violet-400 font-semibold">MX</span>, and <span class="text-pink-400 font-semibold">SSL</span> setup to guarantee maximum email deliverability.
            </p>
        </div>

        <!-- Lookup Control Panel -->
        <section class="backdrop-blur-md bg-[#12121A] border border-white/[0.08] shadow-2xl rounded-3xl p-6 sm:p-8 max-w-4xl mx-auto w-full transition-all duration-300">
            <form id="checkerForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    
                    <!-- Domain Input -->
                    <div class="md:col-span-2 space-y-2">
                        <label for="domainInput" class="block text-sm font-semibold text-slate-300">
                            Domain Name <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-450">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                </svg>
                            </div>
                            <input type="text" name="domain" id="domainInput" required autocomplete="off"
                                   class="block w-full pl-11 pr-4 py-3.5 bg-slate-950/80 border border-white/[0.08] rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all font-medium text-base shadow-inner"
                                   placeholder="e.g., google.com or company.hu">
                        </div>
                    </div>

                    <!-- Optional Selector Input -->
                    <div class="space-y-2">
                        <label for="selectorInput" class="block text-sm font-semibold text-slate-300 flex items-center justify-between">
                            <span>DKIM Selector</span>
                            <span class="text-xs text-indigo-400 font-normal">Optional</span>
                        </label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-450">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                            </div>
                            <input type="text" name="selector" id="selectorInput" autocomplete="off"
                                   class="block w-full pl-11 pr-4 py-3.5 bg-slate-950/80 border border-white/[0.08] rounded-xl text-white placeholder-slate-650 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all font-medium text-base"
                                   placeholder="e.g., default or sig1">
                        </div>
                    </div>
                </div>

                <!-- Submit and Loader -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2">
                    <!-- Preset Badges -->
                    <div class="space-y-1.5">
                        <span class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Try Demo Presets</span>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="loadPreset('demo-perfect.com')" class="px-3 py-1.5 rounded-lg text-xs font-medium border border-emerald-500/20 bg-emerald-500/10 text-emerald-455 hover:bg-emerald-500/20 hover:border-emerald-500/30 transition-colors">
                                Clean Pass (100%)
                            </button>
                            <button type="button" onclick="loadPreset('demo-warning.org')" class="px-3 py-1.5 rounded-lg text-xs font-medium border border-amber-500/20 bg-amber-500/10 text-amber-455 hover:bg-amber-500/20 hover:border-amber-500/30 transition-colors">
                                Warnings (60%)
                            </button>
                            <button type="button" onclick="loadPreset('demo-critical.net')" class="px-3 py-1.5 rounded-lg text-xs font-medium border border-rose-500/20 bg-rose-500/10 text-rose-455 hover:bg-rose-500/20 hover:border-rose-500/30 transition-colors">
                                Critical Issues (20%)
                            </button>
                        </div>
                    </div>

                    <!-- Button -->
                    <button type="submit" id="submitBtn" class="px-6 py-3.5 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-indigo-500/20 flex items-center justify-center space-x-2 custom-glow min-w-[160px]">
                        <span id="btnText">Analyze Domain</span>
                        <!-- Spinner (hidden by default) -->
                        <svg id="btnSpinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </section>

        <!-- Error Dialog Banner (hidden by default) -->
        <div id="errorAlert" role="alert" aria-live="assertive" class="max-w-4xl mx-auto w-full hidden border border-rose-500/20 bg-rose-500/10 text-rose-400 px-5 py-4 rounded-2xl flex items-start space-x-3 animate-pulse">
            <svg class="h-6 w-6 flex-shrink-0 mt-0.5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <h4 class="font-semibold text-slate-100">Scan Failed</h4>
                <p class="text-sm text-rose-300/90 mt-1" id="errorMessage">Please provide a valid domain name.</p>
            </div>
        </div>

        <!-- Skeleton Loader (hidden by default) -->
        <div id="skeletonLoader" class="max-w-6xl mx-auto w-full hidden space-y-8">
            <!-- Header Skeleton -->
            <div class="animate-pulse backdrop-blur-md bg-[#12121A] border border-white/[0.08] rounded-3xl p-6 sm:p-8 flex flex-col md:flex-row items-center gap-8 justify-between">
                <div class="space-y-3 flex-grow w-full">
                    <div class="h-8 bg-slate-800 rounded w-1/3"></div>
                    <div class="h-4 bg-slate-800 rounded w-1/4"></div>
                    <div class="flex gap-2 pt-2">
                        <div class="h-6 bg-slate-800 rounded w-20"></div>
                        <div class="h-6 bg-slate-800 rounded w-20"></div>
                    </div>
                </div>
                <div class="h-28 w-28 bg-slate-800 rounded-full flex-shrink-0"></div>
            </div>
            <!-- Grid Skeleton -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="animate-pulse backdrop-blur-md bg-[#12121A] border border-white/[0.08] rounded-3xl p-6 h-56 space-y-4">
                    <div class="h-6 bg-slate-800 rounded w-1/3"></div>
                    <div class="h-4 bg-slate-800 rounded w-5/6"></div>
                    <div class="h-10 bg-slate-800 rounded w-full"></div>
                </div>
                <div class="animate-pulse backdrop-blur-md bg-[#12121A] border border-white/[0.08] rounded-3xl p-6 h-56 space-y-4">
                    <div class="h-6 bg-slate-800 rounded w-1/3"></div>
                    <div class="h-4 bg-slate-800 rounded w-5/6"></div>
                    <div class="h-10 bg-slate-800 rounded w-full"></div>
                </div>
            </div>
        </div>

        <!-- Dashboard Outputs (hidden until run) -->
        <div id="resultDashboard" class="space-y-8 hidden opacity-0 transition-all duration-700 transform translate-y-4 max-w-6xl mx-auto w-full">
            
            <!-- Diagnostics Header card -->
            <section class="backdrop-blur-md bg-[#12121A] border border-white/[0.08] rounded-3xl p-6 sm:p-8 shadow-2xl flex flex-col md:flex-row items-center gap-8 justify-between relative overflow-hidden">
                <div class="absolute -right-32 -top-32 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -left-32 -bottom-32 w-64 h-64 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none"></div>

                <!-- Info summary -->
                <div class="space-y-4 text-center md:text-left flex-grow">
                    <div>
                        <span id="demoModeBadge" class="hidden px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20 uppercase tracking-wide">
                            Simulated Demo Mode
                        </span>
                        <span id="offlineSimBadge" class="hidden px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 uppercase tracking-wide ml-2">
                            Offline Mock Mode
                        </span>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-2xl sm:text-3xl font-extrabold text-white flex items-center justify-center md:justify-start space-x-2">
                            <span id="scannedDomainName">domain.com</span>
                        </h3>
                        <p class="text-slate-300 text-sm">
                            Diagnostic run completed on <span id="diagnosticDate" class="text-slate-200">12 Aug 2026</span>
                        </p>
                    </div>

                    <!-- Quick Summary Pills -->
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 pt-2">
                        <div class="flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-slate-950 border border-white/[0.08]">
                            <span class="text-slate-350 text-xs uppercase font-semibold">MX</span>
                            <span id="badgeMX" class="h-2.5 w-2.5 rounded-full bg-slate-700"></span>
                        </div>
                        <div class="flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-slate-950 border border-white/[0.08]">
                            <span class="text-slate-350 text-xs uppercase font-semibold">SPF</span>
                            <span id="badgeSPF" class="h-2.5 w-2.5 rounded-full bg-slate-700"></span>
                        </div>
                        <div class="flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-slate-950 border border-white/[0.08]">
                            <span class="text-slate-350 text-xs uppercase font-semibold">DMARC</span>
                            <span id="badgeDMARC" class="h-2.5 w-2.5 rounded-full bg-slate-700"></span>
                        </div>
                        <div class="flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-slate-950 border border-white/[0.08]">
                            <span class="text-slate-350 text-xs uppercase font-semibold">DKIM</span>
                            <span id="badgeDKIM" class="h-2.5 w-2.5 rounded-full bg-slate-700"></span>
                        </div>
                        <div class="flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-slate-950 border border-white/[0.08]">
                            <span class="text-slate-350 text-xs uppercase font-semibold">SSL</span>
                            <span id="badgeSSL" class="h-2.5 w-2.5 rounded-full bg-slate-700"></span>
                        </div>
                    </div>
                </div>

                <!-- Radial Gauge Score widget -->
                <div class="flex flex-col items-center justify-center space-y-2 flex-shrink-0">
                    <div class="relative h-32 w-32 flex items-center justify-center">
                        <svg class="h-full w-full" viewBox="0 0 120 120" aria-label="Deliverability Score radial gauge" role="img">
                            <circle class="text-slate-800" stroke-width="8" stroke="currentColor" fill="transparent" r="50" cx="60" cy="60" />
                            <circle id="scoreRing" class="progress-ring__circle text-indigo-500" stroke-width="8" stroke-dasharray="314.16" stroke-dashoffset="314.16" stroke-linecap="round" stroke="currentColor" fill="transparent" r="50" cx="60" cy="60" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span id="scoreText" class="text-3xl font-extrabold text-white">0</span>
                            <span class="text-[10px] text-slate-300 uppercase tracking-widest font-bold">Health Score</span>
                        </div>
                    </div>
                    <span id="scoreRatingText" class="text-xs font-semibold uppercase tracking-wider text-indigo-400">Evaluating...</span>
                </div>
            </section>

            <!-- Detailed Grid Panels -->
            <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- MX Panel -->
                <div class="backdrop-blur-md bg-[#12121A] border border-white/[0.08] rounded-3xl p-6 shadow-xl flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b border-white/[0.08] pb-3">
                            <div class="flex items-center space-x-2.5">
                                <div class="p-1.5 bg-violet-500/10 text-violet-400 border border-violet-500/20 rounded-lg">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h4 class="font-bold text-white">Mail Exchange (MX)</h4>
                            </div>
                            <span id="statusMX" class="px-2 py-0.5 rounded-md text-xs font-semibold uppercase">Checking</span>
                        </div>

                        <p id="msgMX" class="text-sm text-slate-200">Checking MX record configuration...</p>
                        
                        <!-- List container -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider">Configured Servers</label>
                            <div id="listMX" class="bg-slate-950/80 border border-white/[0.08] rounded-xl p-3 text-xs font-mono space-y-1.5 max-h-[140px] overflow-y-auto">
                                <!-- entries loaded via JS -->
                            </div>
                        </div>
                    </div>

                    <!-- L2 Support Tip Box -->
                    <div class="mt-6 border-l-2 border-indigo-500/50 bg-indigo-500/5 px-4 py-3 rounded-r-xl space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-400 flex items-center space-x-1">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                            <span>Support Guidance</span>
                        </span>
                        <p id="tipMX" class="text-xs text-slate-200 leading-relaxed">Reading recommendations...</p>
                    </div>
                </div>

                <!-- SPF Panel -->
                <div class="backdrop-blur-md bg-[#12121A] border border-white/[0.08] rounded-3xl p-6 shadow-xl flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b border-white/[0.08] pb-3">
                            <div class="flex items-center space-x-2.5">
                                <div class="p-1.5 bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 rounded-lg">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h4 class="font-bold text-white">Sender Policy Framework (SPF)</h4>
                            </div>
                            <span id="statusSPF" class="px-2 py-0.5 rounded-md text-xs font-semibold uppercase">Checking</span>
                        </div>

                        <p id="msgSPF" class="text-sm text-slate-200">Checking SPF record details...</p>

                        <!-- SPF Record box -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-350 uppercase tracking-wider">Raw TXT Record</label>
                                <button type="button" onclick="copyRecordText('recordValSPF')" class="text-slate-400 hover:text-slate-200 text-xs transition-colors flex items-center space-x-1">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg>
                                    <span>Copy Record</span>
                                </button>
                            </div>
                            <div id="recordValSPF" class="bg-slate-950/80 border border-white/[0.08] rounded-xl p-3 text-xs font-mono break-all text-slate-300 min-h-[50px] flex items-center">
                                <!-- SPF text content loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- L2 Support Tip Box -->
                    <div class="mt-6 border-l-2 border-indigo-500/50 bg-indigo-500/5 px-4 py-3 rounded-r-xl space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-400 flex items-center space-x-1">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                            <span>Support Guidance</span>
                        </span>
                        <p id="tipSPF" class="text-xs text-slate-200 leading-relaxed">Reading recommendations...</p>
                    </div>
                </div>

                <!-- DMARC Panel -->
                <div class="backdrop-blur-md bg-[#12121A] border border-white/[0.08] rounded-3xl p-6 shadow-xl flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b border-white/[0.08] pb-3">
                            <div class="flex items-center space-x-2.5">
                                <div class="p-1.5 bg-sky-500/10 text-sky-400 border border-sky-500/20 rounded-lg">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <h4 class="font-bold text-white">DMARC Authentication</h4>
                            </div>
                            <span id="statusDMARC" class="px-2 py-0.5 rounded-md text-xs font-semibold uppercase">Checking</span>
                        </div>

                        <p id="msgDMARC" class="text-sm text-slate-200">Checking DMARC rules...</p>

                        <!-- DMARC Record box -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-355 uppercase tracking-wider">Raw TXT Record</label>
                                <button type="button" onclick="copyRecordText('recordValDMARC')" class="text-slate-400 hover:text-slate-200 text-xs transition-colors flex items-center space-x-1">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg>
                                    <span>Copy Record</span>
                                </button>
                            </div>
                            <div id="recordValDMARC" class="bg-slate-950/80 border border-white/[0.08] rounded-xl p-3 text-xs font-mono break-all text-slate-300 min-h-[50px] flex items-center">
                                <!-- DMARC text loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- L2 Support Tip Box -->
                    <div class="mt-6 border-l-2 border-indigo-500/50 bg-indigo-500/5 px-4 py-3 rounded-r-xl space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-400 flex items-center space-x-1">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                            <span>Support Guidance</span>
                        </span>
                        <p id="tipDMARC" class="text-xs text-slate-200 leading-relaxed">Reading recommendations...</p>
                    </div>
                </div>

                <!-- DKIM Panel -->
                <div class="backdrop-blur-md bg-[#12121A] border border-white/[0.08] rounded-3xl p-6 shadow-xl flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b border-white/[0.08] pb-3">
                            <div class="flex items-center space-x-2.5">
                                <div class="p-1.5 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-lg">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                </div>
                                <h4 class="font-bold text-white">DKIM Key Verification</h4>
                            </div>
                            <span id="statusDKIM" class="px-2 py-0.5 rounded-md text-xs font-semibold uppercase">Checking</span>
                        </div>

                        <p id="msgDKIM" class="text-sm text-slate-200">Checking DKIM parameters...</p>

                        <!-- DKIM Record box -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <label class="block text-xs font-bold text-slate-350 uppercase tracking-wider">Raw TXT Record</label>
                                    <span id="badgeSelectorUsed" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-850 text-slate-300 border border-white/[0.08]">selector: none</span>
                                </div>
                                <button type="button" onclick="copyRecordText('recordValDKIM')" class="text-slate-400 hover:text-slate-200 text-xs transition-colors flex items-center space-x-1">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg>
                                    <span>Copy Record</span>
                                </button>
                            </div>
                            <div id="recordValDKIM" class="bg-slate-950/80 border border-white/[0.08] rounded-xl p-3 text-xs font-mono break-all text-slate-300 min-h-[50px] max-h-[120px] overflow-y-auto flex items-center">
                                <!-- DKIM record contents loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- L2 Support Tip Box -->
                    <div class="mt-6 border-l-2 border-indigo-500/50 bg-indigo-500/5 px-4 py-3 rounded-r-xl space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-400 flex items-center space-x-1">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                            <span>Support Guidance</span>
                        </span>
                        <p id="tipDKIM" class="text-xs text-slate-200 leading-relaxed">Reading recommendations...</p>
                    </div>
                </div>

                <!-- SSL Panel -->
                <div class="backdrop-blur-md bg-[#12121A] border border-white/[0.08] rounded-3xl p-6 shadow-xl flex flex-col justify-between lg:col-span-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between border-b border-white/[0.08] pb-3">
                                <div class="flex items-center space-x-2.5">
                                    <div class="p-1.5 bg-pink-500/10 text-pink-400 border border-pink-500/20 rounded-lg">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <h4 class="font-bold text-white">SSL / HTTPS Certificate</h4>
                                </div>
                                <span id="statusSSL" class="px-2 py-0.5 rounded-md text-xs font-semibold uppercase">Checking</span>
                            </div>

                            <p id="msgSSL" class="text-sm text-slate-200">Checking SSL endpoint...</p>

                            <!-- Certificate Details list -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-slate-350 uppercase tracking-wider">Certificate Metadata</label>
                                <div class="bg-slate-950/80 border border-white/[0.08] rounded-xl p-3 text-xs font-mono space-y-1.5">
                                    <div class="flex justify-between"><span class="text-slate-500">Issuer:</span> <span id="certIssuer" class="text-slate-300">-</span></div>
                                    <div class="flex justify-between"><span class="text-slate-500">Common Name:</span> <span id="certSubject" class="text-slate-300">-</span></div>
                                    <div class="flex justify-between"><span class="text-slate-500">Valid Until:</span> <span id="certExpiry" class="text-slate-300">-</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- L2 Support Tip Box -->
                        <div class="flex flex-col justify-end">
                            <div class="border-l-2 border-indigo-500/50 bg-indigo-500/5 px-4 py-3 rounded-r-xl space-y-1 h-fit md:mb-0">
                                <span class="text-xs font-bold uppercase tracking-wider text-indigo-400 flex items-center space-x-1">
                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                                    <span>Support Guidance</span>
                                </span>
                                <p id="tipSSL" class="text-xs text-slate-200 leading-relaxed">Reading recommendations...</p>
                            </div>
                        </div>
                    </div>
                </div>

            </section>

            <!-- Export / Text Report Box -->
            <section class="backdrop-blur-md bg-[#12121A] border border-white/[0.08] rounded-3xl p-6 shadow-xl space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-white/[0.08] pb-4">
                    <div>
                        <h4 class="font-bold text-white text-base">Deliverability Assessment Summary</h4>
                        <p class="text-xs text-slate-300">Share findings or generate documentation templates for clients.</p>
                    </div>
                    <button type="button" onclick="copyFullReport()" class="px-4 py-2 bg-slate-850 hover:bg-slate-800 active:bg-slate-900 border border-white/[0.08] text-slate-200 hover:text-white font-medium rounded-xl text-xs transition-colors flex items-center space-x-1.5 shadow-sm">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        <span id="copyReportBtnText">Copy Full Report</span>
                    </button>
                </div>
                <div class="relative">
                    <textarea id="fullReportArea" readonly
                              class="w-full bg-slate-950/90 border border-white/[0.08] rounded-2xl p-4 text-xs font-mono text-slate-400 focus:outline-none focus:ring-0 min-h-[160px] leading-relaxed resize-none"></textarea>
                </div>
            </section>

        </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-white/[0.08] bg-[#0A0A0F] py-8 text-center text-xs text-slate-400 mt-12">
        <div class="max-w-7xl mx-auto px-4 space-y-3">
            <p>Designed and built for software engineering portfolios. Live DNS resolutions use native network functions.</p>
            <p class="flex items-center justify-center space-x-2 text-slate-500">
                <span>&copy; <?php echo date('Y'); ?> MailGuard DNS</span>
                <span>•</span>
                <span class="hover:text-slate-400 transition-colors cursor-help" title="No logs or keys are persisted on this host.">Privacy Policy</span>
            </p>
        </div>
    </footer>

    <!-- Toast Notification Container (hidden by default) -->
    <div id="toast" class="fixed bottom-5 right-5 z-50 transform translate-y-20 opacity-0 transition-all duration-300 ease-out border border-emerald-500/20 bg-slate-900/90 text-slate-100 px-4 py-3 rounded-xl flex items-center space-x-2 shadow-2xl pointer-events-none">
        <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span id="toastMsg" class="text-xs font-semibold">Report copied successfully!</span>
    </div>

    <!-- Application Script Loaded as ES6 Module -->
    <script type="module" src="app.js"></script>
</body>
</html>
