<?php 
include 'config.php';
function refreshPage($url, $time = 0){
    // функция обновления/перенаправления страницы
    echo '<META HTTP-EQUIV="Refresh" Content="'. $time .'; URL='.$url.'">'; 
}

/**
 * Абстрактный класс для работы с постами и комментариями
 */
abstract class Common
{
    protected static $pdo;
    protected $data;
    protected $url;
    protected $db_arrays;
    protected $result= True;
    
    function __construct()
    {
        self::$pdo = new PDO(
                'mysql:dbname=' . dbname . ';host=' . dbhost,
                dblogin,
                dbpassword
            );
    }
    protected function clear_database_tables():bool
    {
        try{
            $sql = "DELETE FROM `users`;";
            $stmt = self::$pdo->prepare($sql);
            return $stmt->execute();
            // return $stmt->fetch();
        } catch (Exception $e){
            echo "<p>Sampling error from the database: $e</p>";
            return False;
        }
        return True;
    }

    protected function get_data_from_db(array $tables_list, array $fields_list, array $search):array
    {
        $fields = implode(', ', $fields_list);
        array_walk($tables_list, function (&$item1){$item1 = "`$item1`";});
        $tables = implode(', ', $tables_list);
        $conditions = '';
        try{
            if (count($search) > 0){
                $conditions = array();
                foreach ($search as $search_field => $search_text) {
                    $conditions[] = $search_field."=".$search_text;
                }
                $conditions = ' WHERE ' . implode(' AND ', $conditions);
            }
            $sql = "SELECT ". $fields ." FROM ". $tables . $conditions . ";";
            $stmt = self::$pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchall(PDO::FETCH_ASSOC);
        } catch (Exception $e){
            echo "<p>Sampling error from the database: $e</p>";
            return False;
        }
        return True;
    }

    protected function get_searching_data(string $search_text):array
    {
        try{
            $sql = "SELECT `posts`.`pTitle`, `comments`.`cBody` FROM `posts` INNER JOIN `comments` USING (IDp) WHERE `comments`.`cBody` LIKE '%". $search_text ."%';";
            $stmt = self::$pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchall(PDO::FETCH_ASSOC);
        } catch (Exception $e){
            echo "<p>Sampling error from the database: $e</p>";
            return False;
        }
        return True;
    }


    protected function get_data_from_json():bool
    {
        // функция для получения объекта из json-данных с использованием ssl
        $ch = curl_init();

        try{
            curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

            // декодируем json
            $this->data = json_decode(curl_exec($ch), true);
            curl_close( $ch );
        } catch (Exception $e){
            echo "<p>Json unpacking error: $e</p>";
            return False;
        }
        return True;
    }

    protected function upload_data(string $url):bool
    {
        $this->url = $url;
        if ($this->get_data_from_json() and $this->make_arrays_for_database()){
            try{
                foreach ($this->db_arrays as $table_name => $table_data) {
                    $this->result *= $this->insert_data_to_database($table_name, $table_data);
                }
            } catch (Exception $e){
                echo "<p>Download process error: $e</p>";
                return False;
            }
        } else {
            echo "Problems with data processing '$this->url'";
            return False;
        }
        return $this->result;
    }

    protected function make_arrays_for_database():bool
    {
    }


    protected function insert_data_to_database(string $table_name, array $table_data):bool
    {
        if (count($table_data) == 0){
            return False;
        }
        try{
            $fields_name = implode(', ', array_keys($table_data[array_keys($table_data)[0]]));
            $values_name = array_keys($table_data[array_keys($table_data)[0]]);
            array_walk($values_name, function (&$item1){$item1 = ":$item1";});
            $values_name = implode(', ', $values_name);
            // подготавливаем запрос для вставки
            $sql = "INSERT INTO `". $table_name ."`(". $fields_name .") VALUES (". $values_name .");";
            $stmt = self::$pdo->prepare($sql);
            self::$pdo->beginTransaction();
            // пробегаемся по данным в массиве и добавляем их в соответствующую таблицу
            foreach ($table_data as $row) {
                $stmt->execute($row);
            }
            self::$pdo->commit();
        } catch (Exception $e){
            echo "<p>Error loading to the database: $e</p>";
            return False;
        }
        return True;
    }
    
    protected function check_object_in_database($sender, string $field_name, int $id):bool
    {
        if ($sender instanceof Post){
            $table_name = 'posts';
        } else {
            $table_name = 'comments';
        }
        try{
            $sql = "SELECT ". $field_name ." FROM `". $table_name ."` WHERE ". $field_name ."=:id;";
            $stmt = self::$pdo->prepare($sql);
            $stmt->execute(array('id' => $id));
            return $id == (int)$stmt->fetch(PDO::FETCH_ASSOC)[$field_name];
        } catch (Exception $e){
            echo "<p>Sampling error from the database: $e</p>";
            return False;
        }
        return False;
    }
}


/**
 * Класс для обработки постов
 */
class Post extends Common
{
    protected function make_arrays_for_database():bool
    {
        $this->db_arrays = array('users' => array(), 'posts' => array(), 'posts_bodies' => array());
        $service_array = array('userId' => 'IDu','id' => 'IDp','title' => 'pTitle','body' => 'pbText');
        foreach ($this->data as $post_content) {
            // формируем вложенные массивы для постов и их содержимого

            if (!$this->check_object_in_database($this, 'IDp', (int)$post_content['id']))
            {
                $this->db_arrays['posts'][] = array();
                $this->db_arrays['posts_bodies'][] = array();
                $this->make_content($post_content, $service_array);
            }
        }
        return True;
    }

    private function make_content(array $post_content, array $service_array):bool
    {
        foreach ($post_content as $field_name => $value) {
            // формирум массив постов, иначе формируем массив содержимого постов
            if ($field_name != "body"){
                // добавляем поля в массив постов
                $this->db_arrays['posts'][count($this->db_arrays['posts'])-1][$service_array[$field_name]] = $value;
                // формируем массив пользователя перегружая по идентификатору
                $this->db_arrays['users'][$this->db_arrays['posts'][count($this->db_arrays['posts'])-1][$service_array['userId']]] = array(
                    'IDu' => $this->db_arrays['posts'][count($this->db_arrays['posts'])-1][$service_array['userId']],
                    'uName' => 'Author_'.$this->db_arrays['posts'][count($this->db_arrays['posts'])-1][$service_array['userId']],
                    'uEmail' => 'author'.$this->db_arrays['posts'][count($this->db_arrays['posts'])-1][$service_array['userId']].'@example.com',
                    'IDut' => 1
                );
                
            } else {
                // записываем идентификатор поста в массив содержимого постов
                $this->db_arrays['posts_bodies'][count($this->db_arrays['posts'])-1][$service_array['id']] = $this->db_arrays['posts'][count($this->db_arrays['posts'])-1][$service_array['id']];
                // записываем текст поста в массив содержимого постов
                $this->db_arrays['posts_bodies'][count($this->db_arrays['posts'])-1][$service_array[$field_name]] = $value;
            }
        }
        return True;
    }
}


/**
 * Класс для обработки комментариев
 */
class Comment extends Common
{
    private function get_last_user_id()
    {
        // получение последнего в таблице идентификатора пользователя
        $last_user_id = 0;
        try{
            $sql = "SELECT max(IDu) as last_id FROM `users`;";
            $stmt = self::$pdo->prepare($sql);
            $stmt->execute();
            $last_user_id = (int)$stmt->fetch()['last_id'];
        } catch (Exception $e){
            echo "<p>Sampling error from the database: $e</p>";
            return False;
        }
        return $last_user_id;
    }

    protected function make_arrays_for_database():bool
    {
        $this->db_arrays = array('users' => array(), 'comments' => array());
        $service_array = array('postId' => 'IDp','id' => 'IDc','name' => 'uName','email' => 'uEmail', 'body' => 'cBody');
        foreach ($this->data as $comment_content) {
            // формируем вложенные массивы для постов и их содержимого
            $this->db_arrays['comments'][] = array();
            $conditions = array();
            $conditions[0] = $this->check_object_in_database(new Post(), 'IDp', (int)$comment_content['postId']);
            $conditions[1] = $this->check_object_in_database($this, 'IDc', (int)$comment_content['id']);
            if ($conditions[0] && !$conditions[1])
            {
                $this->make_content($comment_content, $service_array);
            } elseif ($conditions[0] && $conditions[1])
            {
                continue;
            } else {
                $error_text = "Post with ID ". $comment_content['postId'] ." does not exists";
                throw new Exception($error_text, 1);
            }
        }
        return True;
    }

    private function make_content(array $comment_content, array $service_array):bool
    {
        $new_user_id = $this->get_last_user_id()+1;
        foreach ($comment_content as $field_name => $value) {
            // формируем массив пользователя перегружая по идентификатору
            $this->db_arrays['users'][$comment_content['email']] = array(
                'IDu' => $new_user_id+count($this->db_arrays['users'])-1,
                $service_array['name'] => $comment_content['name'],
                $service_array['email'] => $comment_content['email'],
                'IDut' => 2
                );
            // формируем массив содержимого комментариев
            if ($field_name != "name" && $field_name != "email"){
                $this->db_arrays['comments'][count($this->db_arrays['comments'])-1][$service_array[$field_name]] = $value;
                $this->db_arrays['comments'][count($this->db_arrays['comments'])-1]['IDu'] = $this->db_arrays['users'][$comment_content['email']]['IDu'];
            }
        }
        return True;
    }
}


interface DTO
{
    public function get_posts(): array;
    public function upload_posts(): bool;
    public function get_comments(): array;
    public function clear_database(): bool;
    public function upload_comments(): bool;
    public function get_post(int $id): array;
    public function get_user(int $id): array;
    public function get_post_body(int $id): array;
    public function set_posts_url(string $url);
    public function set_comments_url(string $url);
    public function get_comment_to_post(int $id): array;
    public function search_in_comments_body(string $search_text): array;
}

class Blog extends Common implements DTO
{
    private $json_posts_url = 'https://jsonplaceholder.typicode.com/posts';
    private $json_comments_url = 'https://jsonplaceholder.typicode.com/comments';

    public function get_posts(): array
    {
        return $this->get_data_from_db(array('posts'), ['*'], array());
    }
    public function upload_posts(): bool
    {
        $posts = new Post();
        if ($posts->upload_data($this->json_posts_url))
        {
            echo "The posts were successfully uploaded to the database from '". $this->json_posts_url ."'!<br>";
            return True;
        } else {
            echo "The posts were not uploaded to the database from '". $this->json_posts_url ."'!<br>";
            return False;
        }
        return True;
    }
    public function get_comments(): array
    {
        return $this->get_data_from_db(array('comments'), ['*'], array());
    }
    public function clear_database(): bool
    {
        return $this->clear_database_tables();
    }
    public function upload_comments(): bool
    {
        $comments = new Comment();
        if ($comments->upload_data($this->json_comments_url))
        {
            echo "The comments were successfully uploaded to the database from '". $this->json_posts_url ."'!<br>";
            return True;
        } else {
            echo "The comments were not uploaded to the database from '". $this->json_posts_url ."'!<br>";
            return False;
        }
        return True;
    }

    public function get_post(int $id): array
    {
        try
        {
            $post_data = $this->get_data_from_db(array('posts'), ['*'], array('IDp' => $id))[0];
            if (!$post_data){
                throw new Exception("Post not found!", 1);
            }
            return $post_data;
        } catch (Exception $e) {
            return array('IDp' => 'Error', 'IDu' => 'Error', 'pTitle' => 'Error');
        }
    }
    public function get_user(int $id): array
    {
        try
        {
            $post_data = $this->get_data_from_db(array('users'), ['uName', 'uEmail'], array('IDu' => $id))[0];
            if (!$post_data){
                throw new Exception("User not found!", 1);
            }
            return $post_data;
        } catch (Exception $e) {
            return array('IDu' => 'Error', 'uEmail' => 'Error', 'uName' => 'Error');
        }
    }
    public function get_post_body(int $id): array
    {
        try
        {
            $post_data = $this->get_data_from_db(array('posts_bodies'), ['*'], array('IDp' => $id))[0];
            if (!$post_data){
                throw new Exception("Post not found!", 1);
            }
            return $post_data;
        } catch (Exception $e) {
            // echo "<p>Post not found.</p>";
            return array('IDp' => 'Error', 'pbText' => 'Error');
        }
    }
    public function set_posts_url(string $url)
    {
        $this->json_posts_url = $url;
    }
    public function set_comments_url(string $url)
    {
        $this->json_comments_url = $url;
    }
    public function get_comment_to_post(int $id): array
    {
        return $this->get_data_from_db(array('comments'), ['*'], array('IDp' => $id));
    }
    public function search_in_comments_body(string $search_text): array
    {
        return $this->get_searching_data($search_text);
    }
}

$blog = new Blog();
if ($_GET && $_GET['upload']){
    $posts_url = $_GET['posts_url'];
    $comments_url = $_GET['comments_url'];
    if (strlen($posts_url)>0){
        $blog->set_posts_url($posts_url);
    }
    if (strlen($comments_url)>0){
        $blog->set_comments_url($comments_url);
    }
    $blog->upload_posts();
    $blog->upload_comments();
    refreshPage('objects.php', 3);
}
