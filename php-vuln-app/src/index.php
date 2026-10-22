<?php
declare(strict_types=1);

$sapi = PHP_SAPI;
$handlerLabel = match ($sapi) {
    'apache2handler' => 'Apache mod_php (apache2handler)',
    'fpm-fcgi'       => 'PHP-FPM (fpm-fcgi)',
    'cgi-fcgi'       => 'PHP-CGI (cgi-fcgi)',
    default          => $sapi,
};

$isVulnerable = version_compare(PHP_VERSION, '8.5.12', '<');
$memoryLimit = ini_get('memory_limit') ?: 'N/A';
$postMaxSize = ini_get('post_max_size') ?: 'N/A';
$uploadMaxSize = ini_get('upload_max_filesize') ?: 'N/A';
$maxExecutionTime = ini_get('max_execution_time') ?: 'N/A';
$workerPid = getmypid();
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Apache';
$hostname = gethostname() ?: 'php-lab-app';
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>php2shell // Vulnerability Testbed</title>
    <style>
        :root {
            --bg: #0d1117;
            --sidebar-bg: #161b22;
            --main-bg: #0d1117;
            --card-bg: #161b22;
            --border: #30363d;
            --text-primary: #e6edf3;
            --text-secondary: #8b949e;
            --accent-blue: #58a6ff;
            --accent-red: #f85149;
            --accent-green: #3fb950;
            --accent-orange: #d29922;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar (classic security testbed layout like DVWA/bWAPP) */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            padding: 1.5rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            flex-shrink: 0;
        }

        .brand {
            font-size: 1.15rem;
            font-weight: 700;
            color: #f0f6fc;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .brand span {
            color: var(--accent-red);
            font-family: monospace;
        }

        .nav-section {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .nav-title {
            font-size: 0.72rem;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--text-secondary);
            letter-spacing: 0.05em;
            margin-bottom: 0.2rem;
        }

        .nav-item {
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.85rem;
            color: var(--text-primary);
            text-decoration: none;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid transparent;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-item.active {
            background: rgba(88, 166, 255, 0.1);
            border-color: rgba(88, 166, 255, 0.3);
            color: var(--accent-blue);
            font-weight: 600;
        }

        .sidebar-status {
            margin-top: auto;
            background: rgba(5, 8, 15, 0.6);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 0.75rem;
            font-size: 0.78rem;
        }

        /* Main Content */
        .content {
            flex: 1;
            padding: 2rem 2.5rem;
            max-width: 900px;
        }

        .notice-banner {
            background: rgba(248, 81, 73, 0.1);
            border: 1px solid rgba(248, 81, 73, 0.4);
            border-radius: 6px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            color: #ff7b72;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .page-header {
            margin-bottom: 1.5rem;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #f0f6fc;
            margin-bottom: 0.4rem;
        }

        .page-desc {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #f0f6fc;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-info {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }

        .table-info tr {
            border-bottom: 1px solid rgba(48, 54, 61, 0.6);
        }

        .table-info tr:last-child {
            border-bottom: none;
        }

        .table-info td {
            padding: 0.65rem 0.5rem;
        }

        .td-label {
            color: var(--text-secondary);
            width: 38%;
        }

        .td-val {
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            color: var(--text-primary);
            font-weight: 600;
        }

        .badge-pill {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-danger {
            background: rgba(248, 81, 73, 0.15);
            color: var(--accent-red);
            border: 1px solid rgba(248, 81, 73, 0.3);
        }

        .badge-success {
            background: rgba(63, 185, 80, 0.15);
            color: var(--accent-green);
            border: 1px solid rgba(63, 185, 80, 0.3);
        }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; border-right: none; border-bottom: 1px solid var(--border); }
            .content { padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <!-- Sidebar: Classic Security Testbed Style (DVWA / Mutillidae inspired) -->
    <aside class="sidebar">
        <div class="brand">
            <span>[#]</span> php2shell Lab
        </div>

        <div class="nav-section">
            <div class="nav-title">Target Modules</div>
            <div class="nav-item active">
                <span>System Information</span>
                <span>•</span>
            </div>
            <div class="nav-item" style="opacity: 0.5;">
                <span>CVE-2026-109618 (OOM)</span>
                <span style="font-size: 0.7rem;">Target</span>
            </div>
            <div class="nav-item" style="opacity: 0.5;">
                <span>CVE-2026-109619 (UAF)</span>
                <span style="font-size: 0.7rem;">Target</span>
            </div>
        </div>

        <div class="nav-section">
            <div class="nav-title">Lab Controls</div>
            <div class="nav-item" style="opacity: 0.5;">
                <span>Security Level</span>
                <span class="badge-pill badge-danger" style="font-size:0.7rem;">Vulnerable</span>
            </div>
        </div>

        <div class="sidebar-status">
            <div style="color: var(--text-secondary); margin-bottom: 0.25rem;">Node ID</div>
            <div style="font-family: monospace; font-weight: 600; color: var(--accent-blue);"><?= htmlspecialchars($hostname) ?></div>
            <div style="color: var(--text-secondary); margin-top: 0.5rem; margin-bottom: 0.25rem;">Remote Client</div>
            <div style="font-family: monospace; color: #f0f6fc;"><?= htmlspecialchars($clientIp) ?></div>
        </div>
    </aside>

    <!-- Main Workspace -->
    <main class="content">
        <div class="notice-banner">
            <span><strong>⚠️ Isolated Vulnerability Testbed</strong> — Strictly for authorized research &amp; testing.</span>
            <span class="badge-pill badge-danger">CVSS 9.2 Critical</span>
        </div>

        <div class="page-header">
            <h1 class="page-title">Target Node Overview</h1>
            <p class="page-desc">
                This target node is deployed as a controlled security research bed to test and verify unauthenticated PHP core memory execution vectors (CVE-2026-109618 &amp; CVE-2026-109619). It provides unpatched runtime configurations to assess mitigation effectiveness safely.
            </p>
        </div>

        <!-- Target Specifications -->
        <div class="card">
            <div class="card-title">
                <span>Target Node Specifications</span>
                <?php if ($isVulnerable): ?>
                    <span class="badge-pill badge-danger">Vulnerable (&lt; 8.5.12)</span>
                <?php else: ?>
                    <span class="badge-pill badge-success">Patched</span>
                <?php endif; ?>
            </div>
            <table class="table-info">
                <tr>
                    <td class="td-label">PHP Core Version</td>
                    <td class="td-val" style="color: var(--accent-red);"><?= htmlspecialchars(PHP_VERSION) ?></td>
                </tr>
                <tr>
                    <td class="td-label">Server API (SAPI)</td>
                    <td class="td-val"><?= htmlspecialchars($handlerLabel) ?></td>
                </tr>
                <tr>
                    <td class="td-label">Active Worker PID</td>
                    <td class="td-val">PID #<?= $workerPid ?></td>
                </tr>
                <tr>
                    <td class="td-label">Server Software</td>
                    <td class="td-val"><?= htmlspecialchars($serverSoftware) ?></td>
                </tr>
                <tr>
                    <td class="td-label">Zend Thread Safety</td>
                    <td class="td-val"><?= PHP_ZTS ? 'Enabled (ZTS)' : 'Disabled (NTS)' ?></td>
                </tr>
            </table>
        </div>

        <!-- Target Memory & Execution Configuration -->
        <div class="card">
            <div class="card-title">
                <span>Target Memory &amp; Resource Bounds</span>
            </div>
            <table class="table-info">
                <tr>
                    <td class="td-label">memory_limit</td>
                    <td class="td-val"><?= htmlspecialchars($memoryLimit) ?></td>
                </tr>
                <tr>
                    <td class="td-label">post_max_size</td>
                    <td class="td-val"><?= htmlspecialchars($postMaxSize) ?></td>
                </tr>
                <tr>
                    <td class="td-label">upload_max_filesize</td>
                    <td class="td-val"><?= htmlspecialchars($uploadMaxSize) ?></td>
                </tr>
                <tr>
                    <td class="td-label">max_execution_time</td>
                    <td class="td-val"><?= htmlspecialchars((string)$maxExecutionTime) ?>s</td>
                </tr>
            </table>
        </div>
    </main>
</body>
</html>
