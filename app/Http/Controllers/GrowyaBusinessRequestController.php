<?php
namespace App\Http\Controllers;
use App\Models\Business;
use Illuminate\Http\Request;
class GrowyaBusinessRequestController extends Controller {
    public function index(Request $request) {
        $businesses = Business::where('owner_id', $request->user()->id)->latest('id')->get();
        return view('growya-dashboards.business-requests', compact('businesses'));
    }
}
