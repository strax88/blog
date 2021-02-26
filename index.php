<!DOCTYPE html>
<html>
    <head>
        <title>Working with posts and comments</title>
    </head>
    <body>
        <p><a href="functions.php">Functional programming</a></p>
        <p><a href="objects.php">Object-oriented approach</a></p>
        <p><a href="index.php?clear=1">Clear database</a></p>
<?php 
    if ($_GET && $_GET['clear']){
        include 'config.php';
        include 'class_loader.php';

        $blog = new Blog();
        $blog->clear_database();
        echo "<h3><font color='red'>The database is cleared of records</font></h3>";
        refreshPage('index.php', 3);
    }
?>
    </body>
</html>