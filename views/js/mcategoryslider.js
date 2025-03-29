document.addEventListener("DOMContentLoaded", function () {


    const swiper = new Swiper('.swiper', {
        loop: false,
        speed: 1000,
        slidesPerView: 5,
        spaceBetween: 20,
        navigation: {
            nextEl: '.button-next',
            prevEl: '.button-prev'
        },
        mousewheel: {
            forceToAxis: true, 
            invert: true,
        },
        freeMode: true,
        breakpoints: {
            320: { slidesPerView: 1 },
            480: { slidesPerView: 2 },
            768: { slidesPerView: 3 },
            1024: { slidesPerView: 4 }
        }
    });
});