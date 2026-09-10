// ---------------------------------------------------------------- Result Start ----------------------------------------------------------------
// Report start
function printDiv(divName) {
    var printContents = document.getElementById(divName).innerHTML;
    var originalContents = document.body.innerHTML;

    document.body.innerHTML = printContents;

    window.print();

    document.body.innerHTML = originalContents;
}
// Report end



// Start get classes
$("form#result .session").on('change', function (e) {
    var sessionId = $("form#result .session").val();
    var url = $('#url').val();
    var formData = {
        session: sessionId,
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/get-classes',
        success: function (data) {

            var classes_options = '';
            var classes_li = '';

            $.each(JSON.parse(data), function (i, item) {
                classes_options += "<option value=" + item.class.id + ">" + item.class.name + "</option>";
                classes_li += "<li data-value=" + item.class.id + " class='option'>" + item.class.name + "</li>";
            });

            $("select.classes option").not(':first').remove();
            $("select.classes").append(classes_options);

            $("div .classes .current").html($("div .classes .list li:first").html());
            $("div .classes .list li").not(':first').remove();
            $("div .classes .list").append(classes_li);

        },
        error: function (data) {
            console.log(data);
        }
    });
});
// End get classes



// Start get sections
function getResultSections() {
    var sessionId = $("form#result .session").val();
    var classId = $("form#result .classes").val();
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
        url: url + '/get-sections',
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
$("form#result .session").on('change', function (e) {
    getResultSections();
});
$("form#result .classes").on('change', function (e) {
    getResultSections();
});
// End get sections



// Start get exam_types
function getResultExamTypes() {
    var sessionId = $("form#result .session").val();
    var classId = $("form#result .classes").val();
    var sectionId = $("form#result .sections").val();
    var url = $('#url').val();
    var formData = {
        session: sessionId,
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
        url: url + '/get-exam-type',
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
$("form#result .session").on('change', function (e) {
    getResultExamTypes();
});
$("form#result .classes").on('change', function (e) {
    getResultExamTypes();
});
$("form#result .sections").on('change', function (e) {
    getResultExamTypes();
});

// End get exam_types
// ---------------------------------------------------------------- Result End ----------------------------------------------------------------


// ---------------------------------------------------------------- Admission Start ----------------------------------------------------------------
// Start get classes
$("form#admission .session").on('change', function (e) {
    var sessionId = $("form#admission .session").val();

    var url = $('#url').val();
    var formData = {
        session: sessionId,
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/get-classes',
        success: function (data) {

            var classes_options = '';
            var classes_li = '';

            $.each(JSON.parse(data), function (i, item) {
                classes_options += "<option value=" + item.class.id + ">" + item.class.class_tran + "</option>";
                classes_li += "<li data-value=" + item.class.id + " class='option'>" + item.class.class_tran + "</li>";
            });

            $("select.classes option").not(':first').remove();
            $("select.classes").append(classes_options);

            $("div .classes .current").html($("div .classes .list li:first").html());
            $("div .classes .list li").not(':first').remove();
            $("div .classes .list").append(classes_li);

        },
        error: function (data) {
            console.log(data);
        }
    });
});
// End get classes



// Start get sections
function getSections() {
    var sessionId = $("form#admission .session").val();
    var classId = $("form#admission .classes").val();
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
        url: url + '/get-sections',
        success: function (data) {

            var section_options = '';
            var section_li = '';

            $.each(JSON.parse(data), function (i, item) {
                section_options += "<option value=" + item.section.id + ">" + item.section.section_tran + "</option>";
                section_li += "<li data-value=" + item.section.id + " class='option'>" + item.section.section_tran + "</li>";
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
$("form#admission .session").on('change', function (e) {
    getSections();
});
$("form#admission .classes").on('change', function (e) {
    getSections();
});
// End get sections



// Start get exam_types
function getExamTypes() {
    var sessionId = $("form#admission .session").val();
    var classId = $("form#admission .classes").val();
    var sectionId = $("form#admission .sections").val();
    var url = $('#url').val();
    var formData = {
        session: sessionId,
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
        url: url + '/get-exam-type',
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
$("form#admission .session").on('change', function (e) {
    getExamTypes();
});
$("form#admission .classes").on('change', function (e) {
    getExamTypes();
});
$("form#admission .sections").on('change', function (e) {
    getExamTypes();
});

// End get exam_types
// ---------------------------------------------------------------- Admission End ----------------------------------------------------------------


// ---------------------------------------------------------------- Start Language Change --------------------------------------------------------------
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
// ---------------------------------------------------------------- End Language Change ----------------------------------------------------------------

// ---- Sticky header refinement: hide while scrolling down, show while scrolling up ----
(function () {
    var header = document.getElementById('sticky-header');
    if (!header) return;
    var lastY = window.pageYOffset || 0;
    var ticking = false;

    function update() {
        var y = window.pageYOffset || 0;
        if (header.classList.contains('navbar_fixed')) {
            if (y > lastY + 4 && y > 220) {
                header.classList.add('bn-nav-up');        // scrolling down -> tuck away
            } else if (y < lastY - 4) {
                header.classList.remove('bn-nav-up');     // scrolling up -> reveal
            }
        } else {
            header.classList.remove('bn-nav-up');
        }
        lastY = y;
        ticking = false;
    }

    window.addEventListener('scroll', function () {
        if (!ticking) {
            window.requestAnimationFrame(update);
            ticking = true;
        }
    }, { passive: true });
})();
