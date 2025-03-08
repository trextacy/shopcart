document.addEventListener('DOMContentLoaded', function() {
    const cartItemList = document.querySelector('.cart-item-list');

    if (cartItemList) { // ".cart-item-list" が存在する場合のみ処理を実行
        cartItemList.addEventListener('click', function(event) {
            if (event.target.classList.contains('decrease-button') || event.target.classList.contains('increase-button')) {
                // 数量変更ボタンクリック時
                const button = event.target;
                const itemElement = button.closest('.cart-item');
                const itemKey = itemElement.dataset.itemKey; // data-item-key 属性から itemKey を取得
                const quantityInput = itemElement.querySelector('.cart-quantity-input');
                let quantity = parseInt(quantityInput.value);
                const change = button.classList.contains('decrease-button') ? -1 : 1;
                quantity += change;

                if (quantity < 1) {
                    quantity = 1; // 数量が 1 未満にならないように調整
                }

                quantityInput.value = quantity; // 数量表示を更新
                updateCartItemQuantity(itemKey, quantity); // 数量更新処理を実行

            } else if (event.target.classList.contains('cart-item-delete-button')) {
                // 削除ボタンクリック時
                const deleteButton = event.target;
                const itemElement = deleteButton.closest('.cart-item');
                const itemKey = itemElement.dataset.itemKey; // data-item-key 属性から itemKey を取得
                removeItemFromCart(itemKey); // 商品削除処理を実行
            }
        });
    }

    // 数量更新処理 (カートの数量を更新し、カートページを再読み込み)
    function updateCartItemQuantity(itemKey, quantity) {
        const formData = new FormData();
        formData.append('action', 'update_quantity');
        formData.append('item_key', itemKey);
        formData.append('quantity', quantity);

        fetch('cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text(); // HTML全体をテキストとして取得
        })
        .then(html => {
            // カートページを再読み込み (HTML全体をresponseTextで置き換える)
            document.documentElement.innerHTML = html;
            // JavaScript を再実行 (カートページ再読み込み後、再度イベントリスナーを設定するため)
            initializeCart();
        })
        .catch(error => {
            console.error('カート数量更新エラー:', error);
            alert('カート数量の更新に失敗しました。');
        });
    }

    // 商品削除処理 (カートから商品を削除し、カートページを再読み込み)
    function removeItemFromCart(itemKey) {
        const formData = new FormData();
        formData.append('action', 'remove_item');
        formData.append('item_key', itemKey);

        fetch('cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text(); // HTML全体をテキストとして取得
        })
        .then(html => {
             // カートページを再読み込み (HTML全体をresponseTextで置き換える)
            document.documentElement.innerHTML = html;
            // JavaScript を再実行 (カートページ再読み込み後、再度イベントリスナーを設定するため)
            initializeCart();
        })
        .catch(error => {
            console.error('カート削除エラー:', error);
            alert('カートから商品を削除できませんでした。');
        });
    }

    // カート初期化処理 (イベントリスナー設定)
    function initializeCart() {
        const cartItemList = document.querySelector('.cart-item-list');

        if (cartItemList) { // ".cart-item-list" が存在する場合のみ処理を実行
            cartItemList.addEventListener('click', function(event) {
                if (event.target.classList.contains('decrease-button') || event.target.classList.contains('increase-button')) {
                    // 数量変更ボタンクリック時
                    const button = event.target;
                    const itemElement = button.closest('.cart-item');
                    const itemKey = itemElement.dataset.itemKey; // data-item-key 属性から itemKey を取得
                    const quantityInput = itemElement.querySelector('.cart-quantity-input');
                    let quantity = parseInt(quantityInput.value);
                    const change = button.classList.contains('decrease-button') ? -1 : 1;
                    quantity += change;

                    if (quantity < 1) {
                        quantity = 1; // 数量が 1 未満にならないように調整
                    }

                    quantityInput.value = quantity; // 数量表示を更新
                    updateCartItemQuantity(itemKey, quantity); // 数量更新処理を実行

                } else if (event.target.classList.contains('cart-item-delete-button')) {
                    // 削除ボタンクリック時
                    const deleteButton = event.target;
                    const itemElement = deleteButton.closest('.cart-item');
                    const itemKey = itemElement.dataset.itemKey; // data-item-key 属性から itemKey を取得
                    removeItemFromCart(itemKey); // 商品削除処理を実行
                }
            });
        }
    }
    initializeCart(); // 初期化処理を実行
});