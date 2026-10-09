"use strict";

// Return issue book
function showConfirmation(url) {
    Swal.fire({
        title: 'Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}
// Return issue book


// Full calendar start

var calendar = $('#calendar');

if (calendar.length) {

    $.ajax({
        type: "GET",
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: '/events-current-month',
        success: function (data) {
            showFullCalendar(data);
        },
        error: function (data) {
            console.log(data);
        }
    });
}



function showFullCalendar(items) {
    var calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
        });

        var calendar = new FullCalendar.Calendar(calendarEl, {
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            editable: true,
            droppable: true,
            events: items
        });

        calendar.render();
    }

}


// document.addEventListener('DOMContentLoaded', function() {
//     var calendarEl = document.getElementById('calendar');
//     var calendar = new FullCalendar.Calendar(calendarEl, {
//       initialView: 'dayGridMonth',
//     });

//     var calendar = new FullCalendar.Calendar(calendarEl, {
//         headerToolbar: {
//           left: 'prev,next today',
//           center: 'title',
//           right: 'dayGridMonth,timeGridWeek,timeGridDay'
//         },
//         editable: true,
//         droppable: true,
// 		events: data
//       });

//     calendar.render();
// });
// Full calendar end




$(document).on('keyup.nice-select-search', '.parent .nice-select', function () {
    var $self = $(this);
    var $text = $self.find('.nice-select-search').val();
    var url = $('#url').val();

    var formData = {
        text: $text,
    }

    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/parent/get-parent',
        success: function (data) {


            var section_options = '';
            var section_li = '';

            $.each(JSON.parse(data), function (i, item) {
                section_options += "<option value=" + i + ">" + item + "</option>";
                section_li += "<li data-value=" + i + " class='option'>" + item + "</li>";
            });

            $("select.parent option").not(':first').remove();
            $("select.parent").append(section_options);

            $("div .parent .list li").not(':first').remove();
            $("div .parent .list").append(section_li);
        },
        error: function (data) {
            console.log(data);
        }
    });
});


// $(document).on('keyup', '.nice-select-search-box', function(event) {
//     //
// });

$("#subject").on('change', function (e) {
    getStudents();
});

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
})


// Start get section
$("#getSections").on('change', function (e) {
    var classId = $("#getSections").val();
    var url = $('#url').val();
    var formData = {
        id: classId,
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/class-setup/get-sections',
        success: function (data) {

            var section_options = '';
            var section_li = '';

            $.each(JSON.parse(data), function (i, item) {
                section_options += "<option value=" + item.section.id + ">" + item.section.name + "</option>";
                section_li += "<li data-value=" + item.section.id + " class='option'>" + item.section.name + "</li>";
            });

            // console.log(section_options);


            $("select.sections option").not(':first').remove();
            $("select.sections").append(section_options);

            $("div .sections .current").html($("div .sections .list li:first").html());
            $("div .sections .list li").not(':first').remove();
            $("div .sections .list").append(section_li);


        },
        error: function (data) {
            console.log(data);
        }
    });
});
// End get section


// Start promote students
// get classes
$(".session").on('change', function (e) {
    var sessionId = $(".session").val();
    var url = $('#url').val();
    var formData = {
        id: sessionId,
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/promote/students/get-class',
        success: function (data) {
            var session_options = '';
            var session_li = '';
            $.each(JSON.parse(data), function (i, item) {
                session_options += "<option value=" + item.classes_id + ">" + item.class.name + "</option>";
                session_li += "<li data-value=" + item.classes_id + " class='option'>" + item.class.name + "</li>";
            });
            $("select.classes option").not(':first').remove();
            $("select.classes").append(session_options);

            $("div .classes .current").html($("div .classes .list li:first").html());
            $("div .classes .list li").not(':first').remove();
            $("div .classes .list").append(session_li);

            $("div .promoteSections .current").html($("div .promoteSections .list li:first").html());
        },
        error: function (data) {
            console.log(data);
        }
    });
});
// get classes
$(".classes").on('change', function (e) {
    var sessionId = $(".session").val();
    var classId = $(".classes").val();
    var url = $('#url').val();
    var formData = {
        session: sessionId,
        class: classId,
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/promote/students/get-sections',
        success: function (data) {
            var section_options = '';
            var section_li = '';
            $.each(JSON.parse(data), function (i, item) {
                section_options += "<option value=" + item.section.id + ">" + item.section.name + "</option>";
                section_li += "<li data-value=" + item.section.id + " class='option'>" + item.section.name + "</li>";
            });
            $("select.promoteSections option").not(':first').remove();
            $("select.promoteSections").append(section_options);

            $("div .promoteSections .current").html($("div .promoteSections .list li:first").html());
            $("div .promoteSections .list li").not(':first').remove();
            $("div .promoteSections .list").append(section_li);
        },
        error: function (data) {
            console.log(data);
        }
    });
});
// End promote students

// Start get section
$(".getPromoteSections").on('change', function (e) {
    var classId = $(".getPromoteSections").val();
    var url = $('#url').val();
    var formData = {
        id: classId,
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/class-setup/get-sections',
        success: function (data) {

            var section_options = '';
            var section_li = '';

            $.each(JSON.parse(data), function (i, item) {
                section_options += "<option value=" + item.section.id + ">" + item.section.name + "</option>";
                section_li += "<li data-value=" + item.section.id + " class='option'>" + item.section.name + "</li>";
            });

            $("select.promoteSections option").not(':first').remove();
            $("select.promoteSections").append(section_options);

            $("div .promoteSections .list li").not(':first').remove();
            $("div .promoteSections .list").append(section_li);
        },
        error: function (data) {
            console.log(data);
        }
    });
});
// End get section

// Start get exam assign section
function changeExamAssignClass(element) {


    var id = $(element).val();
    var url = $('#url').val();

    if ($("#form_type").val() == "update") {
        $("div .subjects .current").html($("div .subjects .list li:first").html());
        $("#subject_marks_distribute tbody#main").html('');
    } else {
        $("#elect2-subjectMark-container").html('');

        $("select.subjects option").not(':first').remove();


        $("div .subjects .current").html($("div .subjects .list li:first").html());
        $("div .subjects .list li").not(':first').remove();

        $("#subject_marks_distribute tbody#main").html('');

    }

    var formData = {
        id: id,
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/exam-assign/get-sections',
        success: function (data) {

            var sections = '';
            $.each(JSON.parse(data), function (i, item) {

                sections += "<div class='form-check'>";
                sections += "<input class='form-check-input sections' onclick='return checkSection(this)' type='checkbox' name='sections[]' value=" + item.section.id + " id='flexCheckDefault' />";
                sections += "<label class='form-check-label ps-2 pe-5' for='flexCheckDefault'>" + item.section.name + "</label>";
                sections += "</div>";

            });

            $(".exam-assign-section").html(sections);

        },
        error: function (data) {
            console.log(data);
        }
    });
}
// End get exam assign section


function checkSection(element) {

    var classes_id = $(".class").val();
    var url = $('#url').val();

    console.log(classes_id);




    var current_section = '';
    var sections = [];


    if ($("#form_type").val() == "update") {

        $("input[name^='sections']").map(function (idx, ele) {

            if ($(ele).val() != $(element).val()) {
                $(ele).prop('checked', false);
            }

        });

        if ($(element).is(':checked')) {
            var current_section = $(element).val();
        }

        $("#subject_marks_distribute tbody#main").html('');



    } else {

        if ($(element).is(':checked')) {
            var current_section = $(element).val();
        }

        var sections = $("input[name^='sections']").map(function (idx, ele) {

            if ($(ele).is(':checked')) {
                return $(ele).val();
            }

        }).get();



    }

    var formData = {
        classes_id: classes_id,
        section_id: current_section,
        sections: sections,
        form_type: $("#form_type").val(),
    }

    console.log(formData);



    $.ajax({
        type: "GET",
        dataType: 'json',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/exam-assign/get-subjects',
        async: false,
        success: function (data) {


            if ($("#form_type").val() == "update") {

                var section_options = '';
                var section_li = '';

                $.each(data.subjects, function (i, item) {
                    section_options += "<option value=" + item.subject.id + ">" + item.subject.name + "</option>";
                    section_li += "<li data-value=" + item.subject.id + " class='option'>" + item.subject.name + "</li>";
                });

                $("select.subjects option").not(':first').remove();
                $("select.subjects").append(section_options);


                $("div .subjects .current").html($("div .subjects .list li:first").html());
                $("div .subjects .list li").not(':first').remove();
                $("div .subjects .list").append(section_li);

            } else {

                if (data.loop_status) {

                    var subject_options = '';

                    $.each(data.subjects, function (i, item) {
                        subject_options += "<option value=" + item.subject.id + ">" + item.subject.name + "</option>";
                    });

                    $("select.subjects option").not(':first').remove();
                    $("select.subjects").append(subject_options);


                    if (subject_options == '') {
                        $("#subject_marks_distribute tbody#main").html('');
                    }

                }

                if (current_section != '' && data.section_status == false) {

                    Toast.fire({
                        icon: 'error',
                        title: data.message
                    });

                    $(element).prop('checked', false);

                }

            }



        },
        error: function (data) {
            console.log(data);
        }
    });

}

function examAssignSubmit() {
    var exam_types = $('.exam_types').val();
    var classes = $('.classes').val();
    var subjects = $('.subjects').val();

    var sections = $('input[name="sections[]"]').map(function () {
        if ($(this).is(':checked')) {
            return $(this).val();
        }
    }).get();

    var formData = {
        exam_types: exam_types,
        class: classes,
        subjects: subjects,
        sections: sections
    }

    var flag = 0;

    $.ajax({
        type: "POST",
        dataType: 'json',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: '/exam-assign/check-submit',
        async: false,
        success: function (data) {

            if (data.status == false) {

                Toast.fire({
                    icon: 'error',
                    title: data.message
                });

                flag = 1;
            }
        },
        error: function (data) {
            console.log(data);
        }
    });

    if (flag == 1) {
        return false;
    }


}

// Start get subjects
$("#getSubjects").on('change', function (e) {

    var classId = $("#getSections").val();
    var sectionId = $("#getSubjects").val();
    var url = $('#url').val();
    var formData = {
        classes_id: classId,
        section_id: sectionId,
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/assign-subject/get-subjects',
        success: function (data) {
            var subject_options = '';
            var subject_li = '';

            $.each(JSON.parse(data), function (i, item) {
                subject_options += "<option value=" + item.subject.id + ">" + item.subject.name + "</option>";
                subject_li += "<li data-value=" + item.subject.id + " class='option'>" + item.subject.name + "</li>";
            });

            $("select.subjects option").not(':first').remove();
            $("select.subjects").append(subject_options);


            $("div .subjects .current").html($("div .subjects .list li:first").html());
            $("div .subjects .list li").not(':first').remove();
            $("div .subjects .list").append(subject_li);

            getStudents();
        },
        error: function (data) {
            console.log(data);
        }
    });
});
// End get subjects

function arr_diff(a1, a2) {

    var a = [], diff = [];

    for (var i = 0; i < a1.length; i++) {
        a[a1[i]] = true;
    }

    for (var i = 0; i < a2.length; i++) {
        if (a[a2[i]]) {
            delete a[a2[i]];
        } else {
            a[a2[i]] = true;
        }
    }

    for (var k in a) {
        diff.push(k);
    }

    return diff;
}

// Start subject mark start
$("#subjectMark").on('change', function (e) {
    var subjectId = $("#subjectMark").val();
    var url = $('#url').val();

    if ($("#form_type").val() == "update") {

        var new_id = [];
        new_id.push($("#subjectMark").val());
        $("#subject_marks_distribute tbody#main").empty();

    } else {

        var selected_subjects = $("input[name^='selected_subjects']").map(function (idx, ele) {
            return $(ele).val();
        }).get();

        console.log(selected_subjects);
        if (selected_subjects.length > subjectId.length) {  // if remove selected subject
            var remove_id = arr_diff(selected_subjects, subjectId);
            $("#row-" + remove_id).remove();
            return false;
        } else { // when select new subject
            var new_id = arr_diff(subjectId, selected_subjects);
        }
    }

    var formData = {
        ids: new_id
    }

    console.log(formData);

    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/exam-assign/subject-marks-distribution',
        success: function (data) {
            // $("#subject_marks_distribute tbody").empty();
            $("#subject_marks_distribute tbody#main").append(data);
        },
        error: function (data) {
            console.log(data);
        }
    });
});
// End subject mark start

$(document).ready(function () {

    $('.change-role').on('change', function (e) {
        e.preventDefault();
        var url = $('#url').val();
        var role_id = $(this).val();


        var formData = {
            role_id: role_id
        }
        $.ajax({
            type: "GET",
            dataType: 'html',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url + '/users/change-role',
            success: function (data) {
                console.log(data);
                $('#permissions-table tbody').html(data);
            },
            error: function (data) {
            }
        });
    });


    $('.change-module').on('change', function (e) {
        e.preventDefault();
        var url = $('#url').val();
        var code = $('#code').val();
        var module = $(this).val();


        var formData = {
            code: code,
            module: module,
        }
        $.ajax({
            type: "GET",
            dataType: 'html',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url + '/languages/change-module',
            success: function (data) {
                console.log(data);
                $('#terms-form .term-translated-language').html(data);
            },
            error: function (data) {
            }
        });
    });

});

$(document).on('click', '.common-key', function () {
    var value = $(this).val();
    var value = value.split("_");
    if (value[1] == 'read') {
        if (!$(this).is(':checked')) {
            $(this).closest('tr').find('.common-key').prop('checked', false);
        }
    } else {
        if ($(this).is(':checked')) {
            $(this).closest('tr').find('.common-key').first().prop('checked', true);
        }
    }
});

// slider js
$(document).ready(function () {
    $("._common_div").hide();
    let type = $('.file_system').val();
    if (type == 's3') {
        $("._common_div").show();
    } else {
        $("._common_div").hide();
    }

    $('.file_system').on('change', function () {
        let type = $(this).val();
        if (type == 's3') {
            $("._common_div").show(); // show product div
        } else {
            $("._common_div").hide(); // show category div
        }
    });
});

$(document).ready(function () {


    $('.language-change').on('change', function (e) {
        e.preventDefault();
        var url = $('#url').val();
        var code = $(this).val();


        var formData = {
            code: code,
        }
        $.ajax({
            type: "GET",
            dataType: 'html',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url + '/languages/change',
            success: function (data) {
                if (data == 1) {
                    location.reload();
                } else {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    })

                    Toast.fire({
                        icon: 'error',
                        title: 'Language terms not generate yet!'
                    })
                    location.reload();
                }
            },
            error: function (data) {
            }
        });



    });


    $('.session-change').on('change', function (e) {
        e.preventDefault();
        var url = $('#url').val();
        var id = $(this).val();


        var formData = {
            id: id,
        }
        $.ajax({
            type: "GET",
            dataType: 'html',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url + '/sessions/change',
            success: function (data) {
                if (data == 1) {
                    location.reload();
                } else {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    })

                    Toast.fire({
                        icon: 'error',
                        title: 'Language terms not generate yet!'
                    })
                    location.reload();
                }
            },
            error: function (data) {
            }
        });



    });

    $("input[name='theme_mode']").on('change', function (e) {
        var url = $('#url').val();
        var theme_mode = $(this).val();

        var formData = {
            theme_mode: theme_mode,
        }
        $.ajax({
            type: "POST",
            dataType: 'html',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url + '/setting/change-theme',
            success: function (data) {
                if (data) {
                    if (theme_mode == 'dark-theme') {
                        $('#dark_logo').show();
                        $('#default_logo').hide();
                    } else {
                        $('#dark_logo').hide();
                        $('#default_logo').show();
                    }
                    // location.reload();
                }
            },
            error: function (data) {
            }
        });
    });



    // end
});

/*----------------------------------------------
    Nice Scroll js
----------------------------------------------*/
$(".niceScroll").niceScroll({});

/*----------------------------------------------
    Plugin Activision
    --Odometer Counter--
----------------------------------------------*/
$('.odometer').appear(function (e) {
    var odo = jQuery(".odometer");
    odo.each(function () {
        var countNumber = jQuery(this).attr("data-count");
        jQuery(this).html(countNumber);
    });
});

// $(document).on('keyup', '#menuSearch', function () {
//     var url = $('#url').val();
//     var searchData = $(this).val();

//     if (searchData != '') {
//         $.ajax({
//             url: url + '/searchMenuData',
//             type: "post",
//             dataType: "json",
//             data: { searchData: searchData },
//             headers: {
//                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
//             },
//             success: function (data) {
//                 $('#autoCompleteData').removeClass('d-none').html(data.data);
//             }
//         });

//     } else {
//         $('#autoCompleteData').html('');
//     }

// });

// $(document).on('focusout', '#menuSearch', function () {
//     $('#autoCompleteData').addClass('d-nones');
// });

function searchMenu() {
    var url = $('#url').val();
    let value = $('#search_field').val();
    // ajax
    $.ajax({
        url: url + '/searchMenuData',
        type: 'POST',
        data: { search: value },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (data) {
            console.log(data);
            $('#autoCompleteData').removeClass('d-none');
            $('#autoCompleteData').addClass('d-block');
            let str = ``;
            if (data.length > 0) {
                $.each(data, function (index, value) {

                    str += `
                  <li>
                      <a class="suggestion_link" href="${value.route_name}">${value.title}</a>
                  </li>
                  `;
                });
            } else {
                str += `
                  <li>
                      <a class="suggestion_link" href="javascript:void(0)">No Item found !</a>
                  </li>
                  `;
            }

            $('.search_suggestion').html(str);
        }
    });
}

function searchParentMenu() {
    var url = $('#url').val();
    let value = $('#search_field').val();
    // ajax
    $.ajax({
        url: url + '/parent-panel-dashboard/search-parent-menu-data',
        type: 'POST',
        data: { search: value },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (data) {
            console.log(data);
            $('#autoCompleteData').removeClass('d-none');
            $('#autoCompleteData').addClass('d-block');
            let str = ``;
            if (data.length > 0) {
                $.each(data, function (index, value) {

                    str += `
                  <li>
                      <a class="suggestion_link" href="${value.route_name}">${value.title}</a>
                  </li>
                  `;
                });
            } else {
                str += `
                  <li>
                      <a class="suggestion_link" href="javascript:void(0)">No Item found !</a>
                  </li>
                  `;
            }

            $('.search_suggestion').html(str);
        }
    });
}

function searchStudentMenu() {
    var url = $('#url').val();
    let value = $('#search_field').val();
    // ajax
    $.ajax({
        url: url + '/student-panel-dashboard/search-student-menu-data',
        type: 'POST',
        data: { search: value },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (data) {
            console.log(data);
            $('#autoCompleteData').removeClass('d-none');
            $('#autoCompleteData').addClass('d-block');
            let str = ``;
            if (data.length > 0) {
                $.each(data, function (index, value) {

                    str += `
                  <li>
                      <a class="suggestion_link" href="${value.route_name}">${value.title}</a>
                  </li>
                  `;
                });
            } else {
                str += `
                  <li>
                      <a class="suggestion_link" href="javascript:void(0)">No Item found !</a>
                  </li>
                  `;
            }

            $('.search_suggestion').html(str);
        }
    });
}




// Full screen
function toggleFullScreen() {
    if ((document.fullScreenElement && document.fullScreenElement !== null) ||
        (!document.mozFullScreen && !document.webkitIsFullScreen)) {
        if (document.documentElement.requestFullScreen) {
            document.documentElement.requestFullScreen();
        } else if (document.documentElement.mozRequestFullScreen) {
            document.documentElement.mozRequestFullScreen();
        } else if (document.documentElement.webkitRequestFullScreen) {
            document.documentElement.webkitRequestFullScreen(Element.ALLOW_KEYBOARD_INPUT);
        }
    } else {
        if (document.cancelFullScreen) {
            document.cancelFullScreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.webkitCancelFullScreen) {
            document.webkitCancelFullScreen();
        }
    }
}
(function ($, window, document, undefined) {
    "use strict";
    var $ripple = $(".js-ripple");
    $ripple.on("click.ui.ripple", function (e) {
        var $this = $(this);
        var $offset = $this.parent().offset();
        var $circle = $this.find(".c-ripple__circle");
        var x = e.pageX - $offset.left;
        var y = e.pageY - $offset.top;
        $circle.css({
            top: y + "px",
            left: x + "px"
        });
        $this.addClass("is-active");
    });
    $ripple.on(
        "animationend webkitAnimationEnd oanimationend MSAnimationEnd",
        function (e) {
            $(this).removeClass("is-active");
        });
})(jQuery, window, document);

function addNewDocument() {

    var url = $('#url').val();
    var counter = parseInt($('#counter').val()) + 1;

    var formData = {
        counter: counter,
    }

    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/student/add-new-document',
        success: function (data) {
            $("#student-document tbody").append(data);
            $("#counter").val(counter);
            console.log(data);
        },
        error: function (data) {
        }
    });

}


// Subjest assign start
function addSubjectTeacher() {
    var url = $('#url').val();
    var counter = parseInt($('#counter').val()) + 1;

    var formData = {
        counter: counter,
    }

    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/assign-subject/add-subject-teacher',
        success: function (data) {
            $("#subject-teacher tbody").append(data);
            $("#counter").val(counter);
        },
        error: function (data) {
            console.log(data);
        }
    });
}

// Class routine start
function addClassRoutine() {
    var classId = $('.class').val();
    var sectionId = $('.section').val();
    var dayId = $('.day').val();

    if (!classId || !sectionId || !dayId) {
        Toast.fire({
            icon: 'error',
            title: 'Please select first ( ' + (!classId ? "Class " : '') + (!sectionId ? "Section " : '') + (!dayId ? "Day " : '') + ')'
        })
        return;
    }

    var url = $('#url').val();
    var counter = parseInt($('#counter').val()) + 1;

    var formData = {
        classes_id: classId,
        section_id: sectionId,
        counter: counter,
    }

    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/class-routine/add-class-routine',
        success: function (data) {
            $("#class-routines tbody").append(data);
            $("#counter").val(counter);
        },
        error: function (data) {
            console.log(data);
        }
    });
}
// Exam routine start
function addExamRoutine() {
    var classId = $('.class').val();
    var sectionId = $('.section').val();
    var dateId = $('.date').val();

    if (!classId || !sectionId || !dateId) {
        Toast.fire({
            icon: 'error',
            title: 'Please select first ( ' + (!classId ? "Class " : '') + (!sectionId ? "Section " : '') + (!dateId ? "Date " : '') + ')'
        })
        return;
    }

    var url = $('#url').val();
    var counter = parseInt($('#counter').val()) + 1;

    var formData = {
        classes_id: classId,
        section_id: sectionId,
        counter: counter,
    }

    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/exam-routine/add-exam-routine',
        success: function (data) {
            $("#exam-routines tbody").append(data);
            $("#counter").val(counter);
        },
        error: function (data) {
            console.log(data);
        }
    });
}
$("form#examRoutineForm .section").on('change', function (e) {
    $("#exam-routines tbody").empty();
});
$("form#examRoutineForm .class").on('change', function (e) {
    $("#exam-routines tbody").empty();
    $("form#examRoutineForm .sections .current").html($("form#examRoutineForm .sections .list li:first").html());
});
// Exam routine end

// Start marks distribution
function marksDistribution(id) {
    // alert(id);
    var url = $('#url').val();
    var formData = {
        id: id,
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/exam-assign/marks-distribution',
        success: function (data) {
            $("#marks-distribution" + id + " tbody").append(data);
        },
        error: function (data) {
            console.log(data);
        }
    });
}
// End marks distribution

// $('form').validate(
//     {
//      rules: {
//            "subjects[]": "allRequired"
//      },

//      submitHandler : function(form, event) {
//           event.preventDefault();
//           //... etc
//      }
// });


function viewSubjectTeacher(id) {
    var base =
        typeof window.ASSIGN_SUBJECT_SHOW_URL === 'string' && window.ASSIGN_SUBJECT_SHOW_URL.length
            ? window.ASSIGN_SUBJECT_SHOW_URL
            : (($('#url').val() || '').replace(/\/$/, '') + '/assign-subject/show');
    var formData = {
        id: id,
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: base,
        success: function (data) {
            $("#basicModal .modal-dialog").html(data);
        },
        error: function (data) {
            console.log(data);
        }
    });
}
// End subject assign


// start student mark
function viewStudentMark(id) {
    var url = $('#url').val();

    $.ajax({
        type: "GET",
        dataType: 'html',
        cache: false,
        contentType: false,
        data: { id: id },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/marks-register/show',
        success: function (data) {
            $("#modalCustomizeWidth .modal-dialog").html(data);
        },
        error: function (data) {
            console.log(data);
        }
    });
}
// end student mark
$(document).ready(function () {
    try {
        $('#summernote').summernote({
            tabsize: 2,
            height: 300
        });
    } catch (e) {

    }
});
$(document).ready(function () {
    try {
        $('#summernote2').summernote();
    } catch (e) {

    }
});

$(document).ready(function () {
    try {
        $('#summernote3').summernote();
    } catch (e) {

    }
});


function removeRow(element) {
    element.closest('tr').remove();
}


function changeSection(element) {

    var formData = {
        class: $(".select-class").val(),
        section: $(element).val(),
        form_type: $("#form_type").val(),
        id: $("#id").val()
    }

    console.log(formData);

    $.ajax({
        type: "GET",
        dataType: 'json',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: '/assign-subject/check-section',
        success: function (data) {

            if (data.status == false) {

                Toast.fire({
                    icon: 'error',
                    title: data.message
                });

                var section_options = '';
                var section_li = '';

                $.each(data.sections, function (i, item) {
                    section_options += "<option value=" + item.section.id + ">" + item.section.name + "</option>";
                    section_li += "<li data-value=" + item.section.id + " class='option'>" + item.section.name + "</li>";
                });

                // console.log(section_options);
                $("select.sections option").not(':first').remove();
                $("select.sections").append(section_options);

                $("div .sections .current").html($("div .sections .list li:first").html());
                $("div .sections .list li").not(':first').remove();
                $("div .sections .list").append(section_li);
                $("div .sections .list li:first").addClass('selected focus');

                return false;
            }
        },
        error: function (data) {
            console.log(data);
        }
    });
}


$(".exam_type").on('change', function (e) {
    getStudents();
});


// Mark Register start
$("form#markRegister #getSections").on('change', function (e) {
    getMarkRegisterStudents();
});
$("form#markRegister .sections").on('change', function (e) {
    getMarkRegisterStudents();
});
$("form#markRegister .exam_types").on('change', function (e) {
    getMarkRegisterStudents();
});
$("form#markRegister .subjects").on('change', function (e) {
    getMarkRegisterStudents();
});

function getMarkRegisterStudents() {
    var url = $('#url').val();
    var classId = $("#getSections").val();
    var sectionId = $("#getSubjects").val();
    var subjectId = $("#subject").val();
    var exam_type = $(".exam_types").val();

    var formData = {
        class: classId,
        section: sectionId,
        subject: subjectId,
        exam_type: exam_type,
    }

    $("#students_table tbody").empty();
    if (classId && sectionId && subjectId && exam_type) {
        $.ajax({
            type: "GET",
            dataType: 'html',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url + '/student/get-students',
            success: function (data) {
                $("#students_table tbody").append(data);
            },
            error: function (data) {
                console.log(data);
            }
        });
    }
}
// Mark Register end


// Start fees collection students-
// function feesCollectStudents()
// {
//     var url       = $('#url').val();
//     var classId   = $(".class").val();
//     var sectionId = $(".section").val();

//     var formData = {
//         class: classId,
//         section: sectionId,
//     }

//     $("#students_table tbody").empty();
//     if(classId && sectionId)
//     {
//         $.ajax({
//             type: "GET",
//             dataType: 'html',
//             data: formData,
//             headers: {
//                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
//             },
//             url: url + '/fees-collect/get-fees-collect-students',
//             success: function (data) {
//                 // console.log(data);
//                 $("#students_table tbody").append(data);
//             },
//             error: function (data) {
//                 console.log(data);
//             }
//         });
//     }
// }
// End fees collection students-


// Fees master assing
// $("#getSections").on('change', function (e) {
//     assingStudents();
// });
$("#section").on('change', function (e) {
    assingStudents();
});

$("#student_category").on('change', function (e) {
    assingStudents();
});
$("#gender").on('change', function (e) {
    assingStudents();
});

if ($("#page").val() == "create") {
    assingStudents();
}

function assingStudents() {
    var url = $('#url').val();
    var classId = $("#getSections").val();
    var sectionId = $("#section").val();
    var categoryId = $("#student_category").val();
    var genderId = $("#gender").val();

    var formData = {
        class: classId,
        section: sectionId,
        category: categoryId,
        gender: genderId,
        section: sectionId,
    }

    $("#students_table tbody").empty();
    if (classId && sectionId) {
        $.ajax({
            type: "GET",
            dataType: 'html',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url + '/fees-assign/get-fees-assign-students',
            success: function (data) {
                // console.log(data);
                $("#students_table tbody").append(data);
            },
            error: function (data) {
                console.log(data);
            }
        });
    }
}
// Fees master assing end
// Fees master type
$("#fees_group").on('change', function (e) {
    groupTypes();
});

// groupTypes();

function groupTypes() {
    var url = $('#url').val();
    var id = $("#fees_group").val();

    var formData = {
        id: id
    }

    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/fees-assign/get-all-type',
        success: function (data) {
            // console.log(data);
            $("#types_table tbody").empty();
            $("#types_table tbody").append(data);
        },
        error: function (data) {
            console.log(data);
        }
    });
}
// Fees master type end

// view student list

function viewStudentList(id) {
    var url = $('#url').val();
    var formData = {
        id: id,
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/fees-assign/show',
        success: function (data) {
            // $("#view-modal").append(data);
            $("#modalCustomizeWidth .modal-dialog").html(data);
        },
        error: function (data) {
            console.log(data);
        }
    });
}

// end view student list
// view student list

function feesCollect() {

    var fees_assign_childrens = $('input[name="fees_assign_childrens[]"]').map(function () {
        if ($(this).is(':checked')) {
            return $(this).val();
        }
    }).get();

    let discountAmount = $('input[name="discount_amount"]').val();

    if (fees_assign_childrens.length === 0) {
        Toast.fire({
            icon: 'error',
            title: 'Please select at least one item'
        })
        return;
    }

    var formData = {
        fees_assign_childrens: fees_assign_childrens,
        student_id: $("#student_id").val(),
        discount_amount: discountAmount,
    }

    console.log(formData);

    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: '/fees-collect/fees-show',
        success: function (data) {
            // $("#view-modal").append(data);
            $("#modalCustomizeWidth .modal-dialog").html(data);
        },
        error: function (data) {
            console.log(data);
        }
    });
}

// end view student list

// all select

$("#all_students").on('click', function (e) {
    if ($("#all_students").is(':checked')) {
        $(".student").prop("checked", true);
    }
    else {
        $(".student").prop("checked", false);
    }
});
$(document).on('click', '.student', function() {
    const checkboxes = document.querySelectorAll('.student');
    for (let i = 0; i < checkboxes.length; i++) {
        if(!checkboxes[i].checked){
            $("#all_students").prop("checked", false);
            break;
        }
        else
            $("#all_students").prop("checked", true);
    }
});

$("#all_fees_masters").on('click', function (e) {
    if ($("#all_fees_masters").is(':checked')) {
        $(".fees_master").prop("checked", true);
    }
    else {
        $(".fees_master").prop("checked", false);
    }
});
$(document).on('click', '.fees_master', function() {
    const checkboxes = document.querySelectorAll('.fees_master');
    for (let i = 0; i < checkboxes.length; i++) {
        if(!checkboxes[i].checked){
            $("#all_fees_masters").prop("checked", false);
            break;
        }
        else
            $("#all_fees_masters").prop("checked", true);
    }
});

$(".all").on('click', function (e) {
    if ($(".all").is(':checked')) {
        $(".child").prop("checked", true);
    }
    else {
        $(".child").prop("checked", false);
    }
});

$(document).on('click', '.child', function() {
    const checkboxes = document.querySelectorAll('.child');
    for (let i = 0; i < checkboxes.length; i++) {
        if(!checkboxes[i].checked){
            $(".all").prop("checked", false);
            break;
        }
        else
            $(".all").prop("checked", true);
    }
});

// end all select


// class routine check
$("form#classRoutineForm").on("submit", function () {


    var time_schedules = $('select[name="time_schedules[]"]').map(function () {

        console.log($(this).val());
        if ($(this).val() != "") {
            return $(this).val();
        }
    }).get();

    var class_rooms = $('select[name="class_rooms[]"]').map(function () {
        if ($(this).val() != "") {
            return $(this).val();
        }
    }).get();

    var formData = {
        class: $(".class").val(),
        section: $(".section").val(),
        shift: $(".shift").val(),
        day: $(".day").val(),
        form_type: $("#form_type").val(),
        time_schedules: time_schedules,
        class_rooms: class_rooms,
        id: $("#id").val(),
    }

    var flag = 0;

    console.log(formData);
    $.ajax({
        type: "GET",
        dataType: 'json',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: '/class-routine/check-class-routine',
        async: false,
        success: function (data) {
            if (data.status == false) {
                Toast.fire({
                    icon: 'error',
                    title: data.message
                });
                flag++;
            }
        },
        error: function (data) {
            console.log(data);
        }
    });

    if (flag == 1) {
        return false;
    }

});

// Exam routine check
$("form#examRoutineForm").on("submit", function () {


    var time_schedules = $('select[name="time_schedules[]"]').map(function () {

        console.log($(this).val());
        if ($(this).val() != "") {
            return $(this).val();
        }
    }).get();

    var class_rooms = $('select[name="class_rooms[]"]').map(function () {
        if ($(this).val() != "") {
            return $(this).val();
        }
    }).get();

    var formData = {
        class: $(".class").val(),
        section: $(".section").val(),
        shift: $(".shift").val(),
        date: $(".date").val(),
        form_type: $("#form_type").val(),
        time_schedules: time_schedules,
        class_rooms: class_rooms,
        id: $("#id").val(),
    }

    var flag = 0;

    console.log(formData);
    $.ajax({
        type: "GET",
        dataType: 'json',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: '/exam-routine/check-exam-routine',
        async: false,
        success: function (data) {
            if (data.status == false) {
                Toast.fire({
                    icon: 'error',
                    title: data.message
                });
                flag++;
            }
        },
        error: function (data) {
            console.log(data);
        }
    });

    if (flag == 1) {
        return false;
    }

});





// Marksheet students start
$("form#marksheet .class").on('change', function (e) {
    $("form#marksheet .sections .current").html($("form#marksheet .sections .list li:first").html());
    $("form#marksheet .students .current").html($("form#marksheet .students .list li:first").html());
    getStudents();
});
$("form#marksheet .section").on('change', function (e) {
    $("form#marksheet .students .current").html($("form#marksheet .students .list li:first").html());
    getStudents();
});

$("form#fees-collect .section").on('change', function (e) {
    $("form#fees-collect .students .current").html($("form#fees-collect .students .list li:first").html());
    getStudents();
});

// Start Class Section wise get Students
function getStudents() {
    var url = $('#url').val();
    var classId = $(".class").val();
    var sectionId = $(".section").val();
    var formData = {
        class: classId,
        section: sectionId,
    }

    if (classId && sectionId) {
        $.ajax({
            type: "GET",
            dataType: 'json',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: '/report-marksheet/get-students',
            success: function (data) {
                var student_options = '';
                var student_li = '';
                $.each(data, function (i, item) {
                    student_options += "<option value=" + item.student_id + ">" + item.student.first_name + ' ' + item.student.last_name + "</option>";
                    student_li += "<li data-value=" + item.student_id + " class='option'>" + item.student.first_name + ' ' + item.student.last_name + "</li>";
                });

                $("select.students option").not(':first').remove();
                $("select.students").append(student_options);

                $("div .students .current").html($("div .students .list li:first").html());
                $("div .students .list li").not(':first').remove();
                $("div .students .list").append(student_li);
            },
            error: function (data) {
                console.log(data);
            }
        });
    }
}
// Marksheet students end.





// Report start
function printDiv(divName) {
    var printContents = document.getElementById(divName).innerHTML;
    var originalContents = document.body.innerHTML;

    document.body.innerHTML = printContents;

    window.print();

    document.body.innerHTML = originalContents;
}
// Report end



// attendance
$(document).ready(function () {
    $(".attendance .form-check-input").on("click", () => {
        $('#holiday').prop('checked', false);
    });

    $('.form-check-input').on("click", () => {
        const checkedItems = $(".attendance .form-check-input:checked");

        // loop through all form-check-input elements and add or remove the "checkedItem" class based on whether they are checked or unchecked
        $(".attendance .form-check-input").each(function () {
            if ($(this).is(":checked")) {
                $(this).addClass("checkedItem");
            } else {
                $(this).removeClass("checkedItem");
            }
        });

        // loop through checked items and log their value
        checkedItems.each(function () {
            console.log($(this).val());
        });
    });


    $('#holiday').on('click', function () {
        if ($(this).is(":checked")) {

            const checkedItems = $(".attendance .form-check-input:checked");
            checkedItems.each(function () {
                $('.checkedItem').prop('checked', false);
                $(this).addClass("notCheckedItem");
                $(this).removeClass("checkedItem");
            });
        } else {
            $('.notCheckedItem').prop('checked', true);
            $('.notCheckedItem').addClass("checkedItem");
            $('.checkedItem').removeClass("notCheckedItem");
        }
    });
});



// end attendance







// Start get exam_types
function getExamtype() {
    var classId = $(".class").val();
    var sectionId = $(".section").val();
    var url = $('#url').val();
    var formData = {
        class: classId,
        section: sectionId,
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/exam-assign/get-exam-type',
        success: function (data) {

            var exam_type_options = '';
            var exam_type_li = '';

            $.each(JSON.parse(data), function (i, item) {
                exam_type_options += "<option value=" + item.exam_type.id + ">" + item.exam_type.name + "</option>";
                exam_type_li += "<li data-value=" + item.exam_type.id + " class='option'>" + item.exam_type.name + "</li>";
            });

            $("select.exam_types option").not(':first').remove();
            $("select.exam_types").append(exam_type_options);


            $("div .exam_types .current").html($("div .exam_types .list li:first").html());
            $("div .exam_types .list li").not(':first').remove();
            $("div .exam_types .list").append(exam_type_li);
        },
        error: function (data) {
            console.log(data);
        }
    });
}
$("form#marksheet .class").on('change', function (e) {
    getExamtype();
});
$("form#marksheet .section").on('change', function (e) {
    getExamtype();
});
// End get exam_types


// get exam_types from exam routines
$("form#examRoutineForm .class").on('change', function (e) {
    getExamtype();
});
$("form#examRoutineForm .section").on('change', function (e) {
    getExamtype();
});
// end exam_types from exam routines


// get exam_types from exam routines
$("form#markRegister .class").on('change', function (e) {
    getExamtype();
});
$("form#markRegister .section").on('change', function (e) {
    getExamtype();
});
// end exam_types from exam routines

// get merit_list from exam routines
$("form#merit_list .class").on('change', function (e) {
    getExamtype();
});
$("form#merit_list .section").on('change', function (e) {
    getExamtype();
});
// end merit_list from exam routines




// get exam_type from parent panel
$("form#exam_routine .student").on('change', function (e) {
    var url = $('#url').val();
    var formData = {
        id: $(this).val(),
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/parent-panel-exam-routine/exam-types',
        success: function (data) {

            var exam_type_options = '';
            var exam_type_li = '';

            $.each(JSON.parse(data), function (i, item) {
                exam_type_options += "<option value=" + item.exam_type.id + ">" + item.exam_type.name + "</option>";
                exam_type_li += "<li data-value=" + item.exam_type.id + " class='option'>" + item.exam_type.name + "</li>";
            });

            $("select.exam_types option").not(':first').remove();
            $("select.exam_types").append(exam_type_options);

            $("div .exam_types .current").html($("div .exam_types .list li:first").html());
            $("div .exam_types .list li").not(':first').remove();
            $("div .exam_types .list").append(exam_type_li);
        },
        error: function (data) {
            console.log(data);
        }
    });
});
// end get exam_types from parent panel



// get exam_type from parent panel
$("form#marksheet .student").on('change', function (e) {
    var url = $('#url').val();
    var formData = {
        id: $(this).val(),
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/parent-panel-marksheet/exam-types',
        success: function (data) {

            var exam_type_options = '';
            var exam_type_li = '';

            $.each(JSON.parse(data), function (i, item) {
                exam_type_options += "<option value=" + item.exam_type.id + ">" + item.exam_type.name + "</option>";
                exam_type_li += "<li data-value=" + item.exam_type.id + " class='option'>" + item.exam_type.name + "</li>";
            });

            $("select.exam_types option").not(':first').remove();
            $("select.exam_types").append(exam_type_options);


            $("div .exam_types .current").html($("div .exam_types .list li:first").html());
            $("div .exam_types .list li").not(':first').remove();
            $("div .exam_types .list").append(exam_type_li);
        },
        error: function (data) {
            console.log(data);
        }
    });
});
// end get exam_types from parent panel


// date reange picker
$('input[name="dates"]').daterangepicker();




// get account type
$("form#account .account_head_type").on('change', function (e) {
    var url = $('#url').val();
    var formData = {
        id: $(this).val(),
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/report-account/get-account-types',
        success: function (data) {

            var account_type_options = '';
            var account_type_li = '';

            $.each(JSON.parse(data), function (i, item) {
                account_type_options += "<option value=" + item.id + ">" + item.name + "</option>";
                account_type_li += "<li data-value=" + item.id + " class='option'>" + item.name + "</li>";
            });

            $("select.account_types option").not(':first').remove();
            $("select.account_types").append(account_type_options);


            $("div .account_types .current").html($("div .account_types .list li:first").html());
            $("div .account_types .list li").not(':first').remove();
            $("div .account_types .list").append(account_type_li);
        },
        error: function (data) {
            console.log(data);
        }
    });
});
// end get account type










// start sections

function addSocialLink() {
    var url = $('#url').val();
    // var counter = parseInt($('#counter').val()) + 1;

    var formData = {
        // counter: counter,
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/page-sections/add-social-link',
        success: function (data) {
            $("#social_links tbody").append(data);
            // $("#counter").val(counter);
        },
        error: function (data) {
            console.log(data);
        }
    });
}
function addChooseUs() {
    var url = $('#url').val();
    // var counter = parseInt($('#counter').val()) + 1;

    var formData = {
        // counter: counter,
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/page-sections/add-choose-us',
        success: function (data) {
            $("#why_choose_us tbody").append(data);
            // $("#counter").val(counter);
        },
        error: function (data) {
            console.log(data);
        }
    });
}
function addAcademicCurriculum() {
    var url = $('#url').val();
    // var counter = parseInt($('#counter').val()) + 1;

    var formData = {
        // counter: counter,
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/page-sections/add-academic-curriculum',
        success: function (data) {
            $("#academic_curriculum tbody").append(data);
            // $("#counter").val(counter);
        },
        error: function (data) {
            console.log(data);
        }
    });
}

// end sections


// Start examination filter get subjects
$("form.exam_assign .class").on('change', function (e) {
    getExaminationFilterSubject();
});
$("form.exam_assign .section").on('change', function (e) {
    getExaminationFilterSubject();
});
function getExaminationFilterSubject() {
    var classId = $(".class").val();
    var sectionId = $(".section").val();

    if (classId && sectionId) {
        var url = $('#url').val();
        var formData = {
            classes_id: classId,
            section_id: sectionId,
        }
        $.ajax({
            type: "GET",
            dataType: 'html',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url + '/assign-subject/get-subjects',
            success: function (data) {
                var subject_options = '';
                var subject_li = '';

                $.each(JSON.parse(data), function (i, item) {
                    subject_options += "<option value=" + item.subject.id + ">" + item.subject.name + "</option>";
                    subject_li += "<li data-value=" + item.subject.id + " class='option'>" + item.subject.name + "</li>";
                });

                $("select.subjects option").not(':first').remove();
                $("select.subjects").append(subject_options);


                $("div .subjects .current").html($("div .subjects .list li:first").html());
                $("div .subjects .list li").not(':first').remove();
                $("div .subjects .list").append(subject_li);
            },
            error: function (data) {
                console.log(data);
            }
        });
    }
}
// End examination filter get subjects




// Library start

// Add member
$(document).on('keyup.nice-select-search', 'form#member .member .nice-select', function () {
    var $self = $(this);
    var $text = $self.find('.nice-select-search').val();
    var url = $('#url').val();

    var formData = {
        text: $text,
    }

    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/member/get-member',
        success: function (data) {


            var section_options = '';
            var section_li = '';

            $.each(JSON.parse(data), function (i, item) {
                section_options += "<option value=" + i + ">" + item + "</option>";
                section_li += "<li data-value=" + i + " class='option'>" + item + "</li>";
            });

            $("select.member option").not(':first').remove();
            $("select.member").append(section_options);

            $("div .member .list li").not(':first').remove();
            $("div .member .list").append(section_li);
        },
        error: function (data) {
            console.log(data);
        }
    });
});

// Issue Book
// member
$(document).on('keyup.nice-select-search', 'form#issue_book .member .nice-select', function () {
    var $self = $(this);
    var $text = $self.find('.nice-select-search').val();
    var url = $('#url').val();

    var formData = {
        text: $text,
    }

    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/issue-book/get-member',
        success: function (data) {

            var section_options = '';
            var section_li = '';

            $.each(JSON.parse(data), function (i, item) {
                section_options += "<option value=" + item.user_id + ">" + item.user.name + "</option>";
                section_li += "<li data-value=" + item.user_id + " class='option'>" + item.user.name + "</li>";
            });

            $("select.member option").not(':first').remove();
            $("select.member").append(section_options);

            $("div .member .list li").not(':first').remove();
            $("div .member .list").append(section_li);
        },
        error: function (data) {
            console.log(data);
        }
    });
});
// book
$(document).on('keyup.nice-select-search', 'form#issue_book .book .nice-select', function () {
    var $self = $(this);
    var $text = $self.find('.nice-select-search').val();
    var url = $('#url').val();

    var formData = {
        text: $text,
    }

    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/issue-book/get-book',
        success: function (data) {

            var section_options = '';
            var section_li = '';

            $.each(JSON.parse(data), function (i, item) {
                section_options += "<option value=" + i + ">" + item + "</option>";
                section_li += "<li data-value=" + i + " class='option'>" + item + "</li>";
            });

            $("select.book option").not(':first').remove();
            $("select.book").append(section_options);

            $("div .book .list li").not(':first').remove();
            $("div .book .list").append(section_li);
        },
        error: function (data) {
            console.log(data);
        }
    });
});



// Library end

// THEME_MODE_LOGIN_REGISTER
if (typeof activeTheme === 'undefined') {
    const activeTheme = localStorage.getItem('theme_mode');
    if (activeTheme) {
        document.body.classList.remove('default-theme', 'dark-theme');
        document.body.classList.add(activeTheme);
    }
}



// Online examination start

$(document).on('keyup.nice-select-search', 'form#question_bank .question_group .nice-select', function () {
    var $self = $(this);
    var $text = $self.find('.nice-select-search').val();
    var url = $('#url').val();

    var formData = {
        text: $text,
    }

    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/question-bank/get-question-group',
        success: function (data) {

            var section_options = '';
            var section_li = '';

            $.each(JSON.parse(data), function (i, item) {
                section_options += "<option value=" + i + ">" + item + "</option>";
                section_li += "<li data-value=" + i + " class='option'>" + item + "</li>";
            });

            $("select.question_group option").not(':first').remove();
            $("select.question_group").append(section_options);

            $("div .question_group .list li").not(':first').remove();
            $("div .question_group .list").append(section_li);
        },
        error: function (data) {
            console.log(data);
        }
    });
});


$(document).ready(function() {
    $("form#question_bank .total_option").hide();
    $("form#question_bank .options").hide();
    $("form#question_bank .single_choice_ans").hide();
    $("form#question_bank .multiple_choice_ans").hide();
    $("form#question_bank .true_false_ans").hide();


    var type = parseInt($("form#question_bank .question_type").val());
    if(type)
        details(type);

    $("form#question_bank .question_type").on('change', function(e) {
        var type = parseInt($(this).val());
        details(type);
    });

    function details(type) {
        if (type === 1) {
            $("form#question_bank .total_option").show();
            $("form#question_bank .options").show();
            $("form#question_bank .single_choice_ans").show();
            $("form#question_bank .multiple_choice_ans").hide();
            $("form#question_bank .true_false_ans").hide();
        } else if (type === 2) {
            $("form#question_bank .total_option").show();
            $("form#question_bank .options").show();
            $("form#question_bank .single_choice_ans").hide();
            $("form#question_bank .multiple_choice_ans").show();
            $("form#question_bank .true_false_ans").hide();
        } else if (type === 3) {
            $("form#question_bank .total_option").hide();
            $("form#question_bank .options").hide();
            $("form#question_bank .single_choice_ans").hide();
            $("form#question_bank .multiple_choice_ans").hide();
            $("form#question_bank .true_false_ans").show();
        } else {
            $("form#question_bank .total_option").hide();
            $("form#question_bank .options").hide();
            $("form#question_bank .single_choice_ans").hide();
            $("form#question_bank .multiple_choice_ans").hide();
            $("form#question_bank .true_false_ans").hide();
        }
    }
});

$(document).ready(function() {
    $('form#question_bank #total_option').on('change', function() {
        var total_option = Number($(this).val());
        var type = parseInt($("form#question_bank .question_type").val());
        var options = ''; // options
        var section_options = ''; // single_choice_ans
        var section_li = ''; // single_choice_ans
        var multiple_choice_ans = ''; // multiple_choice_ans

        for (var i = 1; i <= total_option; i++) {
            options += `
                <div class="col-md-3 mb-3">
                    <label class="form-label">Option ${i} <span class="fillable">*</span></label>
                    <input class="form-control ot-input" name="option[${i}]" value="" placeholder="Enter option" required>
                </div>
            `;

            if (type === 1) {
                section_options += `<option value='${i}'>Option ${i}</option>`;
                section_li += `<li data-value='${i}' class='option'>Option ${i}</li>`;
            }

            if (type === 2) {
                multiple_choice_ans += `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="multiple_choice_ans[]" value="${i}" id="option${i}">
                        <label class="form-check-label ps-2 pe-5" for="option${i}">Option ${i}</label>
                    </div>
                `;
            }
        }

        // options start
        $('form#question_bank .input_ptions').empty();
        $('form#question_bank .input_ptions').append(options);
        // options end

        // single_choice_ans start
        if (type === 1) {
            $("form#question_bank .single_choice_ans").show();

            $("#single_choice_ans option").not(':first').remove();
            $("#single_choice_ans").append(section_options)
            $("div #single_choice_ans .list li").not(':first').remove();
            $("#single_choice_ans .list").append(section_li);

            $("#single_choice_ans").niceSelect('update');
        }
        // single_choice_ans end

        // multiple_choice_ans start
        if (type === 2) {
            $("form#question_bank .multiple_choice_ans").show();

            $('form#question_bank #multiple_choice_ans').empty();
            $('form#question_bank #multiple_choice_ans').append(multiple_choice_ans);
        }
        // multiple_choice_and end
    });


    $(document).on('change', '.question_type', function () {
        $("#total_option").val('');
        $('#total_option').niceSelect('update');
        $('.input_ptions').empty();
        $('#multiple_choice_ans').empty();
        $('#single_choice_ans').empty();
    });
});


    // start get questions
    // var id = parseInt($("form.onlineExamCreate #question_group").val());
    //     if(id)
    //         getAllQuestions(id);
    $("form#onlineExam #question_group").on('change', function (e) {
        var id = parseInt($(this).val());
        getAllQuestions(id)
    });
    function getAllQuestions(id){
        var url = $('#url').val();

        var formData = {
            id: id
        }

        $.ajax({
            type: "GET",
            dataType: 'html',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url + '/online-exam/get-all-questions',
            success: function (data) {
                console.log(data);
                $("#types_table tbody").empty();
                $("#types_table tbody").append(data);
            },
            error: function (data) {
                console.log(data);
            }
        });
    }
    // start get questions


    // get students
    // $("form#onlineExam #getSections").on('change', function (e) {
    //     examStudents();
    // });
    // $("form#onlineExam #section").on('change', function (e) {
    //     examStudents();
    // });
    // $("form#onlineExam #student_category").on('change', function (e) {
    //     examStudents();
    // });
    // $("form#onlineExam #gender").on('change', function (e) {
    //     examStudents();
    // });

    // function examStudents() {
    //     var url = $('#url').val();
    //     var classId = $("#getSections").val();
    //     var sectionId = $("#section").val();
    //     var categoryId = $("#student_category").val();
    //     var genderId = $("#gender").val();

    //     var formData = {
    //         class: classId,
    //         section: sectionId,
    //         category: categoryId,
    //         gender: genderId,
    //     }

    //     $("#online_exam_students tbody").empty();
    //     if (classId && sectionId) {
    //         $.ajax({
    //             type: "GET",
    //             dataType: 'html',
    //             data: formData,
    //             headers: {
    //                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    //             },
    //             url: url + '/student/get-students',
    //             success: function (data) {
    //                 // console.log(data);
    //                 $("#online_exam_students tbody").append(data);
    //             },
    //             error: function (data) {
    //                 console.log(data);
    //             }
    //         });
    //     }
    // }
    // get students



    // Start get section
    $("form#onlineExam #getSections").on('change', function (e) {
        var classId = $(this).val();
        getSections(classId);
    });
    // var classId = $("form#onlineExam #getSections").val();
    // if(classId)
    //     getSections(classId);

    function getSections(classId){
        var url = $('#url').val();
        var formData = {
            id: classId,
        }
        $.ajax({
            type: "GET",
            dataType: 'html',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url + '/class-setup/get-sections',
            success: function (data) {
                var section_options = '';
                var section_li = '';

                $.each(JSON.parse(data), function (i, item) {
                    section_options += "<option value=" + item.section.id + ">" + item.section.name + "</option>";
                    section_li += "<li data-value=" + item.section.id + " class='option'>" + item.section.name + "</li>";
                });

                $("select.sections option").not(':first').remove();
                $("select.sections").append(section_options);

                $("div .sections .current").html($("div .sections .list li:first").html());
                $("div .sections .list li").not(':first').remove();
                $("div .sections .list").append(section_li);
            },
            error: function (data) {
                console.log(data);
            }
        });
    }
    // End get section




    // Start onlineExam filter get subjects
    $("form#onlineExam #getSections").on('change', function (e) {
        getExaminationFilterSubject();
    });
    $("form#onlineExam #section").on('change', function (e) {
        getExaminationFilterSubject();
    });

    function getExaminationFilterSubject() {
        var classId = $("#getSections").val();
        var sectionId = $("#section").val();

        if (classId && sectionId) {
            var url = $('#url').val();
            var formData = {
                classes_id: classId,
                section_id: sectionId,
            }
            $.ajax({
                type: "GET",
                dataType: 'html',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: url + '/assign-subject/get-subjects',
                success: function (data) {
                    var subject_options = '';
                    var subject_li = '';

                    $.each(JSON.parse(data), function (i, item) {
                        subject_options += "<option value=" + item.subject.id + ">" + item.subject.name + "</option>";
                        subject_li += "<li data-value=" + item.subject.id + " class='option'>" + item.subject.name + "</li>";
                    });

                    $("select.subjects option").not(':first').remove();
                    $("select.subjects").append(subject_options);


                    $("div .subjects .current").html($("div .subjects .list li:first").html());
                    $("div .subjects .list li").not(':first').remove();
                    $("div .subjects .list").append(subject_li);
                },
                error: function (data) {
                    console.log(data);
                }
            });
        }
    }
    // End onlineExam filter get subjects

    // start view students
    function viewStudents(id) {
        var url = $('#url').val();

        $.ajax({
            type: "GET",
            dataType: 'html',
            cache: false,
            contentType: false,
            data: { id: id },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url + '/online-exam/view-students',
            success: function (data) {
                $("#modalCustomizeWidth .modal-dialog").html(data);
            },
            error: function (data) {
                console.log(data);
            }
        });
    }
    // end view students
    // start view questions
    function viewQuestions(id) {
        var url = $('#url').val();

        $.ajax({
            type: "GET",
            dataType: 'html',
            cache: false,
            contentType: false,
            data: { id: id },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url + '/online-exam/view-questions',
            success: function (data) {
                $("#modalCustomizeWidth .modal-dialog").html(data);
            },
            error: function (data) {
                console.log(data);
            }
        });
    }
    // end view questions

    // form confirmation
    $('form.confirmation').submit(function(event) {
        event.preventDefault();
        var form = $(this)[0];

        let title = $('#alert_title').val();
        let confirmButtonText = $('#alert_yes_btn').val();
        let cancelButtonText = $('#alert_cancel_btn').val();

        Swal.fire({
            title,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText,
            cancelButtonText
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
    // form confirmation
// Online examination end




$(document).on('click', '.mark-checkbox', function () {
    if ($(this).is(":checked")) {
        $(this).closest('div').find('.mark-checkbox-value').prop('disabled', true);
    } else {
        $(this).closest('div').find('.mark-checkbox-value').prop('disabled', false);
    }
})




function getFeeData(fees_assigned_children_id, url)
{
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: {
            fees_assigned_children_id
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url,
        success: function (data) {
            $("#modalCustomizeWidth .modal-dialog").html(data);
        },
        error: function (data) {
            console.log(data);
        }
    });
}



// start feePayByStudentModal function
function feePayByStudentModal(fees_assigned_children_id) {
    getFeeData(fees_assigned_children_id, '/student-panel-fees/pay-modal');
}
// end feePayByStudentModal function



// start feePayByParentModal function
function feePayByParentModal(fees_assigned_children_id) {
    getFeeData(fees_assigned_children_id, '/parent-panel-fees/pay-modal');
}
// end feePayByParentModal function


// start feeProofByStudentModal function
function feeProofByStudentModal(fees_assigned_children_id) {
    getFeeData(fees_assigned_children_id, '/student-panel-fees/proof-modal');
}
// end feeProofByStudentModal function



// start feeProofByParentModal function
function feeProofByParentModal(fees_assigned_children_id) {
    getFeeData(fees_assigned_children_id, '/parent-panel-fees/proof-modal');
}
// end feeProofByParentModal function

// start sections

function addLink() {
    var url = $('#url').val();

    var formData = {
        // counter: counter,
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/sections/add-social-link',
        success: function (data) {
            $("#social_links tbody").append(data);
        },
        error: function (data) {
            console.log(data);
        }
    });
  }

  $(document).ready(function () {

    setTimeout(function () {
        $("#payment_type").trigger('change');
        // $("._duration").trigger('change');
    }, 500);

    $("form#package-form-create #payment_type").on("change", () => {

        if($("#payment_type").val() == "postpaid") {

            $(".all").prop('checked', true);
            $(".child").prop('checked', true);

            $("._postpaid_main").removeClass('d-none');
            $("._prepaid_main").addClass('d-none');
        } else {

            $(".all").prop('checked', false);
            $(".child").prop('checked', false);

            $("._prepaid_main").removeClass('d-none');
            $("._postpaid_main").addClass('d-none');


            $("._duration").trigger('change');
        }

    });

    $("form#package-form-edit #payment_type").on("change", () => {

        if($("#payment_type").val() == "postpaid") {

            $("._postpaid_main").removeClass('d-none');
            $("._prepaid_main").addClass('d-none');

        } else {

            $("._prepaid_main").removeClass('d-none');
            $("._postpaid_main").addClass('d-none');

            $("._duration").trigger('change');
        }

    });

    $("._duration").on("change", () => {


        if($("._duration").val() == 4) {
            $("._duration_number_main").addClass('d-none');
        } else {
            $("._duration_number_main").removeClass('d-none');
        }

    });

  });


  const openHomeworkEvaluationModal = (id) => {

    $("#homework_id").val(id);

    var csrfToken   = document.head.querySelector('meta[name="csrf-token"]').content;
    var formData    = new FormData();
    formData.append('homework_id', id);

    console.log(formData);


    $.ajax({
        url: '/homework/students', // Your server-side script handling the upload
        type: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken // Include the CSRF token in the headers
        },
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {

            console.log(response.view);
            $(".modal-body").html(response.view);
        },
        error: function(error) {


        }
    });
  }

  const openIdCardPreviewModal = (id) => {


    var csrfToken   = document.head.querySelector('meta[name="csrf-token"]').content;
    var formData    = new FormData();
    formData.append('idcard_id', id);

    console.log(formData);


    $.ajax({
        url: '/idcard/preview', // Your server-side script handling the upload
        type: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken // Include the CSRF token in the headers
        },
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {

            console.log(response.view);
            $(".modal-body").html(response.view);
        },
        error: function(error) {


        }
    });
  }


  const openCertificatePreviewModal = (id) => {


    var csrfToken   = document.head.querySelector('meta[name="csrf-token"]').content;
    var formData    = new FormData();
    formData.append('certificate_id', id);

    console.log(formData);


    $.ajax({
        url: '/certificate/preview', // Your server-side script handling the upload
        type: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken // Include the CSRF token in the headers
        },
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {

            console.log(response.view);
            $(".modal-body").html(response.view);
        },
        error: function(error) {


        }
    });
  }





