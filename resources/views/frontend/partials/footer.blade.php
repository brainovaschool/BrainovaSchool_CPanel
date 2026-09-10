
    <!--/ Add_Milestone -->
    <!-- UP_ICON  -->
    <div id="back-top">
        <a title="Go to Top" href="#">
            <i class="ti-angle-up"></i>
        </a>
    </div>
    <!--/ UP_ICON -->

   <!--ALL JS SCRIPTS -->
   <script src="{{ global_asset('frontend') }}/js/vendor/jquery-3.4.1.min.js"></script>
   <script src="{{ global_asset('frontend') }}/js/vendor/popper.min.js"></script>
   <script src="{{ global_asset('frontend') }}/js/vendor/bootstrap.min.js"></script>

   <script src="{{ global_asset('frontend') }}/js/owl.carousel.min.js"></script>
   <script src="{{ global_asset('frontend') }}/js/isotope.pkgd.min.js"></script>
   <script src="{{ global_asset('frontend') }}/js/waypoints.min.js"></script>
   <script src="{{ global_asset('frontend') }}/js/jquery.counterup.min.js"></script>
   <script src="{{ global_asset('frontend') }}/js/imagesloaded.pkgd.min.js"></script>
   <script src="{{ global_asset('frontend') }}/js/wow.min.js"></script>
   <script src="{{ global_asset('frontend') }}/js/nice-select.min.js"></script>
   <script src="{{ global_asset('frontend') }}/js/barfiller.js"></script>
   <script src="{{ global_asset('frontend') }}/js/jquery.slicknav.js"></script>
   <script src="{{ global_asset('frontend') }}/js/jquery.magnific-popup.min.js"></script>
   <script src="{{ global_asset('frontend') }}/js/jquery.ajaxchimp.min.js"></script>
   <script src="{{ global_asset('frontend') }}/js/parallax.js"></script>
   <script src="{{ global_asset('frontend') }}/js/mail-script.js"></script>

   <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA7nx22ZmINYk9TGiXDEXGVxghC43Ox6qA"></script>
   <script src="{{ global_asset('frontend') }}/js/map.js"></script>
   <script src="{{ global_asset('frontend') }}/js/theme.js"></script>
   <!-- MAIN JS   -->
   <script src="{{ global_asset('frontend') }}/js/main.js"></script>
   <script src="{{ global_asset('frontend') }}/js/custom.js?v={{ @filemtime(public_path('frontend/js/custom.js')) ?: 1 }}"></script>
   <script src="{{ global_asset('frontend') }}/js/sweetalert2.all.min.js"></script>
   @stack('script')
   </body>

   </html>
