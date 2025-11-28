<?php
// --- Configuration ---
$db = new SQLite3('labyrinthe.db');
session_start();

// --- CASE DE DÉPART FIXE ---
$CASE_DEPART = 13;

// --- GESTION DU RESET (à placer AVANT tout affichage) ---
if (isset($_GET['reset'])) {
    // Réinitialisation complète de la session
    $_SESSION['position'] = $CASE_DEPART;
    $_SESSION['cles'] = 0;
    $_SESSION['cles_ramassees'] = [];
    header("Location: index.php");
    exit;
}

// --- Initialisation de la position du joueur (première visite) ---
if (!isset($_SESSION['position'])) {
    $_SESSION['position'] = $CASE_DEPART;
    $_SESSION['cles'] = 0;
    $_SESSION['cles_ramassees'] = [];
}

// --- Forcer le départ à la case 13 si position invalide ---
$stmt = $db->prepare("SELECT * FROM couloir WHERE id = :id");
$stmt->bindValue(':id', $_SESSION['position'], SQLITE3_INTEGER);
$verif = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$verif) {
    // Si la position actuelle n'existe pas, retour au départ
    $_SESSION['position'] = $CASE_DEPART;
}

// --- Déplacement si demandé ---
if (isset($_GET['move'])) {
    $cible = intval($_GET['move']);

    // Vérifier si déplacement possible via la table passage
    $stmt = $db->prepare("SELECT * FROM passage WHERE (couloir1 = :p AND couloir2 = :c) OR (couloir2 = :p AND couloir1 = :c)");
    $stmt->bindValue(':p', $_SESSION['position'], SQLITE3_INTEGER);
    $stmt->bindValue(':c', $cible, SQLITE3_INTEGER);
    $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($res) {
        $typePassage = $res['type'];

        if ($typePassage === 'libre' || $typePassage === 'vide' || $typePassage === 'depart') {
            $_SESSION['position'] = $cible;
        }
        elseif ($typePassage === 'grille') {
            if (!isset($_SESSION['cles'])) $_SESSION['cles'] = 0;
            if ($_SESSION['cles'] > 0) {
                $_SESSION['cles'] -= 1;
                $_SESSION['position'] = $cible;
                $message = "Vous utilisez une clé pour ouvrir la grille.";
            } else {
                $message = "Il vous faut une clé pour ouvrir cette grille !";
            }
        }
    }
}

// --- Récupérer les infos de la position actuelle ---
$stmt = $db->prepare("SELECT * FROM couloir WHERE id = :id");
$stmt->bindValue(':id', $_SESSION['position'], SQLITE3_INTEGER);
$piece = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$piece) {
    die("Erreur : la case actuelle n'existe pas dans la base.");
}

// --- Gestion de la clé ---
if ($piece['type'] === 'cle') {
    if (!isset($_SESSION['cles'])) $_SESSION['cles'] = 0;
    if (!isset($_SESSION['cles_ramassees'])) $_SESSION['cles_ramassees'] = [];
    
    // Éviter de ramasser la même clé plusieurs fois
    if (!in_array($_SESSION['position'], $_SESSION['cles_ramassees'])) {
        $_SESSION['cles'] += 1;
        $_SESSION['cles_ramassees'][] = $_SESSION['position'];
        $message = "Vous avez ramassé une clé !";
    }
}

// --- Vérifier si le joueur a atteint la sortie ---
if ($piece['type'] === 'sortie') {
    $message = "🎉 FÉLICITATIONS ! Vous avez trouvé la sortie du labyrinthe ! Vous avez gagné ! 🎉";
}

// --- Récupérer les sorties ---
$stmt = $db->prepare("SELECT * FROM passage WHERE couloir1 = :id OR couloir2 = :id");
$stmt->bindValue(':id', $_SESSION['position'], SQLITE3_INTEGER);
$result = $stmt->execute();

$sorties = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $cible = ($row['couloir1'] == $_SESSION['position']) ? $row['couloir2'] : $row['couloir1'];
    $sorties[] = [
        'id' => $cible,
        'type' => $row['type'],
        'pos1' => $row['position1'],
        'pos2' => $row['position2']
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Jeu de Labyrinthe</title>
</head>
<body>

<h1>Labyrinthe</h1>

<?php if (isset($message)): ?>
    <p><strong><?php echo $message; ?></strong></p>
<?php endif; ?>

<h2>Position actuelle : Case <?php echo $_SESSION['position']; ?> 
<?php if ($_SESSION['position'] == $CASE_DEPART): ?>
    (DÉPART)
<?php endif; ?>
</h2>

<p><strong>Inventaire :</strong> <?php echo (isset($_SESSION['cles']) && $_SESSION['cles']>0) ? $_SESSION['cles']." clé(s)" : "Aucune clé"; ?></p>

<h3>Sorties disponibles :</h3>

<?php if (count($sorties) == 0): ?>
    <p>Aucune sortie depuis cette case !</p>
<?php else: ?>
    <?php foreach ($sorties as $s): ?>
        <?php if ($s['type'] === 'libre' || $s['type'] === 'vide' || $s['type'] === 'depart'): ?>
            <a href="?move=<?php echo $s['id']; ?>">
                Aller vers case <?php echo $s['id']; ?> (<?php echo $s['type']; ?>)
            </a><br>
        <?php elseif ($s['type'] === 'grille'): ?>
            <?php if (isset($_SESSION['cles']) && $_SESSION['cles'] > 0): ?>
                <a href="?move=<?php echo $s['id']; ?>">
                    Aller vers case <?php echo $s['id']; ?> (grille)
                </a><br>
            <?php else: ?>
                <span>Case <?php echo $s['id']; ?> (grille - clé requise)</span><br>
            <?php endif; ?>
        <?php else: ?>
            <span>Passage vers <?php echo $s['id']; ?> (<?php echo $s['type']; ?>) - bloqué</span><br>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<br><br>
<a href="?reset=1" onclick="return confirm('Voulez-vous vraiment recommencer la partie ? Vous retournerez à la case 13.');">
    Recommencer au départ (Case 13)
</a>

</body>
</html>