@extends('frontend.master')

@section('title')
    {{ $data['title'] }}
@endsection

@section('main')

<div class="section_padding2">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <form id="aiHelperForm">
                    @csrf
                    <div class="contact_form_box mb_30">
                        <div class="section__title mb_30">
                            <h3 class="mb-0 text-white">{{ $data['title'] }}</h3>
                        </div>

                        <label class="primary_label">{{ $data['question_label'] }}</label>
                        <input type="text" name="input" id="aiHelperInput" class="primary_input mb_30" required>

                        <button type="submit" id="aiHelperSubmit"
                            class="theme_btn submit-btn text-center d-inline-flex gap_14 align-items-center m-0">
                            {{ $data['button_text'] }}
                        </button>

                        <div id="aiHelperResult" class="mt-4" style="display:none;">
                            <p id="aiHelperResultText" class="text-white mb-0"></p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('aiHelperForm').addEventListener('submit', function (e) {
        e.preventDefault();

        var input      = document.getElementById('aiHelperInput').value;
        var token       = document.querySelector('input[name="_token"]').value;
        var submitBtn   = document.getElementById('aiHelperSubmit');
        var resultBox   = document.getElementById('aiHelperResult');
        var resultText  = document.getElementById('aiHelperResultText');

        submitBtn.disabled = true;
        resultBox.style.display = 'none';

        fetch('{{ route('frontend.ai-helper.ask') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ input: input })
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            resultText.textContent = data.message;
            resultBox.style.display = 'block';
        })
        .catch(function () {
            resultText.textContent = 'Something went wrong. Please try again.';
            resultBox.style.display = 'block';
        })
        .finally(function () {
            submitBtn.disabled = false;
        });
    });
</script>

@endsection
