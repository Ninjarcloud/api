<?php


require_once("Rest.inc.php");

//include "../include/php/constants.php"; //Reads constants settings.
//include("../include/php/foldersize.php"); //This script estimates the space occupied by the user.



class API extends REST {

    public $data = "";

//Public method for access api.
//This method dynmically call the method based on the query string
    public function processApi() {

        $func = strtolower(trim(str_replace("/", "", $_REQUEST['rquest'])));
        if ((int) method_exists($this, $func) > 0)
            $this->$func();
        else
            $this->response('', 404);
// If the method not exist with in this class, response would be "Page not found".
    }

    private function methods() {
        $this->response(get_class_methods("API"), 200);
    }

    private function upload() {
        include "../include/php/constants.php";
        include("../include/php/foldersize.php");

        $spaziooccupato_mb = foldersize("../files/" . $this->_request['username']) / (1024 * 1024);
        $spaziototale_mb = USER_SPACE * 1024;
        $filesize = $_FILES['file']['size'] / 1024 / 1024;
        if (($spaziooccupato_mb + $filesize) <= $spaziototale_mb) {
            $ds = DIRECTORY_SEPARATOR; //1
            $username = $this->_request['username'];
            $folder = $this->_request['folder'];
            $storeFolder = "../files/$username$folder";

            function file_extension($filename) {
                $ext = explode(".", $filename);
                return $ext[count($ext) - 1];
            }

            if (!empty($_FILES)) {
                $tempFile = $_FILES['file']['tmp_name'];
                $targetPath = dirname(__FILE__) . "../" . $ds . $storeFolder . $ds;
                $targetFile = $targetPath . $_FILES['file']['name'];
                move_uploaded_file($tempFile, $targetFile);
                //Rename the file for security
                $extension = file_extension($_FILES['file']['name']);
                $file_id = rand(10000000000000000, 99999999999999999);
                $security_id = rand(10000000000000000, 99999999999999999);
                rename("../files/$username$folder" . $_FILES['file']['name'], "../files/$username$folder$security_id.$extension");

                //Connect to database
                $host = DB_SERVER;
                $user = DB_USER;
                $password = DB_PASS;
                $myconn = mysql_connect($host, $user, $password) or die("Connection error");
                $db_name = DB_NAME;
                mysql_select_db($db_name, $myconn);
                $tab_name = TBL_SAFETY_RENAME;

                //Send informations to the database
                $query_sql = "INSERT INTO $tab_name VALUES('" . $_FILES['file']['name'] . "','$file_id','$security_id.$extension','$username','$folder');";
                $tab_name_2 = TBL_VIEWS;
                $query_sql_2 = "INSERT INTO $tab_name_2 VALUES('$username$file_id','0');";
                $result = mysql_query($query_sql, $myconn);
                $result_2 = mysql_query($query_sql_2, $myconn);
                $this->response("", 200);
            } else {
                $this->response("", 400);
            }
        }
    }
    
    private function download() {
        $fn = (isset($this->_request['filename']) ? $this->_request['filename'] : false);
        $file = "../" . $fn;
        $sn = (isset($this->_request['name']) ? $this->_request['name'] : false);
        $secure_name = $sn;

        if (!file_exists($file)) {
            //If there is mold an error
            $this->response("The file does not exist!", 500);
        } else {
            //If the file exists ...
            //Imposed on the header of the page to force the download of the file
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename= " . $secure_name);
            header("Content-Transfer-Encoding: binary");
            //I read the contents of the file
            readfile($file);
        }
    }
    

//Encode array into JSON
    private function json($data) {
        if (is_array($data)) {
            return json_encode($data);
        }
    }

}

// Initiiate Library
$api = new API;
$api->processApi();
?>
