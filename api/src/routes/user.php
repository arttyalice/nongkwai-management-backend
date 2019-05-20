<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->group('/user', function() {
    $this->get('/get/all', function(Request $req, Response $res) {
        
        try {
            $page = $req->getQueryParam('page');
            $size = $req->getQueryParam('size');
            $search = $req->getQueryParam('search');
            $offset = (int)$page * (int)$size;
            if ($page == null || $size == null) {
                return $res->withJSON(array('status' => $size), 400);
            }
            $sql = "SELECT ".
            "user_id, fistname, lastname, position_id, phone, user_status ".
            "FROM user ";
            
            if ($search != null) {
                $sql .= "WHERE user_id LIKE '%$search%' OR ".
                "fistname LIKE '%$search%' OR ".
                "lastname LIKE '%$search%' OR ".
                "phone LIKE '%$search%' OR ".
                "user_status LIKE '%$search%' ";
            }
            $sql .= "ORDER BY user_id desc ".
            "LIMIT $offset, $size";
            $db = new db();
            $db = $db->connect();
            $stm = $db->query($sql);
            $users = $stm->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-type: application/json;');
            return $res->withJSON($users, 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
    $this->get('/get/length', function(Request $req, Response $res, $args) {
        try {
            $db = new db();
            $db = $db->connect();
            $stm = $db->query("SELECT COUNT(user_id) as length FROM user");
            $length = $stm->fetch(PDO::FETCH_ASSOC);
            
            header('Content-type: application/json;');
            return $res->withJSON($length, 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
    $this->get('/get/one/{uID}', function(Request $req, Response $res, $args) {
        $uID = $args['uID'];
        $sql = "SELECT * FROM user WHERE user_id = $uID LIMIT 1";
        try {
            $db = new db();
            $db = $db->connect();
            $stm = $db->query($sql);
            $user = $stm->fetch(PDO::FETCH_ASSOC);
            
            header('Content-type: application/json');
            return $res->withJSON($user, 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
    $this->get('/get/position', function(Request $req, Response $res) {
        $sql = "SELECT * FROM position";
        try {
            $db = new db();
            $db = $db->connect();
            $stm = $db->query($sql);
            $user = $stm->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-type: application/json');
            return $res->withJSON($user, 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
    $this->get('/get/status', function(Request $req, Response $res) {
        $sql = "SELECT * FROM position";
        try {
            $db = new db();
            $db = $db->connect();
            $stm = $db->query($sql);
            $user = $stm->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-type: application/json');
            return $res->withJSON($user, 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
    $this->post('/insert', function(Request $req, Response $res) {
        $db = new db();
        $db = $db->connect();
        $isBegin = $db->beginTransaction();

        $user_name = $req->getParam('user_name');
        $user_pass = $req->getParam('user_pass');
        $first_name = $req->getParam('first_name');
        $last_name = $req->getParam('last_name');
        $position_id = $req->getParam('position_id');
        $phone = $req->getParam('phone');
        $user_status = $req->getParam('user_status');
        $sql = "INSERT INTO 
        user(username, password, fistname, lastname, position_id, phone, user_status)".
        " VALUES ('$user_name', '$user_pass', '$first_name', '$last_name', $position_id, '$phone', $user_status)";
        try {
            $db->exec($sql);
            $db->commit();
            header('Content-type: application/json');
            return $res->withJSON(array('success' => true), 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            $db->rollBack();
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
    $this->get('/delete/{uID}', function(Request $req, Response $res, $args) {
        $userID = $args['uID'];
        $sql = "DELETE FROM user WHERE user_id = $userID";
        try {
            $db = new db();
            $db = $db->connect();
            $stm = $db->query($sql);
            $user = $stm->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-type: application/json');
            return $res->withJSON($user, 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
    $this->post('/update/{uID}', function(Request $req, Response $res, $args) {
        $db = new db();
        $db = $db->connect();
        $isBegin = $db->beginTransaction();

        $user_id = $args['uID'];
        $user_name = $req->getParam('user_name');
        $user_pass = $req->getParam('user_pass');
        $first_name = $req->getParam('first_name');
        $last_name = $req->getParam('last_name');
        $position_id = $req->getParam('position_id');
        $phone = $req->getParam('phone');
        $user_status = $req->getParam('user_status');
        $sql = "UPDATE user
            SET 
                username = '$user_name',
                password = '$user_pass',
                fistname='$first_name',
                lastname='$last_name',
                position_id=$position_id,
                phone='$phone',
                user_status=$user_status
            WHERE user_id = $user_id";
        try {
            $db->exec($sql);
            $db->commit();
            header('Content-type: application/json');
            return $res->withJSON(array('success' => true), 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            $db->rollBack();
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
    $this->post('/login', function(Request $req, Response $res) {
        $uname = $req->getParam('user_name');
        $upass = $req->getParam('user_pass');
        $sql = "SELECT * FROM user WHERE username = '$uname' AND password = '$upass' LIMIT 1";
        try {
            $db = new db();
            $db = $db->connect();
            $stm = $db->query($sql);
            $user = $stm->fetch(PDO::FETCH_ASSOC);
            
            if ($user == false) {
                $resData = array('success' => false);
                return $res->withJSON($resData, 200, JSON_UNESCAPED_UNICODE);
            }
            
            header('Content-type: application/json');
            return $res->withJSON(array('success' => true, 'user' => $user), 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
});
