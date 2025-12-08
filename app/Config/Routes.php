<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->setAutoRoute(false);

// Public
$routes->post('auth/register', 'API\AuthController::register');
$routes->post('auth/login', 'API\AuthController::login');

// Protected with JWT
$routes->group('', ['filter' => 'jwt'], static function (RouteCollection $routes) {
	// Auth
	$routes->post('auth/logout', 'API\AuthController::logout');
	$routes->get('auth/me', 'API\AuthController::me');

	// Users
	$routes->get('users/(:num)', 'API\UserController::show/$1');
	$routes->put('users/(:num)', 'API\UserController::update/$1');

	// Forums
	$routes->post('forums', 'API\ForumController::store');
	$routes->get('forums', 'API\ForumController::index');
	$routes->get('forums/recommended', 'API\ForumController::recommended');
	$routes->get('forums/(:num)', 'API\ForumController::show/$1');
	$routes->patch('forums/(:num)', 'API\ForumController::update/$1', ['filter' => 'forumAdmin']);
	$routes->delete('forums/(:num)', 'API\ForumController::destroy/$1', ['filter' => 'forumAdmin']);

	// Forum members
	$routes->post('join/forum', 'API\ForumMemberController::joinByCode');
	$routes->post('forums/(:num)/join', 'API\ForumMemberController::join/$1');
	$routes->post('forums/(:num)/leave', 'API\ForumMemberController::leave/$1', ['filter' => 'forumMember']);
	$routes->get('forums/(:num)/members', 'API\ForumMemberController::members/$1');
	$routes->get('forums/(:num)/membership', 'API\ForumMemberController::membership/$1');
	$routes->patch('forums/(:num)/members/(:num)', 'API\ForumMemberController::update/$1/$2', ['filter' => 'forumAdmin']);

	// Tasks
	$routes->post('forums/(:num)/tasks', 'API\TaskController::store/$1', ['filter' => 'forumMember']);
	$routes->get('forums/(:num)/tasks', 'API\TaskController::index/$1');
	$routes->get('tasks/(:num)', 'API\TaskController::show/$1');
	$routes->patch('tasks/(:num)', 'API\TaskController::update/$1');
	$routes->delete('tasks/(:num)', 'API\TaskController::destroy/$1');
	$routes->post('tasks/(:num)/attachments', 'API\TaskController::attach/$1', ['filter' => 'forumMember']);

	// Reminders
	$routes->post('tasks/(:num)/reminder', 'API\ReminderController::store/$1');
	$routes->get('reminders', 'API\ReminderController::index');
	$routes->delete('reminders/(:num)', 'API\ReminderController::destroy/$1');

	// Discussions
	$routes->post('forums/(:num)/discussions', 'API\DiscussionController::store/$1', ['filter' => 'forumMember']);
	$routes->post('discussions/(:num)/replies', 'API\DiscussionController::reply/$1', ['filter' => 'discussionMember']);
	$routes->get('forums/(:num)/discussions', 'API\DiscussionController::index/$1');
	$routes->get('discussions/(:num)', 'API\DiscussionController::show/$1');
	$routes->patch('discussions/(:num)', 'API\DiscussionController::update/$1', ['filter' => 'discussionMember']);
	$routes->delete('discussions/(:num)', 'API\DiscussionController::destroy/$1', ['filter' => 'discussionMember']);

	// Notes
	$routes->post('forums/(:num)/notes', 'API\NoteController::store/$1', ['filter' => 'forumMember']);
	$routes->get('forums/(:num)/notes', 'API\NoteController::index/$1');
	$routes->get('notes/(:num)', 'API\NoteController::show/$1');
	$routes->patch('notes/(:num)', 'API\NoteController::update/$1');
	$routes->delete('notes/(:num)', 'API\NoteController::destroy/$1');

	// Media
	$routes->post('media', 'API\MediaController::store');
	$routes->get('forums/(:num)/media', 'API\MediaController::index/$1');
	$routes->get('media/(:num)', 'API\MediaController::show/$1');
	$routes->delete('media/(:num)', 'API\MediaController::destroy/$1');

	// Search
	$routes->get('search', 'API\SearchController::index');

	// Notifications
	$routes->get('notifications', 'API\NotificationController::index');
	// (Optional v1.1) TODO: GET /notifications/stream - SSE stream in the future

	// Counts
	$routes->get('counts', 'API\CountController::index');
	$routes->get('counts/detailed', 'API\CountController::detailed');
	$routes->get('counts/forums', 'API\CountController::forums');
	$routes->get('counts/(:segment)', 'API\CountController::show/$1');
});

// Swagger Docs
$routes->get('docs', 'Docs::index');
