<?php

namespace App\Filters;

use App\Models\UserModel;
use App\Entities\User;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class JwtAuthFilter implements FilterInterface
{
	public function before(RequestInterface $request, $arguments = null)
	{
		$authHeader = $request->getHeaderLine('Authorization');
		log_message('debug', 'JwtAuthFilter Authorization header: ' . ($authHeader ?: '[empty]'));

		if (! preg_match('/Bearer\\s+(.*)$/i', $authHeader, $m)) {
			log_message('debug', 'JwtAuthFilter: missing or invalid Bearer token format');
			return $this->unauthorized('Missing or invalid Authorization header');
		}

		$token = trim($m[1]);
		log_message('debug', 'JwtAuthFilter extracted token (first 20 chars): ' . substr($token, 0, 20) . '...');

		$claims = service('jwt')->verify($token);
		if ($claims === false || empty($claims['sub'])) {
			log_message('debug', 'JwtAuthFilter: token verification failed or missing sub claim');
			return $this->unauthorized('Invalid or expired token');
		}

		$userId = (int) $claims['sub'];
		$user   = (new UserModel())->find($userId);
		if (! $user instanceof User) {
			log_message('debug', 'JwtAuthFilter: user not found for ID ' . $userId);
			return $this->unauthorized('User not found');
		}

		service('authUser')->setUser($user);
		log_message('debug', 'JwtAuthFilter: authenticated user ID ' . $userId);

		return null;
	}

	public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
	{
		// no-op
	}

	private function unauthorized(string $message)
	{
		$response = service('response');
		$response->setStatusCode(401);
		$response->setJSON([
			'error' => [
				'code'    => 401,
				'message' => $message,
			],
		]);
		return $response;
	}
}
