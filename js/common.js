// common.js

function get_variant_key(selected) {
    return Object.values(selected).join('-');
}

function get_variant_image(product, variantKey) {
    const variants = product.variants || {};
    const attributes = product.attributes || {};
    const default_image = product.images[0] || 'https://placehold.jp/300x300.png';

    if (variants[variantKey] && variants[variantKey].image) {
        return variants[variantKey].image;
    }

    const variant_parts = variantKey.split('-');
    for (const attr_name in attributes) {
        const attr_data = attributes[attr_name];
        const images = attr_data.images || {};
        for (const part of variant_parts) {
            if (images[part]) {
                return images[part];
            }
        }
    }

    return default_image;
}

function is_variant_available(product, variantKey) {
    const variants = product.variants || {};
    return variants[variantKey] && !variants[variantKey].sold_out;
}

function updatePriceAndImage(form, priceDisplay, carouselOrSwiper, product, minPrice, isSwiper = false) {
    const selects = form.querySelectorAll('.variant-select');
    const selected = {};
    selects.forEach(select => {
        if (select.value) selected[select.getAttribute('data-attr')] = select.value;
    });
    const variantKey = get_variant_key(selected);
    const variant = product.variants[variantKey];
    const price = variant && !variant.sold_out ? variant.price : null;
    const image = get_variant_image(product, variantKey);

    priceDisplay.textContent = price ? `${price.toLocaleString()}円` : `${minPrice.toLocaleString()}円～`;

    if (isSwiper) {
        const slides = carouselOrSwiper.wrapperEl.querySelectorAll('.swiper-slide');
        let targetIndex = 0;
        slides.forEach((slide, index) => {
            const imgSrc = slide.querySelector('img').src;
            if (imgSrc.includes(image)) {
                targetIndex = index;
            }
        });
        carouselOrSwiper.slideTo(targetIndex);
    } else {
        const items = carouselOrSwiper.querySelectorAll('.carousel-item');
        let targetIndex = 0;
        items.forEach((item, index) => {
            if (item.getAttribute('data-image-src') === image) {
                targetIndex = index;
            }
        });
        const carouselInstance = bootstrap.Carousel.getInstance(carouselOrSwiper) || new bootstrap.Carousel(carouselOrSwiper, { interval: false });
        carouselInstance.to(targetIndex);
    }
}

function validateForm(form, product) {
    const selects = form.querySelectorAll('.variant-select');
    let allSelected = true;
    selects.forEach(select => {
        if (!select.value) allSelected = false;
    });
    if (!allSelected) {
        alert('全部選んでね！');
        return false;
    }
    const selected = {};
    selects.forEach(select => {
        if (select.value) selected[select.getAttribute('data-attr')] = select.value;
    });
    const variantKey = get_variant_key(selected);
    if (!is_variant_available(product, variantKey)) {
        alert('ごめんね、在庫がないよ…');
        return false;
    }
    return true;
}