<?php

namespace App\Http\Controllers\WebsiteSetup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\WebsiteSetup\StudentFeatureAccessRepository;

class StudentFeatureAccessController extends Controller
{
    private $repo;

    public function __construct(StudentFeatureAccessRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $data['students'] = $this->repo->students();
        $data['features']  = $this->repo->getAll();
        $data['title']     = 'Student Feature Access';
        return view('website-setup.student-feature-access.index', compact('data'));
    }

    public function update(Request $request, string $featureKey)
    {
        $result = $this->repo->update($request, $featureKey);

        return redirect()->route('student-feature-access.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }
}
