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
   ===============    ADAUGARE REZERVARE     =================
   =========================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "add") {

    $CodRez = esc($conn, $_POST["CodRezervare"]);
    $PasagerID = (int)$_POST["PasagerID"];
    $ZborID = (int)$_POST["ZborID"];
    $Pret = (float)$_POST["Pret"];
    $Moneda = esc($conn, $_POST["Moneda"]);
    $Statut = esc($conn, $_POST["Statut"]);
    $Obs = esc($conn, $_POST["Observatii"]);
    $NrLoc = esc($conn, $_POST["NrLoc"]);
    $DataRez = esc($conn, $_POST["DataRezervare"]);

    /* ==================== VALIDĂRI (ADD REZERVARE) ==================== */

/* CodRezervare: R + 3 cifre */
if (!preg_match('/^R[0-9]{3}$/', $CodRez)) {
    echo "<script>alert('Codul rezervării trebuie să fie în format R000 (ex: R123).'); window.history.back();</script>";
    exit;
}

/* Unicitate CodRezervare */
$check = mysqli_query($conn, "SELECT CodRezervare FROM Rezervari WHERE CodRezervare='$CodRez'");
if (mysqli_num_rows($check) > 0) {
    echo "<script>alert('Codul de rezervare $CodRez există deja. Alege alt cod.'); window.history.back();</script>";
    exit;
}

/* Pret: cifre + 2 zecimale */
if (!preg_match('/^[0-9]+\.[0-9]{2}$/', $_POST["Pret"])) {
    echo "<script>alert('Prețul trebuie să fie un număr cu EXACT două cifre după virgulă (ex: 150.00).'); window.history.back();</script>";
    exit;
}

/* Moneda: 3 litere mari */
if (!preg_match('/^[A-Z]{3}$/', $Moneda)) {
    echo "<script>alert('Moneda trebuie să conțină EXACT 3 litere majuscule (ex: EUR, USD, MDL).'); window.history.back();</script>";
    exit;
}

/* NrLoc: număr + literă (ex: 12A) */
if (!preg_match('/^[0-9]+[A-Za-z]$/', $NrLoc)) {
    echo "<script>alert('Numărul locului trebuie să fie de forma 12A (număr + literă).'); window.history.back();</script>";
    exit;
}


    mysqli_query($conn, "
        INSERT INTO Rezervari
        (CodRezervare, PasagerID, ZborID, Pret, Moneda, Statut, Observatii, NrLoc, DataRezervare)
        VALUES
        ('$CodRez', $PasagerID, $ZborID, $Pret, '$Moneda', '$Statut', '$Obs', '$NrLoc', '$DataRez')
    ");

    header("Location: rezervari.php");
    exit;
}

/* ===========================================================
   ===============    CITIRE PT EDITARE       =================
   =========================================================== */
$editData = null;
if (isset($_GET["edit"])) {
    $idEdit = (int)$_GET["edit"];
    $resE = mysqli_query($conn, "
        SELECT * FROM Rezervari WHERE RezervareID = $idEdit LIMIT 1
    ");
    $editData = mysqli_fetch_assoc($resE);
}

/* ===========================================================
   ===============    SALVARE EDITARE         =================
   =========================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update") {

    $idEdit = (int)$_POST["RezervareID"];
    $CodRez = esc($conn, $_POST["CodRezervare"]);
    $PasagerID = (int)$_POST["PasagerID"];
    $ZborID = (int)$_POST["ZborID"];
    $Pret = (float)$_POST["Pret"];
    $Moneda = esc($conn, $_POST["Moneda"]);
    $Statut = esc($conn, $_POST["Statut"]);
    $Obs = esc($conn, $_POST["Observatii"]);
    $NrLoc = esc($conn, $_POST["NrLoc"]);
    $DataRez = esc($conn, $_POST["DataRezervare"]);

    /* ==================== VALIDĂRI (EDIT REZERVARE) ==================== */

/* CodRezervare: format R000 */
if (!preg_match('/^R[0-9]{3}$/', $CodRez)) {
    echo "<script>alert('Codul rezervării trebuie să fie în format R000 (ex: R123).'); window.history.back();</script>";
    exit;
}

/* Unicitate la edit – se exclude rezervarea curentă */
$check = mysqli_query($conn, "SELECT CodRezervare FROM Rezervari 
                              WHERE CodRezervare='$CodRez' AND RezervareID!=$idEdit");
if (mysqli_num_rows($check) > 0) {
    echo "<script>alert('Codul de rezervare $CodRez aparține deja altei rezervări.'); window.history.back();</script>";
    exit;
}

/* Pret: două zecimale obligatoriu */
if (!preg_match('/^[0-9]+\.[0-9]{2}$/', $_POST["Pret"])) {
    echo "<script>alert('Prețul trebuie în format 100.00 (două zecimale obligatoriu).'); window.history.back();</script>";
    exit;
}

/* Moneda: 3 litere majuscule */
if (!preg_match('/^[A-Z]{3}$/', $Moneda)) {
    echo "<script>alert('Moneda trebuie să fie formată din 3 litere mari (ex: EUR).'); window.history.back();</script>";
    exit;
}

/* NrLoc: număr + literă */
if (!preg_match('/^[0-9]+[A-Za-z]$/', $NrLoc)) {
    echo "<script>alert('Numărul locului trebuie să fie de forma 12A (număr + literă).'); window.history.back();</script>";
    exit;
}


    mysqli_query($conn, "
        UPDATE Rezervari SET
            CodRezervare='$CodRez',
            PasagerID=$PasagerID,
            ZborID=$ZborID,
            Pret=$Pret,
            Moneda='$Moneda',
            Statut='$Statut',
            Observatii='$Obs',
            NrLoc='$NrLoc',
            DataRezervare='$DataRez'
        WHERE RezervareID=$idEdit
    ");

    header("Location: rezervari.php");
    exit;
}

/* ===========================================================
   ===============    ȘTERGERE REZERVARE      =================
   =========================================================== */
if (isset($_GET["delete"])) {
    $idDel = (int)$_GET["delete"];
    mysqli_query($conn, "DELETE FROM Rezervari WHERE RezervareID=$idDel");
    header("Location: rezervari.php");
    exit;
}

/* ===========================================================
   ===============    FILTRE + SORTARE         ===============
   =========================================================== */

$f_cod = esc($conn, $_GET["cod"] ?? "");
$f_pas = esc($conn, $_GET["pas"] ?? "");
$f_orig = esc($conn, $_GET["orig"] ?? "");
$f_dest = esc($conn, $_GET["dest"] ?? "");
$f_pmin = esc($conn, $_GET["pmin"] ?? "");
$f_pmax = esc($conn, $_GET["pmax"] ?? "");
$f_textpm = esc($conn, $_GET["textpm"] ?? "");
$f_moneda = esc($conn, $_GET["moneda"] ?? "");
$f_statut = esc($conn, $_GET["statut"] ?? "");
$f_data1 = esc($conn, $_GET["data1"] ?? "");
$f_data2 = esc($conn, $_GET["data2"] ?? "");

/* sortare */
$allowedSort = [
    "CodRezervare" => "r.CodRezervare",
    "Pasager" => "pe.Nume",
    "Pret" => "r.Pret",
    "DataRezervare" => "r.DataRezervare"
];

$sort_by = $_GET["sort_by"] ?? "DataRezervare";
$sort_dir = strtolower($_GET["sort_dir"] ?? "desc") === "asc" ? "ASC" : "DESC";

if (!isset($allowedSort[$sort_by])) {
    $sort_by = "DataRezervare";
}

$sort_sql = $allowedSort[$sort_by];

/* ===========================================================
   ===============          PAGINARE            ===============
   =========================================================== */

$per_page = 10; // câte rezervări pe pagină
$page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
$offset = ($page - 1) * $per_page;

/* Query pentru a număra câte rezultate există după filtrare */

$count_sql = "
SELECT COUNT(*) AS total
FROM Rezervari r
JOIN Pasageri pa ON pa.PasagerID = r.PasagerID
JOIN Persoana pe ON pe.PersoanaID = pa.PersoanaID
JOIN Zboruri z ON z.ZborID = r.ZborID
WHERE 1
";

/* Aplicați filtrele și pentru COUNT exact la fel */

if ($f_cod !== "")       $count_sql .= " AND r.CodRezervare LIKE '%$f_cod%'";
if ($f_pas !== "")       $count_sql .= " AND CONCAT(pe.Nume,' ',pe.Prenume) LIKE '%$f_pas%'";
if ($f_orig !== "")      $count_sql .= " AND z.Origine LIKE '%$f_orig%'";
if ($f_dest !== "")      $count_sql .= " AND z.Destinatie LIKE '%$f_dest%'";
if ($f_pmin !== "")      $count_sql .= " AND r.Pret >= ".(float)$f_pmin;
if ($f_pmax !== "")      $count_sql .= " AND r.Pret <= ".(float)$f_pmax;
if ($f_textpm !== "")    $count_sql .= " AND CONCAT(r.Pret,' ',r.Moneda) LIKE '%$f_textpm%'";
if ($f_moneda !== "")    $count_sql .= " AND r.Moneda LIKE '%$f_moneda%'";
if ($f_statut !== "")    $count_sql .= " AND r.Statut = '$f_statut'";
if ($f_data1 !== "")     $count_sql .= " AND DATE(r.DataRezervare) >= '$f_data1'";
if ($f_data2 !== "")     $count_sql .= " AND DATE(r.DataRezervare) <= '$f_data2'";

$count_res = mysqli_query($conn, $count_sql);
$total_rows = mysqli_fetch_assoc($count_res)["total"];
$total_pages = ceil($total_rows / $per_page);


/* ===========================================================
   ===============    QUERY PRINCIPAL          ===============
   =========================================================== */

$sql = "
SELECT 
    r.*,
    pe.Nume,
    pe.Prenume,
    pe.Email,
    z.CodZbor,
    z.Origine,
    z.Destinatie
FROM Rezervari r
JOIN Pasageri pa ON pa.PasagerID = r.PasagerID
JOIN Persoana pe ON pe.PersoanaID = pa.PersoanaID
JOIN Zboruri z ON z.ZborID = r.ZborID
WHERE 1
";
/* aplicăm filtrele */
if ($f_cod !== "")       $sql .= " AND r.CodRezervare LIKE '%$f_cod%'";
if ($f_pas !== "")       $sql .= " AND CONCAT(pe.Nume,' ',pe.Prenume) LIKE '%$f_pas%'";
if ($f_orig !== "")      $sql .= " AND z.Origine LIKE '%$f_orig%'";
if ($f_dest !== "")      $sql .= " AND z.Destinatie LIKE '%$f_dest%'";
if ($f_pmin !== "")      $sql .= " AND r.Pret >= ".(float)$f_pmin;
if ($f_pmax !== "")      $sql .= " AND r.Pret <= ".(float)$f_pmax;
if ($f_textpm !== "")    $sql .= " AND CONCAT(r.Pret,' ',r.Moneda) LIKE '%$f_textpm%'";
if ($f_moneda !== "")    $sql .= " AND r.Moneda LIKE '%$f_moneda%'";
if ($f_statut !== "")    $sql .= " AND r.Statut = '$f_statut'";
if ($f_data1 !== "")     $sql .= " AND DATE(r.DataRezervare) >= '$f_data1'";
if ($f_data2 !== "")     $sql .= " AND DATE(r.DataRezervare) <= '$f_data2'";

$sql .= " ORDER BY $sort_sql $sort_dir LIMIT $offset, $per_page";


$rez = mysqli_query($conn, $sql);

/* ==================== LISTE ZBORURI & PASAGERI ==================== */

$listZ = mysqli_query($conn, "
    SELECT ZborID, CodZbor, Origine, Destinatie
    FROM Zboruri
    ORDER BY CodZbor
");

$listP = mysqli_query($conn, "
    SELECT 
        pa.PasagerID,
        pe.Nume,
        pe.Prenume,
        pe.Email
    FROM Pasageri pa
    JOIN Persoana pe ON pe.PersoanaID = pa.PersoanaID
    ORDER BY pe.Nume, pe.Prenume
");
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>Rezervări – BlueWing Airlines</title>

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

    /* ===== SIDEBAR ===== */
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

    .sidebar-footer {
        margin-top: auto;
        font-size: 11px;
        color: #9ca3af;
        padding-top: 20px;
    }

    /* ===== MAIN ===== */
    .main {
        flex: 1;
        padding: 24px 32px;
    }

    .top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .top-bar h1 {
        font-size: 22px;
        font-weight: 700;
        color: #111827;
    }

    .top-bar p {
        font-size: 13px;
        color: #6b7280;
    }

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

    /* ===== CARD ===== */
    .card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        margin-bottom: 20px;
    }

    /* ===== FORM GRID ===== */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
        margin-bottom: 10px;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-size: 13px;
        color: #6b7280;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 9px 12px;
        font-size: 14px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
    }

    textarea {
        resize: vertical;
        min-height: 60px;
    }

    .form-actions {
        margin-top: 12px;
        display: flex;
        gap: 10px;
    }

    .btn-save {
        background: #10b981;
        color: #fff;
        border: none;
        padding: 8px 18px;
        border-radius: 10px;
        font-size: 14px;
        cursor: pointer;
    }

    .btn-save:hover {
        background: #059669;
    }

    .btn-cancel {
        border: none;
        background: transparent;
        color: #6b7280;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
    }
    /* ===== FILTRARE ===== */
    .filter-title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 16px;
        color: #111827;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 10px;
    }

    .form-group input,
    .form-group select {
        border-radius: 999px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
    }

    /* ===== SORTARE (capsule style) ===== */
    .sort-select {
        appearance: none;
        padding: 8px 16px;
        border-radius: 999px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        font-size: 14px;
        cursor: pointer;

        background-image:
            linear-gradient(45deg, transparent 50%, #6b7280 50%),
            linear-gradient(135deg, #6b7280 50%, transparent 50%),
            linear-gradient(to right, #f9fafb, #f9fafb);
        background-position:
            calc(100% - 20px) calc(50% - 3px),
            calc(100% - 15px) calc(50% - 3px),
            calc(100% - 2.5em) 0.3em;
        background-size:
            5px 5px,
            5px 5px,
            1px 1.5em;
        background-repeat: no-repeat;
    }

    .sort-select:hover { background: #f3f4f6; }

    .btn-reset {
        border-radius: 999px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        padding: 7px 14px;
        font-size: 13px;
        cursor: pointer;
        color: #374151;
        text-decoration: none;
    }

    .btn-reset:hover { background:#e5e7eb; }

    /* ===== TABEL ===== */
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    thead tr {
        background: #f9fafb;
    }

    th {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
    }

    td {
        padding: 12px 16px;
        border-bottom: 1px solid #f3f4f6;
        color: #111827;
    }

    tbody tr:hover {
        background: #f9fafb;
    }

    .td-secondary {
        font-size: 11px;
        color: #9ca3af;
    }

    /* BADGE-uri pentru statut */
    .badge {
        padding: 3px 8px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }

    .b-confirmata { background:#dcfce7; color:#166534; }
    .b-anulata    { background:#fee2e2; color:#b91c1c; }
    .b-checkin    { background:#dbeafe; color:#1d4ed8; }
    .b-asteptare  { background:#fef3c7; color:#b45309; }

    /* ACTION BUTTONS */
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
        <a href="rezervari.php" class="nav-link active">
            <span class="icon">📅</span> Rezervări
        </a>
    </li>

    <li class="nav-item">
        <a href="pasageri.php" class="nav-link">
            <span class="icon">👥</span> Pasageri
        </a>
    </li>

    <!-- Rapoarte → Bilete -->
    <li class="nav-item">
        <a href="bilete.php" class="nav-link">
            <span class="icon">🎫</span> Bilete
        </a>
    </li>

    <!-- Setări → Aeronave -->
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

<!-- MAIN -->
<main class="main">

<div class="top-bar">
    <div>
        <h1>Rezervări</h1>
        <p>Gestionează rezervările pasagerilor</p>
    </div>
    <a href="rezervari.php?add=1" class="btn-primary">＋ Adaugă rezervare</a>
</div>

<!-- =============================== -->
<!--          FORMULAR ADD           -->
<!-- =============================== -->

<?php if (isset($_GET["add"])): ?>
<div class="card">
    <h2 style="margin-bottom:15px;">Adaugă rezervare</h2>

    <form method="post">
        <input type="hidden" name="action" value="add">

        <div class="form-grid">

            <div class="form-group">
                <label>Cod rezervare</label>
                <input type="text" name="CodRezervare" required>
            </div>

            <div class="form-group">
                <label>Pasager</label>
                <select name="PasagerID" required>
                    <option value="">Selectează...</option>
                    <?php while($p = mysqli_fetch_assoc($listP)): ?>
                        <option value="<?php echo $p['PasagerID']; ?>">
                            <?php echo $p['Nume']." ".$p['Prenume']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Zbor</label>
                <select name="ZborID" required>
                    <option value="">Selectează...</option>
                    <?php while($z = mysqli_fetch_assoc($listZ)): ?>
                        <option value="<?php echo $z['ZborID']; ?>">
                            <?php echo $z['CodZbor']." — ".$z['Origine']." → ".$z['Destinatie']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Preț</label>
                <input type="number" step="0.01" name="Pret" required>
            </div>

            <div class="form-group">
                <label>Monedă</label>
                <input type="text" name="Moneda" placeholder="ex: EUR" required>
            </div>

            <div class="form-group">
                <label>Statut</label>
                <select name="Statut">
                    <option value="Confirmata">Confirmată</option>
                    <option value="Anulata">Anulată</option>
                    <option value="Check-in">Check-in</option>
                    <option value="In asteptare">În așteptare</option>
                </select>
            </div>

            <div class="form-group">
                <label>Număr loc</label>
                <input type="text" name="NrLoc" required>
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label>Observații</label>
                <textarea name="Observatii"></textarea>
            </div>

            <div class="form-group">
                <label>Data rezervării</label>
                <input type="datetime-local" name="DataRezervare" required>
            </div>

        </div>

        <div class="form-actions">
            <button class="btn-save">Salvează</button>
            <a href="rezervari.php" class="btn-cancel">Anulează</a>
        </div>

    </form>
</div>
<?php endif; ?>

<!-- =============================== -->
<!--        FORMULAR EDITARE         -->
<!-- =============================== -->

<?php if ($editData): ?>
<div class="card">
    <h2 style="margin-bottom:15px;">Editează rezervare</h2>

    <form method="post">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="RezervareID" value="<?php echo $editData['RezervareID']; ?>">

        <div class="form-grid">

            <div class="form-group">
                <label>Cod rezervare</label>
                <input type="text" name="CodRezervare" value="<?php echo $editData['CodRezervare']; ?>" required>
            </div>

            <div class="form-group">
                <label>Pasager</label>
                <select name="PasagerID">
                    <?php mysqli_data_seek($listP, 0);
                    while($p = mysqli_fetch_assoc($listP)): ?>
                        <option value="<?php echo $p['PasagerID']; ?>"
                            <?php if ($editData['PasagerID']==$p['PasagerID']) echo "selected"; ?>>
                            <?php echo $p['Nume']." ".$p['Prenume']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Zbor</label>
                <select name="ZborID">
                    <?php mysqli_data_seek($listZ, 0);
                    while($z = mysqli_fetch_assoc($listZ)): ?>
                        <option value="<?php echo $z['ZborID']; ?>"
                            <?php if ($editData['ZborID']==$z['ZborID']) echo "selected"; ?>>
                            <?php echo $z['CodZbor']." — ".$z['Origine']." → ".$z['Destinatie']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Preț</label>
                <input type="number" step="0.01" name="Pret" value="<?php echo $editData['Pret']; ?>" required>
            </div>

            <div class="form-group">
                <label>Monedă</label>
                <input type="text" name="Moneda" value="<?php echo $editData['Moneda']; ?>" required>
            </div>

            <div class="form-group">
                <label>Statut</label>
                <select name="Statut">
                    <option value="Confirmata" <?php if($editData['Statut']=="Confirmata") echo "selected"; ?>>Confirmată</option>
                    <option value="Anulata" <?php if($editData['Statut']=="Anulata") echo "selected"; ?>>Anulată</option>
                    <option value="Check-in" <?php if($editData['Statut']=="Check-in") echo "selected"; ?>>Check-in</option>
                    <option value="In asteptare" <?php if($editData['Statut']=="In asteptare") echo "selected"; ?>>În așteptare</option>
                </select>
            </div>

            <div class="form-group">
                <label>Număr loc</label>
                <input type="text" name="NrLoc" value="<?php echo $editData['NrLoc']; ?>" required>
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label>Observații</label>
                <textarea name="Observatii"><?php echo $editData['Observatii']; ?></textarea>
            </div>

            <div class="form-group">
                <label>Data rezervării</label>
                <input type="datetime-local" name="DataRezervare"
                       value="<?php echo str_replace(' ', 'T', $editData['DataRezervare']); ?>" required>
            </div>

        </div>

        <div class="form-actions">
            <button class="btn-save">Salvează modificările</button>
            <a href="rezervari.php" class="btn-cancel">Anulează</a>
        </div>

    </form>
</div>
<?php endif; ?>
<!-- =============================== -->
<!--             FILTRARE            -->
<!-- =============================== -->

<div class="card">

    <div class="filter-title">Filtrare</div>

    <form method="get">

        <div class="filter-grid">

            <div class="form-group">
                <label>Cod rezervare:</label>
                <input type="text" name="cod" value="<?php echo $f_cod; ?>">
            </div>

            <div class="form-group">
                <label>Pasager:</label>
                <input type="text" name="pas" value="<?php echo $f_pas; ?>">
            </div>

            <div class="form-group">
                <label>Origine:</label>
                <input type="text" name="orig" value="<?php echo $f_orig; ?>">
            </div>

            <div class="form-group">
                <label>Destinație:</label>
                <input type="text" name="dest" value="<?php echo $f_dest; ?>">
            </div>

            <div class="form-group">
                <label>Preț minim:</label>
                <input type="number" name="pmin" step="0.01" value="<?php echo $f_pmin; ?>">
            </div>

            <div class="form-group">
                <label>Preț maxim:</label>
                <input type="number" name="pmax" step="0.01" value="<?php echo $f_pmax; ?>">
            </div>

            <div class="form-group">
                <label>Preț + monedă:</label>
                <input type="text" name="textpm" placeholder="ex: 150 EUR" value="<?php echo $f_textpm; ?>">
            </div>

            <div class="form-group">
                <label>Monedă:</label>
                <input type="text" name="moneda" value="<?php echo $f_moneda; ?>">
            </div>

            <div class="form-group">
                <label>Statut:</label>
                <select name="statut">
                    <option value="">Toate</option>
                    <option value="Confirmata"   <?php if ($f_statut=="Confirmata") echo "selected"; ?>>Confirmată</option>
                    <option value="Anulata"      <?php if ($f_statut=="Anulata") echo "selected"; ?>>Anulată</option>
                    <option value="Check-in"     <?php if ($f_statut=="Check-in") echo "selected"; ?>>Check-in</option>
                    <option value="In asteptare" <?php if ($f_statut=="In asteptare") echo "selected"; ?>>În așteptare</option>
                </select>
            </div>

            <div class="form-group">
                <label>Data (de la):</label>
                <input type="date" name="data1" value="<?php echo $f_data1; ?>">
            </div>

            <div class="form-group">
                <label>Data (până la):</label>
                <input type="date" name="data2" value="<?php echo $f_data2; ?>">
            </div>

        </div>

        <!-- ======================= -->
        <!--     SORTARE DESIGN      -->
        <!-- ======================= -->
        <div style="margin-top:20px; display:flex; align-items:center; gap:14px;">

            <label style="font-size:14px; color:#6b7280;">Sortare:</label>

            <select name="sort_by" class="sort-select">
                <option value="CodRezervare"  <?php if($sort_by=="CodRezervare") echo "selected"; ?>>Cod rezervare</option>
                <option value="Pasager"       <?php if($sort_by=="Pasager") echo "selected"; ?>>Pasager</option>
                <option value="Pret"          <?php if($sort_by=="Pret") echo "selected"; ?>>Preț</option>
                <option value="DataRezervare" <?php if($sort_by=="DataRezervare") echo "selected"; ?>>Data rezervare</option>
            </select>

            <select name="sort_dir" class="sort-select">
                <option value="asc"  <?php if($sort_dir=="asc") echo "selected"; ?>>↑ Crescător</option>
                <option value="desc" <?php if($sort_dir=="desc") echo "selected"; ?>>↓ Descrescător</option>
            </select>

            <a href="rezervari.php" class="btn-reset">Resetează filtrele</a>

            <button class="btn-primary">Aplică filtre</button>

        </div>

    </form>
</div>


<!-- ================================= -->
<!--         TABEL REZERVĂRI           -->
<!-- ================================= -->

<div class="card">
    <table>
        <thead>
            <tr>
                <th>CodRezervare</th>
                <th>Pasager</th>
                <th>Zbor</th>
                <th>Preț</th>
                <th>Statut</th>
                <th>Observații</th>
                <th>NrLoc</th>
                <th>DataRezervare</th>
                <th>Acțiuni</th>
            </tr>
        </thead>

        <tbody>
        <?php if ($rez && mysqli_num_rows($rez) > 0): ?>
            <?php while($r = mysqli_fetch_assoc($rez)): ?>

                <?php
                    $badge = "";
                    if ($r["Statut"] == "Confirmata")     $badge = "b-confirmata";
                    if ($r["Statut"] == "Anulata")        $badge = "b-anulata";
                    if ($r["Statut"] == "Check-in")       $badge = "b-checkin";
                    if ($r["Statut"] == "In asteptare")   $badge = "b-asteptare";

                    $dataFrumoasa = date("d.m.Y H:i", strtotime($r["DataRezervare"]));
                ?>

                <tr>
                    <td><?php echo $r["CodRezervare"]; ?></td>

                    <td>
                        <?php echo $r["Nume"]." ".$r["Prenume"]; ?><br>
                        <span class="td-secondary"><?php echo $r["Email"]; ?></span>
                    </td>

                    <td>
                        <?php echo $r["CodZbor"]; ?><br>
                        <span class="td-secondary">
                            <?php echo $r["Origine"]." → ".$r["Destinatie"]; ?>
                        </span>
                    </td>

                    <td><?php echo $r["Pret"]." ".$r["Moneda"]; ?></td>

                    <td>
                        <span class="badge <?php echo $badge; ?>"><?php echo $r["Statut"]; ?></span>
                    </td>

                    <td><?php echo $r["Observatii"]; ?></td>

                    <td><?php echo $r["NrLoc"]; ?></td>

                    <td><?php echo $dataFrumoasa; ?></td>

                    <td>
                        <a class="btn-small" href="rezervari.php?edit=<?php echo $r['RezervareID']; ?>">Editează</a>

                        <a class="btn-small btn-danger"
                           href="rezervari.php?delete=<?php echo $r['RezervareID']; ?>"
                           onclick="return confirm('Ștergi rezervarea?');">
                           Șterge
                        </a>
                    </td>

                </tr>

            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="10" style="padding:18px; color:#9ca3af; text-align:center;">
                    Nu există rezultate pentru filtrele aplicate.
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
        // Reconstruim URL-ul pentru filtre fără pagina curentă
        $query = $_GET;
        unset($query["page"]);
        $base_url = "rezervari.php?" . http_build_query($query) . "&page=";
        ?>

        <!-- Prev -->
        <?php if ($page > 1): ?>
            <a class="btn-small" href="<?= $base_url . ($page - 1) ?>">« Prev</a>
        <?php else: ?>
            <span class="btn-small" style="opacity:0.5; pointer-events:none;">« Prev</span>
        <?php endif; ?>

        <!-- Numere pagini -->
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="btn-small" style="background:#2563eb; color:#fff; border-color:#2563eb;">
                    <?= $i ?>
                </span>
            <?php else: ?>
                <a class="btn-small" href="<?= $base_url . $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <!-- Next -->
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
