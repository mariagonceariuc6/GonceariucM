<?php
/* ===================== CONEXIUNE DB ===================== */
$server = "localhost";
$user   = "root";
$pass   = "";
$db_name = "companieaeriana_db";

$conn = mysqli_connect($server, $user, $pass, $db_name);
mysqli_set_charset($conn, "utf8mb4");
if (!$conn) {
    die("Conexiune eșuată: " . mysqli_connect_error());
}

/* ===================== FUNCȚIE ESC ===================== */
function esc($conn, $v) {
    return mysqli_real_escape_string($conn, trim($v));
}

/* ===================== ADD PERSOANĂ + PASAGER ===================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "add") {

    $Nume          = esc($conn, $_POST["Nume"]           ?? "");
    $Prenume       = esc($conn, $_POST["Prenume"]        ?? "");
    $IDNP          = esc($conn, $_POST["IDNP"]           ?? "");
    $Telefon       = esc($conn, $_POST["Telefon"]        ?? "");
    $Email         = esc($conn, $_POST["Email"]          ?? "");
    $DataNastere  = esc($conn, $_POST["DataNastere"]   ?? "");
    $POZA          = esc($conn, $_POST["POZA"]           ?? "");
    $Nationalitate = esc($conn, $_POST["Nationalitate"]  ?? "");
    $Statut = esc($conn, $_POST["Statut"]  ?? "Activ");

    /* ================= VALIDARE PASAGER (ADD) ================= */

/* Nume */
if (!preg_match('/^[A-Z][a-zA-Z]+$/', $Nume)) {
    echo "<script>alert('Numele trebuie să înceapă cu majusculă și să conțină doar litere.'); window.history.back();</script>";
    exit;
}

/* Prenume */
if (!preg_match('/^[A-Z][a-zA-Z]+$/', $Prenume)) {
    echo "<script>alert('Prenumele trebuie să înceapă cu majusculă și să conțină doar litere.'); window.history.back();</script>";
    exit;
}

/* IDNP: 13 cifre */
if ($IDNP !== "" && !preg_match('/^[0-9]{13}$/', $IDNP)) {
    echo "<script>alert('IDNP trebuie să conțină EXACT 13 cifre.'); window.history.back();</script>";
    exit;
}

/* Telefon: + și cifre */
if ($Telefon !== "" && !preg_match('/^\+[0-9]+$/', $Telefon)) {
    echo "<script>alert('Telefonul trebuie să înceapă cu + și să conțină doar cifre după +.'); window.history.back();</script>";
    exit;
}

/* Email */
if ($Email !== "" && !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $Email)) {
    echo "<script>alert('Email invalid. Format necesar: nume@domeniu.com'); window.history.back();</script>";
    exit;
}

/* Naționalitate */
if ($Nationalitate !== "" && !preg_match('/^[A-Za-z]+$/', $Nationalitate)) {
    echo "<script>alert('Naționalitatea trebuie să conțină doar litere.'); window.history.back();</script>";
    exit;
}


    if ($Nume && $Prenume) {
        /* INSERT PERSOANA */
        $sqlPers = "
            INSERT INTO Persoana (Nume, Prenume, IDNP, Telefon, Email, DataNastere, POZA)
            VALUES ('$Nume', '$Prenume', '$IDNP', '$Telefon', '$Email', '$DataNastere', '$POZA')
        ";
        mysqli_query($conn, $sqlPers);
        $persID = mysqli_insert_id($conn);

        /* INSERT PASAGER */
        $sqlPas = "
            INSERT INTO Pasageri (PersoanaID, Nationalitate, Statut)
            VALUES ($persID, '$Nationalitate', '$Statut')
        ";
        mysqli_query($conn, $sqlPas);
    }

    header("Location: pasageri.php");
    exit;
}

/* ===================== EDITARE: CITIRE ===================== */
$editData = null;

if (isset($_GET["edit"])) {
    $idEdit = (int)$_GET["edit"];
    if ($idEdit > 0) {
        $q = mysqli_query($conn, "
            SELECT pa.PasagerID, pa.PersoanaID, pa.Nationalitate, pa.Statut,
                   pe.Nume, pe.Prenume, pe.IDNP, pe.Telefon, pe.Email, pe.DataNastere, pe.POZA
            FROM Pasageri pa
            JOIN Persoana pe ON pe.PersoanaID = pa.PersoanaID
            WHERE pa.PasagerID = $idEdit
            LIMIT 1
        ");
        if ($q && mysqli_num_rows($q) === 1) {
            $editData = mysqli_fetch_assoc($q);
        }
    }
}

/* ===================== UPDATE PERSOANĂ + PASAGER ===================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update") {

    $PasagerID   = (int)($_POST["PasagerID"]   ?? 0);
    $PersoanaID  = (int)($_POST["PersoanaID"]  ?? 0);

    $Nume          = esc($conn, $_POST["Nume"]           ?? "");
    $Prenume       = esc($conn, $_POST["Prenume"]        ?? "");
    $IDNP          = esc($conn, $_POST["IDNP"]           ?? "");
    $Telefon       = esc($conn, $_POST["Telefon"]        ?? "");
    $Email         = esc($conn, $_POST["Email"]          ?? "");
    $DataNastere  = esc($conn, $_POST["DataNastere"]   ?? "");
    $POZA          = esc($conn, $_POST["POZA"]           ?? "");
    $Nationalitate = esc($conn, $_POST["Nationalitate"]  ?? "");
    $Statut = esc($conn, $_POST["Statut"]  ?? "Activ");

    /* ================= VALIDARE PASAGER (UPDATE) ================= */

/* Nume */
if (!preg_match('/^[A-Z][a-zA-Z]+$/', $Nume)) {
    echo "<script>alert('Numele trebuie să înceapă cu majusculă și să conțină doar litere.'); window.history.back();</script>";
    exit;
}

/* Prenume */
if (!preg_match('/^[A-Z][a-zA-Z]+$/', $Prenume)) {
    echo "<script>alert('Prenumele trebuie să înceapă cu majusculă și să conțină doar litere.'); window.history.back();</script>";
    exit;
}

/* IDNP: 13 cifre */
if ($IDNP !== "" && !preg_match('/^[0-9]{13}$/', $IDNP)) {
    echo "<script>alert('IDNP trebuie să fie format din exact 13 cifre.'); window.history.back();</script>";
    exit;
}

/* Telefon */
if ($Telefon !== "" && !preg_match('/^\+[0-9]+$/', $Telefon)) {
    echo "<script>alert('Telefonul trebuie să înceapă cu + și să conțină doar cifre.'); window.history.back();</script>";
    exit;
}

/* Email */
if ($Email !== "" && !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $Email)) {
    echo "<script>alert('Email invalid. Trebuie forma corectă: nume@domeniu.com'); window.history.back();</script>";
    exit;
}

/* Naționalitate */
if ($Nationalitate !== "" && !preg_match('/^[A-Za-z]+$/', $Nationalitate)) {
    echo "<script>alert('Naționalitatea trebuie să conțină doar litere.'); window.history.back();</script>";
    exit;
}


    if ($PasagerID > 0 && $PersoanaID > 0) {

        /* UPDATE Persoana */
        mysqli_query($conn, "
            UPDATE Persoana SET
                Nume         = '$Nume',
                Prenume      = '$Prenume',
                IDNP         = '$IDNP',
                Telefon      = '$Telefon',
                Email        = '$Email',
                DataNastere = '$DataNastere',
                POZA         = '$POZA'
            WHERE PersoanaID = $PersoanaID
        ");

        /* UPDATE Pasager */
        mysqli_query($conn, "
            UPDATE Pasageri SET
                Nationalitate = '$Nationalitate',
                Statut = '$Statut'
            WHERE PasagerID = $PasagerID
        ");
    }

    header("Location: pasageri.php");
    exit;
}

/* ===================== ȘTERGERE PASAGER ===================== */
if (isset($_GET["delete"])) {
    $idDel = (int)$_GET["delete"];
    if ($idDel > 0) {
        mysqli_query($conn, "DELETE FROM Pasageri WHERE PasagerID = $idDel");
    }
    header("Location: pasageri.php");
    exit;
}

/* ===================== FILTRE ===================== */
$f_nume    = esc($conn, $_GET["nume"]    ?? "");
$f_prenume = esc($conn, $_GET["prenume"] ?? "");
$f_idnp    = esc($conn, $_GET["idnp"]    ?? "");
$f_tel     = esc($conn, $_GET["telefon"] ?? "");
$f_email   = esc($conn, $_GET["email"]   ?? "");
$f_nat     = esc($conn, $_GET["nat"]     ?? "");
$f_status  = esc($conn, $_GET["status"]  ?? "");
$f_d1      = esc($conn, $_GET["d1"]      ?? "");
$f_d2      = esc($conn, $_GET["d2"]      ?? "");

/* ===================== SORTARE ===================== */
$allowedSort = [
    "Nume"          => "pe.Nume",
    "Prenume"       => "pe.Prenume",
    "DataNastere"  => "pe.DataNastere",
    "Nationalitate" => "pa.Nationalitate",
    "Statut" => "pa.Statut"
];

$sort_by  = $_GET["sort_by"]  ?? "Nume";
$sort_dir = $_GET["sort_dir"] ?? "asc";
$sort_dir = strtolower($sort_dir) === "desc" ? "DESC" : "ASC";

if (!isset($allowedSort[$sort_by])) {
    $sort_by = "Nume";
}
$sort_sql = $allowedSort[$sort_by];

/* ===========================================================
   ===============          PAGINARE            ===============
   =========================================================== */

$per_page = 10; // câți pasageri pe pagină
$page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
$offset = ($page - 1) * $per_page;

/* Construim query-ul de COUNT pentru a afla totalul după filtre */
$count_sql = "
    SELECT COUNT(*) AS total
    FROM Pasageri pa
    JOIN Persoana pe ON pe.PersoanaID = pa.PersoanaID
    WHERE 1
";

/* Aplicați aceleași filtre ca în query-ul principal */
if ($f_nume !== "")    $count_sql .= " AND pe.Nume LIKE '%$f_nume%'";
if ($f_prenume !== "") $count_sql .= " AND pe.Prenume LIKE '%$f_prenume%'";
if ($f_idnp !== "")    $count_sql .= " AND pe.IDNP LIKE '%$f_idnp%'";
if ($f_tel !== "")     $count_sql .= " AND pe.Telefon LIKE '%$f_tel%'";
if ($f_email !== "")   $count_sql .= " AND pe.Email LIKE '%$f_email%'";
if ($f_nat !== "")     $count_sql .= " AND pa.Nationalitate LIKE '%$f_nat%'";
if ($f_status !== "")  $count_sql .= " AND pa.Statut = '$f_status'";
if ($f_d1 !== "")      $count_sql .= " AND pe.DataNastere >= '$f_d1'";
if ($f_d2 !== "")      $count_sql .= " AND pe.DataNastere <= '$f_d2'";

$count_res = mysqli_query($conn, $count_sql);
$total_rows = mysqli_fetch_assoc($count_res)["total"];
$total_pages = ceil($total_rows / $per_page);


/* ===================== QUERY LISTĂ PASAGERI ===================== */
$sql = "
    SELECT
        pa.PasagerID,
        pa.PersoanaID,
        pa.Nationalitate,
        pa.Statut,
        pe.Nume,
        pe.Prenume,
        pe.IDNP,
        pe.Telefon,
        pe.Email,
        pe.DataNastere,
        pe.POZA
    FROM Pasageri pa
    JOIN Persoana pe ON pe.PersoanaID = pa.PersoanaID
    WHERE 1
";

if ($f_nume !== "")    $sql .= " AND pe.Nume LIKE '%$f_nume%'";
if ($f_prenume !== "") $sql .= " AND pe.Prenume LIKE '%$f_prenume%'";
if ($f_idnp !== "")    $sql .= " AND pe.IDNP LIKE '%$f_idnp%'";
if ($f_tel !== "")     $sql .= " AND pe.Telefon LIKE '%$f_tel%'";
if ($f_email !== "")   $sql .= " AND pe.Email LIKE '%$f_email%'";
if ($f_nat !== "")     $sql .= " AND pa.Nationalitate LIKE '%$f_nat%'";
if ($f_status !== "")  $sql .= " AND pa.Statut = '$f_status'";
if ($f_d1 !== "")      $sql .= " AND pe.DataNastere >= '$f_d1'";
if ($f_d2 !== "")      $sql .= " AND pe.DataNastere <= '$f_d2'";

$sql .= " ORDER BY $sort_sql $sort_dir LIMIT $offset, $per_page";

$rez = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>Pasageri – BlueWing Airlines</title>

<style>
    *{
        margin:0;
        padding:0;
        box-sizing:border-box;
        font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
    }
    body{
        background:#f3f4f6;
        display:flex;
        min-height:100vh;
    }

    /* ===== SIDEBAR ===== */
    .sidebar{
        width:240px;
        background:#ffffff;
        border-right:1px solid #e5e7eb;
        padding:24px 20px;
        display:flex;
        flex-direction:column;
    }
    .sidebar-logo{
        display:flex;
        align-items:center;
        margin-bottom:28px;
    }
    .logo-box{
        width:40px;
        height:40px;
        border-radius:12px;
        background:#2563eb;
        color:#fff;
        display:flex;
        align-items:center;
        justify-content:center;
        font-weight:700;
        font-size:20px;
        margin-right:10px;
    }
    .logo-title{
        font-size:16px;
        font-weight:600;
        color:#111827;
    }
    .logo-sub{
        font-size:11px;
        color:#6b7280;
        margin-top:-2px;
    }
    .nav{
        list-style:none;
        margin-top:15px;
    }
    .nav-item{
        margin-bottom:6px;
    }
    .nav-link{
        display:flex;
        align-items:center;
        padding:9px 12px;
        border-radius:10px;
        text-decoration:none;
        color:#374151;
        font-size:14px;
        transition:.15s;
    }
    .nav-link .icon{
        width:20px;
        margin-right:10px;
        text-align:center;
    }
    .nav-link:hover{
        background:#f3f4f6;
    }
    .nav-link.active{
        background:#dbeafe;
        color:#1d4ed8;
        font-weight:600;
    }
    .sidebar-footer{
        margin-top:auto;
        font-size:11px;
        color:#9ca3af;
        padding-top:10px;
    }

    /* ===== MAIN ===== */
    .main{
        flex:1;
        padding:24px 32px;
    }
    .top-bar{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:22px;
    }
    .top-bar h1{
        font-size:22px;
        font-weight:700;
        color:#111827;
    }
    .top-bar p{
        font-size:13px;
        color:#6b7280;
    }

    .btn-primary{
        background:#2563eb;
        color:#ffffff;
        border:none;
        padding:9px 16px;
        border-radius:10px;
        font-size:14px;
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        gap:6px;
        cursor:pointer;
    }
    .btn-primary:hover{
        background:#1d4ed8;
    }

    /* ===== CARD ===== */
    .card{
        background:#ffffff;
        border-radius:16px;
        padding:20px 22px;
        box-shadow:0 1px 3px rgba(0,0,0,0.07);
        margin-bottom:20px;
    }

    /* ===== FORM ADD / EDIT ===== */
    .form-grid{
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:16px;
        margin-bottom:10px;
    }
    .form-group label{
        font-size:13px;
        color:#6b7280;
        margin-bottom:4px;
        display:block;
    }
    .form-group input,
    .form-group select{
        width:100%;
        padding:8px 12px;
        border-radius:12px;
        border:1px solid #e5e7eb;
        background:#f9fafb;
        font-size:14px;
    }
    .form-actions{
        margin-top:12px;
        display:flex;
        gap:10px;
    }
    .btn-save{
        background:#10b981;
        color:#fff;
        border:none;
        padding:8px 16px;
        border-radius:10px;
        font-size:14px;
        cursor:pointer;
    }
    .btn-save:hover{
        background:#059669;
    }
    .btn-ghost{
        border:none;
        background:transparent;
        color:#6b7280;
        font-size:14px;
        text-decoration:none;
        cursor:pointer;
    }

    /* ===== FILTRARE ===== */
    .filter-title{
        font-size:16px;
        font-weight:600;
        color:#111827;
        margin-bottom:14px;
    }
    .filter-grid{
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:14px;
        margin-bottom:12px;
    }
    .filter-group label{
        font-size:12px;
        color:#6b7280;
        margin-bottom:4px;
        display:block;
    }
    .filter-group input,
    .filter-group select{
        width:100%;
        padding:7px 12px;
        border-radius:999px;
        border:1px solid #e5e7eb;
        background:#f9fafb;
        font-size:13px;
    }
    .filter-actions{
        margin-top:10px;
        display:flex;
        gap:10px;
        justify-content:flex-end;
        align-items:center;
    }
    .btn-reset{
        background:#f9fafb;
        border:1px solid #e5e7eb;
        padding:7px 14px;
        border-radius:10px;
        font-size:13px;
        cursor:pointer;
        text-decoration:none;
        color:#374151;
    }
    .btn-reset:hover{
        background:#e5e7eb;
    }

    /* ===== SORTARE ===== */
    .sort-row{
        display:flex;
        gap:10px;
        align-items:center;
        font-size:13px;
        color:#6b7280;
        margin-top:4px;
    }
    .sort-select{
        appearance:none;
        -webkit-appearance:none;
        -moz-appearance:none;
        padding:7px 16px;
        border-radius:999px;
        border:1px solid #e5e7eb;
        background:#f9fafb;
        font-size:13px;
        color:#374151;
        cursor:pointer;
        background-image:
            linear-gradient(45deg, transparent 50%, #6b7280 50%),
            linear-gradient(135deg, #6b7280 50%, transparent 50%),
            linear-gradient(to right, #f9fafb, #f9fafb);
        background-position:
            calc(100% - 18px) calc(50% - 3px),
            calc(100% - 13px) calc(50% - 3px),
            calc(100% - 2.6em) 0.3em;
        background-size:5px 5px,5px 5px,1px 1.5em;
        background-repeat:no-repeat;
    }
    .sort-select:hover{
        background:#f3f4f6;
    }

    /* ===== TABEL ===== */
    table{
        width:100%;
        border-collapse:collapse;
        font-size:14px;
    }
    thead tr{
        background:#f9fafb;
    }
    th{
        padding:10px 16px;
        font-size:12px;
        font-weight:600;
        color:#6b7280;
        border-bottom:1px solid #e5e7eb;
        text-align:left;
    }
    td{
        padding:10px 16px;
        border-bottom:1px solid #f3f4f6;
        color:#111827;
    }
    tbody tr:hover{
        background:#f9fafb;
    }

    .avatar{
        width:42px;
        height:42px;
        border-radius:50%;
        object-fit:cover;
        border:2px solid #e5e7eb;
    }

    .badge{
        padding:3px 9px;
        border-radius:999px;
        font-size:11px;
        font-weight:600;
        display:inline-block;
    }
    .b-activ{
        background:#dcfce7;
        color:#166534;
    }
    .b-inactiv{
        background:#fee2e2;
        color:#b91c1c;
    }
    .b-verificat{
        background:#dbeafe;
        color:#1d4ed8;
    }

    .btn-small{
        padding:5px 12px;
        border-radius:10px;
        font-size:12px;
        border:1px solid #e5e7eb;
        background:#ffffff;
        cursor:pointer;
        text-decoration:none;
        color:#374151;
        display:inline-flex;
        align-items:center;
        justify-content:center;
    }
    .btn-small:hover{
        background:#f3f4f6;
    }
    .btn-danger{
        color:#b91c1c;
        border-color:#fecaca;
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
            <span class="icon">🏠</span><span>Dashboard</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="zboruri.php" class="nav-link">
            <span class="icon">🛫</span><span>Zboruri</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="rezervari.php" class="nav-link">
            <span class="icon">📅</span><span>Rezervări</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="pasageri.php" class="nav-link active">
            <span class="icon">👥</span><span>Pasageri</span>
        </a>
    </li>

    <!-- Rapoarte → Bilete -->
    <li class="nav-item">
        <a href="bilete.php" class="nav-link">
            <span class="icon">🎫</span><span>Bilete</span>
        </a>
    </li>

    <!-- Setări → Aeronave -->
    <li class="nav-item">
        <a href="aeronave.php" class="nav-link">
            <span class="icon">✈️</span><span>Aeronave</span>
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
            <h1>Pasageri</h1>
            <p>Gestionează datele pasagerilor</p>
        </div>
        <a href="pasageri.php?add=1" class="btn-primary">＋ Adaugă pasager</a>
    </div>

    <!-- FORMULAR ADD -->
    <?php if (isset($_GET["add"]) && !$editData): ?>
    <div class="card">
        <h2 style="font-size:16px;margin-bottom:14px;">Adaugă pasager</h2>
        <form method="post">
            <input type="hidden" name="action" value="add">

            <div class="form-grid">
                <div class="form-group">
                    <label>Nume</label>
                    <input type="text" name="Nume" required>
                </div>
                <div class="form-group">
                    <label>Prenume</label>
                    <input type="text" name="Prenume" required>
                </div>
                <div class="form-group">
                    <label>IDNP</label>
                    <input type="text" name="IDNP">
                </div>

                <div class="form-group">
                    <label>Telefon</label>
                    <input type="text" name="Telefon">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="Email">
                </div>
                <div class="form-group">
                    <label>Data nașterii</label>
                    <input type="date" name="DataNastere">
                </div>

                <div class="form-group">
                    <label>URL poză (POZA)</label>
                    <input type="text" name="POZA" placeholder="https://...">
                </div>
                <div class="form-group">
                    <label>Naționalitate</label>
                    <input type="text" name="Nationalitate">
                </div>
                <div class="form-group">
                    <label>Status pasager</label>
                    <select name="Statut">
                        <option value="Activ">Activ</option>
                        <option value="Inactiv">Inactiv</option>
                        <option value="Verificat">Verificat</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">Salvează</button>
                <a href="pasageri.php" class="btn-ghost">Anulează</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- FORMULAR EDIT -->
    <?php if ($editData): ?>
    <div class="card">
        <h2 style="font-size:16px;margin-bottom:14px;">Editează pasager</h2>
        <form method="post">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="PasagerID" value="<?php echo (int)$editData['PasagerID']; ?>">
            <input type="hidden" name="PersoanaID" value="<?php echo (int)$editData['PersoanaID']; ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label>Nume</label>
                    <input type="text" name="Nume" value="<?php echo htmlspecialchars($editData['Nume']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Prenume</label>
                    <input type="text" name="Prenume" value="<?php echo htmlspecialchars($editData['Prenume']); ?>" required>
                </div>
                <div class="form-group">
                    <label>IDNP</label>
                    <input type="text" name="IDNP" value="<?php echo htmlspecialchars($editData['IDNP']); ?>">
                </div>

                <div class="form-group">
                    <label>Telefon</label>
                    <input type="text" name="Telefon" value="<?php echo htmlspecialchars($editData['Telefon']); ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="Email" value="<?php echo htmlspecialchars($editData['Email']); ?>">
                </div>
                <div class="form-group">
                    <label>Data nașterii</label>
                    <input type="date" name="DataNastere" value="<?php echo htmlspecialchars($editData['DataNastere']); ?>">
                </div>

                <div class="form-group">
                    <label>URL poză (POZA)</label>
                    <input type="text" name="POZA" value="<?php echo htmlspecialchars($editData['POZA']); ?>">
                </div>
                <div class="form-group">
                    <label>Naționalitate</label>
                    <input type="text" name="Nationalitate" value="<?php echo htmlspecialchars($editData['Nationalitate']); ?>">
                </div>
                <div class="form-group">
                    <label>Status pasager</label>
                    <select name="Statut">
                        <option value="Activ"     <?php if($editData['Statut']=="Activ")     echo "selected"; ?>>Activ</option>
                        <option value="Inactiv"   <?php if($editData['Statut']=="Inactiv")   echo "selected"; ?>>Inactiv</option>
                        <option value="Verificat" <?php if($editData['Statut']=="Verificat") echo "selected"; ?>>Verificat</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">Salvează modificările</button>
                <a href="pasageri.php" class="btn-ghost">Anulează</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- CARD FILTRARE -->
    <div class="card">
        <div class="filter-title">Filtrare</div>
        <form method="get">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Nume</label>
                    <input type="text" name="nume" value="<?php echo htmlspecialchars($f_nume); ?>">
                </div>
                <div class="filter-group">
                    <label>Prenume</label>
                    <input type="text" name="prenume" value="<?php echo htmlspecialchars($f_prenume); ?>">
                </div>
                <div class="filter-group">
                    <label>IDNP</label>
                    <input type="text" name="idnp" value="<?php echo htmlspecialchars($f_idnp); ?>">
                </div>
                <div class="filter-group">
                    <label>Telefon</label>
                    <input type="text" name="telefon" value="<?php echo htmlspecialchars($f_tel); ?>">
                </div>
                <div class="filter-group">
                    <label>Email</label>
                    <input type="text" name="email" value="<?php echo htmlspecialchars($f_email); ?>">
                </div>
                <div class="filter-group">
                    <label>Naționalitate</label>
                    <input type="text" name="nat" value="<?php echo htmlspecialchars($f_nat); ?>">
                </div>
                <div class="filter-group">
                    <label>Status pasager</label>
                    <select name="status">
                        <option value="">-- toate --</option>
                        <option value="Activ"     <?php if($f_status=="Activ")     echo "selected"; ?>>Activ</option>
                        <option value="Inactiv"   <?php if($f_status=="Inactiv")   echo "selected"; ?>>Inactiv</option>
                        <option value="Verificat" <?php if($f_status=="Verificat") echo "selected"; ?>>Verificat</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Data nașterii (de la)</label>
                    <input type="date" name="d1" value="<?php echo htmlspecialchars($f_d1); ?>">
                </div>
                <div class="filter-group">
                    <label>Data nașterii (până la)</label>
                    <input type="date" name="d2" value="<?php echo htmlspecialchars($f_d2); ?>">
                </div>
            </div>

            <div class="sort-row">
                <span>Sortare:</span>
                <select name="sort_by" class="sort-select">
                    <option value="Nume"          <?php if($sort_by=="Nume")          echo "selected"; ?>>Nume</option>
                    <option value="Prenume"       <?php if($sort_by=="Prenume")       echo "selected"; ?>>Prenume</option>
                    <option value="DataNastere"  <?php if($sort_by=="DataNastere")  echo "selected"; ?>>Data nașterii</option>
                    <option value="Nationalitate" <?php if($sort_by=="Nationalitate") echo "selected"; ?>>Naționalitate</option>
                    <option value="Statut" <?php if($sort_by=="Statut") echo "selected"; ?>>Status pasager</option>
                </select>

                <select name="sort_dir" class="sort-select">
                    <option value="asc"  <?php if($sort_dir=="ASC")  echo "selected"; ?>>↑ Crescător</option>
                    <option value="desc" <?php if($sort_dir=="DESC") echo "selected"; ?>>↓ Descrescător</option>
                </select>

                <div class="filter-actions">
                    <a href="pasageri.php" class="btn-reset">Resetează</a>
                    <button type="submit" class="btn-primary">Aplică</button>
                </div>
            </div>
        </form>
    </div>

    <!-- CARD TABEL PASAGERI -->
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Pasager</th>
                    <th>Imagine</th>
                    <th>IDNP</th>
                    <th>Telefon</th>
                    <th>Email</th>
                    <th>Naționalitate</th>
                    <th>Data naștere</th>
                    <th>Status</th>
                    <th>Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rez && mysqli_num_rows($rez) > 0): ?>
                    <?php while($r = mysqli_fetch_assoc($rez)): ?>
                        <?php
                            $badgeClass = "b-activ";
                            if ($r["Statut"] === "Inactiv")   $badgeClass = "b-inactiv";
                            if ($r["Statut"] === "Verificat") $badgeClass = "b-verificat";

                            $dataNice = $r["DataNastere"] ? date("d.m.Y", strtotime($r["DataNastere"])) : "-";
                        ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($r["Nume"] . " " . $r["Prenume"]); ?>
                            </td>
                            <td>
                                <?php if (!empty($r["POZA"])): ?>
                                    <img src="<?php echo htmlspecialchars($r["POZA"]); ?>" class="avatar" alt="poza">
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($r["IDNP"]); ?></td>
                            <td><?php echo htmlspecialchars($r["Telefon"]); ?></td>
                            <td><?php echo htmlspecialchars($r["Email"]); ?></td>
                            <td><?php echo htmlspecialchars($r["Nationalitate"]); ?></td>
                            <td><?php echo $dataNice; ?></td>
                            <td>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo htmlspecialchars($r["Statut"]); ?>
                                </span>
                            </td>
                            <td>
                                <a href="pasageri.php?edit=<?php echo (int)$r['PasagerID']; ?>" class="btn-small">Editează</a>
                                <a href="pasageri.php?delete=<?php echo (int)$r['PasagerID']; ?>"
                                   class="btn-small btn-danger"
                                   onclick="return confirm('Sigur dorești să ștergi acest pasager?');">
                                    Șterge
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="padding:14px;color:#6b7280;">
                            Nu există pasageri pentru filtrele selectate.
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
        // Reconstruim URL-ul fără parametru page
        $query = $_GET;
        unset($query["page"]);
        $base_url = "pasageri.php?" . http_build_query($query) . "&page=";
        ?>

        <!-- PREV -->
        <?php if ($page > 1): ?>
            <a class="btn-small" href="<?= $base_url . ($page - 1) ?>">« Prev</a>
        <?php else: ?>
            <span class="btn-small" style="opacity:0.5; pointer-events:none;">« Prev</span>
        <?php endif; ?>

        <!-- NUMERE -->
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="btn-small"
                      style="background:#2563eb; color:white; border-color:#2563eb;">
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
            <span class="btn-small" style="opacity:0.5; pointer-events:none;">Next »</span>
        <?php endif; ?>

    </div>

<?php endif; ?>


</main>

<?php mysqli_close($conn); ?>
</body>
</html>
