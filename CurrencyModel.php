<?php

class CurrencyModel {
    
    private $dbHost;
    private $dbName;
    private $dbUser;
    private $dbPassword;
    
    private $currentDate = '';
    
    private $errors = array();
    
    function __construct($dbHost, $dbName, $dbUser, $dbPassword) {
        $this->dbHost = $dbHost;
        $this->dbName = $dbName;
        $this->dbUser = $dbUser;
        $this->dbPassword = $dbPassword;
        $this->createDatabase();
    }
    
    public function hasErrors() {
        return (!empty($this->errors));
    }
    
    public function getErrorstring() {
        return implode(" ", $this->errors);
    }
    
    private function getConnection() {
        $mysqli = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName);
        if ($mysqli->connect_errno) {
            $this->errors[] = 'не удалось подключиться к mysql ' . $mysqli->connect_error;
            return null;
        }
        return $mysqli;
    }
    
    public function loadData($url) {
        $context = stream_context_create(
        array(
           'http' => array(
                'max_redirects' => 10100
                )
           )
        );
        $xml = file_get_contents($url, false, $context);
        $valCurs = new SimpleXMLElement($xml);
        $date = $valCurs['Date']->__toString();
        $this->currentDate = $date;
        $mysqli = $this->getConnection();
        if (!$this->existsData($mysqli, $date)) {
            $this->load($mysqli, $valCurs, $date);
        }
        $mysqli->close();
    }
    
    public function getCurrentDate() {
        return $this->currentDate;
    }
    
    public function getCoursesByDate($date) {
        $mysqli = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName);
        if (!empty($date)) {
            $dateTime = DateTime::createFromFormat("d.m.Y", $date);
            $date = $dateTime->format("Y-m-d");
            $stmt = $mysqli->prepare("select currencyId, numCode, charCode, name, course_value, course_date from currency where course_date = ?");
            if ($stmt) {
                $stmt->bind_param('s', $date);
                $ok = $stmt->execute();
                if ($ok) {                    
                    $stmt->bind_result($currencyId, $numCode, $charCode, $name, $course_value, $course_date);
                    $arr = array();
                    $stmt->store_result();
                    while ($stmt->fetch()) {
                        $arr[] = array(
                            "currencyId" => $currencyId, 
                            "numCode" => $numCode, 
                            "charCode" => $charCode, 
                            "name" => $name, 
                            "course_value" => $course_value, 
                            "course_date" => $course_date
                        );
                    }
                    $stmt->close();
                    return $arr;
                } else {
                    $this->errors[] = 'Ошибка базы данных';   
                }
            } else {
                $this->errors[] = 'Ошибка при подготовке запроса';
            }
            $mysqli->close();
        }
        return array();
    }
    
    public function getCourses($currencyId, $date) {
        $mysqli = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName);
        $dateTime = DateTime::createFromFormat("d.m.Y", $date);
        $date = $dateTime->format("Y-m-d");
        $stmt = $mysqli->prepare("select * from currency where currencyId = ? and course_date = ?");
        if ($stmt) {
            $stmt->bind_param('ss', $currencyId, $date);
            $ok = $stmt->execute();
            if ($ok) {
                $result = $stmt->get_result();
                return $result->fetch_all();
            } else {
                $this->errors[] = 'Ошибка базы данных';   
            }
        } else {
            $this->errors[] = 'Ошибка при подготовке запроса';
        }
        $mysqli->close();
        return array();
    }
    
    public function getCoursesByPeriod($currencyId, $dateFrom, $dateTo) {
        $mysqli = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName);
        $query = "select * from currency where currencyId = ? ";
        
        $types = array();
        $vals = array();
        $types[] = "s";
        $vals[] = $currencyId;
        
        if ($dateFrom != null) {
            $dateFrom = $this->dateToSqlFormat($dateFrom);
            $types[] = "s";
            $vals[] = $dateFrom;
            $query .= " and course_date >= ? ";
        }
        if ($dateTo != null) {
            $types[] = "s";
            $dateTo = $this->dateToSqlFormat($dateTo);
            $vals[] = $dateTo;
            $query .= " and course_date <= ? ";
        }
        $stmt = $mysqli->prepare($query);
        if ($stmt) {
            $typesStr = join($types);
            if ($dateFrom != null && $dateTo != null) {
                $stmt->bind_param($typesStr, $currencyId, $dateFrom, $dateTo);
            } else if ($dateFrom != null) {
                $stmt->bind_param($typesStr, $currencyId, $dateFrom);
            } else if ($dateTo != null) {
                $stmt->bind_param($typesStr, $currencyId, $dateTo);
            }
            $ok = $stmt->execute();
            if ($ok) {
                $result = $stmt->get_result();
                return $result->fetch_all();
            } else {
                $this->errors[] = 'Ошибка базы данных';   
            }
        } else {
            $this->errors[] = 'Ошибка при подготовке запроса';
        }
        $mysqli->close();
        return array();
    }
    
    private function dateToSqlFormat($date) {
        $dateTime = DateTime::createFromFormat("d.m.Y", $date);
        return $dateTime->format("Y-m-d");
    }
    
    private function existsData($mysqli, $date) {
        $stmt = $mysqli->prepare("select * from currency where course_date = ?");
        $dateTime = DateTime::createFromFormat("d.m.Y", $date);
        $date = $dateTime->format("Y-m-d");
        if ($stmt) {
            $stmt->bind_param('s', $date);
            $ok = $stmt->execute();
            $stmt->store_result();
            if ($ok) {
                if ($stmt->num_rows > 0) {
                    return true;
                } else {
                    return false;
                }
            } else {
                $this->errors[] = 'Ошибка базы данных';   
            }
        } else {
            $this->errors[] = 'Ошибка при подготовке запроса';
        }
        return false;
    }
    
    private function load($mysqli, $valCurs, $date) {
        $dateTime = DateTime::createFromFormat("d.m.Y", $date);
        $date = $dateTime->format("Y-m-d");
        foreach ($valCurs->Valute as $valute) {
            $numCode = $valute->NumCode;
            $charCode = $valute->CharCode;
            $name = $valute->Name;
            $value = $valute->Value;
            $value = str_replace(",", ".", $value);
            $id = $valute['ID']->__toString();
            $stmt = $mysqli->prepare('insert into currency (currencyId, numCode, charCode, name, course_value, course_date) values (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssds', $id, $numCode, $charCode, $name, $value, $date);
            $ok = $stmt->execute();
            if (!$ok) {
                $this->errors[] = 'ошибка при выполнении запроса';
                return;
            }
        }
    }
    
    
    private function createDatabase() {
        $mysqli = $this->getConnection();
        if ($mysqli != null) {
            $query = "create table if not exists currency (currencyId varchar(255) not null, numCode varchar(255) not null, charCode varchar(255) not null, name varchar(255) not null, course_value decimal(10, 4) not null, course_date date not null)";
            $result = $mysqli->query($query);
            if (!$result) {
                $this->errors[] = 'не удалось создать таблицу в БД';
            }
        }
        $mysqli->close();
    }
    
}

