<?php

header('Content-Encoding: UTF-8');
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.date('Y-m-d').'-affiliate-power.csv"');

if (!session_id()) session_start();
echo "\xEF\xBB\xBF"; //old excel versions compatibility
echo $_SESSION['affiliate-power-csv'];
//session_destroy();
