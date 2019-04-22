<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->group('/address', function() {
    $this->get('/province', function(Request $req, Response $res) {
        try {
            $sql = "SELECT * FROM province";
            $db = new db();
            $db = $db->connect();
            $stm = $db->query($sql);
            $province = $stm->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-type: application/json;');
            return $res->withJSON($province, 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
    $this->get('/district/{pvID}', function(Request $req, Response $res, $args) {
        $pvID = $args['pvID'];
        $sql = "SELECT * FROM district WHERE Pid = $pvID";
        try {
            $db = new db();
            $db = $db->connect();
            $stm = $db->query($sql);
            $district = $stm->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-type: application/json');
            return $res->withJSON($district, 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
    $this->get('/subdistrict/{dtID}', function(Request $req, Response $res, $args) {
        $dtID = $args['dtID'];
        $sql = "SELECT * FROM subdistrict WHERE Did = $dtID";
        try {
            $db = new db();
            $db = $db->connect();
            $stm = $db->query($sql);
            $subDistrict = $stm->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-type: application/json');
            return $res->withJSON($subDistrict, 200, JSON_UNESCAPED_UNICODE);
        } catch(PDOException $err) {
            echo '{"error" : {"text": '.$err->getMessage().'}}';
        }
    });
});
