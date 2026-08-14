# 🛡️ MailGuard DNS: Email Deliverability & DNS Health Checker

<div align="center">

  ![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.0-777bb4?style=for-the-badge&logo=php&logoColor=white)
  ![JavaScript ES6](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
  ![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)
  ![License MIT](https://img.shields.io/badge/License-MIT-45b6fe?style=for-the-badge)

  <p align="center">
    <strong>A professional, high-fidelity diagnostic dashboard designed to inspect, grade, and troubleshoot domain email deliverability and DNS health.</strong>
  </p>

  <sub>Built with modern PHP 8+, Vanilla JS (ES6 Module Architecture), and Tailwind CSS.</sub>
</div>

---

### 📖 Table of Contents
1. [🔍 Overview](#-overview)
2. [🛡️ Why Email Deliverability Setup Matters](#️-why-email-deliverability-setup-matters)
3. [🏗️ Architecture & Security Patterns](#-architecture--security-patterns)
4. [✨ Key Features](#-key-features)
5. [💻 Tech Stack](#-tech-stack)
6. [⚙️ Installation & Local Setup](#️-installation--local-setup)
7. [📊 Scoring Matrix](#-scoring-matrix)
8. [📄 License](#-license)

---

## 🔍 Overview

**MailGuard DNS** is a developer-centric diagnostic utility designed to solve a common, frustrating issue: **emails bouncing or landing in SPAM due to incorrect DNS configurations**. 

By analyzing a given domain, the app runs backend validation routines, checks mail server exchange pointers (MX), authentication protocols (SPF, DKIM, DMARC), and secure web sockets (SSL), returning an interactive score and copyable L2 support templates.

---

## 🛡️ Why Email Deliverability Setup Matters

Modern email receivers (Gmail, Yahoo, Microsoft Outlook) enforce strict validation layers to shield users from phishing. Domains without verified keys suffer high reject rates.

```
Incoming Email ──► [ SPF Check ] ──► [ DKIM Check ] ──► [ DMARC Policy ] ──► Delivery Decision
                        │                  │                  │
                        ▼                  ▼                  ▼
                    Authorized?        Signed &           Define rule:
                    Sender IP?         Untampered?        Reject, Quarantine, or Pass
```

| Check | Protocol | Diagnostic Objective | Consequence if Broken |
| :--- | :--- | :--- | :--- |
| 📧 | **MX Records** | Verifies where incoming mail is routed. | Outgoing mail works, but incoming mail bounces entirely. |
| 📝 | **SPF Record** | Lists the IPs authorized to send mail. | Spammers easily spoof your domain; mail marked as SPAM. |
| 🔑 | **DKIM Record** | Validates cryptographic domain signatures. | Lacks authenticity checks, boosting spam flags on receivers. |
| 🔒 | **DMARC Policy**| Directs action (reject/quarantine) on failures. | Spoofed phishing emails can be sent using your brand name. |
| 🏷️ | **SSL Certificate** | Assures HTTPS link validity for interfaces. | Security warnings trigger on webmail paths. |

---

## 🏗️ Architecture & Security Patterns

The application conforms to a strict separation of concerns, dividing execution into distinct logical layers on the client side and securing DNS query workflows against common security vulnerabilities.

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. UI Layer (render.js / index.php)                             │
│    - Renders high-fidelity OLED styles, SVGs, progress gauges   │
└───────────────┬─────────────────────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. Logic Layer (state.js / useRateLimiter.js)                   │
│    - Reactive state, search countdown timers, client throttles   │
└───────────────┬─────────────────────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. API Layer (dnsService.js)                                    │
│    - AbortController wrappers with 8s connection thresholds     │
└───────────────┬─────────────────────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. Security Layer (sanitizer.js)                                │
│    - RFC 1035 check, XSS entities encoding, clipboard safeguards│
└─────────────────────────────────────────────────────────────────┘
```

### 🔒 Server-Side SSRF Protection
To prevent **Server-Side Request Forgery (SSRF)** attacks during domain name evaluations, the PHP backend:
1. Performs DNS resolution to extract destination IP addresses.
2. Checks IPs against private, loopback, and local networks including:
   - Loopback: `127.0.0.0/8`, `::1`
   - Private subnets: `10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`
   - Link-local and Unique Local: `169.254.0.0/16`, `fc00::/7`, `fe80::/10`
3. Aborts connections immediately if a private destination is detected.

### 🛡️ Client-Side XSS & Clipboard Protections
- **XSS Mitigation:** All DNS record results are processed via an HTML entity encoder (`Sanitizer.sanitizeHTML`) before being rendered into the DOM, preventing execution of injected payloads.
- **Clipboard Defense:** Before writing reports to the system clipboard, control codes and dynamic URI schemes (like `javascript:`) are filtered to block command/payload injection.

### ⏱️ Session Rate Limiting
- The backend API implements session-based rate limiting (Max 10 requests per minute), sending `X-RateLimit-Limit`, `X-RateLimit-Remaining`, and `Retry-After` headers.
- The client UI tracks lockouts and renders a disabled button state with a ticking lockout timer countdown.

---

## ✨ Key Features

*   **Live DNS Queries**: Leverages native PHP networking functions (`dns_get_record`) to fetch MX, SPF, DMARC, and custom-selector DKIM records.
*   **SSL Handshake Analyzer**: Opens secure stream sockets over port 443 with strict peer verification (`verify_peer` and `verify_peer_name`) and validated cert chain structures.
*   **Support Desk Clipboard Tool**: Instantly exports full test details into a structured Markdown block, filtered for safety before clipboard transfer.
*   **Level-2 Support Tips**: Displays descriptive, context-specific tips telling you *why* a record is wrong and *how* to change it in your DNS panel.
*   **Offline / Preset Simulator**: Safely runs on local offline environments using simulated presets (`demo-perfect.com`, `demo-warning.org`, `demo-critical.net`). If internet access fails, it transitions to fallback mock data dynamically, flagging reports with `is_simulated = true`.
*   **WCAG AAA Accessible Design**: Uses Phosphor-aligned SVG status icons rather than color alone to signal pass/fail results. Employs `aria-live="polite"` dynamic notification regions, maintaining a minimum 7:1 contrast ratio over deep OLED backgrounds (`#0A0A0F`).

---

## 💻 Tech Stack

*   **Backend Logic**: PHP 8.0+ (OOP, type hints, stream socket clients, regular expression parsers).
*   **Frontend Logic**: Vanilla JS (ES6 Module Architecture, radial SVG transitions, toast controls, clipboard routines).
*   **Aesthetic Styling**: Vanilla Tailwind CSS (Custom dark grids, skeletons, glow effects).

---

## ⚙️ Installation & Local Setup

### Setup with XAMPP (Apache)
1. Install [XAMPP](https://www.apachefriends.org/) (ensure PHP 8.0+ is active).
2. Clone this repository into your local web root folder:
   ```bash
   cd C:\xampp\htdocs\
   git clone https://github.com/KemField/email-dns-checker.git projekt2
   ```
3. Boot up the **Apache** server in the XAMPP Control Panel.
4. Open your browser and navigate to:
   ```
   http://localhost/projekt2/index.php
   ```

### Setup with PHP's Built-in Server
1. Navigate directly to the project folder:
   ```bash
   cd c:\xampp\htdocs\projekt2\
   ```
2. Start the built-in router:
   ```bash
   php -S 127.0.0.1:8000
   ```
3. Load the dashboard page at:
   ```
   http://127.0.0.1:8000/index.php
   ```

---

## 📊 Scoring Matrix

To compute the overall **Health Score (0-100%)**, the diagnostic backend applies weighted deductions for configurations that fail verification:

```
[ Starting Score: 100 ]
  ├── Missing MX Record              ──► Deduct 35 points
  ├── Insecure SPF (+all) or Missing ──► Deduct 30 points
  ├── Missing DMARC Record           ──► Deduct 20 points
  ├── Missing DKIM Record            ──► Deduct 15 points
  └── Expired / Missing SSL          ──► Deduct 20 points
```

- **Score >= 90**: Green Theme (Excellent / Secure)
- **Score 60 - 89**: Yellow Theme (Warnings Present)
- **Score < 60**: Red Theme (Critical Issues Found)

---

## 📄 License

Distributed under the MIT License. See [LICENSE](LICENSE) for details.