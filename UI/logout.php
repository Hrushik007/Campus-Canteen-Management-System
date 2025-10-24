<!-- =====================================================
     FILE: logout.php
     Logout Handler
     ===================================================== -->
<?php
session_start();
session_destroy();
header("Location: login.php");
exit();
?>
