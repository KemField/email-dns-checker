<?php

/**
 * Class DNSChecker
 *
 * Handles live DNS queries (MX, SPF, DMARC, DKIM) and SSL checks for a given domain.
 * Supports fallback/demo modes for testing in restricted or local offline environments.
 */
class DNSChecker
{
    private string $domain;
    private ?string $dkimSelector;
    private bool $isDemo = false;
    private string $demoPreset = '';

    // Lists of common selectors for auto-detecting DKIM if none is provided
    private const COMMON_DKIM_SELECTORS = ['default', 'google', 'mail', 'k1', 'sig1'];

    /**
     * DNSChecker constructor.
     *
     * @param string $domain The raw domain or URL entered by the user.
     * @param string|null $dkimSelector Optional DKIM selector to check.
     */
    public function __construct(string $domain, ?string $dkimSelector = null)
    {
        $this->dkimSelector = $dkimSelector ? trim($dkimSelector) : null;
        $this->domain = $this->sanitizeDomain($domain);
        $this->detectDemoMode();
    }

    /**
     * Sanitizes and parses the input domain to ensure a clean hostname is used.
     * Handles copy-pasted URLs (http/https), www subdomains, and trailing paths.
     */
    private function sanitizeDomain(string $domain): string
    {
        $domain = trim($domain);
        
        // Remove protocol if present
        if (preg_match('/^https?:\/\//i', $domain)) {
            $parsed = parse_url($domain);
            $domain = $parsed['host'] ?? $domain;
        }

        // Remove www. prefix if present (optional, but standard for email checks since MX is on apex)
        $domain = preg_replace('/^www\./i', '', $domain);

        // Split by slash and keep only the hostname
        $parts = explode('/', $domain);
        $domain = $parts[0];

        // Filter and lowercase
        $domain = filter_var($domain, FILTER_SANITIZE_URL);
        return strtolower($domain);
    }

    /**
     * Detects if the domain matches any simulator presets or if we are offline.
     */
    private function detectDemoMode(): void
    {
        if (str_starts_with($this->domain, 'demo-')) {
            $this->isDemo = true;
            if (str_contains($this->domain, 'perfect')) {
                $this->demoPreset = 'perfect';
            } elseif (str_contains($this->domain, 'warning')) {
                $this->demoPreset = 'warning';
            } else {
                $this->demoPreset = 'critical';
            }
            return;
        }

        // Offline mode fallback: if we cannot resolve google.com, we treat it as demo
        // to prevent localhost developer setups from throwing empty responses
        $googleDns = @dns_get_record('google.com', DNS_A);
        if ($googleDns === false || empty($googleDns)) {
            $this->isDemo = true;
            $this->demoPreset = 'offline_sim';
        }
    }

    /**
     * Executes all security and health checks.
     *
     * @return array Checked results and calculated health score.
     */
    public function check(): array
    {
        if ($this->isDemo) {
            return $this->getSimulatedResults();
        }

        $results = [
            'domain' => $this->domain,
            'is_demo' => false,
            'checks' => [
                'mx' => $this->checkMX(),
                'spf' => $this->checkSPF(),
                'dmarc' => $this->checkDMARC(),
                'dkim' => $this->checkDKIM(),
                'ssl' => $this->checkSSL(),
            ],
            'timestamp' => date('c')
        ];

        $results['score'] = $this->calculateScore($results['checks']);
        return $results;
    }

    /**
     * Verifies Mail Exchange (MX) records.
     */
    private function checkMX(): array
    {
        $records = @dns_get_record($this->domain, DNS_MX);

        if (empty($records)) {
            // Fallback: check if A record exists (some mail systems fallback to A)
            $aRecords = @dns_get_record($this->domain, DNS_A);
            if (empty($aRecords)) {
                return [
                    'status' => 'critical',
                    'records' => [],
                    'message' => 'No MX or A records found. This domain cannot receive emails.',
                    'tip' => 'Add an MX record pointing to your mail server (e.g., mail.yourdomain.com or googlemx) in your DNS panel (cPanel, Cloudflare, AWS Route 53) to route incoming emails correctly.'
                ];
            } else {
                return [
                    'status' => 'warning',
                    'records' => [],
                    'message' => 'No MX records found, but A record exists. Mail delivery might fallback to A, which is deprecated.',
                    'tip' => 'Add an explicit MX record. While legacy mail servers may fallback to the A record to locate your mail host, modern spam filters require explicit MX records.'
                ];
            }
        }

        $mxList = [];
        foreach ($records as $r) {
            $mxList[] = [
                'host' => $r['target'] ?? '',
                'pri' => $r['pri'] ?? 0
            ];
        }

        // Sort by priority ascending
        usort($mxList, fn($a, $b) => $a['pri'] <=> $b['pri']);

        return [
            'status' => 'valid',
            'records' => $mxList,
            'message' => 'Found ' . count($mxList) . ' MX record(s). Mail routing is correctly configured.',
            'tip' => 'Your MX records are active. Ensure the corresponding mail server IP addresses are clean and not blacklisted by spam databases.'
        ];
    }

    /**
     * Verifies Sender Policy Framework (SPF) records.
     */
    private function checkSPF(): array
    {
        $records = @dns_get_record($this->domain, DNS_TXT);
        $spfRecords = [];

        if (!empty($records)) {
            foreach ($records as $r) {
                $txt = $r['txt'] ?? ($r['entries'][0] ?? '');
                if (stripos($txt, 'v=spf1') === 0) {
                    $spfRecords[] = $txt;
                }
            }
        }

        if (empty($spfRecords)) {
            return [
                'status' => 'critical',
                'record' => null,
                'message' => 'Missing SPF (Sender Policy Framework) record.',
                'tip' => 'An SPF record lists the IP addresses and servers allowed to send mail on behalf of your domain. Add a TXT record with value like "v=spf1 include:_spf.google.com ~all" to prevent spoofers from using your domain.'
            ];
        }

        if (count($spfRecords) > 1) {
            return [
                'status' => 'critical',
                'record' => implode(' | ', $spfRecords),
                'message' => 'Multiple SPF records found. This violates RFC standards and invalidates SPF checks.',
                'tip' => 'You must merge your SPF configs into a single TXT record. Combining "v=spf1 include:spf.protection.outlook.com ~all" and "v=spf1 include:_spf.google.com ~all" into "v=spf1 include:spf.protection.outlook.com include:_spf.google.com ~all".'
            ];
        }

        $spf = $spfRecords[0];

        // Analyze mechanisms
        if (str_contains($spf, '+all')) {
            return [
                'status' => 'warning',
                'record' => $spf,
                'message' => 'SPF record contains the highly insecure "+all" mechanism.',
                'tip' => 'The "+all" mechanism authorizes every server on the internet to send mail for you. Replace "+all" with "~all" (SoftFail) or "-all" (Fail) immediately.'
            ];
        }

        return [
            'status' => 'valid',
            'record' => $spf,
            'message' => 'SPF record is active and correctly configured.',
            'tip' => 'Your SPF record is healthy. If you adopt external tools (e.g. Mailchimp, SendGrid, Salesforce), make sure to include their authorization keys inside this single record.'
        ];
    }

    /**
     * Verifies Domain-based Message Authentication, Reporting, and Conformance (DMARC) records.
     */
    private function checkDMARC(): array
    {
        $records = @dns_get_record('_dmarc.' . $this->domain, DNS_TXT);
        $dmarcRecords = [];

        if (!empty($records)) {
            foreach ($records as $r) {
                $txt = $r['txt'] ?? ($r['entries'][0] ?? '');
                if (stripos($txt, 'v=DMARC1') === 0) {
                    $dmarcRecords[] = $txt;
                }
            }
        }

        if (empty($dmarcRecords)) {
            return [
                'status' => 'critical',
                'record' => null,
                'message' => 'Missing DMARC record.',
                'tip' => 'DMARC tells receivers what to do if emails fail SPF/DKIM verification. Publish a TXT record at host "_dmarc.yourdomain.com" with a value of "v=DMARC1; p=quarantine; pct=100; rua=mailto:dmarc-reports@yourdomain.com".'
            ];
        }

        if (count($dmarcRecords) > 1) {
            return [
                'status' => 'critical',
                'record' => implode(' | ', $dmarcRecords),
                'message' => 'Multiple DMARC records found. This is invalid and causes DMARC rules to be ignored.',
                'tip' => 'Delete duplicate DMARC records. Keep only one TXT record at host "_dmarc.yourdomain.com".'
            ];
        }

        $dmarc = $dmarcRecords[0];
        $policy = 'none';

        if (preg_match('/p\s*=\s*([^;]+)/i', $dmarc, $matches)) {
            $policy = strtolower(trim($matches[1]));
        }

        if ($policy === 'none') {
            return [
                'status' => 'warning',
                'record' => $dmarc,
                'message' => 'DMARC is set to monitoring-only policy (p=none).',
                'tip' => 'The "p=none" policy is great for initial testing to gather reports, but does not block spoofed emails. Once you confirm your authentic emails align properly, switch the policy to "p=quarantine" or "p=reject".'
            ];
        }

        return [
            'status' => 'valid',
            'record' => $dmarc,
            'message' => 'DMARC record is active with policy: p=' . htmlspecialchars($policy) . '.',
            'tip' => 'Great job! Your DMARC policy is actively protecting your brand from email impersonation.'
        ];
    }

    /**
     * Verifies DomainKeys Identified Mail (DKIM) records.
     */
    private function checkDKIM(): array
    {
        $selector = $this->dkimSelector;

        if (empty($selector)) {
            // Auto-check common selectors
            foreach (self::COMMON_DKIM_SELECTORS as $s) {
                $records = @dns_get_record($s . '._domainkey.' . $this->domain, DNS_TXT);
                if (!empty($records)) {
                    foreach ($records as $r) {
                        $txt = $r['txt'] ?? ($r['entries'][0] ?? '');
                        if (stripos($txt, 'v=DKIM1') === 0 || stripos($txt, 'k=rsa') === 0 || str_contains($txt, 'p=')) {
                            return [
                                'status' => 'valid',
                                'record' => $txt,
                                'selector_used' => $s,
                                'message' => 'DKIM record auto-detected under common selector "' . htmlspecialchars($s) . '".',
                                'tip' => 'DKIM record was successfully identified under the default selector "' . htmlspecialchars($s) . '". If you use a custom selector, specify it in the optional DKIM input to verify.'
                            ];
                        }
                    }
                }
            }

            return [
                'status' => 'info',
                'record' => null,
                'selector_used' => null,
                'message' => 'No DKIM records found under standard selectors (default, google, mail, k1, sig1).',
                'tip' => 'DKIM keys are published on specific selectors (e.g., mail._domainkey.domain.com). Since no selector was entered, we checked standard names but found none. Please type your actual selector in the field to check.'
            ];
        }

        // User-defined selector check
        $records = @dns_get_record($selector . '._domainkey.' . $this->domain, DNS_TXT);
        $dkimRecords = [];

        if (!empty($records)) {
            foreach ($records as $r) {
                $txt = $r['txt'] ?? ($r['entries'][0] ?? '');
                if (stripos($txt, 'v=DKIM1') === 0 || stripos($txt, 'k=rsa') === 0 || str_contains($txt, 'p=')) {
                    $dkimRecords[] = $txt;
                }
            }
        }

        if (empty($dkimRecords)) {
            return [
                'status' => 'critical',
                'record' => null,
                'selector_used' => $selector,
                'message' => 'No DKIM record found for selector "' . htmlspecialchars($selector) . '".',
                'tip' => 'We queried "' . htmlspecialchars($selector) . '._domainkey.' . htmlspecialchars($this->domain) . '" but found nothing. Make sure the spelling is correct and matches the DNS TXT key published in your DNS panel.'
            ];
        }

        return [
            'status' => 'valid',
            'record' => $dkimRecords[0],
            'selector_used' => $selector,
            'message' => 'Valid DKIM record found for selector "' . htmlspecialchars($selector) . '".',
            'tip' => 'Your DKIM record is correctly configured. Receiver mail servers will use this public key to verify cryptographic signatures of your outgoing messages.'
        ];
    }

    /**
     * Connects over SSL stream to check and parse certificate dates.
     */
    private function checkSSL(): array
    {
        $port = 443;
        $timeout = 5;

        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        $client = @stream_socket_client(
            "ssl://" . $this->domain . ":" . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$client) {
            // Test if domain is alive on standard HTTP (port 80)
            $tcp = @fsockopen($this->domain, 80, $errno, $errstr, $timeout);
            if (!$tcp) {
                return [
                    'status' => 'critical',
                    'message' => 'Could not connect to domain over HTTP (port 80) or HTTPS (port 443). Domain is offline or DNS routing is broken.',
                    'tip' => 'Verify your domain registrars and web server IPs. Check if server firewall settings block incoming port 80/443 traffic.'
                ];
            }
            @fclose($tcp);

            return [
                'status' => 'critical',
                'message' => 'HTTPS port 443 is closed or SSL certificate is missing.',
                'tip' => 'Secure socket connection failed. Install an SSL certificate (e.g. free Let\'s Encrypt or Cloudflare SSL proxy) and open port 443 in your server firewall configurations.'
            ];
        }

        $params = stream_context_get_params($client);
        @fclose($client);

        if (!isset($params['options']['ssl']['peer_certificate'])) {
            return [
                'status' => 'critical',
                'message' => 'Connected, but failed to fetch SSL certificate details.',
                'tip' => 'Ensure the webserver SSL installation is complete and has a valid intermediate certificate chain.'
            ];
        }

        $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
        if (!$cert) {
            return [
                'status' => 'critical',
                'message' => 'SSL certificate is corrupted or unreadable.',
                'tip' => 'Reinstall the SSL certificate on your webserver.'
            ];
        }

        $validFrom = $cert['validFrom_time_t'] ?? 0;
        $validTo = $cert['validTo_time_t'] ?? 0;
        $now = time();

        $issuer = $cert['issuer']['O'] ?? ($cert['issuer']['CN'] ?? 'Unknown Issuer');
        $subject = $cert['subject']['CN'] ?? $this->domain;

        if ($now < $validFrom) {
            return [
                'status' => 'critical',
                'issuer' => $issuer,
                'subject' => $subject,
                'valid_to' => date('Y-m-d H:i:s', $validTo),
                'message' => 'SSL certificate is not yet active (valid starting ' . date('Y-m-d', $validFrom) . ').',
                'tip' => 'The server clock might be out of sync, or the SSL certificate start date configuration is in the future. Reconfigure or reinstall.'
            ];
        }

        if ($now > $validTo) {
            return [
                'status' => 'critical',
                'issuer' => $issuer,
                'subject' => $subject,
                'valid_to' => date('Y-m-d H:i:s', $validTo),
                'message' => 'SSL certificate expired on ' . date('Y-m-d', $validTo) . '.',
                'tip' => 'Renew your SSL certificate immediately to prevent web browsers and modern mail clients from blocking connections to your domain.'
            ];
        }

        $daysRemaining = (int)floor(($validTo - $now) / 86400);

        if ($daysRemaining < 30) {
            return [
                'status' => 'warning',
                'issuer' => $issuer,
                'subject' => $subject,
                'valid_to' => date('Y-m-d H:i:s', $validTo),
                'days_remaining' => $daysRemaining,
                'message' => 'SSL certificate is active but expires soon in ' . $daysRemaining . ' days (on ' . date('Y-m-d', $validTo) . ').',
                'tip' => 'Set up automated Certbot renewal or contact your domain administrator to replace the certificate soon.'
            ];
        }

        return [
            'status' => 'valid',
            'issuer' => $issuer,
            'subject' => $subject,
            'valid_to' => date('Y-m-d H:i:s', $validTo),
            'days_remaining' => $daysRemaining,
            'message' => 'SSL certificate is valid. Issuer: ' . htmlspecialchars($issuer) . '. Active for another ' . $daysRemaining . ' days.',
            'tip' => 'Your SSL is healthy. Continue monitoring and ensure automatic renewals remain active.'
        ];
    }

    /**
     * Calculates the overall deliverability/health score.
     */
    private function calculateScore(array $checks): int
    {
        $score = 100;

        // Scoring weight rules
        $weights = [
            'mx' => ['critical' => 35, 'warning' => 15],
            'spf' => ['critical' => 30, 'warning' => 15],
            'dmarc' => ['critical' => 20, 'warning' => 10],
            'dkim' => ['critical' => 15, 'warning' => 5], // 'info' is 0 points deducted
            'ssl' => ['critical' => 20, 'warning' => 10]
        ];

        foreach ($weights as $key => $levels) {
            if (isset($checks[$key]['status'])) {
                $status = $checks[$key]['status'];
                if ($status === 'critical') {
                    $score -= $levels['critical'];
                } elseif ($status === 'warning') {
                    $score -= $levels['warning'];
                }
            }
        }

        return max(0, min(100, $score));
    }

    /**
     * Preloaded simulation results for offline, localhost, and demo testing.
     */
    private function getSimulatedResults(): array
    {
        $preset = $this->demoPreset;
        $simDomain = $this->domain;

        // Custom details depending on presets
        if ($preset === 'perfect' || $simDomain === 'demo-perfect.com') {
            return [
                'domain' => 'demo-perfect.com',
                'is_demo' => true,
                'score' => 100,
                'checks' => [
                    'mx' => [
                        'status' => 'valid',
                        'records' => [
                            ['host' => 'aspmx.l.google.com', 'pri' => 1],
                            ['host' => 'alt1.aspmx.l.google.com', 'pri' => 5],
                            ['host' => 'alt2.aspmx.l.google.com', 'pri' => 5],
                        ],
                        'message' => 'Found 3 MX records. Mail routing is correctly configured.',
                        'tip' => 'Your MX records are active. Ensure the corresponding mail server IP addresses are clean and not blacklisted by spam databases.'
                    ],
                    'spf' => [
                        'status' => 'valid',
                        'record' => 'v=spf1 include:_spf.google.com include:sendgrid.net ~all',
                        'message' => 'SPF record is active and correctly configured.',
                        'tip' => 'Your SPF record is healthy. If you adopt external tools (e.g. Mailchimp, SendGrid, Salesforce), make sure to include their authorization keys inside this single record.'
                    ],
                    'dmarc' => [
                        'status' => 'valid',
                        'record' => 'v=DMARC1; p=reject; pct=100; rua=mailto:dmarc-reports@demo-perfect.com',
                        'message' => 'DMARC record is active with policy: p=reject.',
                        'tip' => 'Great job! Your DMARC policy is actively protecting your brand from email impersonation.'
                    ],
                    'dkim' => [
                        'status' => 'valid',
                        'record' => 'v=DKIM1; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0Y8x...sXQIDAQAB',
                        'selector_used' => $this->dkimSelector ?: 'google',
                        'message' => 'Valid DKIM record found for selector "' . htmlspecialchars($this->dkimSelector ?: 'google') . '".',
                        'tip' => 'Your DKIM record is correctly configured. Receiver mail servers will use this public key to verify cryptographic signatures of your outgoing messages.'
                    ],
                    'ssl' => [
                        'status' => 'valid',
                        'issuer' => 'Let\'s Encrypt',
                        'subject' => 'demo-perfect.com',
                        'valid_to' => date('Y-m-d H:i:s', time() + 86400 * 180),
                        'days_remaining' => 180,
                        'message' => 'SSL certificate is valid. Issuer: Let\'s Encrypt. Active for another 180 days.',
                        'tip' => 'Your SSL is healthy. Continue monitoring and ensure automatic renewals remain active.'
                    ]
                ],
                'timestamp' => date('c')
            ];
        }

        if ($preset === 'warning' || $simDomain === 'demo-warning.org') {
            return [
                'domain' => 'demo-warning.org',
                'is_demo' => true,
                'score' => 60,
                'checks' => [
                    'mx' => [
                        'status' => 'valid',
                        'records' => [
                            ['host' => 'mail.demo-warning.org', 'pri' => 10]
                        ],
                        'message' => 'Found 1 MX record. Mail routing is correctly configured.',
                        'tip' => 'Your MX records are active. Ensure the corresponding mail server IP addresses are clean and not blacklisted by spam databases.'
                    ],
                    'spf' => [
                        'status' => 'warning',
                        'record' => 'v=spf1 include:_spf.google.com +all',
                        'message' => 'SPF record contains the highly insecure "+all" mechanism.',
                        'tip' => 'The "+all" mechanism authorizes every server on the internet to send mail for you. Replace "+all" with "~all" (SoftFail) or "-all" (Fail) immediately.'
                    ],
                    'dmarc' => [
                        'status' => 'warning',
                        'record' => 'v=DMARC1; p=none; rua=mailto:reports@demo-warning.org',
                        'message' => 'DMARC is set to monitoring-only policy (p=none).',
                        'tip' => 'The "p=none" policy is great for initial testing to gather reports, but does not block spoofed emails. Once you confirm your authentic emails align properly, switch the policy to "p=quarantine" or "p=reject".'
                    ],
                    'dkim' => [
                        'status' => 'info',
                        'record' => null,
                        'selector_used' => null,
                        'message' => 'No DKIM records found under standard selectors (default, google, mail, k1, sig1).',
                        'tip' => 'DKIM keys are published on specific selectors (e.g., mail._domainkey.domain.com). Since no selector was entered, we checked standard names but found none. Please type your actual selector in the field to check.'
                    ],
                    'ssl' => [
                        'status' => 'warning',
                        'issuer' => 'Sectigo Limited',
                        'subject' => 'demo-warning.org',
                        'valid_to' => date('Y-m-d H:i:s', time() + 86400 * 15),
                        'days_remaining' => 15,
                        'message' => 'SSL certificate is active but expires soon in 15 days (on ' . date('Y-m-d', time() + 86400 * 15) . ').',
                        'tip' => 'Set up automated Certbot renewal or contact your domain administrator to replace the certificate soon.'
                    ]
                ],
                'timestamp' => date('c')
            ];
        }

        // Default or critical demo presets
        // (This acts as the default fallback for local/offline developer environments too, so something displays)
        $isOfflineSim = ($preset === 'offline_sim');
        $displayDomain = $isOfflineSim ? $this->domain : 'demo-critical.net';
        
        return [
            'domain' => $displayDomain,
            'is_demo' => true,
            'is_offline_sim' => $isOfflineSim,
            'score' => 20,
            'checks' => [
                'mx' => [
                    'status' => 'critical',
                    'records' => [],
                    'message' => 'No MX or A records found. This domain cannot receive emails.',
                    'tip' => 'Add an MX record pointing to your mail server (e.g., mail.yourdomain.com) in your DNS panel (cPanel, Cloudflare, AWS Route 53) to route incoming emails correctly.'
                ],
                'spf' => [
                    'status' => 'critical',
                    'record' => null,
                    'message' => 'Missing SPF (Sender Policy Framework) record.',
                    'tip' => 'An SPF record lists the IP addresses and servers allowed to send mail on behalf of your domain. Add a TXT record with value like "v=spf1 include:_spf.google.com ~all" to prevent spoofers from using your domain.'
                ],
                'dmarc' => [
                    'status' => 'critical',
                    'record' => null,
                    'message' => 'Missing DMARC record.',
                    'tip' => 'DMARC tells receivers what to do if emails fail SPF/DKIM verification. Publish a TXT record at host "_dmarc.yourdomain.com" with a value of "v=DMARC1; p=quarantine; pct=100; rua=mailto:dmarc-reports@yourdomain.com".'
                ],
                'dkim' => [
                    'status' => 'critical',
                    'record' => null,
                    'selector_used' => $this->dkimSelector ?: 'default',
                    'message' => 'No DKIM record found for selector "' . htmlspecialchars($this->dkimSelector ?: 'default') . '".',
                    'tip' => 'We queried "' . htmlspecialchars($this->dkimSelector ?: 'default') . '._domainkey.' . htmlspecialchars($displayDomain) . '" but found nothing. Make sure the spelling is correct and matches the DNS TXT key published in your DNS panel.'
                ],
                'ssl' => [
                    'status' => 'critical',
                    'message' => 'HTTPS port 443 is closed or SSL certificate is missing.',
                    'tip' => 'Secure socket connection failed. Install an SSL certificate (e.g. free Let\'s Encrypt or Cloudflare SSL proxy) and open port 443 in your server firewall configurations.'
                ]
            ],
            'timestamp' => date('c')
        ];
    }
}
