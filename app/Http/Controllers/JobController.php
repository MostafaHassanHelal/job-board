<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\JobFilterService;

class JobController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(JobFilterService::apply($request));
    }
}
