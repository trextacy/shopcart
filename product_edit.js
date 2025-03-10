// product_edit.js
tinymce.init({
    selector: '#description',
    plugins: 'lists link image',
    toolbar: 'undo redo | bold italic | bullist numlist | link image',
    menubar: false,
    height: 300,
    content_style: 'body { font-family: "Noto Sans JP", sans-serif; font-size: 14px; }'
});

function addAttrEntry() {
    const container = document.getElementById('attr-container');
    const entry = document.createElement('div');
    entry.className = 'attr-entry mb-3 border rounded p-2 position-relative cure-sky-entry';
    entry.innerHTML = `
        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 cure-sky-btn-danger" onclick="this.parentElement.remove()">削除</button>
        <div class="row">
            <div class="col-md-6 mb-2">
                <label class="form-label">属性名だよ</label>
                <input type="text" class="form-control cure-sky-input" name="attr_name[]" placeholder="例: カラー" required>
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label">値（カンマ区切りだよ）</label>
                <input type="text" class="form-control cure-sky-input" name="attr_values[]" placeholder="例: 赤, 青, 白" required>
            </div>
        </div>
    `;
    container.appendChild(entry);
}

function generateVariants() {
    const attrs = Array.from(document.querySelectorAll('.attr-entry')).map(entry => {
        const name = entry.querySelector('input[name="attr_name[]"]').value;
        const values = entry.querySelector('input[name="attr_values[]"]').value.split(',').map(v => v.trim());
        return { name, values };
    });
    if (attrs.length === 0) return;

    const combinations = cartesian(attrs.map(a => a.values));
    const container = document.getElementById('variant-container');
    container.innerHTML = '';

    combinations.forEach(combo => {
        const key = combo.join('-');
        const row = document.createElement('tr');
        row.className = 'variant-entry';
        row.innerHTML = `
            <td>
                <input type="hidden" name="variant_key[]" value="${key}">
                ${key}
            </td>
            <td><input type="number" class="form-control variant-price cure-sky-input" name="variant_price[]" placeholder="価格" min="0" required></td>
            <td>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="variant_sold_out[${key}]" value="1">
                    <label class="form-check-label">売り切れだよ</label>
                </div>
            </td>
            <td>
                <select class="form-select cure-sky-select" name="variant_image[${key}]">
                    <option value="">デフォルト画像だよ</option>
                    ${Array.from(document.querySelectorAll('#image-preview .image-item')).map((item, index) => {
                        const imgSrc = item.querySelector('img').src;
                        const desc = item.querySelector('textarea').value || `画像${index}`;
                        return `<option value="${imgSrc}">${desc}</option>`;
                    }).join('')}
                </select>
            </td>
        `;
        container.appendChild(row);
    });
}

function applyBulkPrice() {
    const bulkPrice = document.getElementById('bulk_price').value;
    if (bulkPrice && !isNaN(bulkPrice)) {
        document.querySelectorAll('.variant-price').forEach(input => input.value = bulkPrice);
    }
}

function cartesian(arrays) {
    return arrays.reduce((acc, curr) => acc.flatMap(x => curr.map(y => x.concat(y))), [[]]);
}

function updateImageOptions() {
    const images = Array.from(document.querySelectorAll('#image-preview .image-item')).map((item, index) => ({
        src: item.querySelector('img').src,
        description: item.querySelector('textarea').value || `画像${index + 1}`
    }));
    document.querySelectorAll('select[name^="variant_image"]').forEach(select => {
        const currentValue = select.value;
        select.innerHTML = '<option value="">デフォルト画像だよ</option>';
        images.forEach(img => {
            const option = document.createElement('option');
            option.value = img.src;
            option.textContent = img.description;
            if (currentValue === img.src) option.selected = true;
            select.appendChild(option);
        });
    });
    const defaultSelect = document.getElementById('default_image');
    defaultSelect.innerHTML = images.map(img => `<option value="${img.src}" ${img.src === images[0].src ? 'selected' : ''}>${img.description}</option>`).join('');
}

const imageInput = document.getElementById('images');
const preview = document.getElementById('image-preview');
let nextIndex = document.querySelectorAll('#image-preview .image-item').length;

function addImageToPreview(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const div = document.createElement('div');
        div.className = 'image-item card p-2 cure-sky-image';
        div.draggable = true;
        div.dataset.index = nextIndex++;
        div.innerHTML = `
            <img src="${e.target.result}" class="card-img-top" style="width: 100px; height: 100px; object-fit: cover;">
            <div class="card-body p-1">
                <textarea class="form-control cure-sky-input" name="image_desc[]" placeholder="画像説明だよ" rows="2" oninput="updateImageOptions()"></textarea>
                <button type="button" class="btn btn-danger btn-sm mt-1 w-100 cure-sky-btn-danger" onclick="this.parentElement.parentElement.remove(); updateImageOptions()">削除だよ</button>
                <input type="hidden" name="new_images[]" value="${file.name}">
            </div>
        `;
        preview.appendChild(div);
        enableDragAndDrop();
        updateImageOptions();
    };
    reader.readAsDataURL(file);
}

imageInput.addEventListener('change', function(e) {
    Array.from(e.target.files).forEach(file => addImageToPreview(file));
});

function enableDragAndDrop() {
    const items = preview.querySelectorAll('.image-item');
    items.forEach(item => {
        item.addEventListener('dragstart', e => e.dataTransfer.setData('text/plain', item.dataset.index));
        item.addEventListener('dragover', e => e.preventDefault());
        item.addEventListener('drop', e => {
            e.preventDefault();
            const fromIndex = e.dataTransfer.getData('text/plain');
            const toIndex = item.dataset.index;
            if (fromIndex !== toIndex) {
                const fromItem = preview.querySelector(`[data-index="${fromIndex}"]`);
                const toItem = preview.querySelector(`[data-index="${toIndex}"]`);
                preview.insertBefore(fromItem, toItem);
                updateIndices();
                updateImageOptions();
            }
        });
    });
}

function updateIndices() {
    const items = preview.querySelectorAll('.image-item');
    items.forEach((item, index) => item.dataset.index = index);
}

function updateImageOrder() {
    const items = preview.querySelectorAll('.image-item');
    const order = [];
    const descriptions = [];
    items.forEach((item, index) => {
        order.push(index);
        descriptions.push(item.querySelector('textarea').value);
    });
    document.getElementById('image-order').value = JSON.stringify(order);
    document.getElementById('image-descriptions').value = JSON.stringify(descriptions);
}

document.getElementById('submitButton').addEventListener('click', () => {
    updateImageOrder();
    document.getElementById('productForm').submit();
});

document.getElementById('copyButton').addEventListener('click', () => {
    const form = document.getElementById('productForm');
    const copyInput = document.createElement('input');
    copyInput.type = 'hidden';
    copyInput.name = 'copy';
    copyInput.value = '1';
    form.appendChild(copyInput);
    updateImageOrder();
    form.submit();
});

enableDragAndDrop();
updateImageOptions();