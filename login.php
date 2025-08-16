<?php
require 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

if(!empty($data['username']) && !empty($data['password'])){
    $stmt = $pdo -> prepare('SELECT * FROM users WHERE username = ?');
    $stmt -> execute([$email]);
    $user = $stmt -> fetch();

        if($user && password_verify($data['password'], $user['password'])){
            $token = generateToken();
            $expires = date('Y-m-d H:i:s' , strtotime('+1 day'));

            //store tokend in the database
             
        }
}




?>