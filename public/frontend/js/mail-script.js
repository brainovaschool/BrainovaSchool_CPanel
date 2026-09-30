    // -------   Contact send ajax

     $(document).ready(function() {
        var form = $('#myForm'); // contact form

        // form submit event
        form.on('submit', function(e) {
            e.preventDefault(); // prevent default form submit

            var name = $("form#myForm .name").val();
            var phone = $("form#myForm .phone").val();
            var email = $("form#myForm .email").val();
            var subject = $("form#myForm .subject").val();
            var message = $("form#myForm .message").val();
            var website = $("form#myForm .website").val();

            var url = $('#url').val();

            var formData = {
                name: name,
                phone: phone,
                email: email,
                subject: subject,
                message: message,
                website: website
            }

            $.ajax({
                url: url + '/contact', // form action url
                type: 'POST', // form submit method get/post
                dataType: 'json', // request type html/json/xml
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    form.trigger('reset'); // reset form
                    Swal.fire({
                        title: data[0],
                        text: data[1],
                        icon: data[2],
                        confirmButtonText: data[3]
                    })
                },
                error: function(e) {
                    Swal.fire({
                        title: data[0],
                        text: data[1],
                        icon: data[2],
                        confirmButtonText: data[3]
                    })
                }
            });
        });
    });




    // -------   Subscribe ajax

     $(document).ready(function() {
        var form = $('.subscription'); // contact form

        // form submit event
        form.on('submit', function(e) {
            e.preventDefault(); // prevent default form submit

            var email = $("form.subscription .email").val();
            var website = $("form.subscription .website").val();
            var url = $('#url').val();

            var formData = {
                email: email,
                website: website,
            }

            $.ajax({
                url: url + '/subscribe', // form action url
                type: 'POST', // form submit method get/post
                dataType: 'json', // request type html/json/xml
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    form.trigger('reset'); // reset form
                    Swal.fire({
                        title: data[0],
                        text: data[1],
                        icon: data[2],
                        confirmButtonText: data[3]
                    })
                },
                error: function(e) {
                    Swal.fire({
                        title: data[0],
                        text: data[1],
                        icon: data[2],
                        confirmButtonText: data[3]
                    })
                }
            });
        });
    });




    // -------   Online admission send ajax

    // $(document).ready(function() {
    //     var form = $('#XXadmission'); // contact form

    //     // form submit event
    //     form.on('submit', function(e) {
    //         e.preventDefault(); // prevent default form submit
    //         var first_name     = $("form#admission .first_name").val();
    //         var last_name      = $("form#admission .last_name").val();
    //         var phone          = $("form#admission .phone").val();
    //         var email          = $("form#admission .email").val();
    //         var session        = $("form#admission .session").val();
    //         var classes        = $("form#admission .classes").val();
    //         var sections       = $("form#admission .sections").val();
    //         var dob            = $("form#admission .dob").val();
    //         var gender         = $("form#admission .gender").val();
    //         var religion       = $("form#admission .religion").val();
    //         var guardian_name  = $("form#admission .guardian_name").val();
    //         var guardian_phone = $("form#admission .guardian_phone").val();

    //         if(first_name=='' || last_name=='' || phone=='' || session=='' || classes=='' || sections=='' || dob=='' || gender=='' || religion=='' || guardian_name=='' || guardian_phone=='')
    //         {
    //             Swal.fire(
    //                 'Attention',
    //                 'Please fill all the required fields',
    //                 'warning'
    //             )
    //             e.preventDefault();
    //             return;
    //         }

    //         var url = $('#url').val();

    //         var formData = {
    //             first_name: first_name,
    //             last_name: last_name,
    //             phone: phone,
    //             email: email,
    //             session: session,
    //             classes: classes,
    //             sections: sections,
    //             dob: dob,
    //             gender: gender,
    //             religion: religion,
    //             guardian_name: guardian_name,
    //             guardian_phone: guardian_phone
    //         }


    //         $.ajax({
    //             url: url + '/online-admission', // form action url
    //             type: 'POST', // form submit method get/post
    //             dataType: 'json', // request type html/json/xml
    //             data: formData,
    //             headers: {
    //                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    //             },
    //             success: function(data) {
    //                 console.log(data);
    //                 Swal.fire({
    //                     title: data[0],
    //                     text: data[1],
    //                     icon: data[2],
    //                     confirmButtonText: data[3]
    //                 })
    //                 form.trigger('reset'); // reset form
    //                 // session
    //                 $("div .session .current").html($("div .session .list li:first").html());
    //                 // classes
    //                 $("select.classes option").not(':first').remove();
    //                 $("div .classes .current").html($("div .classes .list li:first").html());
    //                 $("div .classes .list li").not(':first').remove();
    //                 // sections
    //                 $("select.sections option").not(':first').remove();
    //                 $("div .sections .current").html($("div .sections .list li:first").html());
    //                 $("div .sections .list li").not(':first').remove();
    //                 // gender
    //                 $("select.gender option").not(':first').remove();
    //                 $("div .gender .current").html($("div .gender .list li:first").html());
    //                 $("div .gender .list li").not(':first').remove();
    //                 // religion
    //                 $("select.religion option").not(':first').remove();
    //                 $("div .religion .current").html($("div .religion .list li:first").html());
    //                 $("div .religion .list li").not(':first').remove();
    //             },
    //             error: function(e) {
    //                 Swal.fire({
    //                     title: data[0],
    //                     text: data[1],
    //                     icon: data[2],
    //                     confirmButtonText: data[3]
    //                 })
    //             }
    //         });
    //     });
    // });
