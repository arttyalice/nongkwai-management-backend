<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->group('/contact', function() {
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
                        c.contact_id, c.contact_titlename, c.contact_firstname, c.contact_lastname,
                        c.contact_phone, c.contact_relation, c.patient_id, per.person_titlename, per.person_firstname, per.person_lastname
                    FROM contact as c
                        LEFT JOIN patient as p on c.patient_id = p.patient_id
                        LEFT JOIN person as per on p.id_card = per.id_card ";
            
            if ($search != null) {
                $sql .= "WHERE p.id_card LIKE '$search' OR ".
                "contact_firstname LIKE '$search' OR ".
                "contact_lastname LIKE '$search' OR ".
                "contact_phone LIKE '$search' OR ".
                "person_firstname LIKE '$search' OR ".
                "person_lastname LIKE '$search' ";
            }
            $sql .= "ORDER BY c.contact_id desc ".
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
            $stm = $db->query("SELECT COUNT(contact_id) as length FROM contact");
            $length = $stm->fetch(PDO::FETCH_ASSOC);
            
            header('Content-type: application/json;');
            return $res->withJSON($length, 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
    $this->get('/get/one/{ctID}', function(Request $req, Response $res, $args) {
        try {
            $ctID = $args['ctID'];
            $sql = "SELECT *
                    FROM contact as c
                    WHERE contact_id = $ctID";
            $db = new db();
            $db = $db->connect();
            $stm = $db->query($sql);
            $users = $stm->fetch(PDO::FETCH_ASSOC);
            
            header('Content-type: application/json;');
            return $res->withJSON($users, 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
    $this->get('/get/patient/{patient_id}', function(Request $req, Response $res, $args) {
        $patient_id = $args['patient_id'];
        $sql = "SELECT
                    c.contact_id, c.contact_titlename, c.contact_firstname, c.contact_lastname,
                    c.contact_addNum, c.contact_addMoo, c.contact_addSoi, c.contact_addRoad,
                    c.contact_addVillage, c.SDTid, c.Did, c.Pid,
                    pv.Pname_th, dt.Dname_th, sdt.SDTname_th, sdt.SDTzipcode,
                    c.contact_phone, c.contact_relation, c.patient_id
                FROM contact as c
                    LEFT JOIN province as pv on c.Pid = pv.Pid
                    LEFT JOIN district as dt on c.Did = dt.Did
                    LEFT JOIN subdistrict as sdt on c.SDTid = sdt.SDTid
                WHERE c.patient_id = $patient_id";
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

        $contact_titlename = $req->getParam('contact_titlename');
        $contact_firstname = $req->getParam('contact_firstname');
        $contact_lastname = $req->getParam('contact_lastname');
        $contact_addNum = $req->getParam('contact_addNum');
        $contact_addMoo = $req->getParam('contact_addMoo');
        $contact_addSoi = $req->getParam('contact_addSoi');
        $contact_addRoad = $req->getParam('contact_addRoad');
        $contact_addVillage = $req->getParam('contact_addVillage');
        $SDTid = $req->getParam('SDTid');
        $Did = $req->getParam('Did');
        $Pid = $req->getParam('Pid');
        $contact_phone = $req->getParam('contact_phone');
        $contact_relation = $req->getParam('contact_relation');
        $patient_id = $req->getParam('patient_id');

        $sql = "INSERT INTO contact
        (
            contact_titlename, contact_firstname, contact_lastname,
            contact_addNum, contact_addMoo, contact_addSoi, contact_addRoad,
            contact_addVillage, SDTid,
            Did, Pid, contact_phone, contact_relation, patient_id
        )
        VALUES
        (
            '$contact_titlename', '$contact_firstname', '$contact_lastname',
            '$contact_addNum', $contact_addMoo, '$contact_addSoi', $contact_addRoad,
            '$contact_addVillage', $SDTid, $Did, $Pid,
            '$contact_phone', '$contact_relation', $patient_id
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
    $this->get('/delete/{contact_id}', function(Request $req, Response $res, $args) {
        $contact_id = $args['contact_id'];
        $sql = "DELETE FROM contact WHERE contact_id = $contact_id";
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
    $this->post('/update/{contact_id}', function(Request $req, Response $res, $args) {
        $db = new db();
        $db = $db->connect();
        $isBegin = $db->beginTransaction();

        $contact_id = $args['contact_id'];
        $contact_titlename = $req->getParam('contact_titlename');
        $contact_firstname = $req->getParam('contact_firstname');
        $contact_lastname = $req->getParam('contact_lastname');
        $contact_addNum = $req->getParam('contact_addNum');
        $contact_addMoo = $req->getParam('contact_addMoo');
        $contact_addSoi = $req->getParam('contact_addSoi');
        $contact_addRoad = $req->getParam('contact_addRoad');
        $contact_addVillage = $req->getParam('contact_addVillage');
        $SDTid = $req->getParam('SDTid');
        $Did = $req->getParam('Did');
        $Pid = $req->getParam('Pid');
        $contact_phone = $req->getParam('contact_phone');
        $contact_relation = $req->getParam('contact_relation');
        $patient_id = $req->getParam('patient_id');

        $sql = "UPDATE contact
            SET
                contact_titlename = '$contact_titlename',
                contact_firstname='$contact_firstname',
                contact_lastname='$contact_lastname',
                contact_addNum='$contact_addNum',
                contact_addMoo=$contact_addMoo,
                contact_addSoi='$contact_addSoi',
                contact_addRoad='$contact_addRoad',
                contact_addVillage='$contact_addVillage',
                SDTid=$SDTid,
                Did=$Did,
                Pid=$Pid,
                contact_phone='$contact_phone',
                contact_relation='$contact_relation',
                patient_id=$patient_id
            WHERE contact_id = $contact_id";
        try {
            $db->exec($sql);
            $db->commit();
            header('Content-type: application/json');
            return $res->withJSON(array('success' => true), 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            $db->rollBack();
            return $res->withJSON(array('success' => false, 'description' => $err->getMessage(), 'query' => $sql), 500, JSON_UNESCAPED_UNICODE);
        }
    });
});
