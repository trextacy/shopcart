document.addEventListener('DOMContentLoaded', function() {
  const cartItemList = document.querySelector('.cart-item-list');

  cartItemList.addEventListener('click', function(event) {
    if (event.target.classList.contains('cart-item-delete-button')) {
      // 削除ボタンクリック時の処理

      const deleteButton = event.target;
      const cartItem = deleteButton.closest('.cart-item');
      const itemKey = cartItem.dataset.itemKey;

      if (confirm('カートから商品を削除しますか？')) {
        // 確認ダイアログで「OK」が選択された場合のみ削除処理を実行

        const formData = new FormData();
        formData.append('action', 'remove_item');
        formData.append('item_key', itemKey);

        fetch('cart.php', {
          method: 'POST',
          body: formData,
        })
        .then(response => {
          if (response.ok) {
            // 削除成功時
            cartItem.remove(); //remove() で DOM からカートアイテムを削除

            // カート内商品がなくなった場合、カートが空のメッセージを表示
            if (cartItemList.querySelectorAll('.cart-item').length === 0) {
              const cartEmptyMessage = `
                <div class="alert alert-info" role="alert">
                  カートに商品はまだ入っていません。
                </div>
              `;
              cartItemList.insertAdjacentHTML('beforebegin', cartEmptyMessage); // list-group の前にメッセージを挿入
              cartItemList.remove(); // カートアイテムリスト自体を削除
            } else {
              // **【【【【【カート合計金額を再計算して表示】】】】】**
              updateCartTotal(); // カート合計金額を再計算
            }


          } else {
            // 削除失敗時
            alert('商品の削除に失敗しました。');
          }
        })
        .catch(error => {
          console.error('エラー:', error);
          alert('商品の削除中にエラーが発生しました。');
        });
      }
    }
  });

  // **【【【【【カート合計金額を再計算する関数】】】】】**
  function updateCartTotal() {
    fetch('cart_total_ajax.php?action=get_total') // cart_total_ajax.php に action=get_total でリクエスト
      .then(response => response.json())
      .then(data => {
        if (data && data.total_price !== undefined) {
          // 合計金額表示部分を更新
          const cartSummaryPriceElement = document.querySelector('.cart-summary-price');
          cartSummaryPriceElement.textContent = new Intl.NumberFormat('ja-JP').format(data.total_price) + '円（税込）'; //toLocaleString() を使用
        } else {
          console.error('カート合計金額の取得に失敗しました。');
        }
      })
      .catch(error => {
        console.error('カート合計金額の取得中にエラーが発生しました:', error);
      });
  }

  // **【【【【【ページ読み込み時にカート合計金額を初期表示】】】】】**
  updateCartTotal(); // ページ読み込み時に実行
});