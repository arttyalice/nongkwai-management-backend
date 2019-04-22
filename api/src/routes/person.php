<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->group('/person', function() {
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
            "id_card, person_titlename, person_firstname, person_lastname, person_birthday, person_phone ".
            "FROM person ";
            
            if ($search != null) {
                $sql .= "WHERE id_card LIKE '$search' OR ".
                "person_titlename LIKE '$search' OR ".
                "person_firstname LIKE '$search' OR ".
                "person_lastname LIKE '$search' OR ".
                "person_phone LIKE '$search' OR ".
                "person_birthday LIKE '$search' ";
            }
            $sql .= "ORDER BY id_card desc ".
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
    $this->get('/get/one/{pID}', function(Request $req, Response $res, $args) {
        $pID = $args['pID'];
        $sql = "SELECT * FROM person WHERE id_card = $pID LIMIT 1";
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
    $this->post('/insert', function(Request $req, Response $res) {
        $db = new db();
        $db = $db->connect();
        $isBegin = $db->beginTransaction();

        $id_card = $req->getParam('id_card');
        $person_titlename = $req->getParam('person_titlename');
        $person_firstname = $req->getParam('person_firstname');
        $person_lastname = $req->getParam('person_lastname');
        $person_birthday = $req->getParam('person_birthday');
        $person_nationality = $req->getParam('person_nationality');
        $person_addNum = $req->getParam('person_addNum');
        $person_addMoo = $req->getParam('person_addMoo');
        $person_addSoi = $req->getParam('person_addSoi');
        $person_addRoad = $req->getParam('person_addRoad');
        $person_addVillage = $req->getParam('person_addVillage');
        $STDid = $req->getParam('STDid');
        $Did = $req->getParam('Did');
        $Pid = $req->getParam('Pid');
        $person_phone = $req->getParam('person_phone');
        $person_status = $req->getParam('person_status');
        $person_lat = $req->getParam('person_lat');
        $person_lng = $req->getParam('person_lng');
        $user_id = $req->getParam('user_id');
        $sql = "INSERT INTO person
        (
            id_card, person_titlename, person_firstname, person_lastname,
            person_birthday, person_nationality, person_addNum, person_addMoo,
            person_addSoi, person_addRoad, person_addVillage, STDid,
            Did, Pid, person_phone, person_status, person_lat, person_lng, user_id
        )
        VALUES
        (
            '$id_card', '$person_titlename', '$person_firstname', '$person_lastname',
            '$person_birthday', '$person_nationality', '$person_addNum', $person_addMoo,
            '$person_addSoi', '$person_addRoad', '$person_addVillage', $STDid,
            $Did, $Pid, '$person_phone', '$person_status', $person_lat, $person_lng, $user_id
        )";
        try {
            $db->exec($sql);
            $db->commit();
            header('Content-type: application/json');
            return $res->withJSON(array('success' => true), 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            $db->rollBack();
            return $res->withJSON(array('success' => false, 'description' => $err->getMessage()), 500, JSON_UNESCAPED_UNICODE);
        }
    });
    $this->get('/delete/{pID}', function(Request $req, Response $res, $args) {
        $pID = $args['pID'];
        $sql = "DELETE FROM person WHERE id_card = $pID";
        try {
            $db = new db();
            $db = $db->connect();
            $stm = $db->query($sql);
            
            header('Content-type: application/json');
            return $res->withJSON(array('success' => true), 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            return $res->withJSON(array('success' => false, 'description' => $err->getMessage()), 500, JSON_UNESCAPED_UNICODE);
        }
    });
    $this->post('/update/{id_card}', function(Request $req, Response $res, $args) {
        $db = new db();
        $db = $db->connect();
        $isBegin = $db->beginTransaction();

        $new_id_card = $args['id_card'];
        $old_id_card = $req->getParam('old_id_card');
        $person_titlename = $req->getParam('person_titlename');
        $person_firstname = $req->getParam('person_firstname');
        $person_lastname = $req->getParam('person_lastname');
        $person_birthday = $req->getParam('person_birthday');
        $person_nationality = $req->getParam('person_nationality');
        $person_addNum = $req->getParam('person_addNum');
        $person_addMoo = $req->getParam('person_addMoo');
        $person_addSoi = $req->getParam('person_addSoi');
        $person_addRoad = $req->getParam('person_addRoad');
        $person_addVillage = $req->getParam('person_addVillage');
        $STDid = $req->getParam('STDid');
        $Did = $req->getParam('Did');
        $Pid = $req->getParam('Pid');
        $person_phone = $req->getParam('person_phone');
        $person_status = $req->getParam('person_status');
        $person_lat = $req->getParam('person_lat');
        $person_lng = $req->getParam('person_lng');
        $user_id = $req->getParam('user_id');

        $sql = "UPDATE person
            SET
                id_card = '$new_id_card',
                person_titlename = '$person_titlename',
                person_firstname='$person_firstname',
                person_lastname='$person_lastname',
                person_birthday='$person_birthday',
                person_nationality='$person_nationality',
                person_addNum='$person_addNum',
                person_addMoo='$person_addMoo',
                person_addSoi='$person_addSoi',
                person_addRoad='$person_addRoad',
                person_addVillage='$person_addVillage',
                STDid=$STDid,
                Did=$Did,
                Pid=$Pid,
                person_phone='$person_phone',
                person_status='$person_status',
                person_lat=$person_lat,
                person_lng=$person_lng,
                user_id=$user_id
            WHERE id_card = '$old_id_card'";
        try {
            $db->exec($sql);
            $db->commit();
            header('Content-type: application/json');
            return $res->withJSON(array('success' => true), 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            $db->rollBack();
            return $res->withJSON(array('success' => false, 'description' => $err->getMessage()), 500, JSON_UNESCAPED_UNICODE);
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
