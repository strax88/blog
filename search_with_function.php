<?php 
include 'config.php';
$pdo = new PDO(
                'mysql:dbname=' . dbname . ';host=' . dbhost,
                dblogin,
                dbpassword
            );
function get_search_data($search_text, $pdo){
    echo "<p>$search_text</p>";
    $sql = "SELECT posts.pTitle, comments.cBody FROM posts INNER JOIN comments ON posts.IDp = comments.IDp WHERE comments.cBody LIKE '%". $search_text ."%';";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $search_data = $stmt->fetchall(PDO::FETCH_ASSOC);

    return $search_data;
}
?>
<form method="GET">
    <input type="text" name="search_field" />
    <input type="submit" />
</form>
<p><a href="index.php">Main page</a></p>
<p><a href="functions.php">Functional programming</a></p>

<?php 
    if ($_GET){
        $search_text = $_GET['search_field'];
        $search_data = get_search_data($search_text, $pdo);    
?>
<table border="1">

    <tr>
        <th align="center">Post title</th>
        <th align="center">Comment's body</th>
    </tr>
    <?php 
        foreach ($search_data as $key => $value) {
            echo "<tr>";
            foreach ($value as $k => $v) {
                echo "<td>$v</td>";
            }
            echo "</tr>";
        }
     ?>
</table>
<?php 
    }
 ?>