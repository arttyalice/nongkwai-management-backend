<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Slim\Http\UploadedFile as Postfile;

$app->group('/treatment', function() {
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
                        "t.treatment_id, t.treatment_detail, t.treatment_date, ".
                        "t.height, t.weigth, t.SBP, t.DBP, p.id_card, ".
                        "p.person_titlename, p.person_firstname, p.person_lastname, ".
                        "p.person_phone, d.disability_id, e.elders_id, pt.patient_id ".
                    "FROM treatment as t ".
                        "LEFT JOIN person as p on t.id_card = p.id_card ".
                        "LEFT JOIN disability as d on p.id_card = d.id_card ".
                        "LEFT JOIN elders as e on p.id_card = e.id_card ".
                        "LEFT JOIN patient as pt on p.id_card = pt.id_card ";
            
            if ($search != null) {
                $sql .= "WHERE p.id_card LIKE '%$search%' OR ".
                "person_titlename LIKE '%$search%' OR ".
                "person_firstname LIKE '%$search%' OR ".
                "person_lastname LIKE '%$search%' OR ".
                "person_phone LIKE '%$search%' OR ".
                "person_birthday LIKE '%$search%' ";
            }
            $sql .= "ORDER BY t.treatment_id desc ".
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
            $stm = $db->query("SELECT COUNT(treatment_id) as length FROM treatment");
            $length = $stm->fetch(PDO::FETCH_ASSOC);
            
            header('Content-type: application/json;');
            return $res->withJSON($length, 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
    $this->get('/get/treatment/all/{id_card}', function(Request $req, Response $res, $args) {
        try {
            $id_card = $args["id_card"];
            $sql = "SELECT tm.*
                    FROM treatment as tm
                    WHERE tm.id_card = '$id_card'";

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
    $this->get('/get/one/{tmID}', function(Request $req, Response $res, $args) {
        $tmID = $args['tmID'];
        $sql = "SELECT ".
                "t.*, p.id_card, ".
                "p.person_titlename, p.person_firstname, p.person_lastname, ".
                "p.person_phone, d.disability_id, e.elders_id, pt.patient_id ".
            "FROM treatment as t ".
                "LEFT JOIN person as p on t.id_card = p.id_card ".
                "LEFT JOIN disability as d on p.id_card = d.id_card ".
                "LEFT JOIN elders as e on p.id_card = e.id_card ".
                "LEFT JOIN patient as pt on p.id_card = pt.id_card ".
            "WHERE t.treatment_id = $tmID LIMIT 1";
        $fileQuery = "SELECT *
                    FROM treatment_file
                    WHERE treatment_id = $tmID";
        try {
            $db = new db();
            $db = $db->connect();
            $stm = $db->query($sql);
            $treatment = $stm->fetch(PDO::FETCH_ASSOC);

            if (!$treatment) {
                return $res->withJSON(array('success' => false, 'description' => "treatment not found"), 400, JSON_UNESCAPED_UNICODE);
            }

            $stm = $db->query($fileQuery);
            $files = $stm->fetchAll(PDO::FETCH_ASSOC);

            $data = array("treatment" => $treatment, "files" => $files);
            
            header('Content-type: application/json');
            return $res->withJSON($data, 200, JSON_UNESCAPED_UNICODE);
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
    $this->get('/get/files/{treatment_id}', function(Request $req, Response $res, $args) {
        $treatment_id = $args['treatment_id'];
        $sql = "SELECT *
                FROM treatment_file
                WHERE treatment_id = $treatment_id";
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
    $this->post('/insert/{id_card}', function(Request $req, Response $res, $args) {
        $directory = $this->get('upload_directory');
        $upload_url = $this->get('upload_url');
        $db = new db();
        $db = $db->connect();
        $isBegin = $db->beginTransaction();
        $id_card = $args["id_card"];
        $user_id = $req->getParam("user_id");
        $treatment_detail = $req->getParam("treatment_detail");
        $disease = $req->getParam("disease");
        $hospital = $req->getParam("hospital");
        $height = $req->getParam("height");
        $weigth = $req->getParam("weigth");
        $SBP = $req->getParam("SBP");
        $DBP = $req->getParam("DBP");
        $files = $req->getUploadedFiles();

        $sql = "INSERT INTO  treatment
            (treatment_detail, id_card, user_id, disease, hospital, height, weigth, SBP, DBP)
        VALUES
            ('$treatment_detail', '$id_card', $user_id, '$disease', '$hospital', $height, $weigth, $SBP, $DBP)";

        try {
            $db->exec($sql); 
            $treatment_id = $db->lastInsertId();
            mkdir("./uploads/treatment/".$treatment_id, 0777);
            foreach ($files['files'] as $ele) {
                $filename = moveUploadedFile($directory.'/treatment/'.$treatment_id, $ele);
                $url = $upload_url.'treatment/'.$treatment_id.'/'.$filename;
                $db->exec("INSERT INTO treatment_file(file_name, file_path, treatment_id) VALUES ('$filename', '$url', $treatment_id)");
            }
            $db->commit();
            header('Content-type: application/json');
            return $res->withJSON(array('success' => true), 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            $db->rollBack();
            return $res->withJSON(array('success' => false, 'description' => $err->getMessage()), 500, JSON_UNESCAPED_UNICODE);
        }
    });
    $this->post('/update/{tmID}', function(Request $req, Response $res, $args) {
        $directory = $this->get('upload_directory');
        $upload_url = $this->get('upload_url');
        $db = new db();
        $db = $db->connect();
        $isBegin = $db->beginTransaction();
        $tmID = $args["tmID"];

        $user_id = $req->getParam("user_id");
        $id_card = $req->getParam("id_card");
        $treatment_detail = $req->getParam("treatment_detail");
        $disease = $req->getParam("disease");
        $hospital = $req->getParam("hospital");
        $height = $req->getParam("height");
        $weigth = $req->getParam("weigth");
        $SBP = $req->getParam("SBP");
        $DBP = $req->getParam("DBP");
        $isNew = $req->getParam("isNewFile");
        $files = $req->getUploadedFiles();

        $sql = "UPDATE treatment
                SET
                    treatment_detail='$treatment_detail',
                    id_card='$id_card',
                    user_id=$user_id,
                    disease='$disease',
                    hospital='$hospital',
                    height=$height,
                    weigth=$weigth,
                    SBP=$SBP,
                    DBP=$DBP
                WHERE treatment_id = $tmID";
        try {
            $db->exec($sql);

            if ($isNew == "true") {
                // $db->exec("DELETE FROM treatment_file WHERE treatment_id = $tmID");

                foreach ($files['newFile'] as $ele) {
                    $filename = moveUploadedFile($directory.'/treatment/'.$tmID, $ele);
                    $url = $upload_url.'treatment/'.$tmID.'/'.$filename;
                    $db->exec("INSERT INTO treatment_file(file_name, file_path, treatment_id) VALUES ('$filename', '$url', $tmID)");
                }
            }
            $db->commit();
            header('Content-type: application/json');
            return $res->withJSON(array('success' => true, '$sql' => $sql), 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            $db->rollBack();
            return $res->withJSON(array('success' => false, 'description' => $err->getMessage()), 500, JSON_UNESCAPED_UNICODE);
        }
    });
    $this->post('/delete/{tmID}', function(Request $req, Response $res, $args) {
        $directory = $this->get('upload_directory');
        $db = new db();
        $db = $db->connect();
        $isBegin = $db->beginTransaction();
        $tmID = $args["tmID"];

        $sql = "DELETE FROM treatment WHERE treatment_id = $tmID";
        $file_sql = "DELETE FROM treatment_file WHERE treatment_id = $tmID";
        try {
            $db->exec($sql);
            $db->exec($file_sql);
            
            array_map('unlink', glob("$directory/treatment/$tmID/*.*"));
            rmdir("$directory/treatment/$tmID");

            $db->commit();
            header('Content-type: application/json');
            return $res->withJSON(array('success' => true), 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            $db->rollBack();
            return $res->withJSON(array('success' => false, 'description' => $err->getMessage()), 500, JSON_UNESCAPED_UNICODE);
        }
    });
});

function moveUploadedFile($directory, Postfile $uploadedFile) {
    $filename = $uploadedFile->getClientFilename();
    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
    return $filename;
}
