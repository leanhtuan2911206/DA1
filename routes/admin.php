<?php

$action = $_GET['action'] ?? '/';

match ($action) {
    '/'         => (new HomeController)->index(),
    'admin'     => (new AdminController)->index(),
    'login'     => (new AuthController)->login(),
    'logout'    => (new AuthController)->logout(),
};