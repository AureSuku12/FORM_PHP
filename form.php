<?php
$serveur = "localhost"; 
$user = "Paquerette"; 
$pass = "Pamplemouss";

// Fonction pour afficher les array différemment
function echoArray(&$array, $output = '') {
    $output = $output . '<ul>';
    if (empty($array)) { return; }
    elseif (!is_array($array)) { $output .= '<li>' . $array . '</li>'; }
    else {
        foreach ($array as $k => $v) {
            if (!is_array($v)) {
                $output .= '<li>' . $k . ' &rarr; ' . $v . '</li>';
            } else {
                $output .= '<li>' . $k;
                $output .= echoArray($v, $output) . '</li>';
            }
        }
    }
    $output .= '</ul>';
    return $output;
}

// Fonction pour valider les données
function valid_donnees($donnees) {
    if ($donnees === null) {
        return '';
    }
    $donnees = trim($donnees);
    $donnees = strip_tags($donnees);
    $donnees = preg_replace('/<script.*?<\/script>/is', '', $donnees);
    $donnees = preg_replace('/(on\w+\s*=\s*["\']).*?["\']/is', '', $donnees);
    $donnees = stripslashes($donnees);
    $donnees = htmlspecialchars($donnees, ENT_QUOTES,);
    return $donnees;
}

echo "<h2>Résumé de votre formulaire de demande :</h2>";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errorMessages = [];

    // Entrée des variables :
    $userInput = isset($_POST['input_field']) ? valid_donnees($_POST['input_field']) : '';
    $bdate = valid_donnees($_POST['bdate']);
    $timeEvent = valid_donnees($_POST['event']);
    $artist = valid_donnees($_POST['artist']);
    $description_event = valid_donnees($_POST['description']);
    $promotor = valid_donnees($_POST['promo']);
    $Venue_Name = valid_donnees($_POST['venue_name']);
    $address = array(
        'street' => valid_donnees($_POST['venue_address_1']),
        'street_line2' => valid_donnees($_POST['venue_address_2']),
        'city' => valid_donnees($_POST['city']),
        'region' => valid_donnees($_POST['region']),
        'postal' => valid_donnees($_POST['postal']),
        'country' => valid_donnees($_POST['country']),
    );

    $capacity = valid_donnees($_POST['capacity']);
    $attendance = valid_donnees($_POST['attendance']);
    $Type_Performance = valid_donnees($_POST['performance']);
    $timeMin = valid_donnees($_POST['time']);
    $Contact = array(
        'firstname' => valid_donnees($_POST['contact_firstname']),
        'lastname' => valid_donnees($_POST['contact_lastname']),
        'email' => valid_donnees(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)),
        'number' => valid_donnees($_POST['number']),
    );
    $record = isset($_POST['recorded']) ? valid_donnees($_POST['recorded']) : null;
    $fichier = $_FILES['fileToUpload'];
    
    // Conditions de validation
    if (stripos($userInput, '<script') !== false) {
        $errorMessages[] = "Le contenu contient un script et a été supprimé.";
    }
    // Date mise à jour
    date_default_timezone_set("Europe/Paris");
    $now = time();
    
    if (empty($bdate)) { 
        $errorMessages[] = "La date de l'événement est requise.";
    } else { 
        $date = date("Y-m-d", $now);
        if ($bdate < $date){
        $errorMessages[] = 'La date est déjà dépassée.';
        }
    }

    if (empty($timeEvent)){
        $errorMessages[] = "L'heure de l'événement est requise.";
    }

    if (empty($artist)){
        $errorMessages[] = "L'artiste est requis.";
    }

    if (empty($description_event) || strlen($description_event) >= 170){
        $errorMessages[] = "La description de l'événement est requise.";
    }

    if (empty($promotor) || strlen($promotor) >= 100){
        $errorMessages[] = "Le nom du promoteur est requis.";
    }

    if (empty($Venue_Name)){
        $errorMessages[] = "Le nom du lieu est requis.";
    }

    if (empty($address['street']) || empty($address['city']) || empty($address['region']) || empty($address['postal']) || empty($address['country']) || strlen($address['street']) >= 46 || strlen($address['postal']) >= 10 || !is_numeric($address['postal']) || strlen($address['city']) >= 163 || strlen($address['region']) >= 85) {
        $errorMessages[] = "Tous les champs d'adresse sont requis.";
    }

    if (empty($capacity) || !is_numeric($capacity) || strlen($capacity) >= 6) {
        $errorMessages[] = "La capacité doit être un nombre valide.";
    } 

    if (empty($attendance) || !is_numeric($attendance) || strlen($attendance) >= 6) {
        $errorMessages[] = "Le nombre de participants doit être un nombre valide.";
    } 

    if (empty($Type_Performance)){
        $errorMessages[] = "Le type de performance est requis.";
    }

    if (empty($timeMin) || !is_numeric($timeMin) || strlen($attendance) >= 5) {
        $errorMessages[] = "La durée de l'événement en minutes est requise.";
    }

    if (empty($Contact['firstname']) || empty($Contact['lastname']) || empty($Contact['email']) || empty($Contact['number']) || strlen($Contact['firstname']) >= 50 || strlen($Contact['lastname']) >= 50 || strlen($Contact['number']) >= 15 || !is_numeric($Contact['number'])) {
        $errorMessages[] = "Les informations de contact sont invalides.";
    }

    if (false === filter_var($Contact['email'], FILTER_VALIDATE_EMAIL)){
        $errorMessages[] = "L'adresse mail est invalide.";
    }

    if (empty($record)){
        $errorMessages[] = "La réponse concernant l'enregistrement est requise.";
    }
 
        // Vérification du fichier recu
    if (isset($fichier) && $fichier['error'] === 0) {
        $fileInfo = pathinfo($fichier['name']);
        $extension = strtolower($fileInfo['extension']);
        $mimeType = mime_content_type($fichier['tmp_name']);
    
    // Vérification du type MIME (verifie le contenu des fichiers uploads merci steve)
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];


    if ($fichier['size'] > 10000000) {
        $errorMessages[] = "File upload failed, file is too large.";
    } elseif (!in_array($mimeType, $allowedMimeTypes)) {
        $errorMessages[] = "File upload failed, type MIME {$mimeType} is not allowed.";
    } else {
        $path = __DIR__ . '/uploads/';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        // Générer un nom de fichier unique (merci sofiane le goat)
        $hashedFileName = uniqid() . '.' . $extension;
        $destination = $path . $hashedFileName;
        if (!move_uploaded_file($fichier['tmp_name'], $destination)) {
            $errorMessages[] = "An error occurred while uploading the file.";
        } else {
            $fileUrl = 'uploads/' . $hashedFileName;
        }
    }
        }
    
    }

    // Affichage des erreurs
    if (!empty($errorMessages)) {
        echo "<div class='errors'>";
        foreach ($errorMessages as $error) {
            echo "<p style='color:red;'>$error</p>";
        }
        echo "</div>";
    } else {
        // Si aucune erreur, afficher les données
        echo "<br>La date encodée est $bdate.";
        echo "<br>L'heure souhaitée est $timeEvent.";
        echo "<br>L'artiste enregistré est $artist.";
        echo "<br>La description de votre événement donnée est $description_event.";
        echo "<br>Le nom du promoteur est $promotor.";
        echo "<br>Le nom du lieu est $Venue_Name";
        echo "<br><br>L'adresse enregistrée : ";
        echo echoArray($address);
        echo " 1=> Russia, 2=> Germany, 3=> France, 4=> Armenia, 5=> USA<br>";
        echo "<br>La capacité est $capacity.";
        echo "<br>Le nombre attendu est $attendance.";
        echo "<br><br>Le type de performance est $Type_Performance.";
        echo "<br> 1=> Solo Performance <br> 2=> Full Band<br>";
        echo "<br>L'événement va durer $timeMin minutes.";
        echo "<br>Les informations de contact enregistrées : ";
        echo echoArray($Contact);
        echo "<br>L'événement sera enregistré : $record.";
        //Afficher le fichier recu :
        if (isset($fileUrl)) {
            // Vérifiez l'extension du fichier
            $fileExtension = strtolower(pathinfo($fileUrl, PATHINFO_EXTENSION));

            // Si c'est une image, on l'affiche directement
            if (in_array($fileExtension, ['jpg', 'jpeg', 'png'])) {
                echo "<p><strong>Uploaded File:</strong><br><img src='$fileUrl' alt='Uploaded Image' style='max-width: 100%; height: auto;'></p>";
            }
            // Si c'est un PDF, on affiche un lien
            elseif ($fileExtension == 'pdf') {
                echo "<p><strong>Uploaded File:</strong><br><a href='$fileUrl' target='_blank'>View PDF</a></p>";
            }
        }
    }


// Message de réussite
if (!empty($errorMessages)) { 
    foreach ($errorMessages as $error) { 
    } 
} else {
    echo "<p style='color:green;'>Form submitted successfully!</p>";
}

// Lien vers le site HTML
echo '<a href="form.html">Pour remplir le formulaire, cliquez ici !</a>';
?>
