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

/* ==================== ESC ==================== */
function esc($c, $v) {
    return mysqli_real_escape_string($c, trim($v));
}

/* ===========================================================
   ===============      ADAUGĂ BILET       ====================
   =========================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["action"])
    && $_POST["action"] === "add_bilet") {

    $RezervareID = (int)($_POST["RezervareID"] ?? 0);
    $CodBilet    = esc($conn, $_POST["CodBilet"] ?? "");
    $Clasa       = esc($conn, $_POST["Clasa"] ?? "");
    $BagajMana   = (int)($_POST["BagajMana"] ?? 0);
    $BagajCala   = (int)($_POST["BagajCala"] ?? 0);
    $Statut      = esc($conn, $_POST["Statut"] ?? "Activ");
    $DataEmitere = esc($conn, $_POST["DataEmitere"] ?? "");

    /* ==================== VALIDĂRI ==================== */

    // Rezervare obligatorie
    if ($RezervareID <= 0) {
        echo "<script>alert('Selectează o rezervare validă!'); window.history.back();</script>";
        exit;
    }

    // Cod bilet – format BW000
    if (!preg_match('/^BW\d{3}$/', $CodBilet)) {
        echo "<script>alert('Cod bilet invalid! Format corect: BW000'); window.history.back();</script>";
        exit;
    }

    // Bagajele trebuie să fie >= 0 și realiste
    if ($BagajMana < 0 || $BagajMana > 200) {
        echo "<script>alert('Bagajul de mână trebuie să fie între 0 și 200 kg!'); window.history.back();</script>";
        exit;
    }

    if ($BagajCala < 0 || $BagajCala > 200) {
        echo "<script>alert('Bagajul de cală trebuie să fie între 0 și 200 kg!'); window.history.back();</script>";
        exit;
    }

    // Data emiterii validă
    if ($DataEmitere == "") {
        echo "<script>alert('Data emiterii este obligatorie!'); window.history.back();</script>";
        exit;
    }

    if ($DataEmitere > date("Y-m-d")) {
        echo "<script>alert('Data emiterii nu poate fi în viitor!'); window.history.back();</script>";
        exit;
    }

    /* prețul este COPIAT automat din Rezervari */
    $PretBilet = 0;
    if ($RezervareID > 0) {
        $qPret = mysqli_query(
            $conn,
            "SELECT Pret FROM Rezervari WHERE RezervareID = $RezervareID LIMIT 1"
        );
        if ($qPret && mysqli_num_rows($qPret) === 1) {
            $rowPret = mysqli_fetch_assoc($qPret);
            $PretBilet = (float)$rowPret["Pret"];
        }
    }

    if ($RezervareID > 0 && $CodBilet !== "" && $DataEmitere !== "") {
        $sqlAdd = "
            INSERT INTO Bilete
    (RezervareID, CodBilet, Clasa, BagajMana, BagajCala, Statut, DataEmitere)
VALUES
    ($RezervareID, '$CodBilet', '$Clasa', $BagajMana, $BagajCala, '$Statut', '$DataEmitere')";
        mysqli_query($conn, $sqlAdd);
    }

    header("Location: bilete.php");
    exit;
}

/* ===========================================================
   ===============     CITIRE PT EDITARE     =================
   =========================================================== */
$editBilet = null;
if (isset($_GET["edit"])) {
    $editID = (int)$_GET["edit"];
    if ($editID > 0) {
        $qEdit = mysqli_query(
            $conn,
            "SELECT * FROM Bilete WHERE BiletID = $editID LIMIT 1"
        );
        $editBilet = mysqli_fetch_assoc($qEdit);
    }
}

/* ===========================================================
   ===============       SALVARE EDITARE     =================
   =========================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["action"])
    && $_POST["action"] === "update_bilet") {

    $BiletID     = (int)($_POST["BiletID"] ?? 0);
    $RezervareID = (int)($_POST["RezervareID"] ?? 0);
    $CodBilet    = esc($conn, $_POST["CodBilet"] ?? "");
    $Clasa       = esc($conn, $_POST["Clasa"] ?? "");
    $BagajMana   = (int)($_POST["BagajMana"] ?? 0);
    $BagajCala   = (int)($_POST["BagajCala"] ?? 0);
    $Statut      = esc($conn, $_POST["Statut"] ?? "Activ");
    $DataEmitere = esc($conn, $_POST["DataEmitere"] ?? "");

     /* ==================== VALIDĂRI ==================== */

    if ($BiletID <= 0) {
        echo "<script>alert('ID bilet invalid!'); window.history.back();</script>";
        exit;
    }

    if ($RezervareID <= 0) {
        echo "<script>alert('Selectează rezervarea!'); window.history.back();</script>";
        exit;
    }

    if (!preg_match('/^BW\d{3}$/', $CodBilet)) {
        echo "<script>alert('Cod bilet invalid! Format corect: BW000'); window.history.back();</script>";
        exit;
    }

    if ($BagajMana < 0 || $BagajMana > 200) {
        echo "<script>alert('Bagajul de mână trebuie între 0 și 200 kg!'); window.history.back();</script>";
        exit;
    }

    if ($BagajCala < 0 || $BagajCala > 200) {
        echo "<script>alert('Bagajul de cală trebuie între 0 și 200 kg!'); window.history.back();</script>";
        exit;
    }

    if ($DataEmitere == "") {
        echo "<script>alert('Data emiterii este obligatorie!'); window.history.back();</script>";
        exit;
    }

    if ($DataEmitere > date("Y-m-d")) {
        echo "<script>alert('Data emiterii nu poate fi în viitor!'); window.history.back();</script>";
        exit;
    }

    /* La salvare, din nou prețul se sincronizează din Rezervari */
    $PretBilet = 0;
    if ($RezervareID > 0) {
        $qPret = mysqli_query(
            $conn,
            "SELECT Pret FROM Rezervari WHERE RezervareID = $RezervareID LIMIT 1"
        );
        if ($qPret && mysqli_num_rows($qPret) === 1) {
            $rowPret = mysqli_fetch_assoc($qPret);
            $PretBilet = (float)$rowPret["Pret"];
        }
    }

    if ($BiletID > 0 && $RezervareID > 0 && $CodBilet !== "" && $DataEmitere !== "") {
        $sqlUp = "
            UPDATE Bilete SET
                RezervareID = $RezervareID,
                CodBilet    = '$CodBilet',
                Clasa       = '$Clasa',
                BagajMana   = $BagajMana,
                BagajCala   = $BagajCala,
                Statut      = '$Statut',
                DataEmitere = '$DataEmitere',
                Pret        = '$PretBilet',
            WHERE BiletID = '$BiletID'
        ";
        mysqli_query($conn, $sqlUp);
    }

    header("Location: bilete.php");
    exit;
}

/*  ===============        ȘTERGERE BILET      ================= */
if (isset($_GET["delete"])) {
    $delID = (int)$_GET["delete"];
    if ($delID > 0) {
        mysqli_query($conn, "DELETE FROM Bilete WHERE BiletID = $delID");
    }
    header("Location: bilete.php");
    exit;
}

/* ===========================================================
   ===============          FILTRE + SORTARE  =================
   =========================================================== */

$f_cod   = esc($conn, $_GET["cod"]   ?? "");   // CodBilet
$f_pas   = esc($conn, $_GET["pas"]   ?? "");   // Nume/Prenume pasager
$f_clasa = esc($conn, $_GET["clasa"] ?? "");
$f_stat  = esc($conn, $_GET["stat"]  ?? "");
$f_bmmin = esc($conn, $_GET["bmmin"] ?? "");
$f_bmmax = esc($conn, $_GET["bmmax"] ?? "");
$f_bcmin = esc($conn, $_GET["bcmin"] ?? "");
$f_bcmax = esc($conn, $_GET["bcmax"] ?? "");
$f_pmin  = esc($conn, $_GET["pmin"]  ?? "");
$f_pmax  = esc($conn, $_GET["pmax"]  ?? "");
$f_d1    = esc($conn, $_GET["d1"]    ?? "");
$f_d2    = esc($conn, $_GET["d2"]    ?? "");

/* sortare – doar aceste coloane permit sortare din UI */
$allowedSort = [
    "Pasager"     => "pe.Nume, pe.Prenume",
    "CodBilet"    => "b.CodBilet",
    "Pret"        => "b.Pret",
    "DataEmitere" => "b.DataEmitere"
];

$sort_by  = $_GET["sort_by"]  ?? "DataEmitere";
$sort_dir = strtolower($_GET["sort_dir"] ?? "desc");
$sort_dir_sql = ($sort_dir === "asc") ? "ASC" : "DESC";

if (!isset($allowedSort[$sort_by])) {
    $sort_by = "DataEmitere";
}
$sort_sql = $allowedSort[$sort_by];

/* ===========================================================
   ===============          PAGINARE            ===============
   =========================================================== */

$per_page = 10; // bilete per pagină
$page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
$offset = ($page - 1) * $per_page;

/* ======== COUNT TOTAL DUPĂ FILTRE ======== */

$count_sql = "
SELECT COUNT(*) AS total
FROM Bilete b
JOIN Rezervari r ON r.RezervareID = b.RezervareID
JOIN Pasageri  pa ON pa.PasagerID  = r.PasagerID
JOIN Persoana  pe ON pe.PersoanaID = pa.PersoanaID
WHERE 1
";

/* aplicăm aceleași filtre ca în query-ul principal */

if ($f_cod !== "")   $count_sql .= " AND b.CodBilet LIKE '%$f_cod%'";
if ($f_pas !== "")   $count_sql .= " AND CONCAT(pe.Nume,' ',pe.Prenume) LIKE '%$f_pas%'";
if ($f_clasa !== "") $count_sql .= " AND b.Clasa = '$f_clasa'";
if ($f_stat !== "")  $count_sql .= " AND b.Statut = '$f_stat'";

if ($f_bmmin !== "") $count_sql .= " AND b.BagajMana >= ".(int)$f_bmmin;
if ($f_bmmax !== "") $count_sql .= " AND b.BagajMana <= ".(int)$f_bmmax;

if ($f_bcmin !== "") $count_sql .= " AND b.BagajCala >= ".(int)$f_bcmin;
if ($f_bcmax !== "") $count_sql .= " AND b.BagajCala <= ".(int)$f_bcmax;

if ($f_pmin !== "")  $count_sql .= " AND b.Pret >= ".(float)$f_pmin;
if ($f_pmax !== "")  $count_sql .= " AND b.Pret <= ".(float)$f_pmax;

if ($f_d1 !== "")    $count_sql .= " AND b.DataEmitere >= '$f_d1'";
if ($f_d2 !== "")    $count_sql .= " AND b.DataEmitere <= '$f_d2'";

$count_res = mysqli_query($conn, $count_sql);
$total_rows = mysqli_fetch_assoc($count_res)["total"];
$total_pages = ceil($total_rows / $per_page);


/* ===========================================================
   ===============      QUERY PRINCIPAL LISTĂ  ===============
   =========================================================== */

$sql = "
SELECT
    b.*,
    r.CodRezervare,
    r.Pret  AS PretRezervare,
    r.Moneda,
    pe.Nume,
    pe.Prenume,
    pe.Email
FROM Bilete b
JOIN Rezervari r  ON r.RezervareID = b.RezervareID
JOIN Pasageri  pa ON pa.PasagerID  = r.PasagerID
JOIN Persoana  pe ON pe.PersoanaID = pa.PersoanaID
WHERE 1
";

/* aplicăm filtrele */
if ($f_cod !== "")   $sql .= " AND b.CodBilet LIKE '%$f_cod%'";
if ($f_pas !== "")   $sql .= " AND CONCAT(pe.Nume,' ',pe.Prenume) LIKE '%$f_pas%'";
if ($f_clasa !== "") $sql .= " AND b.Clasa = '$f_clasa'";
if ($f_stat !== "")  $sql .= " AND b.Statut = '$f_stat'";

if ($f_bmmin !== "") $sql .= " AND b.BagajMana >= ".(int)$f_bmmin;
if ($f_bmmax !== "") $sql .= " AND b.BagajMana <= ".(int)$f_bmmax;

if ($f_bcmin !== "") $sql .= " AND b.BagajCala >= ".(int)$f_bcmin;
if ($f_bcmax !== "") $sql .= " AND b.BagajCala <= ".(int)$f_bcmax;

if ($f_pmin !== "")  $sql .= " AND b.Pret >= ".(float)$f_pmin;
if ($f_pmax !== "")  $sql .= " AND b.Pret <= ".(float)$f_pmax;

if ($f_d1 !== "")    $sql .= " AND b.DataEmitere >= '$f_d1'";
if ($f_d2 !== "")    $sql .= " AND b.DataEmitere <= '$f_d2'";

$sql .= " ORDER BY $sort_sql $sort_dir_sql LIMIT $offset, $per_page";


$listaBilete = mysqli_query($conn, $sql);

/* ===========================================================
   ===============      LISTĂ REZERVĂRI PT FORM ==============
   =========================================================== */

$listRez = mysqli_query(
    $conn,
    "SELECT 
        r.RezervareID,
        r.CodRezervare,
        z.CodZbor,
        pe.Nume,
        pe.Prenume
     FROM Rezervari r
     JOIN Pasageri pa ON pa.PasagerID  = r.PasagerID
     JOIN Persoana pe ON pe.PersoanaID = pa.PersoanaID
     JOIN Zboruri z   ON z.ZborID      = r.ZborID
     ORDER BY r.CodRezervare DESC"
);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>Bilete – BlueWing Airlines</title>

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

</style>
</head>

<body>

<!-- ================= SIDEBAR ================= -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-box">✈</div>
        <div>
            <div class="logo-title">BlueWing Airlines</div>
            <div class="logo-sub">Management system</div>
        </div>
    </div>

    <ul class="nav">
    <li class="nav-item">
        <a href="dashboard.php" class="nav-link">
            <span class="icon">🏠</span> Dashboard
        </a>
    </li>

    <li class="nav-item">
        <a href="zboruri.php" class="nav-link">
            <span class="icon">🛫</span> Zboruri
        </a>
    </li>

    <li class="nav-item">
        <a href="rezervari.php" class="nav-link">
            <span class="icon">📅</span> Rezervări
        </a>
    </li>

    <li class="nav-item">
        <a href="pasageri.php" class="nav-link">
            <span class="icon">👥</span> Pasageri
        </a>
    </li>

    <li class="nav-item">
        <a href="bilete.php" class="nav-link active">
            <span class="icon">🎫</span> Bilete
        </a>
    </li>

    <li class="nav-item">
        <a href="aeronave.php" class="nav-link">
            <span class="icon">✈️</span> Aeronave
        </a>
    </li>
</ul>


    <div class="sidebar-footer">
        BlueWing Admin • <?php echo date("Y"); ?>
    </div>
</aside>


<!-- ================= MAIN ================= -->
<main class="main">

<div class="top-bar">
    <div>
        <h1>Bilete</h1>
        <p>Gestionarea biletelor de îmbarcare</p>
    </div>

    <a href="bilete.php?add=1" class="btn-primary">＋ Adaugă bilet</a>
</div>
<!-- =============================== -->
<!--        FORMULAR – ADD           -->
<!-- =============================== -->

<?php if (isset($_GET["add"])): ?>
<div class="card">
    <h2 style="margin-bottom:15px;">Adaugă bilet</h2>

    <form method="post">
        <input type="hidden" name="action" value="add_bilet">

        <div class="form-grid">

            <!-- Rezervare -->
            <div class="form-group">
                <label>Rezervare</label>
                <select name="RezervareID" required>
                    <option value="">Selectează rezervarea...</option>

                    <?php while($rz = mysqli_fetch_assoc($listRez)): ?>
                        <option value="<?php echo $rz['RezervareID']; ?>">
                            <?php echo $rz['CodRezervare']." — ".$rz['Nume']." ".$rz['Prenume']." (".$rz['CodZbor'].")"; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Cod Bilet -->
            <div class="form-group">
                <label>Cod bilet</label>
                <input type="text" name="CodBilet" placeholder="ex: BW001" required>
            </div>

            <!-- Clasa -->
            <div class="form-group">
                <label>Clasa</label>
                <select name="Clasa" required>
                    <option value="Economy">Economy</option>
                    <option value="Business">Business</option>
                    <option value="First">First</option>
                </select>
            </div>

            <!-- Bagaj Mana -->
            <div class="form-group">
                <label>Bagaj mână (kg)</label>
                <input type="number" name="BagajMana" min="0" value="0" required>
            </div>

            <!-- Bagaj Cala -->
            <div class="form-group">
                <label>Bagaj cală (kg)</label>
                <input type="number" name="BagajCala" min="0" value="0" required>
            </div>

            <!-- Statut -->
            <div class="form-group">
                <label>Statut</label>
                <select name="Statut" required>
                    <option value="Activ">Activ</option>
                    <option value="Anulat">Anulat</option>
                    <option value="Folosit">Folosit</option>
                </select>
            </div>

            <!-- Data emitere -->
            <div class="form-group">
                <label>Data emiterii</label>
                <input type="date" name="DataEmitere" required>
            </div>

        </div>

        <div class="form-actions" style="margin-top:10px;">
            <button class="btn-primary">Salvează bilet</button>
            <a href="bilete.php" class="btn-reset">Anulează</a>
        </div>

    </form>
</div>
<?php endif; ?>



<!-- =============================== -->
<!--        FORMULAR – EDITARE       -->
<!-- =============================== -->

<?php if ($editBilet): ?>
<div class="card">
    <h2 style="margin-bottom:15px;">Editează bilet</h2>

    <form method="post">
        <input type="hidden" name="action" value="update_bilet">
        <input type="hidden" name="BiletID" value="<?php echo $editBilet['BiletID']; ?>">

        <div class="form-grid">

            <!-- Rezervare -->
            <div class="form-group">
                <label>Rezervare</label>
                <select name="RezervareID" required>
                    <?php
                        mysqli_data_seek($listRez, 0);
                        while($rz = mysqli_fetch_assoc($listRez)):
                    ?>
                        <option value="<?php echo $rz['RezervareID']; ?>"
                            <?php if ($editBilet['RezervareID'] == $rz['RezervareID']) echo "selected"; ?>>
                            <?php echo $rz['CodRezervare']." — ".$rz['Nume']." ".$rz['Prenume']." (".$rz['CodZbor'].")"; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Cod bilet -->
            <div class="form-group">
                <label>Cod bilet</label>
                <input type="text" name="CodBilet" value="<?php echo $editBilet['CodBilet']; ?>" required>
            </div>

            <!-- Clasa -->
            <div class="form-group">
                <label>Clasa</label>
                <select name="Clasa" required>
                    <option value="Economy"  <?php if($editBilet['Clasa']=="Economy") echo "selected"; ?>>Economy</option>
                    <option value="Business" <?php if($editBilet['Clasa']=="Business") echo "selected"; ?>>Business</option>
                    <option value="First"    <?php if($editBilet['Clasa']=="First") echo "selected"; ?>>First</option>
                </select>
            </div>

            <!-- Bagaj mână -->
            <div class="form-group">
                <label>Bagaj mână (kg)</label>
                <input type="number" name="BagajMana"
                       value="<?php echo $editBilet['BagajMana']; ?>" min="0" required>
            </div>

            <!-- Bagaj cală -->
            <div class="form-group">
                <label>Bagaj cală (kg)</label>
                <input type="number" name="BagajCala"
                       value="<?php echo $editBilet['BagajCala']; ?>" min="0" required>
            </div>

            <!-- Statut -->
            <div class="form-group">
                <label>Statut</label>
                <select name="Statut" required>
                    <option value="Activ"   <?php if($editBilet['Statut']=="Activ") echo "selected"; ?>>Activ</option>
                    <option value="Anulat"  <?php if($editBilet['Statut']=="Anulat") echo "selected"; ?>>Anulat</option>
                    <option value="Folosit" <?php if($editBilet['Statut']=="Folosit") echo "selected"; ?>>Folosit</option>
                </select>
            </div>

            <!-- Data emitere -->
            <div class="form-group">
                <label>Data emiterii</label>
                <input type="date" name="DataEmitere"
                       value="<?php echo $editBilet['DataEmitere']; ?>" required>
            </div>

        </div>

        <div class="form-actions" style="margin-top:10px;">
            <button class="btn-primary">Salvează modificările</button>
            <a href="bilete.php" class="btn-reset">Anulează</a>
        </div>

    </form>
</div>
<?php endif; ?>
<!-- =============================== -->
<!--            FILTRARE             -->
<!-- =============================== -->

<div class="card">
    <div class="filter-title">Filtrare</div>

    <form method="get">
        <div class="filter-grid">

            <!-- Cod bilet -->
            <div class="form-group">
                <label>Cod bilet:</label>
                <input type="text" name="cod" value="<?php echo $f_cod; ?>">
            </div>

            <!-- Pasager -->
            <div class="form-group">
                <label>Pasager:</label>
                <input type="text" name="pas" value="<?php echo $f_pas; ?>">
            </div>

            <!-- Statut -->
            <div class="form-group">
                <label>Statut:</label>
                <select name="stat" class="sort-select">
                    <option value="">Toate</option>
                    <option value="Activ"   <?php if($f_stat=="Activ") echo "selected"; ?>>Activ</option>
                    <option value="Anulat"  <?php if($f_stat=="Anulat") echo "selected"; ?>>Anulat</option>
                    <option value="Folosit" <?php if($f_stat=="Folosit") echo "selected"; ?>>Folosit</option>
                </select>
            </div>

            <!-- Data emitere (de la) -->
            <div class="form-group">
                <label>Data emitere (de la):</label>
                <input type="date" name="d1" value="<?php echo $f_d1; ?>">
            </div>

            <!-- Data emitere (până la) -->
            <div class="form-group">
                <label>Data emitere (până la):</label>
                <input type="date" name="d2" value="<?php echo $f_d2; ?>">
            </div>

        </div>

        <!-- SORTARE -->
        <div style="margin-top:20px; display:flex; align-items:center; gap:14px;">
            <label style="font-size:14px; color:#6b7280;">Sortare:</label>

            <select name="sort_by" class="sort-select">
                <option value="CodBilet" <?php if($sort_by=="CodBilet") echo "selected"; ?>>Cod bilet</option>
                <option value="Pasager"  <?php if($sort_by=="Pasager") echo "selected"; ?>>Pasager</option>
                <option value="Pret"     <?php if($sort_by=="Pret") echo "selected"; ?>>Preț</option>
                <option value="DataEmitere" <?php if($sort_by=="DataEmitere") echo "selected"; ?>>Data emitere</option>
            </select>

            <select name="sort_dir" class="sort-select">
                <option value="asc"  <?php if($sort_dir=="asc") echo "selected"; ?>>↑ Crescător</option>
                <option value="desc" <?php if($sort_dir=="desc") echo "selected"; ?>>↓ Descrescător</option>
            </select>

            <a href="bilete.php" class="btn-reset">Reset</a>
            <button class="btn-primary">Aplică</button>
        </div>

    </form>
</div>
                            



<!-- =============================== -->
<!--            TABEL BILETE         -->
<!-- =============================== -->

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Pasager</th>
                <th>Cod bilet</th>
                <th>Clasa</th>
                <th>Bagaj mână</th>
                <th>Bagaj cală</th>
                <th>Preț</th>
                <th>Statut</th>
                <th>Data emitere</th>
                <th>Acțiuni</th>
            </tr>
        </thead>

        <tbody>
        <?php if ($listaBilete && mysqli_num_rows($listaBilete) > 0): ?>
    <?php while($b = mysqli_fetch_assoc($listaBilete)): ?>


                <?php
                    // Badge color
                    $badge = "";
                    if ($b["Statut"] == "Activ")   $badge = "b-confirmata";
                    if ($b["Statut"] == "Anulat")  $badge = "b-anulata";
                    if ($b["Statut"] == "Folosit") $badge = "b-checkin";

                    $dataF = date("d.m.Y", strtotime($b["DataEmitere"]));
                ?>

                <tr>
                    <td>
                        <?php echo $b["Nume"]." ".$b["Prenume"]; ?>
                    </td>


                    <td><?php echo $b["CodBilet"]; ?></td>
                    <td><?php echo $b["Clasa"]; ?></td>
                    <td><?php echo $b["BagajMana"]; ?> kg</td>
                    <td><?php echo $b["BagajCala"]; ?> kg</td>

                    <td><?php echo $b["PretRezervare"]; ?> EUR</td>

                    <td>
                        <span class="badge <?php echo $badge; ?>">
                            <?php echo $b["Statut"]; ?>
                        </span>
                    </td>

                    <td><?php echo $dataF; ?></td>

                    <td>
                        <a href="bilete.php?edit=<?php echo $b['BiletID']; ?>" class="btn-small">Editează</a>
                        <a href="bilete.php?delete=<?php echo $b['BiletID']; ?>"
                           onclick="return confirm('Ștergi biletul?');"
                           class="btn-small btn-danger">Șterge</a>
                    </td>
                </tr>

            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="9" style="padding:17px; text-align:center; color:#999;">
                    Nu există bilete conform filtrării.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ====================== PAGINARE ====================== -->
<?php if ($total_pages > 1): ?>

    <div style="padding:20px; display:flex; justify-content:center; gap:8px;">

        <?php
        // construim URL-ul cu filtre dar fără page
        $query = $_GET;
        unset($query["page"]);
        $base_url = "bilete.php?" . http_build_query($query) . "&page=";
        ?>

        <!-- PREV -->
        <?php if ($page > 1): ?>
            <a class="btn-small" href="<?= $base_url . ($page - 1) ?>">« Prev</a>
        <?php else: ?>
            <span class="btn-small" style="opacity:0.4; pointer-events:none;">« Prev</span>
        <?php endif; ?>

        <!-- NUMERE -->
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="btn-small" style="background:#2563eb;color:white;border-color:#2563eb;">
                    <?= $i ?>
                </span>
            <?php else: ?>
                <a class="btn-small" href="<?= $base_url . $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <!-- NEXT -->
        <?php if ($page < $total_pages): ?>
            <a class="btn-small" href="<?= $base_url . ($page + 1) ?>">Next »</a>
        <?php else: ?>
            <span class="btn-small" style="opacity:0.4; pointer-events:none;">Next »</span>
        <?php endif; ?>

    </div>

<?php endif; ?>


</main>

<?php mysqli_close($conn); ?>
</body>
</html>
