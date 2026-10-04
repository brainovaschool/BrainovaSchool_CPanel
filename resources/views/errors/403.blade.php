@extends('errors.master')

@section('title', 'Not Available')
@section('main')
<main>
    <section
      class="error-wrapper p-0 m-0 text-center d-flex justify-content-center align-items-center flex-column"
    >
      <div
        class="error-content p-0 m-0 text-center d-flex justify-content-center align-items-center flex-column"
      >
        <!-- Deliberately reuses the same illustration/layout as the other
             error pages — this isn't a broken link, so it shouldn't look
             or feel like one; only the wording changes. -->
        <img src="{{asset('backend')}}/assets/images/error/error500.png" alt="" />
        <h1 class="mt-30">This page isn't turned on for your account</h1>
        <p class="mt-10">
            {{ (isset($exception) && $exception->getMessage()) ? $exception->getMessage() : "Your school hasn't given your account access to this yet. If you think this should be available to you, ask your school." }}
        </p>
        <div class="btn-back-to-homepage mt-28">
            <a href="{{url('dashboard')}}" class="submit-button pv-16  btn ot-btn-primary">
            {{ ___('error.back_to_homepage') }}
            </a>
          </div>
      </div>
    </section>
  </main>
  @endsection