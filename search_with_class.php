<?php 
include 'config.php';
include 'class_loader.php'
?>
<form method="GET">
    <input type="text" name="search_field" />
    <input type="submit" value="Search" name="search" />
</form>

<p><a href="index.php">Main page</a></p>
<p><a href="objects.php">Object-oriented approach</a></p>

<?php 
    if ($_GET){
        $blog = new Blog();
        $search_text = $_GET['search_field'];
        $search_data = $blog->search_in_comments_body($search_text);
        if (count($search_data) > 0 && strlen($search_text) > 0){
            foreach ($search_data as $field_name => $content) {
                if ($field_name != 'pTitle'){
                    foreach ($content as $key => $value) {
                        $value = explode($search_text, $value);
                        $search_data[$field_name][$key] = implode('<font style="background-color: #ccc; color: blue"><b>'.$search_text.'</b></font>', $value);
                    }
                }
            }
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
        } else {
            echo "No matches found";
        }
    }
 ?>