<?php

namespace App\Interfaces\StudentInfo;

interface StudentInterface
{

    public function all();

    public function getStudents($request, ?int $teacherStaffId = null);

    public function getPaginateAll();

    public function getPaginateAllForTeacher(int $staffId);

    public function getStudentUserIdsForTeacher(int $staffId): array;

    public function searchStudents($request, ?int $teacherStaffId = null);

    public function store($request);

    public function show($id);

    public function update($request, $id);

    public function destroy($id);

    public function sessionClassStudentVisibleToTeacher(int $sessionClassStudentId, int $staffId): bool;

    public function studentVisibleToTeacher(int $studentId, int $staffId): bool;
}
