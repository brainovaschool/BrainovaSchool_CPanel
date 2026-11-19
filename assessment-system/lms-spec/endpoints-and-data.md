# LMS endpoint map and field mapping

## Endpoints

| Method | Path | Direction | Purpose |
|---|---|---|---|
| GET | /api/lms/v1/students?grade=&updated_since= | LMS → Assessment | Student accounts and IDs (id, name, grade, status, parent_ids) |
| GET | /api/lms/v1/teachers | LMS → Assessment | Teacher accounts, roles and the classes they teach |
| GET | /api/lms/v1/parents?student_id= | LMS → Assessment | Parent accounts linked to a student |
| GET | /api/lms/v1/subjects, /courses, /grades | LMS → Assessment | Subject, course and grade records (id, name, code, grade_range) |
| GET | /api/lms/v1/syllabus?subject_id= | LMS → Assessment | Units, modules, terms, topics and learning objectives |
| POST | /api/lms/v1/assessment-assignments | Assessment → LMS | Assign an assessment to students, classes or applicants (with due date) |
| GET | /api/assessments/v1/assignments?student_id= | Assessment → LMS | Assigned assessments for a student account |
| POST | /api/assessments/v1/attempts | Browser → Assessment | Start an attempt. Server picks questions and returns the first item |
| PUT | /api/assessments/v1/attempts/{id}/responses | Browser → Assessment | Save answers with time spent. Adaptive attempts return the next item |
| POST | /api/assessments/v1/attempts/{id}/submit | Browser → Assessment | Submit, score and compute the report |
| POST | /api/assessments/v1/attempts/{id}/events | Browser → Assessment | Log interruptions (tab hidden, full screen exited, reconnects) |
| GET | /api/assessments/v1/attempts/{id}/report | Assessment → LMS | Diagnostic report as JSON (and PDF with ?format=pdf) |
| POST | /api/lms/v1/students/{id}/placements | Assessment → LMS | Write the teacher-approved recommended course or level to the student profile |
| GET/POST/PUT | /api/assessments/v1/questions | Teacher → Assessment | Question bank create, update, approve and import |
| POST | /api/assessments/v1/live-sessions | Teacher → Assessment | Create live sessions. Student devices join over WebSockets (Laravel Reverb) |
| WEBHOOK | assessment.completed | Assessment → LMS | Notify the LMS when a report is ready |

## Field mapping

| LMS field | Brainova field | Note |
|---|---|---|
| students.id | student_external_id | Existing Brainova student ID, never re-created |
| users.role | role | admin, teacher, student, parent (maps to Laravel roles/permissions) |
| courses.id | course_id | Existing course record |
| grades.id | grade | 1–10 |
| assessment_attempts.id | attempt uuid | Stable ID for linking reports |
| assessment_responses.* | responses[] | Per-question record with time and marks |
| assessment_topic_results.* | topic_results[] | Level, confidence and evidence per topic |
| student_placements.* | recommendation | System suggestion, teacher override, finalised flag |

## Data shapes in the demo (JSON, saved in the browser)

Top-level keys: v, sample, settings, bank, students, parents, assessments, attempts, supervision, sessions, sampleCheckAdded

- **student:** id, name, grade, parentId, accom
- **question (bank):** id, subject, topic, unit, grades, diff, type, text, objective, explanation, marks, time, prereq, source, status, b, c, marking, options, answer
- **assessment:** id, title, purpose, application, courseId, grades, ageGroup, level, outcomes, instructions, topics, excludeObj, model, dist, adaptive, mode, durationMin, perQuestionSec, nav, allowBack, allowSkip, allowChange, shuffleQ, shuffleOpts, attempts, autoSubmit, feedback, resultVis, passPct, parentSupervision, fullscreen, autoAdvance, status, assignedTo, created
- **attempt:** id, asmtId, studentId, studentName, grade, application, courseId, mode, model, subjectList, title, started, finished, durationMs, items, responses, events, trace, supervision, review, released, scores, preview
- **response (per question):** seq, qid, topic, subject, diff, type, objective, b, c, status, marks, maxMarks, answerRaw, answer, correctAnswer, timeMs, estSec, changed, visits, timedOut, probe, tag, why