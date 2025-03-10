// delayheader.js

document.addEventListener('DOMContentLoaded', () => {
    // Swiperを初期化（横にスライド）
    const swiper = new Swiper('.mySwiper', {
        direction: 'horizontal', // 横に変更
        slidesPerView: 1,
        spaceBetween: 0,
        pagination: { el: '.swiper-pagination', clickable: true },
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
        loop: true, // 最後から最初に戻る（横カルーセルっぽく）
    });

    const header = document.querySelector('#main-header');
    const swiperEl = document.querySelector('.mySwiper');

    // Swiperの表示状態をチェックする関数
    const checkSwiperVisibility = () => {
        const rect = swiperEl.getBoundingClientRect();
        const windowHeight = window.innerHeight;

        const isSwiperVisible = rect.top < windowHeight && rect.bottom > 0;

        if (isSwiperVisible) {
            header.classList.remove('show');
        } else {
            header.classList.add('show');
        }
    };

    // ページ読み込み時にチェック
    checkSwiperVisibility();

    // スクロールするたびにチェック
    window.addEventListener('scroll', checkSwiperVisibility);
});