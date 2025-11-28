<?php

	//Documentation php pour sqlite : https://www.php.net/manual/en/book.sqlite3.php
	

	/* Paramètres */
	$bdd_fichier = 'labyrinthe.db';	//Fichier de la base de données
	$type ='vide';		//Type de couloir à lister
   

	$sqlite = new SQLite3($bdd_fichier);		//On ouvre le fichier de la base de données
	
	/* Instruction SQL pour récupérer la liste des pieces adjacentes à la pièce paramétrée */
	$sql = 'SELECT couloir.id, couloir.type FROM couloir WHERE type=:type';
	
	
	/* Préparation de la requete et de ses paramètres */
	$requete = $sqlite -> prepare($sql);	<?php
// --- Configuration ---
$db = new SQLite3('labyrinthe.db');
session_start();

// --- Trouver la case départ (id = 13) ---
$depart = 13;

// --- Initialisation de la position du joueur ---
if (!isset($_SESSION['position'])) {
    $_SESSION['position'] = $depart;
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

        // Passage libre
        if ($typePassage === 'libre' || $typePassage === 'vide' || $typePassage === 'depart') {
            $_SESSION['position'] = $cible;
        }
        // Passage grille : nécessite une clé
        elseif ($typePassage === 'grille') {
            if (!isset($_SESSION['cles'])) $_SESSION['cles'] = 0;
            if ($_SESSION['cles'] > 0) {
                // Utiliser une clé et passer
                $_SESSION['cles'] -= 1;
                $_SESSION['position'] = $cible;
                echo "<p><strong>Vous utilisez une clé pour ouvrir la grille.</strong></p>";
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
    <style>
        body { font-family: Arial, sans-serif; background: #eee; padding: 20px; }
        .case { padding: 10px; margin: 5px; display: inline-block; background: #fff; border-radius: 5px; }
        .bouton { display: inline-block; padding: 10px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin: 3px; }
        .bouton:hover { background: #45a049; }
        .blocked { background: #999 !important; }
    </style>
</head>
<body>

<h1>Labyrinthe</h1>
<h2>Position actuelle : Case <?php echo $_SESSION['position']; ?> (<?php echo $piece['type']; ?>)</h2>

<h3>Sorties possibles :</h3>
<?php if (count($sorties) == 0): ?>
    <p>Aucune sortie depuis cette case.</p>
<?php else: ?>
    <?php foreach ($sorties as $s): ?>
        <?php if ($s['type'] === 'libre'): ?>
            <a class="bouton" href="?move=<?php echo $s['id']; ?>">
                Aller vers <?php echo $s['id']; ?> (libre)
            </a>
        <?php else: ?>
            <span class="bouton blocked">
                <?php echo "Passage vers {$s['id']} ({$s['type']})"; ?>
            </span>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<br><br>
<a href="?reset=1" class="bouton" style="background:#c0392b;">Réinitialiser au départ</a>

<?php
if (isset($_GET['reset'])) {
    $_SESSION['position'] = $depart;
    header("Location: index.php");
    exit;
}
?>

<?php
// --- Gestion de la clé ---
if ($piece['type'] === 'cle') {
    if (!isset($_SESSION['cles'])) $_SESSION['cles'] = 0;
    $_SESSION['cles'] += 1;
    echo "<p><strong>Vous avez ramassé une clé !</strong></p>";
}
?>

<p><strong>Inventaire :</strong> <?php echo (isset($_SESSION['cles']) && $_SESSION['cles']>0) ? $_SESSION['cles']." clé(s)" : "Aucune clé"; ?></p> :</strong> <?php echo isset($_SESSION['cle']) ? "Clé obtenue" : "Aucune clé"; ?></p>

</body>
</html>
	$requete -> bindValue(':type', $type, SQLITE3_TEXT);
	
	$result = $requete -> execute();	//Execution de la requête et récupération du résultat

	/* On génère et on affiche notre page HTML avec la liste de nos films */
	echo "<!DOCTYPE html>\n";		//On demande un saut de ligne avec \n, seulement avec " et pas '
	echo "<html lang=\"fr\"><head><meta charset=\"UTF-8\">\n";	//Avec " on est obligé d'échapper les " a afficher avec \
	echo "<title>Liste des couloirs</title>\n";
	echo "</head>\n";
	
	echo "<body>\n";
	echo "<h1>Liste des couloirs</h1>\n";
	echo "<ul>";
	while($couloir = $result -> fetchArray(SQLITE3_ASSOC)) {
		echo '<li>'.$couloir['id']." (type : {$couloir['type']})</li>";
	}
  	echo "</ul>";
	echo "</body>\n";
	echo "</html>\n";
	
	
	$sqlite -> close();			//On ferme bien le fichier de la base de données avant de terminer!
	
?>
