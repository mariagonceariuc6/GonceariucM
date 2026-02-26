<?php
/* ==================== CONEXIUNE DB ==================== */
$server = "localhost";
$user   = "root";
$pass   = "";
$db_name = "companieaeriana_db";

$conn = mysqli_connect($server, $user, $pass, $db_name);
mysqli_set_charset($conn, "utf8mb4");
if (!$conn) {
    die("Conexiune eșuată: " . mysqli_connect_error());
}

/* ESC */
function esc($c, $v) {
    return mysqli_real_escape_string($c, trim($v));
}

/* ===========================================================
   ===============      ADAUGĂ AVION     ======================
   =========================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["action"])
    && $_POST["action"] === "add_avion") {

    $CodAvion    = esc($conn, $_POST["CodAvion"]);
    $Model       = esc($conn, $_POST["Model"]);
    $Capacitate  = (int)$_POST["Capacitate"];
    $AnFabricare = (int)$_POST["AnFabricare"];
    $Statut      = esc($conn, $_POST["Statut"]);

    /* ==================== VALIDĂRI AERONAVE (ADD) ==================== */

// Validare CodAvion: LL000 (două litere mari + 3 cifre)
if (!preg_match('/^[A-Z]{2}[0-9]{3}$/', $CodAvion)) {
    echo "<script>alert('Cod avion invalid! Format corect: LL000 (ex: AB123).'); window.history.back();</script>";
    exit;
}

// Validare Model
if (!preg_match('/^[A-Za-z0-9\s\-]+$/', $Model)) {
    echo "<script>alert('Model invalid! Sunt permise doar litere, cifre, spații și liniuțe.'); window.history.back();</script>";
    exit;
}

// Validare Capacitate
if ($Capacitate < 10 || $Capacitate > 900) {
    echo "<script>alert('Capacitatea trebuie să fie între 10 și 900.'); window.history.back();</script>";
    exit;
}

// Validare An Fabricare
$yearNow = date('Y');
if ($AnFabricare < 1950 || $AnFabricare > $yearNow) {
    echo "<script>alert('An fabricare invalid! Trebuie între 1950 și $yearNow.'); window.history.back();</script>";
    exit;
}

// Validare Statut
$validStat = ["Activ", "Reparatie", "Retras"];
if (!in_array($Statut, $validStat)) {
    echo "<script>alert('Statut invalid!'); window.history.back();</script>";
    exit;
}


    if ($CodAvion !== "" && $Model !== "" && $Capacitate > 0) {
        mysqli_query(
            $conn,
            "INSERT INTO Avioane (CodAvion, Model, Capacitate, AnFabricare, Statut)
             VALUES ('$CodAvion', '$Model', $Capacitate, $AnFabricare, '$Statut')"
        );
    }

    header("Location: aeronave.php");
    exit;
}

/* ======== EDITARE ========= */
$editAvion = null;
if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];
    $q = mysqli_query($conn, "SELECT * FROM Avioane WHERE AvionID = $id LIMIT 1");
    $editAvion = mysqli_fetch_assoc($q);
}

/* ======== SALVARE EDITARE ========= */
if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["action"])
    && $_POST["action"] === "update_avion") {

    $AvionID     = (int)$_POST["AvionID"];
    $CodAvion    = esc($conn, $_POST["CodAvion"]);
    $Model       = esc($conn, $_POST["Model"]);
    $Capacitate  = (int)$_POST["Capacitate"];
    $AnFabricare = (int)$_POST["AnFabricare"];
    $Statut      = esc($conn, $_POST["Statut"]);

    /* ==================== VALIDĂRI AERONAVE (UPDATE) ==================== */

// Cod avion: LL000
if (!preg_match('/^[A-Z]{2}[0-9]{3}$/', $CodAvion)) {
    echo "<script>alert('Cod avion invalid!!!!! Format corect: LL000 (ex: AB123).'); window.history.back();</script>";
    exit;
}

// Model
if (!preg_match('/^[A-Za-z0-9\s\-]+$/', $Model)) {
    echo "<script>alert('Model invalid! Doar litere, cifre, spații și liniuțe.'); window.history.back();</script>";
    exit;
}

// Capacitate
if ($Capacitate < 10 || $Capacitate > 900) {
    echo "<script>alert('Capacitatea trebuie să fie între 10 și 900.'); window.history.back();</script>";
    exit;
}

// An fabricare
$yearNow = date('Y');
if ($AnFabricare < 1950 || $AnFabricare > $yearNow) {
    echo "<script>alert('An fabricare invalid! Trebuie între 1950 și $yearNow.'); window.history.back();</script>";
    exit;
}

// Statut
$validStat = ["Activ", "Reparatie", "Retras"];
if (!in_array($Statut, $validStat)) {
    echo "<script>alert('Statut invalid!'); window.history.back();</script>";
    exit;
}


    mysqli_query(
        $conn,
        "UPDATE Avioane SET
            CodAvion='$CodAvion',
            Model='$Model',
            Capacitate=$Capacitate,
            AnFabricare=$AnFabricare,
            Statut='$Statut'
         WHERE AvionID=$AvionID"
    );

    header("Location: aeronave.php");
    exit;
}

/* ======== ȘTERGERE ========= */
if (isset($_GET["delete"])) {
    $id = (int)$_GET["delete"];
    mysqli_query($conn, "DELETE FROM Avioane WHERE AvionID = $id");
    header("Location: aeronave.php");
    exit;
}

/* ================= FILTRARE ================= */
$f_cod   = esc($conn, $_GET["cod"] ?? "");
$f_mod   = esc($conn, $_GET["mod"] ?? "");
$f_stat  = esc($conn, $_GET["stat"] ?? "");
$f_cmin  = esc($conn, $_GET["cmin"] ?? "");
$f_cmax  = esc($conn, $_GET["cmax"] ?? "");
$f_an1   = esc($conn, $_GET["an1"] ?? "");
$f_an2   = esc($conn, $_GET["an2"] ?? "");

$allowedSort = [
    "CodAvion"    => "CodAvion",
    "Model"       => "Model",
    "Capacitate"  => "Capacitate",
    "AnFabricare" => "AnFabricare",
];

$sort_by  = $_GET["sort_by"]  ?? "AnFabricare";
$sort_dir = strtolower($_GET["sort_dir"] ?? "desc");
$sort_dir_sql = ($sort_dir === "asc") ? "ASC" : "DESC";

if (!isset($allowedSort[$sort_by]))
    $sort_by = "AnFabricare";

/* ==================== PAGINARE ==================== */

$per_page = 7; // câte aeronave să afișeze pe o pagină
$page = isset($_GET["page"]) ? max(1, (int)$_GET["page"]) : 1;
$offset = ($page - 1) * $per_page;

/* calculăm totalul pentru paginare */
$sql_count = "SELECT COUNT(*) AS total FROM Avioane WHERE 1";

if ($f_cod !== "")  $sql_count .= " AND CodAvion LIKE '%$f_cod%'";
if ($f_mod !== "")  $sql_count .= " AND Model LIKE '%$f_mod%'";
if ($f_stat !== "") $sql_count .= " AND Statut='$f_stat'";
if ($f_cmin !== "") $sql_count .= " AND Capacitate >= ".(int)$f_cmin;
if ($f_cmax !== "") $sql_count .= " AND Capacitate <= ".(int)$f_cmax;
if ($f_an1 !== "")  $sql_count .= " AND AnFabricare >= ".(int)$f_an1;
if ($f_an2 !== "")  $sql_count .= " AND AnFabricare <= ".(int)$f_an2;

$res_count = mysqli_query($conn, $sql_count);
$total_rows = mysqli_fetch_assoc($res_count)["total"];
$total_pages = ceil($total_rows / $per_page);


/* ================= LISTĂ ================= */
$sql = "SELECT * FROM Avioane WHERE 1";

if ($f_cod !== "")  $sql .= " AND CodAvion LIKE '%$f_cod%'";
if ($f_mod !== "")  $sql .= " AND Model LIKE '%$f_mod%'";
if ($f_stat !== "") $sql .= " AND Statut='$f_stat'";

if ($f_cmin !== "") $sql .= " AND Capacitate >= ".(int)$f_cmin;
if ($f_cmax !== "") $sql .= " AND Capacitate <= ".(int)$f_cmax;

if ($f_an1 !== "")  $sql .= " AND AnFabricare >= ".(int)$f_an1;
if ($f_an2 !== "")  $sql .= " AND AnFabricare <= ".(int)$f_an2;

$sql .= " ORDER BY $sort_by $sort_dir_sql LIMIT $per_page OFFSET $offset";

$lista = mysqli_query($conn, $sql);


?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>Aeronave – BlueWing Airlines</title>

<!-- CSS-ul tău exact -->
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

body {
    background: #f3f4f6;
    display: flex;
    min-height: 100vh;
}

/* ================= SIDEBAR ================= */
.sidebar {
    width: 240px;
    background: #ffffff;
    border-right: 1px solid #e5e7eb;
    padding: 24px 20px;
    display: flex;
    flex-direction: column;
}

.sidebar-logo {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
}

.logo-box {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: #2563eb;
    color: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
    font-weight: 700;
    font-size: 20px;
    margin-right: 12px;
}

.logo-title {
    font-size: 16px;
    font-weight: 600;
    color: #111827;
}

.logo-sub {
    font-size: 11px;
    color: #6b7280;
    margin-top: -2px;
}

.nav {
    list-style: none;
    margin-top: 10px;
}

.nav-item {
    margin-bottom: 6px;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 14px;
    color: #374151;
    transition: .15s;
}

.nav-link:hover {
    background: #f3f4f6;
}

.nav-link.active {
    background: #dbeafe;
    color: #1d4ed8;
    font-weight: 600;
}

.nav-link .icon {
    width: 20px;
    margin-right: 10px;
    text-align: center;
}

/* FOOTER */
.sidebar-footer {
    margin-top: auto;
    font-size: 11px;
    color: #9ca3af;
    padding-top: 20px;
}

/* ================= MAIN ================= */
.main {
    flex: 1;
    padding: 24px 32px;
}

/* TOP BAR */
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.top-bar h1 {
    font-size: 24px;
    font-weight: 700;
    color: #111827;
}

.top-bar p {
    font-size: 13px;
    color: #6b7280;
}

/* BUTTON PRIMARY */
.btn-primary {
    background: #2563eb;
    color: #fff;
    border: none;
    padding: 9px 16px;
    border-radius: 10px;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    text-decoration: none;
}

.btn-primary:hover {
    background: #1d4ed8;
}

/* CARD */
.card {
    background: #ffffff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    margin-bottom: 20px;
}

/* GRID FORM */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 20px;
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    color: #6b7280;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 9px 12px;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    font-size: 14px;
}

/* ================= SORTARE – STIL DROPDOWN ROTUND ================= */
.sort-select {
    appearance: none;
    padding: 8px 16px;
    border-radius: 999px;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    font-size: 14px;
    color: #374151;
    cursor: pointer;

    background-image:
        linear-gradient(45deg, transparent 50%, #6b7280 50%),
        linear-gradient(135deg, #6b7280 50%, transparent 50%),
        linear-gradient(to right, #f9fafb, #f9fafb);
    background-position:
        calc(100% - 20px) calc(50% - 3px),
        calc(100% - 15px) calc(50% - 3px),
        calc(100% - 2.5em) 0.3em;
    background-size: 5px 5px, 5px 5px, 1px 1.5em;
    background-repeat: no-repeat;
}

.sort-select:hover { background: #f3f4f6; }

.btn-reset {
    border-radius:999px;
    border:1px solid #e5e7eb;
    background:#f9fafb;
    padding:7px 14px;
    font-size:13px;
    cursor:pointer;
    color:#374151;
    text-decoration:none;
}
.btn-reset:hover { background:#e5e7eb; }

/* TABLE */
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

thead tr { background: #f9fafb; }

th {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    padding: 12px 16px;
    border-bottom: 1px solid #e5e7eb;
    text-align:left;
}

td {
    padding: 12px 16px;
    border-bottom: 1px solid #f3f4f6;
    color: #111827;
}

tbody tr:hover { background: #f9fafb; }

.badge {
    padding: 4px 9px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
}

.b-activ   { background:#dcfce7; color:#166534; }
.b-anulat  { background:#fee2e2; color:#b91c1c; }
.b-folosit { background:#dbeafe; color:#1d4ed8; }
.btn-small {
        padding: 5px 12px;
        border-radius: 10px;
        font-size: 12px;
        border: 1px solid #e5e7eb;
        background: white;
        cursor: pointer;
        text-decoration: none;
        color: #374151;
    }

    .btn-small:hover {
        background: #f3f4f6;
    }

    .btn-danger {
        border-color: #fecaca;
        color: #b91c1c;
    }
<?php echo file_get_contents(__FILE__, false, null, __COMPILER_HALT_OFFSET__); ?>
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
        <li class="nav-item"><a href="zboruri.php" class="nav-link"><span class="icon">🛫</span>Zboruri</a></li>
        <li class="nav-item"><a href="rezervari.php" class="nav-link"><span class="icon">📅</span>Rezervări</a></li>
        <li class="nav-item"><a href="pasageri.php" class="nav-link"><span class="icon">👥</span>Pasageri</a></li>
        <li class="nav-item"><a href="bilete.php" class="nav-link"><span class="icon">🎫</span>Bilete</a></li>
        <li class="nav-item"><a href="aeronave.php" class="nav-link active"><span class="icon">✈️</span>Aeronave</a></li>
    </ul>

    <div class="sidebar-footer">
        BlueWing Admin • <?= date("Y") ?>
    </div>
</aside>

<!-- MAIN -->
<main class="main">

<div class="top-bar">
    <div>
        <h1>Aeronave</h1>
        <p>Gestionarea flotei companiei</p>
    </div>
    <a href="aeronave.php?add=1" class="btn-primary">＋ Adaugă aeronava</a>
</div>

<?php if (isset($_GET["add"])): ?>
<div class="card">
<h2 style="margin-bottom:15px;">Adaugă aeronava</h2>

<form method="post">
<input type="hidden" name="action" value="add_avion">

<div class="form-grid">

    <div class="form-group">
        <label>Cod avion</label>
        <input type="text" name="CodAvion" required>
    </div>

    <div class="form-group">
        <label>Model</label>
        <input type="text" name="Model" required>
    </div>

    <div class="form-group">
        <label>Capacitate</label>
        <input type="number" name="Capacitate" min="1" required>
    </div>

    <div class="form-group">
        <label>An fabricare</label>
        <input type="number" name="AnFabricare" min="1950" max="<?= date("Y") ?>" required>
    </div>

    <div class="form-group">
        <label>Statut</label>
        <select name="Statut">
            <option value="Activ">Activ</option>
            <option value="Reparatie">Reparație</option>
            <option value="Retras">Retras</option>
        </select>
    </div>

</div>

<button class="btn-primary">Salvează</button>
<a href="aeronave.php" class="btn-reset">Anulează</a>
</form>
</div>
<?php endif; ?>


<?php if ($editAvion): ?>
<div class="card">
<h2 style="margin-bottom:15px;">Editează aeronava</h2>

<form method="post">
<input type="hidden" name="action" value="update_avion">
<input type="hidden" name="AvionID" value="<?= $editAvion['AvionID'] ?>">

<div class="form-grid">

    <div class="form-group">
        <label>Cod avion</label>
        <input type="text" name="CodAvion" value="<?= $editAvion['CodAvion'] ?>" required>
    </div>

    <div class="form-group">
        <label>Model</label>
        <input type="text" name="Model" value="<?= $editAvion['Model'] ?>" required>
    </div>

    <div class="form-group">
        <label>Capacitate</label>
        <input type="number" name="Capacitate" value="<?= $editAvion['Capacitate'] ?>" required>
    </div>

    <div class="form-group">
        <label>An fabricare</label>
        <input type="number" name="AnFabricare" value="<?= $editAvion['AnFabricare'] ?>" required>
    </div>

    <div class="form-group">
        <label>Statut</label>
        <select name="Statut">
            <option value="Activ"     <?= $editAvion['Statut']=="Activ"?"selected":"" ?>>Activ</option>
            <option value="Reparatie" <?= $editAvion['Statut']=="Reparatie"?"selected":"" ?>>Reparație</option>
            <option value="Retras"    <?= $editAvion['Statut']=="Retras"?"selected":"" ?>>Retras</option>
        </select>
    </div>

</div>

<button class="btn-primary">Salvează modificările</button>
<a href="aeronave.php" class="btn-reset">Anulează</a>
</form>
</div>
<?php endif; ?>


<!-- FILTRARE -->
<div class="card">
    <div class="filter-title">Filtrare</div>

    <form method="get">
        <div class="form-grid">

            <div class="form-group">
                <label>Cod avion:</label>
                <input type="text" name="cod" value="<?= $f_cod ?>">
            </div>

            <div class="form-group">
                <label>Model:</label>
                <input type="text" name="mod" value="<?= $f_mod ?>">
            </div>

            <div class="form-group">
                <label>Statut:</label>
                <select name="stat" class="sort-select">
                    <option value="">Toate</option>
                    <option value="Activ"     <?= $f_stat=="Activ"?"selected":"" ?>>Activ</option>
                    <option value="Reparatie" <?= $f_stat=="Reparatie"?"selected":"" ?>>Reparație</option>
                    <option value="Retras"    <?= $f_stat=="Retras"?"selected":"" ?>>Retras</option>
                </select>
            </div>

            <div class="form-group">
                <label>Capacitate (min):</label>
                <input type="number" name="cmin" value="<?= $f_cmin ?>">
            </div>

            <div class="form-group">
                <label>Capacitate (max):</label>
                <input type="number" name="cmax" value="<?= $f_cmax ?>">
            </div>

            <div class="form-group">
                <label>An fabricare (de la):</label>
                <input type="number" name="an1" value="<?= $f_an1 ?>">
            </div>

            <div class="form-group">
                <label>An fabricare (până la):</label>
                <input type="number" name="an2" value="<?= $f_an2 ?>">
            </div>

        </div>

        <div style="margin-top:20px; display:flex; align-items:center; gap:14px;">
            <label style="font-size:14px; color:#6b7280;">
                Sortare:
            </label>

            <select name="sort_by" class="sort-select">
                <option value="CodAvion"    <?= $sort_by=="CodAvion"?"selected":"" ?>>Cod avion</option>
                <option value="Model"       <?= $sort_by=="Model"?"selected":"" ?>>Model</option>
                <option value="Capacitate"  <?= $sort_by=="Capacitate"?"selected":"" ?>>Capacitate</option>
                <option value="AnFabricare" <?= $sort_by=="AnFabricare"?"selected":"" ?>>An fabricare</option>
            </select>

            <select name="sort_dir" class="sort-select">
                <option value="asc"  <?= $sort_dir=="asc"?"selected":"" ?>>↑ Crescător</option>
                <option value="desc" <?= $sort_dir=="desc"?"selected":"" ?>>↓ Descrescător</option>
            </select>

            <a href="aeronave.php" class="btn-reset">Reset</a>
            <button class="btn-primary">Aplică</button>
        </div>

    </form>
</div>

<!-- TABEL -->
<div class="card">
    <table>
        <thead>
            <tr>
                <th>Cod avion</th>
                <th>Model</th>
                <th>Capacitate</th>
                <th>An fabricare</th>
                <th>Statut</th>
                <th>Acțiuni</th>
            </tr>
        </thead>

        <tbody>
        <?php if ($lista && mysqli_num_rows($lista)>0): ?>
            <?php while($a = mysqli_fetch_assoc($lista)): ?>

                <?php
                    $badge = "b-activ";
                    if ($a["Statut"]=="Reparatie") $badge="b-folosit";
                    if ($a["Statut"]=="Retras")    $badge="b-anulat";
                ?>

                <tr>
                    <td><?= $a["CodAvion"] ?></td>
                    <td><?= $a["Model"] ?></td>
                    <td><?= $a["Capacitate"] ?></td>
                    <td><?= $a["AnFabricare"] ?></td>

                    <td><span class="badge <?= $badge ?>"><?= $a["Statut"] ?></span></td>

                    <td>
                        <a href="aeronave.php?edit=<?= $a['AvionID'] ?>" class="btn-small">Editează</a>
                        <a href="aeronave.php?delete=<?= $a['AvionID'] ?>"
                           onclick="return confirm('Ștergi aeronava?');"
                           class="btn-small btn-danger">Șterge</a>
                    </td>
                </tr>

            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="padding:17px; text-align:center; color:#999;">
                    Nu există aeronave conform filtrării.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- PAGINARE -->
<div style="margin-top: 20px; display:flex; gap:6px;">

<?php
// păstrăm filtrele în URL
$base_url = "aeronave.php?";
$params = $_GET;
unset($params["page"]);
$query_string = http_build_query($params);

if ($query_string !== "") $query_string .= "&";
?>

<?php if ($page > 1): ?>
    <a class="btn-small"
       href="aeronave.php?<?= $query_string ?>page=<?= $page-1 ?>">
       ← Înapoi
    </a>
<?php endif; ?>

<?php
// numere pagini
for ($i = 1; $i <= $total_pages; $i++):
    $active = ($i == $page) ? "background:#dbeafe;" : "";
?>
    <a class="btn-small"
       style="<?= $active ?>"
       href="aeronave.php?<?= $query_string ?>page=<?= $i ?>">
       <?= $i ?>
    </a>
<?php endfor; ?>

<?php if ($page < $total_pages): ?>
    <a class="btn-small"
       href="aeronave.php?<?= $query_string ?>page=<?= $page+1 ?>">
       Înainte →
    </a>
<?php endif; ?>

</div>


</main>

<?php __HALT_COMPILER(); ?>
