<?php

namespace App\Http\Controllers\WebsiteSetup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\WebsiteSetup\CounterRepository;
use Illuminate\Support\Facades\Schema;
use App\Http\Requests\WebsiteSetup\Counter\CounterStoreRequest;
use App\Http\Requests\WebsiteSetup\Counter\CounterUpdateRequest;
use App\Repositories\LanguageRepository;

class CounterController extends Controller
{
    private $counterRepo;
    private $lang_repo;

    function __construct(CounterRepository $counterRepo, LanguageRepository $lang_repo)
    {
        if (!Schema::hasTable('settings') && !Schema::hasTable('users')  ) {
            abort(400);
        }
        $this->counterRepo                  = $counterRepo;
        $this->lang_repo                  = $lang_repo;
    }

    public function index()
    {
        $data['counter'] = $this->counterRepo->getAll();
        $data['title'] = ___('settings.Counter');
        return view('website-setup.counter.index', compact('data'));
    }

    public function create()
    {
        $data['title']       = ___('website.create_counter');
        return view('website-setup.counter.create', compact('data'));
    }

    public function store(CounterStoreRequest $request)
    {
        $result = $this->counterRepo->store($request);
        if($result['status']){
            return redirect()->route('counter.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['counter']      = $this->counterRepo->show($id);
        $data['title']       = ___('website.edit_counter');
        return view('website-setup.counter.edit', compact('data'));
    }

    public function update(CounterUpdateRequest $request, $id)
    {
        $result = $this->counterRepo->update($request, $id);
        if($result['status']){
            return redirect()->route('counter.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function translate($id)
    {
        $data['counter']      = $this->counterRepo->show($id);
        $data['translates']      = $this->counterRepo->translates($id);
        $data['languages']      = $this->lang_repo->all();
        $data['title']       = ___('website.edit_about');
        return view('website-setup.counter.translate', compact('data'));
    }


    public function translateUpdate(Request $request, $id)
    {
        $result = $this->counterRepo->translateUpdate($request, $id);
        if($result['status']){
            return redirect()->route('counter.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function delete($id)
    {
        $result = $this->counterRepo->destroy($id);
        if($result['status']):
            $success[0] = $result['message'];
            $success[1] = 'success';
            $success[2] = ___('alert.deleted');
            $success[3] = ___('alert.OK');
            return response()->json($success);
        else:
            $success[0] = $result['message'];
            $success[1] = 'error';
            $success[2] = ___('alert.oops');
            return response()->json($success);
        endif;
    }

    public function bulkDelete(\Illuminate\Http\Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $deleted = $this->counterRepo->bulkDestroy($ids);
        if ($deleted === 0) {
            return response()->json([___('alert.something_went_wrong_please_try_again'), 'error', ___('alert.oops'), ___('alert.OK')]);
        }
        return response()->json([___('alert.deleted_successfully'), 'success', ___('alert.deleted'), ___('alert.OK')]);
    }

    public function bulkStatus(\Illuminate\Http\Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $result = $this->counterRepo->bulkStatus($ids, (int) $request->input('status', 1));
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.updated'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }
}
