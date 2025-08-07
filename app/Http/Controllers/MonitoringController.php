<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Monitoring;

class MonitoringController extends Controller
{
    public function index()
    {
        $monitorings = Monitoring::where('is_active', 1)->orderBy('name')->get();
        return view('monitoring.index', compact('monitorings'));
    }
}
