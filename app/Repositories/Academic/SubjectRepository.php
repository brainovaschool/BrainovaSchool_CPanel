<?php

namespace App\Repositories\Academic;

use App\Enums\Settings;
use App\Interfaces\Academic\SubjectInterface;
use App\Models\Academic\Subject;
use App\Traits\ReturnFormatTrait;

class SubjectRepository implements SubjectInterface
{
    use ReturnFormatTrait;
    private $subject;

    public function __construct(Subject $subject)
    {
        $this->subject = $subject;
    }

    public function all()
    {
        return $this->subject->active()->get();
    }

    public function getAll()
    {
        return $this->subject->latest()->paginate(Settings::PAGINATE);
    }

    public function store($request)
    {
        try {
            $subjectStore              = new $this->subject;
            $subjectStore->name        = $request->name;
            $subjectStore->code        = $request->code;
            $subjectStore->type        = $request->type;
            $subjectStore->status      = $request->status;
            $subjectStore->save();
            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function show($id)
    {
        return $this->subject->find($id);
    }

    public function update($request, $id)
    {
        try {
            $subjectUpdate              = $this->subject->findOrfail($id);
            $subjectUpdate->name        = $request->name;
            $subjectUpdate->code        = $request->code;
            $subjectUpdate->type        = $request->type;
            $subjectUpdate->status      = $request->status;
            $subjectUpdate->save();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy($id)
    {
        try {
            $subjectDestroy = $this->subject->find($id);
            $subjectDestroy->delete();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
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
}
