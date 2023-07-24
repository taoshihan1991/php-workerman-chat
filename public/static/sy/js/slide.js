var homeTopSlider = new Swiper('#home-top-slide', {
    navigation: {
        nextEl: '.home-top-swiper-button-next',
        prevEl: '.home-top-swiper-button-prev',
    },
    pagination: {
        el: '.home-top-swiper-pagination',
        clickable: true
    },
    speed: 600,
    autoplay: {
        delay: 7000
    },
})
