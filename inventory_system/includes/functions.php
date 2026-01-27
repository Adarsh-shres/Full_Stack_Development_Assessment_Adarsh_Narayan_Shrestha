<?php  

function e($string){
    return htmlspecialchars($string,ENT_QUOTES,'UTF-8');
}

function require_login(){
    if(!isset($_SESSION['user_id'])){
        header('Location: ../public/login.php');
        exit();
    }
}
function require_admin() {
    require_login();
    if ($_SESSION['role'] !== 'admin') {
        die("Access Denied: You do not have permission to view this page.");
    }
}
?>
