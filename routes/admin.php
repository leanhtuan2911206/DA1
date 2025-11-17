<?php

$action = $_GET['action'] ?? '/';

match ($action) {
    '/'         => (new HomeController)->index(),
    'admin'     => (new AdminController)->index(),
    'login'     => (new AuthController)->login(),
    'logout'    => (new AuthController)->logout(),
    'tour-categories'  => (new TourCategoryController)->index(),

    'tour-categories-create'  => (new TourCategoryController)->create(),
    'tour-categories-store'   => (new TourCategoryController)->store(),
};