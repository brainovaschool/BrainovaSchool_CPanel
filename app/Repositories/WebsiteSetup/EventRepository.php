<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Interfaces\WebsiteSetup\EventInterface;
use App\Models\Event;
use App\Models\EventTranslate;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;

class EventRepository implements EventInterface
{

    use ReturnFormatTrait;
    use CommonHelperTrait;
    private $event;
    private $event_trans;

    public function __construct(Event $event, EventTranslate $event_trans)
    {
        $this->event       = $event;
        $this->event_trans = $event_trans;
    }

    public function all()
    {
        return $this->event->where('session_id', setting('session'))->active()->get();
    }

    public function getAll()
    {
        return $this->event->where('session_id', setting('session'))->orderBy('id', 'desc')->paginate(Settings::PAGINATE);
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            $row              = new $this->event;
            $row->session_id  = setting('session');
            $row->title       = $request->title;
            $row->description = $request->description;
            $row->date        = $request->date;
            $row->start_time  = $request->start_time;
            $row->end_time    = $request->end_time;
            $row->address     = $request->address;
            $row->upload_id   = $this->UploadImageCreate($request->image, 'backend/uploads/event', $row->upload_id);
            $row->status      = $request->status;
            $row->save();

            $en_row              = new $this->event_trans;
            $en_row->event_id    = $row->id;
            $en_row->locale      = request()->locale ?? config('app.locale');
            $en_row->title       = $request->title;
            $en_row->description = $request->description;
            $en_row->address     = $request->address;
            $en_row->save();

            DB::commit();
            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function show($id)
    {
        return $this->event->find($id);
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {
            $row              = $this->event->findOrfail($id);
            $row->title       = $request->title;
            $row->description = $request->description;
            $row->date        = $request->date;
            $row->start_time  = $request->start_time;
            $row->end_time    = $request->end_time;
            $row->address     = $request->address;
            $row->upload_id   = $this->UploadImageUpdate($request->image, 'backend/uploads/event', $row->upload_id);
            $row->status      = $request->status;
            $row->save();

            // Add translation update
            if ($row->defaultTranslate) {
                $row->defaultTranslate->update([
                    'title'       => $request->title,
                    'description' => $request->description,
                    'address'     => $request->address,
                ]);
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
            $row = $this->event->find($id);
            $this->UploadImageDelete($row->upload_id);
            $row->delete();

            DB::commit();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkDestroy(array $ids): int
    {
        $deleted = 0;
        foreach ($ids as $id) {
            if ($this->destroy($id)['status']) {
                $deleted++;
            }
        }
        return $deleted;
    }

    public function bulkStatus(array $ids, int $status)
    {
        try {
            $this->event->whereIn('id', $ids)->update(['status' => $status]);
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function translateUpdate($request, $id)
    {

        DB::beginTransaction();
        try {
            $delete_old = $this->event_trans->where('event_id', $id)->delete();
            $slider     = $this->show($id);

            foreach ($request->title as $key => $title) {
                $row              = new $this->event_trans;
                $row->event_id    = $id;
                $row->locale      = $key;
                $row->title       = $title;
                $row->description = isset($request->description[$key]) ? $request->description[$key] : $slider->description;
                $row->address     = isset($request->address[$key]) ? $request->address[$key] : $slider->address;
                $row->save();
            }

            DB::commit();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            dd($th);
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function translates($event_id)
    {
        return $this->event_trans->where('event_id', $event_id)->get()->groupBy('locale');
    }
}