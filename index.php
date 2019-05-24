<?php 
    require_once 'CurrencyModel.php';
    session_start();
    if (isset($_GET['logout'])) {
        unset($_SESSION['auth']);
        header('Location: login.php');
    }
    if (!isset($_SESSION['auth'])) {
        header('Location: login.php');
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <p><a href="?logout=1">Выйти из системы</a></p>
        <h1>Курсы валют</h1>
        <?php
            $dbConfig = include('db.php');
            $model = new CurrencyModel($dbConfig['host'], $dbConfig['database'], $dbConfig['user'], $dbConfig['password']);
            if ($model->hasErrors()) {
                echo $model->getErrorstring();
            }
            $model->loadData("http://www.cbr.ru/scripts/XML_daily.asp");
            if ($model->hasErrors()) {
                echo $model->getErrorstring();
            }
            $currentDate = $model->getCurrentDate();
            $dataArray = $model->getCoursesByDate($currentDate);  
        ?>
        <table style="border-collapse: collapse;">
            <?php foreach ($dataArray as $arr) { ?>
            <tr>
                <?php foreach ($arr as $value) { ?>
                    <td style="border: 1px solid black; padding: 5px;"><?= $value ?></td>
                <?php } ?>
            </tr>
            <?php } ?>
        </table>
    </body>
</html>
