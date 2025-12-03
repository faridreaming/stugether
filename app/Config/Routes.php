<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->setAutoRoute(false);

// ========================
// PUBLIC ROUTES (No Authentication Required)
// ========================
$routes->group('api', ['namespace' => 'App\Controllers\API'], static function ($routes) {
    // Auth - Public endpoints
    $routes->post('auth/register', 'AuthController::register');
    $routes->post('auth/login', 'AuthController::login');
});

// ========================
// PROTECTED ROUTES (JWT Authentication Required)
// ========================
$routes->group('api', ['namespace' => 'App\Controllers\API', 'filter' => 'jwt'], static function ($routes) {
    
    // ========================
    // Auth Routes (Protected)
    // ========================
    $routes->post('auth/logout', 'AuthController::logout');
    $routes->get('auth/me', 'AuthController::me');

    // ========================
    // User Routes
    // ========================
    $routes->get('users/(:num)', 'UserController::show/$1');
    $routes->put('users/(:num)', 'UserController::update/$1');

    // ========================
    // Forum Routes
    // ========================
    $routes->post('forums', 'ForumController::store');
    $routes->get('forums', 'ForumController::index');
    $routes->get('forums/recommended', 'ForumController::recommended');
    $routes->get('forums/(:num)', 'ForumController::show/$1');
    $routes->patch('forums/(:num)', 'ForumController::update/$1', ['filter' => 'forumAdmin']);
    $routes->delete('forums/(:num)', 'ForumController::destroy/$1', ['filter' => 'forumAdmin']);
    
    // Forum Members
    $routes->post('forums/(:num)/join', 'ForumController::join/$1');
    $routes->post('forums/(:num)/leave', 'ForumController::leave/$1');
    $routes->get('forums/(:num)/members', 'ForumController::members/$1');
    $routes->patch('forums/(:num)/members/(:num)', 'ForumMemberController::update/$1/$2', ['filter' => 'forumAdmin']);

    // ========================
    // Task Routes
    // ========================
    $routes->get('forums/(:num)/tasks', 'TaskController::index/$1');
    $routes->post('forums/(:num)/tasks', 'TaskController::store/$1');
    $routes->get('tasks/(:num)', 'TaskController::show/$1');
    $routes->patch('tasks/(:num)', 'TaskController::update/$1');
    $routes->delete('tasks/(:num)', 'TaskController::destroy/$1');

    // ========================
    // Note Routes
    // ========================
    $routes->get('forums/(:num)/notes', 'NoteController::index/$1');
    $routes->post('forums/(:num)/notes', 'NoteController::store/$1');
    $routes->get('notes/(:num)', 'NoteController::show/$1');
    $routes->patch('notes/(:num)', 'NoteController::update/$1');
    $routes->delete('notes/(:num)', 'NoteController::destroy/$1');

    // ========================
    // Discussion Routes
    // ========================
    $routes->get('forums/(:num)/discussions', 'DiscussionController::index/$1');
    $routes->post('forums/(:num)/discussions', 'DiscussionController::store/$1');
    $routes->get('discussions/(:num)', 'DiscussionController::show/$1');
    $routes->patch('discussions/(:num)', 'DiscussionController::update/$1');
    $routes->delete('discussions/(:num)', 'DiscussionController::destroy/$1');
    $routes->post('discussions/(:num)/replies', 'DiscussionController::reply/$1');

    // ========================
    // Reminder Routes
    // ========================
    $routes->get('reminders', 'ReminderController::index');
    $routes->post('reminders', 'ReminderController::create');
    $routes->delete('reminders/(:num)', 'ReminderController::delete/$1');
});

// ========================
// DOCUMENTATION
// ========================
$routes->get('api/docs', 'Docs::index');
