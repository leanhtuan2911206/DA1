<?php

$action = $_GET['action'] ?? '/';

match ($action) {
    '/'         => (new HomeController)->index(),
    'admin'     => (new AdminController)->index(),
    'tour-categories'     => (new AdminController)->tourCategories(),
};