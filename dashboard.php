<?php
/* ==================== CONEXIUNE ==================== */
$server = "localhost";
$user   = "root";
$pass   = "";
$db_name = "companieaeriana_db";

$conn = mysqli_connect($server, $user, $pass, $db_name);
if (!$conn) {
    die("<h2 style='color:red;'>Conexiunea a eșuat: " . mysqli_connect_error() . "</h2>");
}
mysqli_set_charset($conn, "utf8mb4");


/* ==================== STATISTICI ==================== */

/* ZBORURI ACTIVE AZI */
$sqlActiveFlights = "
    SELECT COUNT(*) AS cnt
    FROM Zboruri
    WHERE ZborActiv = 1
      AND DataZbor = CURDATE()
";
$res = mysqli_query($conn, $sqlActiveFlights);
$zboruriActiveAzi = ($res ? mysqli_fetch_assoc($res)['cnt'] : 0);

/* REZERVĂRI NOI AZI */
$sqlNewRes = "
    SELECT COUNT(*) AS cnt
    FROM Rezervari
    WHERE DataRezervare = CURDATE()
";
$res = mysqli_query($conn, $sqlNewRes);
$rezervariNoi = ($res ? mysqli_fetch_assoc($res)['cnt'] : 0);

/* TOTAL PASAGERI */
$sqlPass = "SELECT COUNT(*) AS cnt FROM Pasageri";
$res = mysqli_query($conn, $sqlPass);
$totalPasageri = ($res ? mysqli_fetch_assoc($res)['cnt'] : 0);

/* Zboruri recente (ultimele 3) */
$sqlRecent = "
    SELECT CodZbor, Origine, Destinatie, OraPlecare, Status
    FROM Zboruri
    ORDER BY DataZbor DESC, OraPlecare DESC
    LIMIT 3
";
$zboruriRecente = mysqli_query($conn, $sqlRecent);

?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>BlueWing Airlines – Dashboard</title>

<style>
    * { box-sizing:border-box; margin:0; padding:0; }
    body {
        font-family: "Segoe UI", sans-serif;
        background:#f3f4f6;
        color:#111827;
    }
    .layout {
        display:flex;
        min-height:100vh;
    }

    /* ========== SIDEBAR ========== */
    .sidebar {
        width:240px;
        background:#ffffff;
        border-right:1px solid #e5e7eb;
        color:#334155;
        display:flex;
        flex-direction:column;
        padding:24px 16px;
    }
    .sidebar-logo {
        display:flex;
        align-items:center;
        padding:0 4px;
        margin-bottom:32px;
    }
    .logo-box {
        width:42px;
        height:42px;
        border-radius:12px;
        background:#2563eb;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:22px;
        font-weight:700;
        color:white;
        margin-right:10px;
    }
    .logo-text-title {
        font-size:16px;
        font-weight:600;
        color:#0f172a;
    }
    .logo-text-sub {
        font-size:12px;
        color:#64748b;
    }

    .sidebar-nav {
        list-style:none;
    }
    .sidebar-item {
        margin-bottom:4px;
    }
    .sidebar-link {
        display:flex;
        align-items:center;
        padding:10px 12px;
        border-radius:10px;
        text-decoration:none;
        color:#334155;
        font-size:14px;
        transition:0.15s;
    }
    .sidebar-link span.icon {
        width:20px;
        margin-right:10px;
        text-align:center;
        font-size:16px;
    }
    .sidebar-link:hover {
        background:#f1f5f9;
    }
    .sidebar-link.active {
        background:#2563eb;
        color:white;
    }
    .sidebar-link.active span.icon {
        color:white;
    }

    .sidebar-footer {
        margin-top:auto;
        font-size:11px;
        color:#94a3b8;
        padding-left:6px;
    }

    /* ========== MAIN ========== */
    .main {
        flex:1;
        padding:24px 28px;
    }
    .main-header h1 {
        font-size:24px;
        font-weight:700;
        margin-bottom:4px;
    }
    .main-header p {
        font-size:13px;
        color:#6b7280;
    }

    /* CARDURI STATISTICI */
    .stats-row {
        display:grid;
        grid-template-columns:repeat(3, 1fr);
        gap:16px;
        margin:20px 0;
    }
    .card {
        background:#ffffff;
        border-radius:16px;
        padding:16px 18px;
        box-shadow:0 1px 3px rgba(15,23,42,0.08);
    }

    .stat-card-header {
        display:flex;
        justify-content:space-between;
        margin-bottom:12px;
        font-size:13px;
        color:#6b7280;
    }
    .stat-card-icon {
        width:28px;
        height:28px;
        border-radius:999px;
        background:#eff6ff;
        color:#2563eb;
        display:flex;
        justify-content:center;
        align-items:center;
        font-size:16px;
    }
    .stat-card-value {
        font-size:26px;
        font-weight:700;
    }
    .stat-card-change {
        color:#22c55e;
        font-size:12px;
    }

    /* ZBORURI RECENTE */
    .bottom-row {
        margin-top:20px;
    }
    .card-title {
        font-size:14px;
        font-weight:600;
        margin-bottom:12px;
    }
    .recent-list {
        margin-top:8px;
    }
    .flight-item {
        display:flex;
        justify-content:space-between;
        padding:10px 12px;
        border-radius:12px;
        background:#f9fafb;
        margin-bottom:8px;
    }
    .flight-code {
        font-size:13px;
        color:#2563eb;
        font-weight:600;
    }
    .flight-route {
        font-size:12px;
        color:#6b7280;
    }
    .flight-right {
        text-align:right;
        font-size:12px;
    }
    .flight-time {
        color:#4b5563;
        margin-bottom:4px;
    }

    .status-pill {
        padding:2px 8px;
        border-radius:999px;
        font-size:11px;
        font-weight:500;
    }
    .status-programat { background:#dcfce7; color:#166534; }
    .status-intarziat { background:#fee2e2; color:#b91c1c; }
    .status-decolat   { background:#dbeafe; color:#1d4ed8; }
    .status-anulat    { background:#fee2e2; color:#b91c1c; }

</style>
</head>
<body>

<div class="layout">

    <!-- SIDEBAR -->
    <!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-box">✈</div>
        <div>
            <div class="logo-text-title">BlueWing Airlines</div>
            <div class="logo-text-sub">Management system</div>
        </div>
    </div>

    <ul class="sidebar-nav">

        <li class="sidebar-item">
            <a href="dashboard.php" class="sidebar-link active">
                <span class="icon">🏠</span> Dashboard
            </a>
        </li>

        <li class="sidebar-item">
            <a href="zboruri.php" class="sidebar-link">
                <span class="icon">🛫</span> Zboruri
            </a>
        </li>

        <li class="sidebar-item">
            <a href="rezervari.php" class="sidebar-link">
                <span class="icon">📅</span> Rezervări
            </a>
        </li>

        <li class="sidebar-item">
            <a href="pasageri.php" class="sidebar-link">
                <span class="icon">👥</span> Pasageri
            </a>
        </li>

        <li class="sidebar-item">
            <a href="bilete.php" class="sidebar-link">
                <span class="icon">🎫</span> Bilete
            </a>
        </li>

        <li class="sidebar-item">
            <a href="aeronave.php" class="sidebar-link">
                <span class="icon">✈️</span> Aeronave
            </a>
        </li>

    </ul>

    <div class="sidebar-footer">
        BlueWing Admin • <?= date("Y") ?>
    </div>
</aside>

    <!-- MAIN CONTENT -->
    <main class="main">
        <div class="main-header">
            <h1>Dashboard</h1>
            <p>Bine ai venit în sistemul de management al companiei aeriene</p>
        </div>

        <!-- 3 CARDURI STATISTICI -->
        <div class="stats-row">

            <div class="card">
                <div class="stat-card-header">
                    <span>Zboruri active azi</span>
                    <div class="stat-card-icon">🛫</div>
                </div>
                <div class="stat-card-value"><?= $zboruriActiveAzi ?></div>
            </div>

            <div class="card">
                <div class="stat-card-header">
                    <span>Rezervări noi</span>
                    <div class="stat-card-icon">📅</div>
                </div>
                <div class="stat-card-value"><?= $rezervariNoi ?></div>
            </div>

            <div class="card">
                <div class="stat-card-header">
                    <span>Total pasageri</span>
                    <div class="stat-card-icon">👥</div>
                </div>
                <div class="stat-card-value"><?= $totalPasageri ?></div>
            </div>

        </div>

        <!-- Zboruri recente -->
        <div class="bottom-row">
            <div class="card">
                <div class="card-title">Zboruri recente</div>

                <div class="recent-list">
                    <?php if ($zboruriRecente && mysqli_num_rows($zboruriRecente) > 0): ?>
                        <?php while ($f = mysqli_fetch_assoc($zboruriRecente)): ?>

                            <?php
                                $status = strtolower($f["Status"]);
                                $badgeClass = "status-programat";

                                if ($status === "intirziat" || $status === "întârziat") $badgeClass = "status-intarziat";
                                if ($status === "decolat") $badgeClass = "status-decolat";
                                if ($status === "anulat") $badgeClass = "status-anulat";
                            ?>

                            <div class="flight-item">
                                <div>
                                    <div class="flight-code"><?= $f["CodZbor"] ?></div>
                                    <div class="flight-route"><?= $f["Origine"] ?> → <?= $f["Destinatie"] ?></div>
                                </div>

                                <div class="flight-right">
                                    <div class="flight-time"><?= substr($f["OraPlecare"], 0, 5) ?></div>
                                    <span class="status-pill <?= $badgeClass ?>">
                                        <?= $f["Status"] ?>
                                    </span>
                                </div>
                            </div>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="font-size:13px;color:#6b7280;">Nu există zboruri înregistrate.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </main>

</div>

<?php mysqli_close($conn); ?>
</body>
</html>
