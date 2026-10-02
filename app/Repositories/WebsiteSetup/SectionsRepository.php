<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Interfaces\WebsiteSetup\SectionsInterface;
use App\Models\SectionTranslate;
use App\Models\WebsiteSetup\PageSections;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;

class SectionsRepository implements SectionsInterface
{

    use ReturnFormatTrait;
    use CommonHelperTrait;
    private $sections;
    private $section_trans;

    public function __construct(PageSections $sections, SectionTranslate $section_trans)
    {
        $this->sections      = $sections;
        $this->section_trans = $section_trans;
    }

    public function all()
    {
        return $this->sections->get();
    }

    public function getAll()
    {
        return $this->sections->paginate(Settings::PAGINATE);
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            $row              = new $this->sections;
            $row->name        = $request->name;
            $row->upload_id   = $this->UploadImageCreate($request->image, 'backend/uploads/sliders');
            $row->description = $request->description;
            $row->status      = $request->status;
            $row->serial      = $request->serial;
            $row->save();

            DB::commit();
            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function show($id)
    {
        return $this->sections->find($id);
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {
            $row = $this->sections->findOrfail($id);
            if ($request->name != '') {
                $row->name = $request->name;
            }

            if ($request->image != '') {
                $row->upload_id = $this->UploadImageUpdate($request->image, 'backend/uploads/frontend', $row->upload_id);
            }

            if ($request->description != '') {
                $row->description = $request->description;
            }

            // Process different section types data
            if ($row->key == 'social_links') {
                $data = [];
                foreach ($request->data['name'] as $key => $value) {
                    $data[] = [
                        'name' => $value,
                        'icon' => $request->data['icon'][$key],
                        'link' => $request->data['link'][$key],
                    ];
                }
                $row->data = $data;
            }

            if ($row->key == 'statement') {
                $data = [];
                foreach ($request->data['title'] as $key => $value) {
                    $data[] = [
                        'title'       => $value,
                        'description' => $request->data['description'][$key],
                    ];
                }
                $row->data      = $data;
                $row->video_url = $request->video_url ?: null;
            }

            if ($row->key == 'study_at') {
                $data = [];
                foreach ($request->data['title'] as $key => $value) {
                    $data[] = [
                        'icon'        => array_key_exists('icon', $request->data) ?
                        (array_key_exists($key, $request->data['icon']) ?
                            $this->UploadImageCreate($request->data['icon'][$key], 'backend/uploads/frontend') :
                            $row->data[$key]['icon']) :
                        $row->data[$key]['icon'],
                        'title'       => $value,
                        'description' => $request->data['description'][$key],
                    ];
                }
                $row->data = $data;
            }

            if ($row->key == 'explore') {
                $data = [];
                foreach ($request->data['title'] as $key => $value) {
                    $data[] = [
                        'tab'         => $request->data['tab'][$key],
                        'title'       => $value,
                        'description' => $request->data['description'][$key],
                    ];
                }
                $row->data = $data;
            }

            if ($row->key == 'why_choose_us' || $row->key == 'academic_curriculum') {
                $data = [];
                foreach ($request->data as $key => $value) {
                    $data[] = $value;
                }
                $row->data = $data;
            }

            $row->save();

            // Update translations
            if ($row->defaultTranslate) {
                $translateData = [
                    "name"        => $request->name ?? $row->name,
                    "description" => $request->description ?? $row->description,
                    "data"        => $row->data,
                ];
                $row->defaultTranslate->update($translateData);
            }

            DB::commit();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $row = $this->sections->find($id);
            $this->UploadImageDelete($row->upload_id);
            $row->delete();

            DB::commit();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function translateUpdate($request, $id)
    { 
        //dd($request->all());
        DB::beginTransaction();
        try {
            $delete_old = $this->section_trans->where('section_id', $id)->delete();
            $section    = $this->show($id);
            $pSection   = PageSections::find($id);
// dd($pSection);
            if ($request->has('name')) {
                foreach ($request->name as $key => $name) {
                    if ($pSection->key == 'study_at') {
                        $jsonData      = json_encode($request->data[$key]);
                        $data          = json_decode($jsonData, true);
                        $formattedData = collect($data['icon'])->map(function ($item, $index) use ($data) {
                            return [
                                'icon'        => intval($data['icon'][$index]),
                                'title'       => $data['title'][$index],
                                'description' => $data['description'][$index],
                            ];
                        })->toArray();
                    }

                    if ($pSection->key == 'statement') {
                        $jsonData      = json_encode($request->data[$key]);
                        $data          = json_decode($jsonData, true);
                        $formattedData = collect($data['title'])->map(function ($item, $index) use ($data) {
                            return [
                                'title'       => $data['title'][$index],
                                'description' => $data['description'][$index],
                            ];
                        })->toArray();
                    }

                    if ($pSection->key == 'explore') {
                        $jsonData      = json_encode($request->data[$key]);
                        $data          = json_decode($jsonData, true);
                        $formattedData = collect($data['tab'])->map(function ($item, $index) use ($data) {
                            return [
                                'tab'         => $data['tab'][$index],
                                'title'       => $data['title'][$index],
                                'description' => $data['description'][$index],
                            ];
                        })->toArray();
                    }

                    if ($pSection->key == 'why_choose_us' || $pSection->key == 'academic_curriculum') {
                        $formattedData = $request->data[$key];
                    }

                    $row              = new $this->section_trans;
                    $row->section_id  = $id;
                    $row->locale      = $key;
                    $row->name        = $name;
                    $row->description = isset($request->description[$key]) ? $request->description[$key] : @$section->description;
                    $row->data        = json_encode(@$formattedData);
                    $row->save();
                }
            }

            if ($pSection->key == 'social_links') {
                $formattedData = [];
                foreach ($request->data as $lang => $info) {
                    $formattedLangData = [];
                    for ($i = 0; $i < count($info['name']); $i++) {
                        $formattedLangData[] = [
                            'name' => $info['name'][$i],
                            'icon' => $info['icon'][$i],
                            'link' => $info['link'][$i],
                        ];
                    }
                    $formattedData[$lang] = json_encode($formattedLangData);
                    $row                  = new $this->section_trans;
                    $row->section_id      = $id;
                    $row->locale          = $lang;
                    $row->data            = $formattedData[$lang];
                    $row->save();
                }
            }

            DB::commit();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            dd($th);
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function translates($section_id)
    {
        return $this->section_trans->where('section_id', $section_id)->get()->groupBy('locale');
    }
}