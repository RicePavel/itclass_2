<?php

require_once 'CurrencyModel.php';

$dbConfig = include('db.php');
$model = new CurrencyModel($dbConfig['host'], $dbConfig['database'], $dbConfig['user'], $dbConfig['password']);
if ($model->hasErrors()) {
    echo $model->getErrorstring();
}
$model->loadData("http://www.cbr.ru/scripts/XML_daily.asp");
if ($model->hasErrors()) {
    echo $model->getErrorstring();
}

$currencyId = $_GET['id'];
$dateFrom = $_GET['dateFrom'];
$dateTo = $_GET['dateTo'];
$result = array(
    "error" => '',
    "data" => array()
);

if (empty($currencyId)) {
    $result['error'] = 'Не передан ИД валюты';
} else if (empty($dateFrom) && empty($dateTo)) {
    $result['error'] = 'Не задан период';
} else {
    $data = $model->getCoursesByPeriod($currencyId, $dateFrom, $dateTo);
    $result['data'] = $data;
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);




