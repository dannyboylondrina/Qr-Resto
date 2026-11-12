<?php

namespace App\Controllers;

use App\Libraries\OrderService;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    /**
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    protected $helpers = ['url', 'form', 'text', 'dineflow'];

    /** @var \CodeIgniter\Database\BaseConnection */
    protected $db;

    protected OrderService $orders;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->db     = \Config\Database::connect();
        $this->orders = new OrderService();
    }

    protected function json($data, int $status = 200)
    {
        return $this->response->setStatusCode($status)->setJSON($data);
    }

    protected function jsonError(string $message, int $status = 422)
    {
        return $this->json(['ok' => false, 'error' => $message], $status);
    }

    /** Responds with JSON to fetch() calls, or redirects back with a flash message for normal form posts. */
    protected function reply(string $message, bool $ok = true, ?string $to = null)
    {
        if ($this->request->isAJAX()) {
            return $this->json(['ok' => $ok, 'message' => $ok ? $message : null, 'error' => $ok ? null : $message], $ok ? 200 : 422);
        }
        $r = $to ? redirect()->to($to) : redirect()->back();
        return $r->with($ok ? 'success' : 'error', $message);
    }

    protected function render(string $view, array $data = []): string
    {
        return view($view, $data);
    }
}
