<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->group('/allowance', function() {
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
            "p.id_card, p.person_titlename, p.person_firstname, p.person_lastname, ".
            "p.person_birthday, p.person_phone, p.person_lat, p.person_lng, d.disability_id, ".
            "e.elders_id, pt.patient_id, al.allowance_date, al.allowance_type, al.allowance_money, ".
            "al.allowance_id ".
            "FROM person as p ".
            "LEFT JOIN disability as d on p.id_card = d.id_card ".
            "LEFT JOIN elders as e on p.id_card = e.id_card ".
            "LEFT JOIN patient as pt on p.id_card = pt.id_card ".
            "RIGHT JOIN allowance as al on p.id_card = al.id_card ";
            
            if ($search != null) {
                $sql .= "WHERE id_card LIKE '%$search%' OR ".
                "person_titlename LIKE '%$search%' OR ".
                "person_firstname LIKE '%$search%' OR ".
                "person_lastname LIKE '%$search%' OR ".
                "person_phone LIKE '%$search%' OR ".
                "person_birthday LIKE '%$search%' ";
            }
            $sql .= "ORDER BY al.allowance_id desc ".
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
    $this->get('/get/report', function(Request $req, Response $res) {
        try {
            $from = $req->getQueryParam('from');
            $to = $req->getQueryParam('to');
            $type = json_decode($req->getQueryParam('type'), true);
            $allowance = json_decode($req->getQueryParam('allowance'), true);
            
            $sql = "SELECT ".
            "p.id_card, p.person_titlename, p.person_firstname, p.person_lastname, ".
            "p.person_birthday, p.person_phone, p.person_lat, p.person_lng, d.disability_id, ".
            "e.elders_id, pt.patient_id, al.allowance_date, al.allowance_type, al.allowance_money, ".
            "al.allowance_id ".
            "FROM person as p ".
            "LEFT JOIN disability as d on p.id_card = d.id_card ".
            "LEFT JOIN elders as e on p.id_card = e.id_card ".
            "LEFT JOIN patient as pt on p.id_card = pt.id_card ".
            "RIGHT JOIN allowance as al on p.id_card = al.id_card ";
            
            if ($from != null && $to != null) {
                $sql .= "WHERE al.allowance_date BETWEEN '$from' AND '$to' ";
                if (count($type) > 0) {
                    foreach ($type as $key => $value) {
                        if ($value == 1) {
                            $sql .= "AND pt.patient_id IS NOT NULL ";
                        } elseif ($value == 2) {
                            $sql .= "AND d.disability_id IS NOT NULL ";
                        } elseif ($value == 3) {
                            $sql .= "AND e.elders_id IS NOT NULL ";
                        }
                    }

                    foreach ($allowance as $key => $value) {
                        if ($value == 1) {
                            $sql .= "AND al.allowance_type = 3 ";
                        } elseif ($value == 2) {
                            $sql .= "AND al.allowance_type = 2 ";
                        } elseif ($value == 3) {
                            $sql .= "AND al.allowance_type = 1 ";
                        }
                    }
                }
            } else {
                if (count($type) > 0 || count($allowance) > 0) {
                    $ifF = true;
                    foreach ($type as $key => $value) {
                        if ($value == 1) {
                            if ($ifF == true) {
                                $sql .= "WHERE pt.patient_id IS NOT NULL ";
                                $ifF = false;
                            } else {
                                $sql .= "AND pt.patient_id IS NOT NULL ";
                            }
                        } elseif ($value == 2) {
                            if ($ifF == true) {
                                $sql .= "WHERE d.disability_id IS NOT NULL ";
                                $ifF = false;
                            } else {
                                $sql .= "AND d.disability_id IS NOT NULL ";
                            }
                        } elseif ($value == 3) {
                            if ($ifF == true) {
                                $sql .= "WHERE e.elders_id IS NOT NULL ";
                                $ifF = false;
                            } else {
                                $sql .= "AND e.elders_id IS NOT NULL ";
                            }
                        }
                    }

                    foreach ($allowance as $key => $value) {
                        if ($value == 1) {
                            if ($ifF == true) {
                                $sql .= "WHERE al.allowance_type = 3 ";
                                $ifF = false;
                            } else {
                                $sql .= "AND al.allowance_type = 3 ";
                            }
                        } elseif ($value == 2) {
                            if ($ifF == true) {
                                $sql .= "WHERE al.allowance_type = 2 ";
                                $ifF = false;
                            } else {
                                $sql .= "AND al.allowance_type = 2 ";
                            }
                        } elseif ($value == 3) {
                            if ($ifF == true) {
                                $sql .= "WHERE al.allowance_type = 1 ";
                                $ifF = false;
                            } else {
                                $sql .= "AND al.allowance_type = 1 ";
                            }
                        }
                    }
                }
            }

            $sql .= "ORDER BY al.allowance_id desc ";
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
            $stm = $db->query("SELECT COUNT(allowance_id) as length FROM allowance");
            $length = $stm->fetch(PDO::FETCH_ASSOC);
            
            header('Content-type: application/json;');
            return $res->withJSON($length, 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
    $this->get('/get/one/{alID}', function(Request $req, Response $res, $args) {
        $alID = $args['alID'];
        $sql = "SELECT ".
        "p.id_card, p.person_titlename, p.person_firstname, p.person_lastname, ".
        "p.person_birthday, p.person_phone, p.person_lat, p.person_lng, d.disability_id, ".
        "e.elders_id, pt.patient_id, al.allowance_date, al.allowance_type, al.allowance_money, ".
        "al.allowance_id ".
        "FROM person as p ".
        "LEFT JOIN disability as d on p.id_card = d.id_card ".
        "LEFT JOIN elders as e on p.id_card = e.id_card ".
        "LEFT JOIN patient as pt on p.id_card = pt.id_card ".
        "RIGHT JOIN allowance as al on p.id_card = al.id_card ".
        "WHERE allowance_id = $alID";
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
    $this->get('/get/person/{id_card}', function(Request $req, Response $res, $args) {
        try {
            $id_card = $args["id_card"];

            $sql = "SELECT al.* ".
            "FROM allowance as al ".
            "WHERE al.id_card = $id_card ".
            "ORDER BY al.allowance_id desc ";

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
    $this->post('/insert', function(Request $req, Response $res, $args) {
        $db = new db();
        $db = $db->connect();
        $isBegin = $db->beginTransaction();

        $id_card = $req->getParam("id_card");
        $year = $req->getParam("year");
        $amount = $req->getParam("amount");
        $type = $req->getParam("type");

        $sql = "INSERT INTO allowance
        (
            id_card, allowance_type, allowance_money
        )
        VALUES
        (
            '$id_card', $type, $amount
        )";
        try {
            $db->exec($sql);
            $db->commit();
            header('Content-type: application/json');
            return $res->withJSON(array('success' => true, 'adlID' => $adl_id), 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            $db->rollBack();
            return $res->withJSON(array('success' => false, 'description' => $err->getMessage()), 500, JSON_UNESCAPED_UNICODE);
        }
    });
    $this->post('/update/{alID}', function(Request $req, Response $res, $args) {
        $db = new db();
        $db = $db->connect();
        $isBegin = $db->beginTransaction();

        $alID = $args['alID'];
        $id_card = $req->getParam("id_card");
        $year = $req->getParam("year");
        $amount = $req->getParam("amount");
        $type = $req->getParam("type");

        $sql = "UPDATE allowance
            SET 
                id_card='$id_card',
                allowance_type=$type,
                allowance_money=$amount
            WHERE allowance_id = $alID
        ";
        try {
            $db->exec($sql);
            $db->commit();
            header('Content-type: application/json');
            return $res->withJSON(array('success' => true, 'adlID' => $adl_id), 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            $db->rollBack();
            return $res->withJSON(array('success' => false, 'description' => $err->getMessage()), 500, JSON_UNESCAPED_UNICODE);
        }
    });
});
