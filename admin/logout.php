<?php
require_once __DIR__ . '/includes/bootstrap.php';
destroyAdminSession();
header('Location: index.php');
exit;
