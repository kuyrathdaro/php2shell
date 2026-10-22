# php2shell (CVE-2026-109618 & CVE-2026-109619 — PHP < 8.5.12)

A security research and proof-of-concept (PoC) repository demonstrating the **php2shell** PHP core runtime Remote Code Execution (RCE) vulnerabilities (**CVE-2026-109618** and **CVE-2026-109619**) affecting **PHP versions under `8.5.12`** in an isolated, Dockerized lab environment.

---

## ⚠️ Legal Disclaimer & Warning

> [!CAUTION]
> **FOR EDUCATIONAL AND AUTHORIZED SECURITY TESTING PURPOSES ONLY.**

Use of this software, script, or documentation for attacking targets without prior mutual consent is **strictly illegal**. It is the end user's sole responsibility to obey all applicable local, state, national, and international laws.

---

## 📜 Terms of Use

By accessing, downloading, or using any part of this repository (`php2shell`), you agree to the following terms:

1. **Educational Purpose Only**: This material is provided strictly for academic study, defensive security research, CTF competitions, and authorized penetration testing (where explicit written permission has been obtained from the system owner).
2. **Authorized Environments Only**: You may only execute or test code from this repository against systems you own or have explicit, documented authorization to test.
3. **No Malicious Use**: You must not use this project to gain unauthorized access, disrupt services, exfiltrate data, or cause harm to any third-party systems or networks.
4. **Compliance with Laws**: You are solely responsible for ensuring that your use of this repository complies with all applicable cybersecurity and computer misuse laws in your jurisdiction.

---

## 🛡️ Limitation of Liability

**THE AUTHORS, CONTRIBUTORS, AND MAINTAINERS ASSUME NO LIABILITY AND ARE NOT RESPONSIBLE FOR ANY MISUSE OR DAMAGE CAUSED BY THIS PROGRAM.**

This software is provided **"AS IS"**, without warranty of any kind, express or implied, including but not limited to the warranties of merchantability, fitness for a particular purpose, and noninfringement. In no event shall the author(s) or copyright holder(s) be liable for any claim, direct or indirect damages, data loss, service interruption, legal consequences, or other liability, whether in an action of contract, tort, or otherwise, arising from, out of, or in connection with the software or the use or other dealings in the software.

---

## 🔍 Vulnerability Overview

**php2shell** refers to two critical memory-safety vulnerabilities in the PHP core runtime (CVSS v4.0: **9.2 Critical**, disclosed October 2026):

- **CVE-2026-109618 (OOM `longjmp` Memory Corruption)**: When PHP hits an out-of-memory (OOM) condition during request parsing (such as multipart form uploads), `longjmp()` aborts execution while internal structures are only partially initialized. Subsequent cleanup routines treat uninitialized memory as valid pointers, leading to memory corruption and arbitrary code execution.
- **CVE-2026-109619 (Stale Global State / Use-After-Free)**: PHP retains internal global pointers referencing request data after backing memory is freed. When a subsequent request is handled by the same worker process before those references are reset, a Use-After-Free (UAF) condition can be triggered to hijack control flow.

### Affected PHP Versions & Patched Releases

| PHP Branch | Vulnerable Versions | Patched Version (Oct 2026) | Status |
| :--- | :--- | :--- | :--- |
| **PHP 8.5** | `< 8.5.12` | **`8.5.12+`** | Active (Patched) |
| **PHP 8.4** | `< 8.4.27` | **`8.4.27+`** | Active (Patched) |
| **PHP 8.3** | `< 8.3.36` | **`8.3.36+`** | Active (Patched) |
| **PHP 8.2** | `< 8.2.35` | **`8.2.35+`** | Security Support (Patched) |
| **PHP ≤ 8.1** | `5.4.x – 8.1.x` | *End of Life (Vendor Backports Only)* | EOL |

- **Affected SAPIs**: `PHP-FPM`, Apache `mod_php`, `php-cgi`, FrankenPHP, and LiteSpeed.

---

## 📁 Project Structure

```text
php2shell/
├── php-vuln-app/           # Isolated Dockerized PHP lab environment
│   ├── src/
│   │   └── index.php       # Displays PHP version and SAPI (mod_php)
│   ├── Dockerfile          # Apache + mod_php (php:8.5.11-apache)
│   └── docker-compose.yml  # Runs php-app on port 8080
├── exploit.py              # CLI request script (lab/educational use only)
└── README.md               # Project documentation, CVE details, and legal terms
```

## 🚀 Usage (Isolated Lab Environment)

Ensure you are running inside an isolated local lab environment before testing:

```bash
# 1. Start the local container (Apache + mod_php on port 8080)
cd php-vuln-app
docker compose up -d --build

# 2. Test the endpoint on port 8080
cd ..
python exploit.py --url http://localhost:8080/index.php --lhost 127.0.0.1 --port 4444
```

---

## 🔒 Remediation & Mitigation

- **Upgrade PHP Immediately**: Update to **PHP 8.5.12**, **8.4.27**, **8.3.36**, or **8.2.35** (or higher).
- **Recycle Worker Processes**: In environments where immediate patching is delayed, reduce `pm.max_requests` in `PHP-FPM` or `MaxConnectionsPerChild` in Apache `mod_php` to limit worker reuse across requests, and enforce strict request body size limits at the reverse proxy/WAF layer.
