<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;

class Reviews extends BaseController
{
    public function index()
    {
        $reviews = $this->db->table('reviews r')->select('r.*, o.code')->join('orders o', 'o.id = r.order_id', 'left')
            ->orderBy('r.is_approved', 'ASC')->orderBy('r.id', 'DESC')->limit(300)->get()->getResultArray();
        $avg = $this->db->query('SELECT AVG(rating) a, COUNT(*) n FROM reviews')->getRowArray();
        return view('staff/reviews', ['title' => 'Guest reviews', 'active' => 'reviews', 'reviews' => $reviews, 'avg' => $avg]);
    }

    public function action(int $id, string $action)
    {
        $r = $this->db->table('reviews')->where('id', $id)->get()->getRowArray();
        if (! $r) {
            return $this->reply('Review not found.', false);
        }
        if ($action === 'delete') {
            $this->db->table('reviews')->where('id', $id)->delete();
        } else {
            $this->db->table('reviews')->where('id', $id)->update(['is_approved' => $action === 'approve' ? 1 : 0]);
        }
        log_activity('review_' . $action, 'review', $id, $r['rating'] . '★ ' . mb_substr((string) $r['comment'], 0, 80));
        return $this->reply(['approve' => 'Review is now shown on the home page.', 'hide' => 'Review hidden.', 'delete' => 'Review deleted.'][$action]);
    }
}
