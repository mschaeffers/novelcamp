<?php 

use \App\DataModel\Character;

//If Post request is not set, redirect to Characters page
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $newCharacter = new Character();
    $newCharacter->FirstName = $_POST['first_name'] ?? '';
    $newCharacter->LastName = $_POST['last_name'] ?? '';

    header('Location: ../Pages/Characters.php');
    exit();
}

?>
<html>
<head>
    <title>Add Character Page</title>
    <link rel="stylesheet" type="text/css" href="../styles.css">
</head>
<body>
    <p><a href="../Characters.php">Characters</a></p>
    <h1>Add New Character</h1>
    <form action="add_character.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_character">
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" required>
        <br>
        <label for="last_name">Last Name:</label>
    </form>
</body>
</html>