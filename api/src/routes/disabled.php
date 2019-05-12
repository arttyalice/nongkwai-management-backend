<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->group('/disabled', function() {
    $this->get('/get/all', function(Request $req, Response $res) {
        try {
            $page = $req->getQueryParam('page');
            $size = $req->getQueryParam('size');
            $search = $req->getQueryParam('search');
            $offset = (int)$page * (int)$size;
            if ($page == null || $size == null) {
                return $res->withJSON(array('status' => $size), 400);
            }
            $sql = "SELECT
                d.disability_id, d.disability_info, d.disability_detail, d.disability_type,
                d.id_card, d.getmoney_id, p.person_titlename, p.person_firstname, 
                p.person_lastname, p.person_phone, p.person_birthday
            FROM disability AS d
                LEFT JOIN person as p on d.id_card = p.id_card
            ";
            
            if ($search != null) {
                $sql .= "WHERE d.disability_info LIKE '$search' OR ".
                "d.disability_detail LIKE '$search' OR ".
                "d.disability_type LIKE '$search' OR ".
                "d.id_card LIKE '$search' OR ".
                "p.person_titlename LIKE '$search' OR ".
                "p.person_firstname LIKE '$search' OR ".
                "p.person_lastname LIKE '$search' ";
            }
            $sql .= "ORDER BY d.disability_id desc ".
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
        $sql = "SELECT
            d.disability_id, d.disability_info, d.disability_detail, d.disability_type,
            d.id_card, d.getmoney_id, p.person_titlename, p.person_firstname,
            p.person_lastname, p.person_phone, p.person_birthday
        FROM disability AS d
            LEFT JOIN person as p on d.id_card = p.id_card
        WHERE d.id_card = $pID LIMIT 1";
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
        $disability_info = $req->getParam('disability_info');
        $disability_detail = $req->getParam('disability_detail');
        $disability_type = $req->getParam('disability_type');
        $getmoney_id = $req->getParam('getmoney_id');
        $user_id = $req->getParam('user_id');

        $sql = "INSERT INTO disability
                    (disability_info, disability_detail, disability_type, id_card, user_id, getmoney_id)
                VALUES
                    ('$disability_info', '$disability_detail', '$disability_type', '$id_card', $user_id, $getmoney_id)";
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
    $this->get('/delete/{disID}', function(Request $req, Response $res, $args) {
        $disID = $args['disID'];
        $sql = "DELETE FROM disability WHERE disability_id = $disID";
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
    $this->post('/update/{disID}', function(Request $req, Response $res, $args) {
        $db = new db();
        $db = $db->connect();
        $isBegin = $db->beginTransaction();

        $disID = $args['disID'];
        $id_card = $req->getParam('id_card');
        $disability_info = $req->getParam('disability_info');
        $disability_detail = $req->getParam('disability_detail');
        $disability_type = $req->getParam('disability_type');
        $getmoney_id = $req->getParam('getmoney_id');
        $user_id = $req->getParam('user_id');

        $sql = "UPDATE disability
            SET
                id_card = '$id_card',
                disability_info = '$disability_info',
                disability_detail='$disability_detail',
                disability_type='$disability_type',
                getmoney_id='$getmoney_id',
                user_id='$user_id'
            WHERE disability_id = $disID";
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
});
