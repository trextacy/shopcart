const ProductCommon = {
    attrIndex: 0,

    addAttrEntry(containerId) {
        this.attrIndex++;
        const container = document.getElementById(containerId);
        const entry = document.createElement('div');
        entry.className = 'attr-entry mb-3 cure-prism-entry';
        entry.innerHTML = `
            <div class="d-flex mb-2">
                <input type="text" class="form-control cure-prism-input me-2" name="attr_name[]" placeholder="属性名 (例: カラー)" required>
                <input type="text" class="form-control cure-prism-input me-2" name="attr_values[]" placeholder="値 (例: 赤, 青, 白)" required oninput="ProductCommon.updateAttrValueImages(this, ${this.attrIndex})">
                <button type="button" class="btn btn-danger cure-prism-btn-danger" onclick="this.parentElement.parentElement.remove()">削除だよ</button>
            </div>
            <div class="form-check mb-2">
                <input type="checkbox" class="form-check-input" name="variant_display[${this.attrIndex}]" id="variant_display_${this.attrIndex}" value="button_group">
                <label class="form-check-label" for="variant_display_${this.attrIndex}">Button Groupにするよ♪</label>
            </div>
            <div class="attr-values-images" data-attr-index="${this.attrIndex}"></div>
        `;
        container.appendChild(entry);
    },

    generateVariants(containerId) {
        const attrs = Array.from(document.querySelectorAll('.attr-entry')).map(entry => {
            const name = entry.querySelector('input[name="attr_name[]"]').value.trim();
            const values = entry.querySelector('input[name="attr_values[]"]').value.split(',').map(v => v.trim()).filter(Boolean);
            return { name, values };
        }).filter(a => a.name && a.values.length);
        if (!attrs.length) return;

        const combinations = this.cartesian(attrs.map(a => a.values));
        const container = document.getElementById(containerId);
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
                <td>
                    <input type="number" class="form-control cure-prism-input variant-price" name="variant_price[]" placeholder="価格" min="0" required>
                </td>
                <td>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="variant_sold_out[${key}]" value="1">
                        <label class="form-check-label">売り切れだよ</label>
                    </div>
                </td>
                <td>
                    <select class="form-select cure-prism-select variant-image" name="variant_image[${key}]" onchange="ProductCommon.updatePreview(this)">
                        <option value="">デフォルト画像だよ</option>
                    </select>
                </td>
            `;
            container.appendChild(row);
        });
        this.updateImageOptions();
    },

    cartesian(arrays) {
        return arrays.reduce((acc, curr) => acc.flatMap(x => curr.map(y => [].concat(x, y))), [[]]);
    },

    updateImageOptions() {
        const images = Array.from(document.querySelectorAll('#image-preview .image-item')).map((item, index) => ({
            src: item.querySelector('img').src,
            description: item.querySelector('textarea').value || `画像${index + 1}`,
            path: item.querySelector('input[name="existing_images[]"]')?.value || item.querySelector('input[name="new_images[]"]')?.value
        }));
        document.querySelectorAll('.variant-image, .attr-image-select').forEach(select => {
            const initialValue = select.dataset.initialValue || '';
            select.innerHTML = '<option value="">デフォルト画像だよ</option>';
            images.forEach(img => {
                const option = document.createElement('option');
                option.value = img.path;
                option.textContent = img.description;
                option.dataset.src = img.src;
                if (initialValue === img.path) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
            this.updatePreview(select);
        });
    },

    updatePreview(select) {
        const previewImg = select.nextElementSibling;
        if (previewImg && select.value && select.selectedOptions[0]?.dataset.src) {
            previewImg.src = select.selectedOptions[0].dataset.src;
            previewImg.style.display = 'block';
        } else if (previewImg) {
            previewImg.style.display = 'none';
        }
    },

    applyBulkPrice() {
        const bulkPrice = document.getElementById('bulk_price').value;
        if (bulkPrice && !isNaN(bulkPrice) && bulkPrice >= 0) {
            document.querySelectorAll('.variant-price').forEach(input => input.value = bulkPrice);
        }
    },

    updateAttrValueImages(input, attrIndex) {
        const values = input.value.split(',').map(v => v.trim()).filter(Boolean);
        let container = document.querySelector(`.attr-values-images[data-attr-index="${attrIndex}"]`);
        if (!container) {
            const entry = input.closest('.attr-entry');
            if (entry) {
                container = document.createElement('div');
                container.className = 'attr-values-images';
                container.dataset.attrIndex = attrIndex;
                entry.appendChild(container);
            } else {
                return;
            }
        }
        container.innerHTML = '';
        values.forEach((value, index) => {
            const div = document.createElement('div');
            div.className = 'd-flex align-items-center mb-2';
            div.innerHTML = `
                <span class="me-2" style="min-width: 80px;">${value}</span>
                <select class="form-select attr-image-select cure-prism-select" name="attr_value_images[${attrIndex}][]" data-value="${value}" onchange="ProductCommon.updatePreview(this)">
                    <option value="">デフォルト画像だよ</option>
                </select>
                <img class="preview-img ms-2" src="" style="width: 50px; height: 50px; object-fit: cover; display: none;" alt="プレビュー">
            `;
            container.appendChild(div);
        });
        this.updateImageOptions();
    },

    enableDragAndDrop(previewId) {
        const preview = document.getElementById(previewId);
        const items = preview.querySelectorAll('.image-item');
        items.forEach(item => {
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', item.dataset.index);
                e.dataTransfer.effectAllowed = 'move';
            });
            item.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });
            item.addEventListener('drop', (e) => {
                e.preventDefault();
                const fromIndex = e.dataTransfer.getData('text/plain');
                const toIndex = item.dataset.index;
                if (fromIndex !== toIndex) {
                    const fromItem = preview.children[fromIndex];
                    const toItem = preview.children[toIndex];
                    const nextSibling = toItem.nextSibling === fromItem ? toItem : toItem.nextSibling;
                    preview.insertBefore(fromItem, nextSibling);
                    this.updateIndices(previewId);
                    this.updateImageOptions();
                }
            });
        });
    },

    updateIndices(previewId) {
        const preview = document.getElementById(previewId);
        const items = preview.querySelectorAll('.image-item');
        items.forEach((item, index) => {
            item.dataset.index = index;
            const textarea = item.querySelector('textarea[name^="image_desc"]');
            if (textarea) textarea.name = `image_desc[${index}]`;
        });
    },

    updateImageOrder(previewId, orderId, descId) {
        const preview = document.getElementById(previewId);
        const items = preview.querySelectorAll('.image-item');
        const order = Array.from(items).map(item => item.dataset.index);
        const descriptions = Array.from(items).map(item => item.querySelector('textarea[name^="image_desc"]').value || '');
        document.getElementById(orderId).value = JSON.stringify(order);
        document.getElementById(descId).value = JSON.stringify(descriptions);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[name="attr_values[]"]').forEach((input, index) => {
        ProductCommon.updateAttrValueImages(input, index);
    });
});