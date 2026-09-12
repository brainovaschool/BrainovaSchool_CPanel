<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebsiteSetup\Testimonial\TestimonialRequest;
use App\Repositories\WebsiteSetup\TestimonialRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TestimonialController extends Controller
{
    private $repo;

    public function __construct(TestimonialRepository $repo)
    {
        if (!Schema::hasTable('settings') && !Schema::hasTable('users')) {
            abort(400);
        }
        $this->repo = $repo;
    }

    public function index()
    {
        $data['testimonials'] = $this->repo->getAll();
        $data['title']        = ___('settings.testimonials');
        return view('website-setup.testimonial.index', compact('data'));
    }

    public function create()
    {
        $data['title'] = ___('settings.add_testimonial');
        return view('website-setup.testimonial.create', compact('data'));
    }

    public function store(TestimonialRequest $request)
    {
        $result = $this->repo->store($request);
        if ($result['status']) {
            return redirect()->route('testimonial.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['testimonial'] = $this->repo->show($id);
        if (!$data['testimonial']) {
            return redirect()->route('testimonial.index')->with('danger', ___('alert.not_found'));
        }
        $data['title'] = ___('settings.edit_testimonial');
        return view('website-setup.testimonial.edit', compact('data'));
    }

    public function update(TestimonialRequest $request, $id)
    {
        $result = $this->repo->update($request, $id);
        if ($result['status']) {
            return redirect()->route('testimonial.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function delete($id)
    {
        $result = $this->repo->destroy($id);
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.deleted'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }

    public function bulkDelete(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $result = $this->repo->bulkDestroy($ids);
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.deleted'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }

    public function bulkStatus(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $result = $this->repo->bulkStatus($ids, (int) $request->input('status', 1));
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.updated'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }
}
