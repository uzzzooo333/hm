<?php
require_once '../config.php';

// Get filter parameters
$category_slug = $_GET['category'] ?? null;
$search_query = $_GET['search'] ?? null;
$sort = $_GET['sort'] ?? 'latest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where = ["hv.status = 'active'"];
$params = [];
$types = '';

if($category_slug) {
    $where[] = "hc.slug = ?";
    $params[] = $category_slug;
    $types .= 's';
}

if($search_query) {
    $where[] = "(hv.title LIKE ? OR hv.description LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$where_clause = implode(' AND ', $where);

// Sorting
$order_by = match($sort) {
    'popular' => 'hv.views DESC',
    'oldest' => 'hv.created_at ASC',
    default => 'hv.created_at DESC'
};

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM health_videos hv JOIN health_categories hc ON hv.category_id = hc.id WHERE $where_clause";
$count_stmt = $conn->prepare($count_sql);
if($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_videos = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_videos / $per_page);

// Get videos
$sql = "SELECT hv.*, hc.name as category_name, hc.slug as category_slug 
        FROM health_videos hv
        JOIN health_categories hc ON hv.category_id = hc.id
        WHERE $where_clause
        ORDER BY $order_by
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$videos = $stmt->get_result();

// Get categories
$categories = $conn->query("
    SELECT hc.*, COUNT(hv.id) as video_count
    FROM health_categories hc
    LEFT JOIN health_videos hv ON hc.id = hv.category_id AND hv.status = 'active'
    WHERE hc.parent_id IS NULL
    GROUP BY hc.id
    ORDER BY hc.order_position
");

$current_category = null;
if($category_slug) {
    $cat_stmt = $conn->prepare("SELECT * FROM health_categories WHERE slug = ?");
    $cat_stmt->bind_param('s', $category_slug);
    $cat_stmt->execute();
    $current_category = $cat_stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $current_category ? htmlspecialchars($current_category['name']) : 'All Videos' ?> - MediConnect360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* Top Navigation */
        .top-nav {
            background: #2c3e50;
            padding: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .top-nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand {
            color: #7b68ee;
            font-size: 20px;
            font-weight: 600;
            text-decoration: none;
        }

        .nav-brand:hover {
            color: #9381ff;
        }

        .nav-buttons .btn {
            margin-left: 10px;
            border-radius: 8px;
        }

        .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
        }

        .btn-outline-secondary {
            border-color: #6c757d;
            color: #6c757d;
        }

        /* Hero Header */
        .hero-header {
            background: linear-gradient(135deg, #7b68ee, #9381ff);
            color: white;
            padding: 50px 0;
        }

        .breadcrumb {
            background: rgba(255,255,255,0.2);
            border-radius: 25px;
            padding: 10px 20px;
            display: inline-flex;
            margin-bottom: 20px;
        }

        .breadcrumb-item a {
            color: white;
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: white;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            color: white;
            content: "/";
        }

        .hero-title {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .hero-subtitle {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 25px;
        }

        .stat-badge {
            display: inline-block;
            background: white;
            color: #7b68ee;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
        }

        /* Main Content */
        .content-wrapper {
            padding: 40px 0;
        }

        .sidebar {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 20px;
        }

        .sidebar h5 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            color: #2c3e50;
        }

        .sidebar h5 i {
            margin-right: 10px;
            color: #7b68ee;
        }

        /* Search Box */
        .search-box {
            position: relative;
            margin-bottom: 30px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 50px 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #7b68ee;
            box-shadow: 0 0 0 3px rgba(123, 104, 238, 0.1);
        }

        .search-box button {
            position: absolute;
            right: 5px;
            top: 5px;
            width: 42px;
            height: 42px;
            border: none;
            background: #7b68ee;
            color: white;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
        }

        .search-box button:hover {
            background: #6a5acd;
            transform: scale(1.05);
        }

        /* Categories List */
        .category-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .category-item {
            margin-bottom: 8px;
        }

        .category-item a {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-radius: 10px;
            color: #2c3e50;
            text-decoration: none;
            transition: all 0.3s;
        }

        .category-item a:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }

        .category-item a.active {
            background: #7b68ee;
            color: white;
        }

        .category-item a.active .badge {
            background: rgba(255,255,255,0.3);
            color: white;
        }

        .category-item .badge {
            background: #e0e0e0;
            color: #666;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
        }

        /* Content Header */
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .content-header h5 {
            color: #2c3e50;
            font-weight: 600;
            margin: 0;
        }

        .sort-buttons {
            display: flex;
            gap: 10px;
        }

        .sort-btn {
            padding: 8px 20px;
            border-radius: 25px;
            border: 2px solid #e0e0e0;
            background: white;
            color: #666;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .sort-btn:hover {
            border-color: #7b68ee;
            color: #7b68ee;
        }

        .sort-btn.active {
            background: #7b68ee;
            border-color: #7b68ee;
            color: white;
        }

        /* Video Card */
        .video-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
            margin-bottom: 25px;
            cursor: pointer;
        }

        .video-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        .video-thumbnail {
            position: relative;
            width: 100%;
            padding-top: 56.25%; /* 16:9 */
            background: #000;
            overflow: hidden;
        }

        .video-thumbnail img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .video-play-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .video-card:hover .video-play-overlay {
            background: rgba(0,0,0,0.5);
        }

        .play-btn-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #dc3545;
            transition: all 0.3s;
        }

        .video-card:hover .play-btn-icon {
            transform: scale(1.15);
            background: white;
        }

        .video-duration {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }

        .video-info {
            padding: 18px;
        }

        .video-category-badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .video-title {
            font-size: 15px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .video-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #999;
            font-size: 13px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #f0f0f0;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 12px;
        }

        .empty-state i {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h4 {
            color: #666;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #999;
            margin-bottom: 25px;
        }

        /* Pagination */
        .pagination {
            justify-content: center;
            margin-top: 40px;
        }

        .pagination .page-link {
            border-radius: 8px;
            margin: 0 5px;
            border: 2px solid #e0e0e0;
            color: #666;
            font-weight: 600;
            padding: 8px 16px;
        }

        .pagination .page-link:hover {
            background: #7b68ee;
            border-color: #7b68ee;
            color: white;
        }

        .pagination .page-item.active .page-link {
            background: #7b68ee;
            border-color: #7b68ee;
        }
    </style>
</head>
<body>

<!-- Top Navigation -->
<nav class="top-nav">
    <div class="container">
        <a href="index.php" class="nav-brand">
            <i class="bi bi-arrow-left me-2"></i>Health Center
        </a>
        <div class="nav-buttons">
            <a href="articles.php" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-newspaper me-1"></i>Articles
            </a>
            <a href="../login.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-box-arrow-in-right me-1"></i>Login
            </a>
        </div>
    </div>
</nav>

<!-- Hero Header -->
<div class="hero-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="index.php">Health Center</a></li>
                <li class="breadcrumb-item active"><?= $current_category ? htmlspecialchars($current_category['name']) : 'All Videos' ?></li>
            </ol>
        </nav>

        <h1 class="hero-title">
            <?= $current_category ? htmlspecialchars($current_category['name']) : 'All Health Videos' ?>
        </h1>
        <p class="hero-subtitle">
            <?= $current_category ? htmlspecialchars($current_category['description']) : 'Watch expert health videos and tutorials' ?>
        </p>
        <span class="stat-badge">
            <i class="bi bi-camera-video me-2"></i><?= $total_videos ?> Videos
        </span>
    </div>
</div>

<!-- Main Content -->
<div class="content-wrapper">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="sidebar">
                    <!-- Search -->
                    <h5><i class="bi bi-search"></i>Search Videos</h5>
                    <form action="" method="GET" class="search-box">
                        <?php if($category_slug): ?>
                        <input type="hidden" name="category" value="<?= $category_slug ?>">
                        <?php endif; ?>
                        <input type="text" name="search" placeholder="Search..." 
                               value="<?= htmlspecialchars($search_query ?? '') ?>">
                        <button type="submit"><i class="bi bi-search"></i></button>
                    </form>

                    <!-- Categories -->
                    <h5><i class="bi bi-grid"></i>Categories</h5>
                    <ul class="category-list">
                        <li class="category-item">
                            <a href="videos.php" class="<?= !$category_slug ? 'active' : '' ?>">
                                <span><i class="bi bi-collection-play me-2"></i>All Videos</span>
                                <span class="badge"><?= $total_videos ?></span>
                            </a>
                        </li>
                        <?php while($cat = $categories->fetch_assoc()): ?>
                        <li class="category-item">
                            <a href="videos.php?category=<?= $cat['slug'] ?>" 
                               class="<?= $category_slug === $cat['slug'] ? 'active' : '' ?>">
                                <span>
                                    <i class="bi bi-<?= $cat['icon'] ?: 'play-circle' ?> me-2"></i>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </span>
                                <span class="badge"><?= $cat['video_count'] ?></span>
                            </a>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Content Header -->
                <div class="content-header">
                    <h5>Showing <?= $total_videos ?> of <?= $total_videos ?> videos</h5>
                    <div class="sort-buttons">
                        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'latest'])) ?>" 
                           class="sort-btn <?= $sort === 'latest' ? 'active' : '' ?>">
                            <i class="bi bi-clock"></i>Latest
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'popular'])) ?>" 
                           class="sort-btn <?= $sort === 'popular' ? 'active' : '' ?>">
                            <i class="bi bi-fire"></i>Popular
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'oldest'])) ?>" 
                           class="sort-btn <?= $sort === 'oldest' ? 'active' : '' ?>">
                            <i class="bi bi-archive"></i>Oldest
                        </a>
                    </div>
                </div>

                <?php if($videos->num_rows > 0): ?>
                <!-- Videos Grid -->
                <div class="row">
                    <?php while($video = $videos->fetch_assoc()): 
                        $thumbnail = $video['thumbnail_url'] ?: "https://img.youtube.com/vi/{$video['youtube_id']}/maxresdefault.jpg";
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="video-card" data-bs-toggle="modal" data-bs-target="#videoModal<?= $video['id'] ?>">
                            <div class="video-thumbnail">
                                <img src="<?= $thumbnail ?>" alt="<?= htmlspecialchars($video['title']) ?>">
                                <div class="video-play-overlay">
                                    <div class="play-btn-icon">
                                        <i class="bi bi-play-fill"></i>
                                    </div>
                                </div>
                                <?php if($video['duration']): ?>
                                <div class="video-duration"><?= $video['duration'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="video-info">
                                <span class="video-category-badge"><?= htmlspecialchars($video['category_name']) ?></span>
                                <h6 class="video-title"><?= htmlspecialchars($video['title']) ?></h6>
                                <div class="video-stats">
                                    <span><i class="bi bi-eye me-1"></i><?= number_format($video['views']) ?></span>
                                    <span><i class="bi bi-youtube me-1"></i>YouTube</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Video Modal -->
                    <div class="modal fade" id="videoModal<?= $video['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-xl modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?= htmlspecialchars($video['title']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-0">
                                    <div class="ratio ratio-16x9">
                                        <iframe src="https://www.youtube.com/embed/<?= $video['youtube_id'] ?>?autoplay=1" 
                                                allowfullscreen allow="autoplay"></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <nav>
                    <ul class="pagination">
                        <?php if($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>

                        <?php if($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>

                <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="bi bi-x-circle"></i>
                    <h4>No Videos Found</h4>
                    <p>Try adjusting your search or browse all videos</p>
                    <a href="videos.php" class="btn btn-primary">View All Videos</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="py-5"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Stop video when modal closes
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('hidden.bs.modal', function () {
        const iframe = this.querySelector('iframe');
        if (iframe) {
            iframe.src = iframe.src;
        }
    });
});
</script>

</body>
</html>
