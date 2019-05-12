<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->group('/aid', function() {
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
                pa.patient_id,
                pa.patient_habitatchoice,
                pa.patient_habitatdetail,
                pa.patient_distance,
                pa.patient_distancedetail,
                pa.patient_distancecheck,
                pa.patient_distancebecouse,
                pa.patient_residancechoice,
                pa.patient_residancedetail,
                pa.patient_sickyear,
                pa.patient_sysmptom,
                pa.patient_assistance,
                pa.patient_incomeSum,
                pa.patient_incomeDetail,
                pa.patient_expensesSum,
                pa.patient_expensesDetail,
                pa.id_card,
                pa.getmoney_id,
                pe.person_titlename,
                pe.person_firstname,
                pe.person_lastname,
                pe.person_phone,
                pe.person_birthday
            FROM patient AS pa
                LEFT JOIN person as pe on pa.id_card = pe.id_card
            ";
            
            if ($search != null) {
                $sql .= "WHERE pa.patient_habitatchoice LIKE '%$search%' OR ".
                "pa.patient_habitatdetail LIKE '%$search%' OR ".
                "pa.patient_distance LIKE '%$search%' OR ".
                "pa.id_card LIKE '%$search%' OR ".
                "pe.person_titlename LIKE '%$search%' OR ".
                "pe.person_firstname LIKE '%$search%' OR ".
                "pe.person_lastname LIKE '%$search%' ";
            }
            $sql .= "ORDER BY pa.patient_id desc ".
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
    $this->get('/get/one/{id_card}', function(Request $req, Response $res, $args) {
        $id_card = $args['id_card'];
        $sql = "SELECT 
            pa.patient_id,
            pa.patient_habitatchoice,
            pa.patient_habitatdetail,
            pa.patient_distance,
            pa.patient_distancedetail,
            pa.patient_distancecheck,
            pa.patient_distancebecouse,
            pa.patient_residancechoice,
            pa.patient_residancedetail,
            pa.patient_sickyear,
            pa.patient_sysmptom,
            pa.patient_assistance,
            pa.patient_incomeSum,
            pa.patient_incomeDetail,
            pa.patient_expensesSum,
            pa.patient_expensesDetail,
            pa.id_card,
            pa.getmoney_id,
            pe.person_titlename,
            pe.person_firstname,
            pe.person_lastname,
            pe.person_phone,
            pe.person_birthday
        FROM patient AS pa
            LEFT JOIN person as pe on pa.id_card = pe.id_card
        WHERE pa.id_card = $id_card LIMIT 1";
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
        $patient_habitatchoice = $req->getParam('patient_habitatchoice');
        $patient_habitatdetail = $req->getParam('patient_habitatdetail');
        $patient_distance = $req->getParam('patient_distance');
        $patient_distancedetail = $req->getParam('patient_distancedetail');
        $patient_distancecheck = $req->getParam('patient_distancecheck');
        $patient_distancebecouse = $req->getParam('patient_distancebecouse');
        $patient_residancechoice = $req->getParam('patient_residancechoice');
        $patient_residancedetail = $req->getParam('patient_residancedetail');
        $patient_sickyear = $req->getParam('patient_sickyear');
        $patient_sysmptom = $req->getParam('patient_sysmptom');
        $patient_assistance = $req->getParam('patient_assistance');
        $patient_incomeSum = $req->getParam('patient_incomeSum');
        $patient_incomeDetail = $req->getParam('patient_incomeDetail');
        $patient_expensesSum = $req->getParam('patient_expensesSum');
        $patient_expensesDetail = $req->getParam('patient_expensesDetail');
        $getmoney_id = $req->getParam('getmoney_id');
        $user_id = $req->getParam('user_id');

        $sql = "INSERT INTO patient
                    (patient_habitatchoice, patient_habitatdetail, patient_distance, patient_distancedetail,
                    patient_distancecheck, patient_distancebecouse, patient_residancechoice, patient_residancedetail,
                    patient_sickyear, patient_sysmptom, patient_assistance, patient_incomeSum,
                    patient_incomeDetail, patient_expensesSum, patient_expensesDetail,
                    id_card, user_id, getmoney_id)
                VALUES
                    ('$patient_habitatchoice', '$patient_habitatdetail', '$patient_distance', $patient_distancedetail,
                    '$patient_distancecheck', '$patient_distancebecouse', '$patient_residancechoice', '$patient_residancedetail',
                    $patient_sickyear, '$patient_sysmptom', '$patient_assistance', $patient_incomeSum,
                    '$patient_incomeDetail', $patient_expensesSum, '$patient_expensesDetail',
                    '$id_card', $user_id, $getmoney_id)";
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
    $this->get('/delete/{patient_id}', function(Request $req, Response $res, $args) {
        $patient_id = $args['patient_id'];
        $sql = "DELETE FROM patient WHERE patient_id = $patient_id";
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

        $id_card = $args['id_card'];
        $patient_habitatchoice = $req->getParam('patient_habitatchoice');
        $patient_habitatdetail = $req->getParam('patient_habitatdetail');
        $patient_distance = $req->getParam('patient_distance');
        $patient_distancedetail = $req->getParam('patient_distancedetail');
        $patient_distancecheck = $req->getParam('patient_distancecheck');
        $patient_distancebecouse = $req->getParam('patient_distancebecouse');
        $patient_residancechoice = $req->getParam('patient_residancechoice');
        $patient_residancedetail = $req->getParam('patient_residancedetail');
        $patient_sickyear = $req->getParam('patient_sickyear');
        $patient_sysmptom = $req->getParam('patient_sysmptom');
        $patient_assistance = $req->getParam('patient_assistance');
        $patient_incomeSum = $req->getParam('patient_incomeSum');
        $patient_incomeDetail = $req->getParam('patient_incomeDetail');
        $patient_expensesSum = $req->getParam('patient_expensesSum');
        $patient_expensesDetail = $req->getParam('patient_expensesDetail');
        $getmoney_id = $req->getParam('getmoney_id');
        $user_id = $req->getParam('user_id');

        $sql = "UPDATE patient
            SET
                patient_habitatchoice = '$patient_habitatchoice',
                patient_habitatdetail='$patient_habitatdetail',
                patient_distance='$patient_distance',
                patient_distancedetail='$patient_distancedetail',
                patient_distancecheck='$patient_distancecheck',
                patient_distancebecouse='$patient_distancebecouse',
                patient_residancechoice='$patient_residancechoice',
                patient_residancedetail='$patient_residancedetail',
                patient_sickyear='$patient_sickyear',
                patient_sysmptom='$patient_sysmptom',
                patient_assistance='$patient_assistance',
                patient_incomeSum='$patient_incomeSum',
                patient_incomeDetail='$patient_incomeDetail',
                patient_expensesSum='$patient_expensesSum',
                patient_expensesDetail='$patient_expensesDetail',
                getmoney_id='$getmoney_id',
                user_id='$user_id'
            WHERE id_card = $id_card";
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
