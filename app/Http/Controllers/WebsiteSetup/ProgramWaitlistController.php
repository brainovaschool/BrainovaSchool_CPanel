<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use App\Repositories\WebsiteSetup\ProgramWaitlistRepository;

class ProgramWaitlistController extends Controller
{
    private $repo;

    function __construct(ProgramWaitlistRepository $repo)
    {
        if (!Schema::hasTable('settings') && !Schema::hasTable('users')) {
            abort(400);
        }
        $this->repo = $repo;
    }

    public function index()
    {
        $data['waitlist'] = $this->repo->all();
        $data['title']    = 'Program Waitlist';
        return view('website-setup.program-waitlist.index', compact('data'));
    }

    public function delete($id)
    {
        $result = $this->repo->destroy($id);
        if ($result['status']) {
            $success[0] = $result['message'];
            $success[1] = 'success';
            $success[2] = ___('alert.deleted');
            $success[3] = ___('alert.OK');
            return response()->json($success);
        }

        $error[0] = $result['message'];
        $error[1] = 'error';
        $error[2] = ___('common.opps');
        $error[3] = ___('alert.OK');
        return response()->json($error);
    }
}
