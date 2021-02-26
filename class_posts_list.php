<p><a href="index.php">Main page</a></p>
<p><a href="search_with_class.php">Search in comment's body</a></p>
<p><a href="functions.php">Functional programming</a></p>

<?php 
include "config.php";
include "class_loader.php";
$blog = new Blog();
if ($_GET && $_GET['post']){
	$post_data = $blog->get_post($_GET['post']);
	$title = strtoupper($post_data['pTitle']);
	$author = implode(' | ', $blog->get_user($post_data['IDu']));
	$body = $blog->get_post_body($post_data['IDp'])['pbText'];
	$comments = $blog->get_comment_to_post($post_data['IDp']);
	echo "<p><a href='class_posts_list.php'>Posts list</a></p>";
	echo "<h1>$title</h1>";
	echo "<h4>$author</h4>";
	echo "<p>$body</p>";
	echo "<hr>";
	echo "<br>";
	echo "<h5>Comments:</h5>";
	foreach ($comments as $comment_number => $comment_content) {
		$commentator = implode(' | ', $blog->get_user($comment_content['IDu']));
		echo "<div style='background-color: #ccc'>";
		echo "<p>";
		echo "Commentator: $commentator";
		echo "</p>";
		echo "<p>";
		echo $comment_content['cBody'];
		echo "</p>";
		echo "</div><hr>";
	}
} else {
	echo "<h1>POSTS LIST</h1>";
	$posts_list = $blog->get_posts();
	foreach ($posts_list as $post_number => $post_content) {
		// var_dump($post_content['pTitle']);
		?>
		<a href="class_posts_list.php?post=<?php echo $post_content['IDp']; ?>"><?php echo $post_content['pTitle']; ?></a><br>
		<?php
	}
}
?>