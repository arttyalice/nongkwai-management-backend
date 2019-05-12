<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->group('/visiting', function() {
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
            "p.id_card, p.person_titlename, p.person_firstname, p.person_lastname, p.person_birthday, p.person_phone, p.person_lat, p.person_lng, d.disability_id, e.elders_id, pt.patient_id ".
            "FROM person as p ".
            "LEFT JOIN disability as d on p.id_card = d.id_card ".
            "LEFT JOIN elders as e on p.id_card = e.id_card ".
            "LEFT JOIN patient as pt on p.id_card = pt.id_card ";
            
            if ($search != null) {
                $sql .= "WHERE id_card LIKE '%$search%' OR ".
                "person_titlename LIKE '%$search%' OR ".
                "person_firstname LIKE '%$search%' OR ".
                "person_lastname LIKE '%$search%' OR ".
                "person_phone LIKE '%$search%' OR ".
                "person_birthday LIKE '%$search%' ";
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
    $this->get('/get/visiting/all/{id_card}', function(Request $req, Response $res, $args) {
        try {
            $id_card = $args["id_card"];
            $sql = "SELECT (adl.feeding + adl.grooming + adl.transfer +
                        adl.toilet + adl.mobility + adl.dressing +
                        adl.stairs + adl.bathing + adl.bowels +
                        adl.bladder) as adl_summary, v.visiting_date,
                        v.visiting_detail
                    FROM visiting_adl as adl
                        LEFT JOIN visiting as v on v.visiting_id = adl.visiting_id
                    WHERE v.id_card = '$id_card'";

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
        $sql = "SELECT ".
            "p.id_card, p.person_titlename, p.person_firstname, p.person_lastname, p.person_birthday, p.person_phone, p.person_lat, p.person_lng, d.disability_id, e.elders_id, pt.patient_id ".
            "FROM person as p ".
            "LEFT JOIN disability as d on p.id_card = d.id_card ".
            "LEFT JOIN elders as e on p.id_card = e.id_card ".
            "LEFT JOIN patient as pt on p.id_card = pt.id_card ".
            "WHERE p.id_card = $pID LIMIT 1";
        try {
            $db = new db();
            $db = $db->connect();
            $stm = $db->query($sql);
            $user = $stm->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return $res->withJSON(array('success' => false, 'description' => "person not found"), 400, JSON_UNESCAPED_UNICODE);
            }
            
            header('Content-type: application/json');
            return $res->withJSON($user, 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
    $this->get('/get/adl/summary/{adlID}', function(Request $req, Response $res, $args) {
        $adlID = $args['adlID'];
        $sql = "SELECT (adl.feeding + adl.grooming + adl.transfer +
                        adl.toilet + adl.mobility + adl.dressing +
                        adl.stairs + adl.bathing + adl.bowels +
                        adl.bladder) as adl_summary, v.visiting_date,
                        p.person_titlename, p.person_firstname, p.person_lastname
                FROM visiting_adl as adl
                    LEFT JOIN visiting as v on v.visiting_id = adl.visiting_id
                    LEFT JOIN person as p on v.id_card = p.id_card
                WHERE adlID = $adlID";
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
    $this->post('/insert/{id_card}', function(Request $req, Response $res, $args) {
        $db = new db();
        $db = $db->connect();
        $isBegin = $db->beginTransaction();

        $visiting_detail = $req->getParam("visiting_detail");
        $id_card = $args["id_card"];
        $user_id = $req->getParam("user_id");

        $feeding = $req->getParam("feeding");
        $grooming = $req->getParam("grooming");
        $transfer = $req->getParam("transfer");
        $toilet = $req->getParam("toilet");
        $mobility = $req->getParam("mobility");
        $dressing = $req->getParam("dressing");
        $stairs = $req->getParam("stairs");
        $bathing = $req->getParam("bathing");
        $bowels = $req->getParam("bowels");
        $bladder = $req->getParam("bladder");

        $sql = "INSERT INTO visiting
        (
            id_card, user_id, visiting_detail
        )
        VALUES
        (
            '$id_card', $user_id, '$visiting_detail'
        )";
        try {
            $db->exec($sql);
            $visiting_id = $db->lastInsertId();
            $adl_sql = "INSERT INTO visiting_adl
            (
                feeding, grooming, transfer, toilet, mobility, dressing, stairs, bathing, bowels, bladder, visiting_id
            )
            VALUES
            (
                $feeding,
                $grooming,
                $transfer,
                $toilet,
                $mobility,
                $dressing,
                $stairs,
                $bathing,
                $bowels,
                $bladder,
                $visiting_id
            )";
            $db->exec($adl_sql);
            $adl_id = $db->lastInsertId();
            $db->commit();
            header('Content-type: application/json');
            return $res->withJSON(array('success' => true, 'adlID' => $adl_id), 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            $db->rollBack();
            return $res->withJSON(array('success' => false, 'description' => $err->getMessage()), 500, JSON_UNESCAPED_UNICODE);
        }
    });
});
