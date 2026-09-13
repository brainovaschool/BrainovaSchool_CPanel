<?php

namespace App\Http\Controllers\WebsiteSetup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\WebsiteSetup\ContactInfoRepository;
use Illuminate\Support\Facades\Schema;
use App\Http\Requests\WebsiteSetup\ContactInfo\ContactInfoStoreRequest;
use App\Http\Requests\WebsiteSetup\ContactInfo\ContactInfoUpdateRequest;
use App\Repositories\LanguageRepository;

class ContactInfoController extends Controller
{
    private $contactInfoRepo;
    private $lang_repo;

    function __construct(ContactInfoRepository $contactInfoRepo, LanguageRepository $lang_repo)
    {
        if (!Schema::hasTable('settings') && !Schema::hasTable('users')  ) {
            abort(400);
        }
        $this->contactInfoRepo                  = $contactInfoRepo;
        $this->lang_repo                  = $lang_repo;
    }

    public function index()
    {
        $data['contact_info'] = $this->contactInfoRepo->getAll();
        $data['title'] = ___('settings.contact_information');
        return view('website-setup.contact-info.index', compact('data'));
    }

    public function create()
    {
        $data['title']       = ___('website.create_contact_information');
        return view('website-setup.contact-info.create', compact('data'));
    }

    public function store(ContactInfoStoreRequest $request)
    {
        $result = $this->contactInfoRepo->store($request);
        if($result['status']){
            return redirect()->route('contact-info.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['contact_info']      = $this->contactInfoRepo->show($id);
        $data['title']       = ___('website.edit_contact_information');
        return view('website-setup.contact-info.edit', compact('data'));
    }

    public function update(ContactInfoUpdateRequest $request, $id)
    {
        $result = $this->contactInfoRepo->update($request, $id);
        if($result['status']){
            return redirect()->route('contact-info.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function translate($id)
    {
        $data['contact_info']      = $this->contactInfoRepo->show($id);
        $data['translates']      = $this->contactInfoRepo->translates($id);
        $data['languages']      = $this->lang_repo->all();
        $data['title']       = ___('website.edit_about');
        return view('website-setup.contact-info.translate', compact('data'));
    }


    public function translateUpdate(Request $request, $id)
    {
        $result = $this->contactInfoRepo->translateUpdate($request, $id);
        if($result['status']){
            return redirect()->route('contact-info.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function delete($id)
    {
        $result = $this->contactInfoRepo->destroy($id);
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
        $deleted = $this->contactInfoRepo->bulkDestroy($ids);
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
        $result = $this->contactInfoRepo->bulkStatus($ids, (int) $request->input('status', 1));
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.updated'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }
}
