<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Usage in routes: ['filter' => 'auth'] (any staff) or ['filter' => 'auth:admin,cashier'].
 * Admin always passes. Accounts with role "pending" are never let in.
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('dineflow');
        $user = current_user();

        if (! $user || session('mfa_pending')) {
            if ($this->wantsJson($request)) {
                return service('response')->setStatusCode(401)->setJSON(['error' => 'Please sign in again.']);
            }
            session()->set('redirect_after_login', current_url());
            return redirect()->to(site_url('login'))->with('error', 'Please sign in to continue.');
        }

        if ($user['role'] === 'pending') {
            session()->remove('user_id');
            return redirect()->to(site_url('login'))->with('error', 'Your account is waiting for an admin to assign a role.');
        }

        if ($arguments && ! has_role(...$arguments)) {
            if ($this->wantsJson($request)) {
                return service('response')->setStatusCode(403)->setJSON(['error' => 'Not allowed for your role.']);
            }
            return redirect()->to(site_url($user['role'] === 'kitchen' ? 'kitchen' : 'staff'))
                ->with('error', 'That page is not available for your role.');
        }
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private function wantsJson(RequestInterface $request): bool
    {
        return $request->isAJAX() || str_contains($request->getHeaderLine('Accept'), 'application/json');
    }
}
