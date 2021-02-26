<?php 
include 'config.php';
$pdo = new PDO(
                'mysql:dbname=' . dbname . ';host=' . dbhost,
                dblogin,
                dbpassword
            );

function file_get_contents_curl( $url ) {
    // функция для получения объекта из json-данных с использованием ssl

    $ch = curl_init();

    curl_setopt( $ch, CURLOPT_AUTOREFERER, TRUE );
    curl_setopt( $ch, CURLOPT_HEADER, 0 );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $ch, CURLOPT_URL, $url );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, TRUE );

    // декодируем json
    $data = json_decode(curl_exec( $ch ), true);
    curl_close( $ch );

    return $data;

}

function make_data_from_posts_object($posts_object){
    // формирование массивов из json-данных о постах

    $posts_array = array(); // массив данных постов
    $posts_bodies_array = array(); // массив данных содержимого постов
    $authors_array = array(); // массив авторов

    // сервисный массив для преобразования ключей массивов в ключи столбцов таблиц базы данных
    $service_array = array('userId' => 'IDu','id' => 'IDp','title' => 'pTitle','body' => 'pbText');
    // перебираем массив постов
    foreach ($posts_object as $key => $value) {
        // формируем вложенные массивы для постов и их содержимого
        $posts_array[] = array();
        $posts_bodies_array[] = array();
        foreach ($value as $k => $v) {
            // формирум массив постов и авторов, иначе формируем массив содержимого постов
            if ($k != "body"){
                // добавляем поля в массив постов
                $posts_array[count($posts_array)-1][$service_array[$k]] = $v;
                // формируем массив пользователя перегружая по идентификатору
                $authors_array[$posts_array[count($posts_array)-1][$service_array['userId']]] = array(
                    'IDu' => $posts_array[count($posts_array)-1][$service_array['userId']],
                    'uName' => 'Author_'.$posts_array[count($posts_array)-1][$service_array['userId']],
                    'uEmail' => 'author'.$posts_array[count($posts_array)-1][$service_array['userId']].'@example.com',
                    'IDut' => 1
                );
            } else {
                // записываем идентификатор поста в массив содержимого постов
                $posts_bodies_array[count($posts_array)-1]['IDpb'] = $posts_array[count($posts_array)-1][$service_array['id']];
                // записываем текст поста в массив содержимого постов
                $posts_bodies_array[count($posts_array)-1][$service_array[$k]] = $v;
            }
        }
    }
    // возвращаем массив 
    return array('users' => $authors_array, 'posts' => $posts_array, 'posts_bodies' => $posts_bodies_array);
}

function insert_posts_data_to_database($pdo, $key, $data_array_item){
    // добавление данных в базу

    switch ($key) {
        // выбираем действие по ключу для гибкости в отслеживании проблем
        case 'posts':
            {
                try{
                    // подготавливаем запрос для вставки
                    $sql = "INSERT INTO `" . $key . "`(IDu, IDp, pTitle) VALUES (?, ?, ?);";
                    $stmt = $pdo->prepare($sql);
                    $pdo->beginTransaction();
                    // пробегаемся по данным в массиве и добавляем их в соответствующую таблицу
                    foreach ($data_array_item as $data) {
                        $stmt->execute(array_values($data));
                    }
                    $pdo->commit();
                } catch (Exception $e){
                    echo "<p>Error: $e</p>";
                    return False;
                }
                return True;
            break;
            }
        case 'posts_bodies':
            {
                try{
                    // подготавливаем запрос для вставки
                    $sql = "INSERT INTO `" . $key . "`(IDp, pbText) VALUES (?, ?);";
                    $stmt = $pdo->prepare($sql);
                    $pdo->beginTransaction();
                    // пробегаемся по данным в массиве и добавляем их в соответствующую таблицу
                    foreach ($data_array_item as $data) {
                        $stmt->execute(array_values($data));
                    }
                    $pdo->commit();
                } catch (Exception $e){
                    echo "<p>Error: $e</p>";
                    return False;
                }
                return True;
            break;
            }
        case 'users':
            {
                try{
                    // подготавливаем запрос для вставки
                    $sql = "INSERT INTO `" . $key . "`(IDu, uName, uEmail, IDut) VALUES (?, ?, ?, ?);";
                    $stmt = $pdo->prepare($sql);
                    $pdo->beginTransaction();
                    // пробегаемся по данным в массиве и добавляем их в соответствующую таблицу
                    foreach ($data_array_item as $data) {
                        $stmt->execute(array_values($data));
                    }
                    $pdo->commit();
                } catch (Exception $e){
                    echo "<p>Error: $e</p>";
                    return False;
                }
                return True;
            break;
            }
        
        default:
            return False;
            break;
    }
    return False;
}

function processing_posts_data($pdo, $url){

    $json_posts_url = 'https://jsonplaceholder.typicode.com/posts';
    if (strlen($url) > 0){
        $json_posts_url = $url;
    }
    $posts_object = file_get_contents_curl($json_posts_url);
    $posts_data = make_data_from_posts_object($posts_object);
    $result = True;
    foreach ($posts_data as $key => $value) {
        $result *= insert_posts_data_to_database($pdo, $key, $value);
    }
    if ($result){
        echo "Posts data upload completed!<br>";
    } else {
        echo "Loading error!<br>";
    }
}

function get_last_user_id(){
    // получение последнего в таблице идентификатора пользователя
    $last_user_id = 0;
    $pdo = new PDO(
                'mysql:dbname=' . dbname . ';host=' . dbhost,
                dblogin,
                dbpassword
            );
    try{
        $sql = "SELECT max(IDu) as last_id FROM `users`;";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $last_user_id = (int)$stmt->fetch()['last_id'];
    } catch (Exception $e){
        echo "<p>Error: $e</p>";
        return False;
    }
    return $last_user_id;
}

function make_data_from_comments_object($comments_object){
    // формирование массивов из json-данных о комментариях

    $comments_array = array(); // массив данных постов
    $authors_array = array(); // массив авторов

    // сервисный массив для преобразования ключей массивов в ключи столбцов таблиц базы данных
    $service_array = array('postId' => 'IDp','id' => 'IDc','name' => 'uName','email' => 'uEmail', 'body' => 'cBody');

    $new_user_id = get_last_user_id()+1;
    // перебираем массив постов
    foreach ($comments_object as $key => $value) {
        // формируем вложенные массивы для постов и их содержимого
        $comments_array[] = array();
        foreach ($value as $k => $v) {
            // формирум массив постов и авторов, иначе формируем массив содержимого постов
            $authors_array[$value['email']] = array(
                'IDu' => $new_user_id+count($authors_array)-1,
                $service_array['name'] => $value['name'],
                $service_array['email'] => $value['email'],
                'IDut' => 2
                );
            if ($k != "name" && $k != "email"){
                // добавляем поля в массив постов
                $comments_array[count($comments_array)-1][$service_array[$k]] = $v;
                $comments_array[count($comments_array)-1]['IDu'] = $authors_array[$value['email']]['IDu'];
                // формируем массив пользователя перегружая по идентификатору
            }
        }
    }
    return array('users' => $authors_array, 'comments' => $comments_array);
}

function insert_comments_data_to_database($pdo, $key, $data_array_item){
    // добавление данных в базу
    switch ($key) {
        // выбираем действие по ключу для гибкости в отслеживании проблем
        case 'comments':
            {
                try{
                    // подготавливаем запрос для вставки
                    $sql = "INSERT INTO `" . $key . "`(IDc, IDp, IDu, cBody) VALUES (:IDc, :IDp, :IDu, :cBody);";
                    $stmt = $pdo->prepare($sql);
                    $pdo->beginTransaction();
                    // пробегаемся по данным в массиве и добавляем их в соответствующую таблицу
                    foreach ($data_array_item as $data) {
                        $stmt->execute($data);
                    }
                    $pdo->commit();
                } catch (Exception $e){
                    echo "<p>Error: $e</p>";
                    return False;
                }
                return True;
            break;
            }
        case 'users':
            {
                try{
                    // подготавливаем запрос для вставки
                    $sql = "INSERT INTO `" . $key . "`(IDu, uName, uEmail, IDut) VALUES (:IDu, :uName, :uEmail, :IDut);";
                    $stmt = $pdo->prepare($sql);
                    $pdo->beginTransaction();
                    // пробегаемся по данным в массиве и добавляем их в соответствующую таблицу
                    foreach ($data_array_item as $data) {
                        $stmt->execute($data);
                    }
                    $pdo->commit();
                } catch (Exception $e){
                    echo "<p>Error: $e</p>";
                    return False;
                }
                return True;
            break;
            }
        
        default:
            return False;
            break;
    }
    return False;
}

function processing_comments_data($pdo, $url){
    $json_comments_url = 'https://jsonplaceholder.typicode.com/comments';
    if (strlen($url) > 0){
        $json_comments_url = $url;
    }
    $comments_object = file_get_contents_curl($json_comments_url);
    $comments_data = make_data_from_comments_object($comments_object);
    $result = True;
    foreach ($comments_data as $key => $value) {
        $result *= insert_comments_data_to_database($pdo, $key, $value);
    }
    if ($result){
        echo "Comments data upload completed!<br>";
    } else {
        echo "Loading error!<br>";
    }
}

function refreshPage($url, $time = 0){
    // функция обновления/перенаправления страницы
    echo '<META HTTP-EQUIV="Refresh" Content="'. $time .'; URL='.$url.'">'; 
}

if ($_GET){
    $posts_url = $_GET['posts_url'];
    $comments_url = $_GET['comments_url'];
    processing_posts_data($pdo, $posts_url);
    processing_comments_data($pdo, $comments_url);
    refreshPage('./functions.php', 3);
}


 ?>