<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->group('/elder', function() {
    $this->get('/get/all', function(Request $req, Response $res) {
        try {
            $page = $req->getQueryParam('page');
            $size = $req->getQueryParam('size');
            $search = $req->getQueryParam('search');
            $sql = "SELECT
                e.elders_id, e.elders_info, e.elders_detail, e.id_card, 
                e.getmoney_id, p.person_titlename, p.person_firstname, 
                p.person_lastname, p.person_phone, p.person_birthday
            FROM elders AS e
                INNER JOIN person as p on e.id_card = p.id_card
            ";
            if ($size != 'all') {
                $offset = (int)$page * (int)$size;
                if ($page == null || $size == null) {
                    return $res->withJSON(array('status' => $size), 400);
                }
                if ($search != null) {
                    $sql .= "WHERE e.elders_info LIKE '%$search%' OR ".
                    "e.elders_detail LIKE '%$search%' OR ".
                    "e.id_card LIKE '%$search%' OR ".
                    "p.person_titlename LIKE '%$search%' OR ".
                    "p.person_firstname LIKE '%$search%' OR ".
                    "p.person_lastname LIKE '%$search%' ";
                }
                $sql .= "ORDER BY e.elders_id desc ".
                "LIMIT $offset, $size";
            } else {
                $sql .= "ORDER BY e.elders_id desc ";
            }
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
            $stm = $db->query("SELECT COUNT(elders_id) as length FROM elders");
            $length = $stm->fetch(PDO::FETCH_ASSOC);
            
            header('Content-type: application/json;');
            return $res->withJSON($length, 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
    $this->get('/get/one/{id_card}', function(Request $req, Response $res, $args) {
        $id_card = $args['id_card'];
        $sql = "SELECT
            e.elders_id, e.elders_info, e.elders_detail, e.id_card, 
            e.getmoney_id, gm.getmoney_name, p.person_titlename, p.person_firstname, 
            p.person_lastname, p.person_phone, p.person_birthday
        FROM elders AS e
            INNER JOIN person as p on e.id_card = p.id_card
            LEFT JOIN get_money as gm on e.getmoney_id = gm.getmoney_id
        WHERE e.id_card = $id_card LIMIT 1";
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
        $elders_info = $req->getParam('elders_info');
        $elders_detail = $req->getParam('elders_detail');
        $getmoney_id = $req->getParam('getmoney_id');
        $user_id = $req->getParam('user_id');

        $sql = "INSERT INTO elders
                    (elders_info, elders_detail, elders_type, id_card, user_id, getmoney_id)
                VALUES
                    ('$elders_info', '$elders_detail', '$elders_type', '$id_card', $user_id, $getmoney_id)";
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
    $this->post('/update/{edID}', function(Request $req, Response $res, $args) {
        $db = new db();
        $db = $db->connect();
        $isBegin = $db->beginTransaction();

        $edID = $args['edID'];
        $id_card = $req->getParam('id_card');
        $elders_info = $req->getParam('elders_info');
        $elders_detail = $req->getParam('elders_detail');
        $getmoney_id = $req->getParam('getmoney_id');
        $user_id = $req->getParam('user_id');

        $sql = "UPDATE elders
            SET
                id_card = '$id_card',
                elders_info = '$elders_info',
                elders_detail='$elders_detail',
                getmoney_id='$getmoney_id',
                user_id='$user_id'
            WHERE elders_id = $edID";
        try {
            $db->exec($sql);
            $db->commit();
            header('Content-type: application/json');
            return $res->withJSON(array('success' => $sql), 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            $db->rollBack();
            return $res->withJSON(array('success' => false, 'description' => $err->getMessage()), 500, JSON_UNESCAPED_UNICODE);
        }
    });
});
