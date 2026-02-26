<?php
/* ==================== CONEXIUNE ==================== */
$server = "localhost";
$user   = "root";
$pass   = "";
$db_name = "companieaeriana_db";

$conn = mysqli_connect($server, $user, $pass, $db_name);
if (!$conn) {
    die("Conexiunea a eșuat: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

function esc($conn, $s) {
    return mysqli_real_escape_string($conn, $s);
}

/* ==================== SALVARE EDITARE ZBOR ==================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "edit_flight") {

    $id         = (int)$_POST["ZborID"];
    $CodZbor    = esc($conn, $_POST["CodZbor"]);
    $Origine    = esc($conn, $_POST["Origine"]);
    $Destinatie = esc($conn, $_POST["Destinatie"]);
    $DataZbor   = esc($conn, $_POST["DataZbor"]);
    $OraPlecare = esc($conn, $_POST["OraPlecare"]);
    $OraSosire  = esc($conn, $_POST["OraSosire"]);
    $Poarte     = esc($conn, $_POST["Poarte"]);
    $Status     = esc($conn, $_POST["Status"]);
    $ZborActiv  = (int)$_POST["ZborActiv"];
    $AvionID    = (int)$_POST["AvionID"];
    
/* === VALIDARE CodZbor (EDIT) === */

/* Format MD000 */
if (!preg_match('/^MD[0-9]{3}$/', $CodZbor)) {
    echo "<script>alert('Codul zborului trebuie să fie în format MD000 (ex: MD123).'); window.history.back();</script>";
    exit;
}

/* Unic, dar excludem zborul curent */
$check = mysqli_query($conn, "SELECT CodZbor FROM Zboruri WHERE CodZbor='$CodZbor' AND ZborID!=$id");
if (mysqli_num_rows($check) > 0) {
    echo "<script>alert('Codul de zbor $CodZbor aparține deja altui zbor.'); window.history.back();</script>";
    exit;
}



    $sql = "
        UPDATE Zboruri SET
            CodZbor='$CodZbor',
            Origine='$Origine',
            Destinatie='$Destinatie',
            DataZbor='$DataZbor',
            OraPlecare='$OraPlecare',
            OraSosire=" . ($OraSosire ? "'$OraSosire'" : "NULL") . ",
            Poarta=" . ($Poarte ? "'$Poarte'" : "NULL") . ",
            Status='$Status',
            ZborActiv=$ZborActiv,
            AvionID=$AvionID
        WHERE ZborID=$id
    ";
    mysqli_query($conn, $sql);

    header("Location: zboruri.php");
    exit;
}


/* ==================== ADAUGĂ ZBOR NOU ==================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "add_flight") {

    $CodZbor    = esc($conn, $_POST["CodZbor"]);
    $Origine    = esc($conn, $_POST["Origine"]);
    $Destinatie = esc($conn, $_POST["Destinatie"]);
    $DataZbor   = esc($conn, $_POST["DataZbor"]);
    $OraPlecare = esc($conn, $_POST["OraPlecare"]);
    $OraSosire  = esc($conn, $_POST["OraSosire"]);
    $Poarte     = esc($conn, $_POST["Poarte"]);
    $Status     = esc($conn, $_POST["Status"]);
    $ZborActiv  = (int)$_POST["ZborActiv"];
    $AvionID    = (int)$_POST["AvionID"];

    /* === VALIDARE CodZbor (ADD) === */

$errors = [];

/* Format MD000 */
if (!preg_match('/^MD[0-9]{3}$/', $CodZbor)) {
    echo "<script>alert('Codul zborului trebuie să fie în format MD000 (ex: MD123).'); window.history.back();</script>";
    exit;
}

/* Unic în baza de date */
$check = mysqli_query($conn, "SELECT CodZbor FROM Zboruri WHERE CodZbor='$CodZbor'");
if (mysqli_num_rows($check) > 0) {
    echo "<script>alert('Codul de zbor $CodZbor există deja. Alege alt cod.'); window.history.back();</script>";
    exit;
}



    $sql = "
        INSERT INTO Zboruri
        (CodZbor, Origine, Destinatie, DataZbor, OraPlecare, OraSosire, Poarta, Status, ZborActiv, AvionID)
        VALUES
        ('$CodZbor', '$Origine', '$Destinatie', '$DataZbor', '$OraPlecare',
         " . ($OraSosire ? "'$OraSosire'" : "NULL") . ",
         " . ($Poarte ? "'$Poarte'" : "NULL") . ",
         '$Status', $ZborActiv, $AvionID)
    ";
    mysqli_query($conn, $sql);

    header("Location: zboruri.php");
    exit;
}


/* ==================== ȘTERGERE ZBOR ==================== */
if (isset($_GET["delete"])) {
    $id = (int)$_GET["delete"];
    mysqli_query($conn, "DELETE FROM Zboruri WHERE ZborID = $id");
    header("Location: zboruri.php");
    exit;
}


/* ==================== CITIRE DATE EDITARE ==================== */
$editMode = false;
$editData = null;

if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];
    $res = mysqli_query($conn, "SELECT * FROM Zboruri WHERE ZborID = $id LIMIT 1");
    if ($res && mysqli_num_rows($res) === 1) {
        $editData = mysqli_fetch_assoc($res);
        $editMode = true;
    }
}


/* ==================== FILTRARE ==================== */
$filter_cod   = $_GET["cod"]        ?? "";
$filter_orig  = $_GET["origine"]    ?? "";
$filter_dest  = $_GET["destinatie"] ?? "";
$filter_date  = $_GET["data"]       ?? "";
$filter_stat  = $_GET["status"]     ?? "";
$filter_activ = $_GET["activ"]      ?? "";


$allowedSort = [
    "CodZbor"    => "z.CodZbor",
    "Origine"    => "z.Origine",
    "Destinatie" => "z.Destinatie",
    "DataZbor"   => "z.DataZbor",
    "OraPlecare" => "z.OraPlecare",
    "OraSosire"  => "z.OraSosire",
    "Poarta"     => "z.Poarta",
    "Status"     => "z.Status",
    "ZborActiv"  => "z.ZborActiv",
    "CodAvion"   => "a.CodAvion"
];

$sort_by  = $_GET["sort_by"]  ?? "DataZbor";
$sort_dir = $_GET["sort_dir"] ?? "desc";

if (!isset($allowedSort[$sort_by])) $sort_by = "DataZbor";
$sort_sql = $allowedSort[$sort_by];

$sort_dir_sql = strtolower($sort_dir) === "asc" ? "ASC" : "DESC";


/* ==================== PAGINARE ==================== */

$per_page = 10; // cate zboruri pe pagina
$page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
$offset = ($page - 1) * $per_page;

/* Pentru a numara totalul de rezultate dupa filtrare */
$count_sql = "
SELECT COUNT(*) as total
FROM Zboruri z
LEFT JOIN Avioane a ON z.AvionID = a.AvionID
WHERE 1
";

/* Duplicate filtrarea pentru count */
if ($filter_cod !== "")       $count_sql .= " AND z.CodZbor LIKE '%" . esc($conn, $filter_cod) . "%'";
if ($filter_orig !== "")      $count_sql .= " AND z.Origine = '" . esc($conn, $filter_orig) . "'";
if ($filter_dest !== "")      $count_sql .= " AND z.Destinatie = '" . esc($conn, $filter_dest) . "'";
if ($filter_date !== "")      $count_sql .= " AND z.DataZbor = '" . esc($conn, $filter_date) . "'";
if ($filter_stat !== "")      $count_sql .= " AND z.Status = '" . esc($conn, $filter_stat) . "'";
if ($filter_activ !== "")     $count_sql .= " AND z.ZborActiv = " . ($filter_activ === "1" ? 1 : 0);

$count_res = mysqli_query($conn, $count_sql);
$total_rows = mysqli_fetch_assoc($count_res)["total"];
$total_pages = ceil($total_rows / $per_page);


/* ==================== QUERY LISTARE ZBORURI ==================== */

$sql = "
SELECT 
    z.*, 
    a.CodAvion, 
    a.Model
FROM Zboruri z
LEFT JOIN Avioane a ON z.AvionID = a.AvionID
WHERE 1
";

if ($filter_cod !== "") {
    $sql .= " AND z.CodZbor LIKE '%" . esc($conn, $filter_cod) . "%'";
}
if ($filter_orig !== "") {
    $sql .= " AND z.Origine = '" . esc($conn, $filter_orig) . "'";
}
if ($filter_dest !== "") {
    $sql .= " AND z.Destinatie = '" . esc($conn, $filter_dest) . "'";
}
if ($filter_date !== "") {
    $sql .= " AND z.DataZbor = '" . esc($conn, $filter_date) . "'";
}
if ($filter_stat !== "") {
    $sql .= " AND z.Status = '" . esc($conn, $filter_stat) . "'";
}
if ($filter_activ !== "") {
    $sql .= " AND z.ZborActiv = " . ($filter_activ === "1" ? 1 : 0);
}

$sql .= " ORDER BY $sort_sql $sort_dir_sql LIMIT $offset, $per_page";

$flights = mysqli_query($conn, $sql);



/* ==================== ORAȘE ==================== */
$orase = ["Chișinău","București","Cluj","Iași","Timișoara","Istanbul","Paris","Londra","Roma","München"];


/* ==================== AVIOANE ==================== */
$avioaneList = mysqli_query($conn, "SELECT * FROM Avioane ORDER BY CodAvion ASC");

?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>Zboruri – BlueWing Airlines</title>

<style>

    * { margin:0; padding:0; box-sizing:border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { background:#f3f4f6; display:flex; min-height:100vh; }

    /* ===== SIDEBAR ALB (ca la dashboard) ===== */
    .sidebar {
        width:240px;
        background:#ffffff;
        border-right:1px solid #e5e7eb;
        padding:24px 18px;
        display:flex;
        flex-direction:column;
    }
    .sidebar-logo {
        display:flex;
        align-items:center;
        margin-bottom:32px;
        padding:0 6px;
    }
    .logo-box {
        width:40px; height:40px;
        border-radius:12px;
        background:#3b82f6;
        color:#fff;
        display:flex;
        align-items:center;
        justify-content:center;
        font-weight:700;
        font-size:20px;
        margin-right:10px;
    }
    .logo-title { font-size:15px; font-weight:600; color:#111827; }
    .logo-sub { font-size:11px; color:#6b7280; }

    .nav {
        list-style:none;
        margin-top:10px;
    }
    .nav-item { margin-bottom:4px; }
    .nav-link {
        display:flex;
        align-items:center;
        padding:10px 12px;
        border-radius:10px;
        text-decoration:none;
        color:#374151;
        font-size:14px;
        transition:background .15s, color .15s;
    }
    .nav-link span.icon {
        width:20px;
        margin-right:10px;
        text-align:center;
        font-size:16px;
    }
    .nav-link:hover {
        background:#f3f4f6;
    }
    .nav-link.active {
        background:#dbeafe;
        color:#1d4ed8;
        font-weight:600;
    }
    .sidebar-footer {
        margin-top:auto;
        font-size:11px;
        color:#9ca3af;
        padding:0 6px;
    }

    /* ===== MAIN ===== */
    .main {
        flex:1;
        padding:24px 32px;
    }
    .top-bar {
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:20px;
    }
    .top-bar-left h1 {
        font-size:22px;
        font-weight:700;
        color:#111827;
        margin-bottom:4px;
    }
    .top-bar-left p {
        font-size:13px;
        color:#6b7280;
    }
    .btn-primary {
        background:#2563eb;
        color:#ffffff;
        border:none;
        padding:9px 16px;
        border-radius:10px;
        font-size:14px;
        display:inline-flex;
        align-items:center;
        gap:8px;
        cursor:pointer;
        text-decoration:none;
    }
    .btn-primary:hover {
        background:#1d4ed8;
    }

    /* ===== CARDURI ===== */
    .card {
        background:#ffffff;
        border-radius:16px;
        box-shadow:0 1px 3px rgba(15,23,42,0.08);
    }

    /* ===== FORMULAR ADD / EDIT (card sus) ===== */
    .add-card {
        margin-bottom:22px;
        padding:18px 20px;
    }
    .add-card h3 {
        font-size:16px;
        margin-bottom:12px;
        color:#111827;
    }
    .form-row {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:14px;
        margin-bottom:10px;
    }
    .form-group label {
        font-size:12px;
        color:#6b7280;
        display:block;
        margin-bottom:3px;
    }
    .form-group input,
    .form-group select {
        width:100%;
        padding:8px 10px;
        border-radius:10px;
        border:1px solid #e5e7eb;
        font-size:13px;
        background:#f9fafb;
    }
    .form-actions {
        margin-top:10px;
        display:flex;
        gap:10px;
    }
    .btn-save {
        background:#10b981;
        color:#fff;
        border:none;
        padding:8px 14px;
        border-radius:10px;
        font-size:13px;
        cursor:pointer;
    }
    .btn-save:hover { background:#059669; }
    .btn-ghost {
        background:transparent;
        border:none;
        color:#6b7280;
        font-size:13px;
        cursor:pointer;
        text-decoration:none;
        display:inline-flex;
        align-items:center;
    }

    /* ===== FILTRARE ===== */
    .filter-card {
        padding:18px 20px;
        margin-bottom:18px;
    }
    .filter-header {
        font-size:15px;
        font-weight:600;
        margin-bottom:14px;
        color:#111827;
    }
    .filter-grid {
        display:grid;
        grid-template-columns:repeat(6,minmax(0,1fr));
        gap:12px;
        align-items:flex-end;
    }
    .filter-group label {
        font-size:12px;
        color:#6b7280;
        display:block;
        margin-bottom:4px;
    }
    .filter-group input,
    .filter-group select {
        width:100%;
        padding:8px 10px;
        border-radius:999px;
        border:1px solid #e5e7eb;
        font-size:13px;
        background:#f9fafb;
    }
    .filter-actions {
        display:flex;
        gap:8px;
        justify-content:flex-end;
        margin-top:12px;
    }
    .btn-reset {
        border-radius:999px;
        border:1px solid #e5e7eb;
        background:#f9fafb;
        padding:7px 14px;
        font-size:13px;
        cursor:pointer;
        color:#374151;
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        justify-content:center;
    }
    .btn-reset:hover { background:#e5e7eb; }

    /* sortare */
    .sort-row {
        display:flex;
        justify-content:flex-end;
        margin-top:6px;
        gap:8px;
        align-items:center;
        font-size:12px;
        color:#6b7280;
    }
    .sort-row select {
        padding:5px 10px;
        border-radius:999px;
        border:1px solid #e5e7eb;
        background:#f9fafb;
        font-size:12px;
    }

    /* ===== TABEL ===== */
    .table-card {
        padding:18px 0 6px 0;
    }
    table {
        width:100%;
        border-collapse:collapse;
        font-size:13px;
    }
    thead tr {
        background:#f9fafb;
    }
    th, td {
        padding:10px 18px;
        text-align:left;
    }
    th {
        font-weight:600;
        color:#6b7280;
        border-bottom:1px solid #e5e7eb;
        font-size:12px;
    }
    tbody tr {
        border-bottom:1px solid #f3f4f6;
    }
    tbody tr:hover {
        background:#f9fafb;
    }
    .code-link {
        color:#2563eb;
        text-decoration:none;
        font-weight:600;
        font-size:13px;
    }
    .code-link:hover { text-decoration:underline; }
    .time-text { font-size:13px; color:#111827; }

    .status-pill {
        display:inline-block;
        padding:2px 8px;
        border-radius:999px;
        font-size:11px;
        font-weight:500;
    }
    .status-programat  { background:#dbeafe; color:#1d4ed8; }
    .status-intarziat  { background:#fef3c7; color:#b45309; }
    .status-decolat    { background:#dcfce7; color:#166534; }
    .status-anulat     { background:#fee2e2; color:#b91c1c; }

    .badge-activ {
        display:inline-block;
        padding:2px 8px;
        border-radius:999px;
        font-size:10px;
        background:#dcfce7;
        color:#166534;
        font-weight:500;
    }
    .badge-inactiv {
        display:inline-block;
        padding:2px 8px;
        border-radius:999px;
        font-size:10px;
        background:#fee2e2;
        color:#b91c1c;
        font-weight:500;
    }

    .btn-small {
        border-radius:999px;
        border:1px solid #e5e7eb;
        padding:5px 10px;
        font-size:12px;
        background:#ffffff;
        cursor:pointer;
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        color:#374151;
    }
    .btn-small:hover { background:#f3f4f6; }
    .btn-danger {
        border-color:#fecaca;
        color:#b91c1c;
    }   
</style>

</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-box">✈</div>
        <div>
            <div class="logo-title">BlueWing Airlines</div>
            <div class="logo-sub">Management system</div>
        </div>
    </div>

<ul class="nav">
    <li class="nav-item"><a href="dashboard.php" class="nav-link"><span class="icon">🏠</span>Dashboard</a></li>
    <li class="nav-item"><a href="zboruri.php" class="nav-link active"><span class="icon">🛫</span>Zboruri</a></li>
    <li class="nav-item"><a href="rezervari.php" class="nav-link"><span class="icon">📅</span>Rezervări</a></li>
    <li class="nav-item"><a href="pasageri.php" class="nav-link"><span class="icon">👥</span>Pasageri</a></li>
    <li class="nav-item"><a href="bilete.php" class="nav-link"><span class="icon">🎫</span>Bilete</a></li>
    <li class="nav-item"><a href="aeronave.php" class="nav-link"><span class="icon">✈️</span>Aeronave</a></li>
</ul>

    <div class="sidebar-footer">BlueWing Admin • <?= date("Y") ?></div>
</aside>

<!-- MAIN -->
<main class="main">

    <div class="top-bar">
        <div class="top-bar-left">
            <h1>Zboruri</h1>
            <p>Gestionează toate zborurile companiei aeriene</p>
        </div>

        <a class="btn-primary" href="zboruri.php?add=1">＋ Adaugă zbor</a>
    </div>


<!-- ================= FORMULAR EDITARE ================= -->
<?php if ($editMode): ?>
<div class="card add-card">
    <h3>Editează zborul <?= htmlspecialchars($editData["CodZbor"]) ?></h3>

    <form method="post">
        <input type="hidden" name="action" value="edit_flight">
        <input type="hidden" name="ZborID" value="<?= $editData["ZborID"] ?>">

        <div class="form-row">
            <div class="form-group">
                <label>Cod zbor</label>
                <input type="text" name="CodZbor" value="<?= $editData["CodZbor"] ?>" required>
            </div>

            <div class="form-group">
                <label>Origine</label>
                <select name="Origine" required>
                    <?php foreach ($orase as $o): ?>
                        <option value="<?= $o ?>" <?= $editData["Origine"]==$o?"selected":"" ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Destinație</label>
                <select name="Destinatie" required>
                    <?php foreach ($orase as $o): ?>
                        <option value="<?= $o ?>" <?= $editData["Destinatie"]==$o?"selected":"" ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>


        <div class="form-row">
            <div class="form-group">
                <label>Avion</label>
                <select name="AvionID" required>
                    <?php 
                    $avioane2 = mysqli_query($conn, "SELECT * FROM Avioane ORDER BY CodAvion");
                    while($a = mysqli_fetch_assoc($avioane2)): ?>
                        <option value="<?= $a["AvionID"] ?>"
                            <?= ($editData["AvionID"] == $a["AvionID"]) ? "selected" : "" ?>>
                            <?= $a["CodAvion"] ?> — <?= $a["Model"] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Data zborului</label>
                <input type="date" name="DataZbor" value="<?= $editData["DataZbor"] ?>" required>
            </div>

            <div class="form-group">
                <label>Ora plecare</label>
                <input type="time" name="OraPlecare" value="<?= substr($editData["OraPlecare"],0,5) ?>" required>
            </div>
        </div>


        <div class="form-row">

            <div class="form-group">
                <label>Ora sosire</label>
                <input type="time" name="OraSosire" value="<?= $editData["OraSosire"] ? substr($editData["OraSosire"],0,5) : "" ?>">
            </div>

            <div class="form-group">
                <label>Poartă</label>
                <input type="text" name="Poarte" value="<?= $editData["Poarta"] ?>">
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="Status">
                    <option value="Programat" <?= $editData["Status"]=="Programat"?"selected":"" ?>>Programat</option>
                    <option value="Intirziat" <?= $editData["Status"]=="Intirziat"?"selected":"" ?>>Întârziat</option>
                    <option value="Decolat"   <?= $editData["Status"]=="Decolat"?"selected":"" ?>>Decolat</option>
                    <option value="Anulat"    <?= $editData["Status"]=="Anulat"?"selected":"" ?>>Anulat</option>
                </select>
            </div>
        </div>


        <div class="form-row">
            <div class="form-group">
                <label>Zbor activ</label>
                <select name="ZborActiv">
                    <option value="1" <?= $editData["ZborActiv"]==1?"selected":"" ?>>Activ</option>
                    <option value="0" <?= $editData["ZborActiv"]==0?"selected":"" ?>>Inactiv</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn-save">Salvează modificările</button>
            <a href="zboruri.php" class="btn-ghost">Anulează</a>
        </div>
    </form>
</div>
<?php endif; ?>


<!-- ================= FORMULAR ADAUGARE ================= -->
<?php if (!$editMode && isset($_GET["add"])): ?>
<div class="card add-card">
    <h3>Adaugă zbor nou</h3>

    <form method="post">
        <input type="hidden" name="action" value="add_flight">

        <div class="form-row">
            <div class="form-group">
                <label>Cod zbor</label>
                <input type="text" name="CodZbor" required>
            </div>

            <div class="form-group">
                <label>Origine</label>
                <select name="Origine" required>
                    <option value="">Alege...</option>
                    <?php foreach ($orase as $o): ?>
                        <option value="<?= $o ?>"><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Destinație</label>
                <select name="Destinatie" required>
                    <option value="">Alege...</option>
                    <?php foreach ($orase as $o): ?>
                        <option value="<?= $o ?>"><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>


        <div class="form-row">

            <div class="form-group">
                <label>Avion</label>
                <select name="AvionID" required>
                    <option value="">Alege avion</option>
                    <?php while($a = mysqli_fetch_assoc($avioaneList)): ?>
                        <option value="<?= $a["AvionID"] ?>">
                            <?= $a["CodAvion"] ?> — <?= $a["Model"] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Data zborului</label>
                <input type="date" name="DataZbor" required>
            </div>

            <div class="form-group">
                <label>Ora plecare</label>
                <input type="time" name="OraPlecare" required>
            </div>
        </div>


        <div class="form-row">
            <div class="form-group">
                <label>Ora sosire</label>
                <input type="time" name="OraSosire">
            </div>

            <div class="form-group">
                <label>Poartă</label>
                <input type="text" name="Poarte" placeholder="A12">
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="Status">
                    <option value="Programat">Programat</option>
                    <option value="Intirziat">Întârziat</option>
                    <option value="Decolat">Decolat</option>
                    <option value="Anulat">Anulat</option>
                </select>
            </div>
        </div>


        <div class="form-row">
            <div class="form-group">
                <label>Zbor activ</label>
                <select name="ZborActiv">
                    <option value="1">Activ</option>
                    <option value="0">Inactiv</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn-save">Salvează zbor</button>
            <a href="zboruri.php" class="btn-ghost">Anulează</a>
        </div>
    </form>
</div>
<?php endif; ?>


<!-- ================= CARD FILTRARE ================= -->
<div class="card filter-card">
    <div class="filter-header">Filtrare</div>

    <form method="get">
        <div class="filter-grid">

            <div class="filter-group">
                <label>Cod zbor</label>
                <input type="text" name="cod" value="<?= $filter_cod ?>">
            </div>

            <div class="filter-group">
                <label>Origine</label>
                <select name="origine">
                    <option value="">Toate</option>
                    <?php foreach($orase as $o): ?>
                        <option value="<?= $o ?>" <?= $filter_orig==$o?"selected":"" ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Destinație</label>
                <select name="destinatie">
                    <option value="">Toate</option>
                    <?php foreach($orase as $o): ?>
                        <option value="<?= $o ?>" <?= $filter_dest==$o?"selected":"" ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Dată</label>
                <input type="date" name="data" value="<?= $filter_date ?>">
            </div>

            <div class="filter-group">
                <label>Status</label>
                <select name="status">
                    <option value="">Toate</option>
                    <option value="Programat" <?= $filter_stat=="Programat"?"selected":"" ?>>Programat</option>
                    <option value="Intirziat" <?= $filter_stat=="Intirziat"?"selected":"" ?>>Întârziat</option>
                    <option value="Decolat"   <?= $filter_stat=="Decolat"?"selected":"" ?>>Decolat</option>
                    <option value="Anulat"    <?= $filter_stat=="Anulat"?"selected":"" ?>>Anulat</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Zbor activ</label>
                <select name="activ">
                    <option value="">Toate</option>
                    <option value="1" <?= $filter_activ==="1"?"selected":"" ?>>Activ</option>
                    <option value="0" <?= $filter_activ==="0"?"selected":"" ?>>Inactiv</option>
                </select>
            </div>

        </div>

        <div class="sort-row">
            <span>Sortare:</span>

            <select name="sort_by">
                <option value="DataZbor"   <?= $sort_by=="DataZbor"?"selected":"" ?>>DataZbor</option>
                <option value="CodZbor"    <?= $sort_by=="CodZbor"?"selected":"" ?>>CodZbor</option>
                <option value="Origine"    <?= $sort_by=="Origine"?"selected":"" ?>>Origine</option>
                <option value="Destinatie" <?= $sort_by=="Destinatie"?"selected":"" ?>>Destinație</option>
                <option value="CodAvion"   <?= $sort_by=="CodAvion"?"selected":"" ?>>Avion</option>
            </select>

            <select name="sort_dir">
                <option value="asc"  <?= $sort_dir=="asc"?"selected":"" ?>>↑ Crescător</option>
                <option value="desc" <?= $sort_dir=="desc"?"selected":"" ?>>↓ Descrescător</option>
            </select>

            <div class="filter-actions">
                <a href="zboruri.php" class="btn-reset">Resetează</a>
                <button class="btn-primary">Aplică</button>
            </div>
        </div>
    </form>
</div>


<!-- ================= TABEL ZBORURI ================= -->
<div class="card table-card">

<table>
    <thead>
        <tr>
            <th>CodZbor</th>
            <th>Origine</th>
            <th>Destinație</th>
            <th>Data</th>
            <th>Plecare</th>
            <th>Sosire</th>
            <th>Poartă</th>
            <th>Avion</th>
            <th>Status</th>
            <th>Activ</th>
            <th>Acțiuni</th>
        </tr>
    </thead>

    <tbody>
    <?php if ($flights && mysqli_num_rows($flights) > 0): ?>
        <?php while($f = mysqli_fetch_assoc($flights)): ?>

            <?php
                $statusClass = "status-programat";
                if ($f["Status"]=="Intirziat") $statusClass="status-intarziat";
                if ($f["Status"]=="Decolat")   $statusClass="status-decolat";
                if ($f["Status"]=="Anulat")    $statusClass="status-anulat";
            ?>

            <tr>
                <td><a class="code-link"><?= $f["CodZbor"] ?></a></td>
                <td><?= $f["Origine"] ?></td>
                <td><?= $f["Destinatie"] ?></td>
                <td><?= $f["DataZbor"] ?></td>
                <td><?= substr($f["OraPlecare"],0,5) ?></td>
                <td><?= $f["OraSosire"] ? substr($f["OraSosire"],0,5) : "-" ?></td>
                <td><?= $f["Poarta"] ?: "-" ?></td>

                <td>
                    <?php if ($f["CodAvion"]): ?>
                        <span style="color:#2563eb; font-weight:600;">
                            <?= $f["CodAvion"] ?>
                        </span>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>

                <td>
                    <span class="status-pill <?= $statusClass ?>">
                        <?= $f["Status"]=="Intirziat" ? "Întârziat" : $f["Status"] ?>
                    </span>
                </td>

                <td>
                    <?= $f["ZborActiv"] ? "<span class='badge-activ'>Activ</span>" : "<span class='badge-inactiv'>Inactiv</span>" ?>
                </td>

                <td>
                    <a class="btn-small" href="zboruri.php?edit=<?= $f["ZborID"] ?>">Editează</a>
                    <a class="btn-small btn-danger"
                       href="zboruri.php?delete=<?= $f["ZborID"] ?>"
                       onclick="return confirm('Ștergi zborul?');">
                        Șterge
                    </a>
                </td>
            </tr>

        <?php endwhile; ?>

    <?php else: ?>
        <tr>
            <td colspan="11" style="padding:16px; color:#6b7280;">Nu există zboruri.</td>
        </tr>
    <?php endif; ?>
    </tbody>

</table>

</div>

<!-- ================= PAGINARE ================= -->
<?php if ($total_pages > 1): ?>
    <div style="padding: 18px; display:flex; justify-content:center; gap:6px;">

        <?php 
        // reconstruim query string-ul fără "page"
        $query = $_GET;
        unset($query["page"]);
        $base_url = "zboruri.php?" . http_build_query($query) . "&page=";
        ?>

        <!-- PREV -->
        <?php if ($page > 1): ?>
            <a class="btn-small" href="<?= $base_url . ($page - 1) ?>">« Prev</a>
        <?php else: ?>
            <span class="btn-small" style="opacity:0.5; pointer-events:none;">« Prev</span>
        <?php endif; ?>

        <!-- NUMERE PAGINI -->
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="btn-small" style="background:#2563eb; color:white; border-color:#2563eb;"><?= $i ?></span>
            <?php else: ?>
                <a class="btn-small" href="<?= $base_url . $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <!-- NEXT -->
        <?php if ($page < $total_pages): ?>
            <a class="btn-small" href="<?= $base_url . ($page + 1) ?>">Next »</a>
        <?php else: ?>
            <span class="btn-small" style="opacity:0.5; pointer-events:none;">Next »</span>
        <?php endif; ?>

    </div>
<?php endif; ?>


</main>

<?php mysqli_close($conn); ?>
</body>
</html>
