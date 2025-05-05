<?php
session_start();
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Cookie Parameters:\n";
print_r(session_get_cookie_params());

$_SESSION['test'] = 'test value';
echo "\nSession Data:\n";
print_r($_SESSION);
