<?php
$servername = "localhost";
$username = "paol";
$password = "12345";
$dbname = "albergo";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
$metodo=$_SERVER["REQUEST_METHOD"];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode( '/', $uri ); 
$numero=0;

$ct = isset($_SERVER["CONTENT_TYPE"]) ? $_SERVER["CONTENT_TYPE"] : null;
if ($ct) {
    $type = explode("/", $ct);
} else {
    $type = ['application', 'json'];
}
$retct=$_SERVER["HTTP_ACCEPT"];
$ret=explode("/",$retct);
if ($metodo=="GET"){
    $sql ="";
    if (count($uri) == 3 && $uri[2]==null) {
        $tables_result = $conn->query("SHOW TABLES");
        $tables_data = array();
        if ($tables_result->num_rows > 0) {
            while ($table_row = $tables_result->fetch_row()) {
                $table_name = $table_row[0];
                $table_content_result = $conn->query("SELECT * FROM $table_name");
                $table_data = array();
                if ($table_content_result->num_rows > 0) {
                    while ($row = $table_content_result->fetch_assoc()) {
                        $table_data[] = $row;
                    }
                }
                $tables_data[$table_name] = $table_data;
            }
        }
    $json_data = json_encode($tables_data);
    } else{
    if (count($uri) == 3) {
        $tableName = $uri[2];
        $sql = "SELECT * FROM $tableName";
    } elseif (count($uri) == 4) {
        $tableName = $uri[2];
        $id = $uri[3];
        $sql = "SELECT * FROM $tableName WHERE Id = $id";
    } else {
        http_response_code(400);
        echo "URI non valido.";
        exit();
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $result = $conn->query($sql);

    if ($result) {
        if ($result->num_rows > 0) {
            $rows = array();
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            echo json_encode($rows);
        } else {
            echo "Nessun dato trovato.";
        }
    } else {
        echo "Errore durante l'esecuzione della query: " . $conn->error;
    }       
}
}
if ($metodo=="POST"){
   $body=file_get_contents('php://input');
    if ($type[1]=="json"){
        $data = json_decode($body,true);
    }
    if ($type[1]=="xml"){
        $xml = simplexml_load_string($body);
        $json = json_encode($xml);
        $data = json_decode($json, true);
    }
    
    if(is_array($data) && 
    array_key_exists('nome_tabella', $data) &&
    array_key_exists('id', $data) &&
    count($data) >= 3){
        http_response_code(400);
        echo "L'array non contiene tutti i requisiti";
        exit();
    }

    $sql="INSERT INTO ".$data["nome_tabella"];
    $array_dati=$data;
    unset($array_dati['nome_tabella']);
    unset($array_dati['id']);

    $chiavi_stringa = "";
    $valori_stringa ="";
    foreach($array_dati as $chiave => $valore){
        $chiavi_stringa .= $chiave . ", ";
        $valori_stringa .= "'".$valore . "', ";
    }
    $chiavi_stringa = rtrim($chiavi_stringa, ", ");
    $valori_stringa = rtrim($valori_stringa, ", ");
    $sql=$sql." (".$chiavi_stringa.") VALUES (".$valori_stringa.");";
    $stmt = $conn->prepare($sql);
    $params = array_values($array_dati);
    $types = str_repeat('s', count($params)); 
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    echo $sql;
    $result = $conn->query($sql);
    header("Content-Type: ".$retct);    
    if ($ret[1]=="json"){
        echo json_encode($data);
    }
    if ($ret[1]=="xml"){
        $xml = new SimpleXMLElement('<root/>');
        array_walk_recursive($data, array ($xml, 'addChild'));    
        echo $xml->asXML();
    }

}
if ($metodo=="PUT"){
    echo "put\n";
    echo "<br>";
   $body=file_get_contents('php://input');
    if ($type[1]=="json"){
        $data = json_decode($body,true);
        foreach ($data as $dat){
            echo $dat;
            echo "<br>";
        }
    }
    if ($type[1]=="xml"){
        $xml = simplexml_load_string($body);
        $json = json_encode($xml);
        $data = json_decode($json, true);
    }
    if (is_array($data) && 
        array_key_exists('nome_tabella', $data) &&
        array_key_exists('id', $data) &&
        count($data) >= 3) {
        echo "L'array contiene almeno nome_tabella, id e almeno una terza chiave.";
        $array_dati=$data;
        unset($array_dati['nome_tabella']);
        unset($array_dati['id']);

        $stringa_chiave_valore=""; 
        foreach ($array_dati as $chiave => $valore) {
            $stringa_chiave_valore .= $chiave . "=" ."'". $valore ."'". ", ";
        }
        $stringa_chiave_valore = rtrim($stringa_chiave_valore, ", ");

        $sql="UPDATE ".$data["nome_tabella"]." SET ".$stringa_chiave_valore." WHERE id=".$data["id"].";";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $data["id"]);
        $stmt->execute();
        echo $sql;
        $result = $conn->query($sql);
        header("Content-Type: ".$retct);    
        if ($ret[1]=="json"){
            echo json_encode($data);
        }
        if ($ret[1]=="xml"){
            $xml = new SimpleXMLElement('<root/>');
            array_walk_recursive($data, array ($xml, 'addChild'));    
            echo $xml->asXML();
        }

    } else {
        http_response_code(400);
        echo "L'array non contiene tutti i requisiti";
        exit();
    }
   
}
if ($metodo=="DELETE"){
    echo "delete";

    $body=file_get_contents('php://input');
     if ($type[1]=="json"){
         $data = json_decode($body,true);
     }
     if ($type[1]=="xml"){
         $xml = simplexml_load_string($body);
         $json = json_encode($xml);
         $data = json_decode($json, true);
     }
     
     if(is_array($data) && 
     array_key_exists('nome_tabella', $data) &&
     array_key_exists('id', $data) &&
     count($data) >= 3){
         http_response_code(400);
         echo "L'array non contiene tutti i requisiti";
         exit();
     }
     $sql = "DELETE FROM ".$data["nome_tabella"]. " WHERE id = ".$data["id"].";";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $data["id"]);
    $stmt->execute();

     echo $sql;
     $result = $conn->query($sql);
     header("Content-Type: ".$retct);    
     echo $retct;
     if ($ret[1]=="json"){
         echo json_encode($data);
     }
     if ($ret[1]=="xml"){
         $xml = new SimpleXMLElement('<root/>');
         array_walk_recursive($data, array ($xml, 'addChild'));    
         echo $xml->asXML();
     }
}

$conn->close();
?>