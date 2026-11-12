<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        $reviews = $this->db->table('reviews')->where('is_approved', 1)->orderBy('id', 'DESC')->limit(6)->get()->getResultArray();
        $seats   = (int) $this->db->table('dining_tables')->selectSum('seats')->get()->getRow()->seats;
        $tables  = $this->db->table('dining_tables')->countAllResults();

        return view('home', ['title' => 'Salo — Modern Filipino Kitchen · Makati', 'reviews' => $reviews, 'seats' => $seats ?: 56, 'tableCount' => $tables]);
    }

    public function lang(string $code)
    {
        if (in_array($code, ['en', 'fil'], true)) {
            session()->set('locale', $code);
        }
        $back = previous_url();
        return redirect()->to($back && str_starts_with($back, base_url()) ? $back : site_url('/'));
    }
}
