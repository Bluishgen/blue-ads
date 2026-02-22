jQuery(document).ready(function($) {
    // Initialize the carousel with 4-second interval
    $('#blue-ads-slider').carousel({
        interval: 4000 // 4 seconds
    });

    // Manual controls
    $('.carousel-control-prev').click(function(e) {
        e.preventDefault();
        $('#blue-ads-slider').carousel('prev');
    });

    $('.carousel-control-next').click(function(e) {
        e.preventDefault();
        $('#blue-ads-slider').carousel('next');
    });

    // Ensure smooth transition between slides
    $('#blue-ads-slider').on('slide.bs.carousel', function () {
        $(this).find('.carousel-item').removeClass('active');
    });

    $('#blue-ads-slider').on('slid.bs.carousel', function () {
        $(this).find('.carousel-item.active').addClass('active');
    });
});