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
    'tours-get-info' => (new TourController)->getTourInfo(),

    'bookings'              => (new BookingController)->index(),
    'bookings-create'        => (new BookingController)->create(),
    'bookings-store'         => (new BookingController)->store(),
    'bookings-edit'          => (new BookingController)->edit(),
    'bookings-update'        => (new BookingController)->update(),
    'bookings-update-status' => (new BookingController)->updateStatus(),
    'bookings-delete'        => (new BookingController)->delete(),

    'guides'         => (new GuideController)->index(),
    'guides-create'  => (new GuideController)->create(),
    'guides-store'   => (new GuideController)->store(),
    'guides-edit'    => (new GuideController)->edit(),
    'guides-update'  => (new GuideController)->update(),
    'guides-update-status' => (new GuideController)->updateStatus(),
    'guides-delete'  => (new GuideController)->delete(),

    'users'          => (new UserController)->index(),
    'users-create'   => (new UserController)->create(),
    'users-edit'    => (new UserController)->edit(),
    'users-update'  => (new UserController)->update(),
    'users-store'   => (new UserController)->store(),
    'users-delete'  => (new UserController)->delete(),
};