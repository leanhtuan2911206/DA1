<?php

$action = $_GET['action'] ?? '/';

match ($action) {
    '/'         => (new HomeController)->index(),
    'admin'     => (new AdminController)->index(),
    'login'     => (new AuthController)->login(),
    'logout'    => (new AuthController)->logout(),
    'tour-categories'  => (new TourCategoryController)->index(),
    'tours'             => (new TourController)->index(),

    'tour-categories-create'  => (new TourCategoryController)->create(),
    'tour-categories-store'   => (new TourCategoryController)->store(),
    'tour-categories-edit'    => (new TourCategoryController)->edit(),
    'tour-categories-update'  => (new TourCategoryController)->update(),
    'tour-categories-delete'  => (new TourCategoryController)->delete(),
    
    'tours-create'  => (new TourController)->create(),
    'tours-store'   => (new TourController)->store(),
    'tours-edit'    => (new TourController)->edit(),
    'tours-update'  => (new TourController)->update(),
    'tours-delete'  => (new TourController)->delete(),
};