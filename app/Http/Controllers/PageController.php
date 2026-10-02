<?php

namespace App\Http\Controllers;

use App\Services\SchoolContent;

class PageController extends Controller
{
    public function show(SchoolContent $content, string $page = 'home')
    {
        abort_unless(in_array($page, ['home','about','program','facilities','teachers','gallery','contact','privacy','terms']), 404);
        return view('pages.'.$page, ['site' => $content->all(), 'page' => $page]);
    }
}
