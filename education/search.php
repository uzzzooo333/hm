<?php
require_once '../config.php';
require_once '../includes/header.php';

$query = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$results = [];

if($query) {
    // Search articles
    $articles = $conn->query("
        SELECT 'article' as type, ha.id, ha.title, ha.slug, ha.excerpt as description, 
               hc.name as category, ha.featured_image, ha.views
        FROM health_articles ha
        JOIN health_categories hc ON ha.category_id = hc.id
        WHERE ha.status = 'published' 
        AND (ha.title LIKE '%$query%' OR ha.content LIKE '%$query%' OR ha.tags LIKE '%$query%')
    ");
    
    // Search videos
    $videos = $conn->query("
        SELECT 'video' as type, hv.id, hv.title, hv.youtube_id as slug, hv.description, 
               hc.name as category, hv.thumbnail_url as featured_image, hv.views
        FROM health_videos hv
        JOIN health_categories hc ON hv.category_id = hc.id
        WHERE hv.status = 'active' 
        AND (hv.title LIKE '%$query%' OR hv.description LIKE '%$query%')
    ");
    
    while($row = $articles->fetch_assoc()) $results[] = $row;
    while($row = $videos->fetch_assoc()) $results[] = $row;
}
?>

<div class="container-fluid">
    <h2 class="mb-4">Search Results for "<?= htmlspecialchars($query) ?>"</h2>
    
    <?php if(empty($results)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> No results found. Try different keywords.
    </div>
    <?php else: ?>
    <p class="text-muted">Found <?= count($results) ?> results</p>
    
    <div class="row g-3">
        <?php foreach($results as $item): ?>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <span class="badge bg-<?= $item['type'] == 'article' ? 'primary' : 'danger' ?> mb-2">
                        <?= ucfirst($item['type']) ?> - <?= $item['category'] ?>
                    </span>
                    <h5><?= htmlspecialchars($item['title']) ?></h5>
                    <p class="text-muted"><?= htmlspecialchars(substr($item['description'], 0, 150)) ?>...</p>
                    <div class="d-flex justify-content-between">
                        <small><i class="bi bi-eye"></i> <?= $item['views'] ?> views</small>
                        <a href="<?= $item['type'] == 'article' ? 'article_view.php?slug=' . $item['slug'] : 'videos.php#video' . $item['id'] ?>" 
                           class="btn btn-sm btn-outline-primary">View</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
