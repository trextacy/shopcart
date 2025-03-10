<?php
if (!function_exists('handle_search_query')) {
    function handle_search_query() {
        $search_query = trim($_GET['search'] ?? '');
        if ($search_query) {
            if (basename($_SERVER['PHP_SELF']) !== 'index.php') {
                header("Location: index.php?search=" . urlencode($search_query));
                exit;
            }
        }
        return htmlspecialchars($search_query);
    }
}

echo <<<EOT
<script>
document.addEventListener('DOMContentLoaded', () => {
    console.log('Search script loaded');
    const searchToggle = document.getElementById('searchToggle');
    const searchForm = document.querySelector('.search-form');
    
    if (!searchToggle || !searchForm) {
        console.error('Search elements not found:', { toggle: !!searchToggle, form: !!searchForm });
        return;
    }
    
    console.log('Search elements found, adding listeners');
    
    searchToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        searchForm.classList.toggle('active');
//        console.log('Search toggle clicked, current state:', searchForm.classList.contains('active'));
//        console.log('Search form width:', window.getComputedStyle(searchForm).width); // 実際の幅をログ出力
    });
    
    document.addEventListener('click', (e) => {
        if (!searchForm.contains(e.target) && e.target !== searchToggle) {
//            console.log('Clicked outside, closing search');
            searchForm.classList.remove('active');
        }
    });
});
</script>
EOT;
?>