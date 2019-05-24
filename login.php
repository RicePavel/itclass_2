<?php 
    require_once 'CurrencyModel.php';
    require_once 'SimpleUserModel.php';
    session_start();
    
    $errors = '';
    if (isset($_POST['submit'])) {
        $userName = $_POST['login'];
        $password = $_POST['password'];
        $userModel = new SimpleUserModel();
        if ($userModel->valid($userName, $password)) {
            $_SESSION['auth'] = 1;
            header('Location: index.php');
        } else {
            $errors = 'Неверные логин или пароль';
        }
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <h1>вход в систему</h1>
        <p><?= $errors ?></p>
        <form method="POST">
            Логин: <input type="text" name="login" /> <br/>
            Пароль: <input type="password" name="password" /> <br/>
            <input type="submit" name="submit" value="Вход" />
        </form>
    </body>
</html>
