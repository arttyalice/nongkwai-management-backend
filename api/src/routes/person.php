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
            "p.id_card, p.person_titlename, p.person_firstname, p.person_lastname, p.person_birthday, p.person_phone, ".
            "d.disability_id, e.elders_id, pt.patient_id ".
            "FROM person as p ".
            "LEFT JOIN disability as d on p.id_card = d.id_card ".
            "LEFT JOIN elders as e on p.id_card = e.id_card ".
            "LEFT JOIN patient as pt on p.id_card = pt.id_card ";
            
            if ($search != null) {
                $sql .= "WHERE p.id_card LIKE '%$search%' OR ".
                "p.person_titlename LIKE '%$search%' OR ".
                "p.person_firstname LIKE '%$search%' OR ".
                "p.person_lastname LIKE '%$search%' OR ".
                "p.person_phone LIKE '%$search%' OR ".
                "p.person_birthday LIKE '%$search%' ";
            }
            $sql .= "ORDER BY p.id_card desc ".
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
    $this->get('/get/list', function(Request $req, Response $res) {
        try {
            $sql = "SELECT ".
            "p.id_card, CONCAT(person_titlename, ' ',person_firstname, ' ', person_lastname) as name, ".
            "d.disability_id, e.elders_id, pt.patient_id ".
            "FROM person as p ".
            "LEFT JOIN disability as d on p.id_card = d.id_card ".
            "LEFT JOIN elders as e on p.id_card = e.id_card ".
            "LEFT JOIN patient as pt on p.id_card = pt.id_card ";
            $sql .= "ORDER BY p.id_card desc ";
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
        $sql = "SELECT p.*, d.disability_id, e.elders_id, pt.patient_id, ".
            "pv.Pname_th, dt.Dname_th, sdt.SDTname_th, sdt.SDTzipcode ".
            "FROM person as p ".
                "LEFT JOIN disability as d on p.id_card = d.id_card ".
                "LEFT JOIN elders as e on p.id_card = e.id_card ".
                "LEFT JOIN patient as pt on p.id_card = pt.id_card ".
                "LEFT JOIN province as pv on p.Pid = pv.Pid ".
                "LEFT JOIN district as dt on p.Did = dt.Did ".
                "LEFT JOIN subdistrict as sdt on p.STDid = sdt.SDTid ".
            "WHERE p.id_card = '$pID'";
        try {
            $db = new db();
            $db = $db->connect();
            $stm = $db->query($sql);
            $user = $stm->fetch(PDO::FETCH_ASSOC);
            $user['person_type'] = array();
            if ($user['id_card']) {
                $pStm = $db->query("SELECT patient_id FROM patient WHERE id_card = $pID LIMIT 1");
                $tmpP = $pStm->fetch(PDO::FETCH_ASSOC);
                if (!empty($tmpP)) array_push($user['person_type'], 1);

                $dStm = $db->query("SELECT disability_id FROM disability WHERE id_card = $pID LIMIT 1");
                $tmpD = $dStm->fetch(PDO::FETCH_ASSOC);
                if (!empty($tmpD)) array_push($user['person_type'], 2);

                $eStm = $db->query("SELECT elders_id FROM elders WHERE id_card = $pID LIMIT 1");
                $tmpE = $eStm->fetch(PDO::FETCH_ASSOC);
                if (!empty($tmpE)) array_push($user['person_type'], 3);
            } else {
                return $res->withJSON(array('success' => false, 'description' => "person not found"), 400, JSON_UNESCAPED_UNICODE);
            }
            
            header('Content-type: application/json');
            return $res->withJSON($user, 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
    $this->get('/get/length', function(Request $req, Response $res, $args) {
        try {
            $db = new db();
            $db = $db->connect();
            $stm = $db->query("SELECT COUNT(id_card) as length FROM person");
            $length = $stm->fetch(PDO::FETCH_ASSOC);
            
            header('Content-type: application/json;');
            return $res->withJSON($length, 200, JSON_UNESCAPED_UNICODE);
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
        $person_type = json_decode($req->getParam('person_type'));
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
        for ($i=0; $i < count($person_type); $i++) {
            $ele = $person_type[$i];
            if ($ele == 1) {
                $db->exec("INSERT INTO patient (id_card, user_id) VALUES ('$id_card', $user_id)");
            } elseif ($ele == 2) {
                $db->exec("INSERT INTO disability (id_card, user_id) VALUES ('$id_card', $user_id)");
            } else {
                $db->exec("INSERT INTO elders (id_card, user_id) VALUES ('$id_card', $user_id)");
            }
        }
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
        $sql = array(
            "DELETE FROM person WHERE id_card = $pID",
            "DELETE FROM allowance WHERE id_card = $pID",
            "DELETE FROM disability WHERE id_card = $pID",
            "DELETE FROM elders WHERE id_card = $pID",
            "DELETE FROM patient WHERE id_card = $pID",
            "DELETE FROM treatment WHERE id_card = $pID",
            "DELETE FROM visiting WHERE id_card = $pID"
        );
        try {
            $db = new db();
            $db = $db->connect();
            foreach ($sql as $key => $value) {
                $db->exec($value);
            }
            
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

        $id_card = $args['id_card'];
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
        $person_type = json_decode($req->getParam('person_type'));
        $old_person_type = json_decode($req->getParam('old_person_type'));

        $sql = "UPDATE person
            SET
                id_card = '$id_card',
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
            WHERE id_card = '$id_card'";
        try {
            $db->exec($sql);
            // $db->exec("DELETE FROM patient WHERE id_card LIKE '$id_card'");
            // $db->exec("DELETE FROM disability WHERE id_card LIKE '$id_card'");
            // $db->exec("DELETE FROM elders WHERE id_card LIKE '$id_card'");

            // for ($i=0; $i < count($person_type); $i++) {
            //     $ele = $person_type[$i];
            //     if ($ele == 1) {
            //         $db->exec("INSERT INTO patient (id_card, user_id) VALUES ('$id_card', $user_id)");
            //     } elseif ($ele == 2) {
            //         $db->exec("INSERT INTO disability (id_card, user_id) VALUES ('$id_card', $user_id)");
            //     } else {
            //         $db->exec("INSERT INTO elders (id_card, user_id) VALUES ('$id_card', $user_id)");
            //     }
            // }

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
