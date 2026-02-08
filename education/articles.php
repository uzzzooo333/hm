<?php
require_once '../config.php';

// Get filter parameters
$category_slug = isset($_GET['category']) ? sanitize($_GET['category']) : null;
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : null;
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'latest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query
$where = ["ha.status = 'published'"];
$params = [];

if($category_slug) {
    $where[] = "hc.slug = ?";
    $params[] = $category_slug;
}

if($search_query) {
    $where[] = "(ha.title LIKE ? OR ha.content LIKE ? OR ha.tags LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = implode(' AND ', $where);

// Sorting
$order_by = match($sort) {
    'popular' => 'ha.views DESC',
    'oldest' => 'ha.published_at ASC',
    default => 'ha.published_at DESC'
};

// Get total count
$count_query = "SELECT COUNT(*) as total 
                FROM health_articles ha 
                JOIN health_categories hc ON ha.category_id = hc.id 
                WHERE $where_clause";
$count_stmt = $conn->prepare($count_query);
if(!empty($params)) {
    $types = str_repeat('s', count($params));
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_articles = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_articles / $per_page);

// Get articles
$query = "SELECT ha.*, hc.name as category_name, hc.slug as category_slug, 
                 u.name as author_name, u.specialization,
                 DATE_FORMAT(ha.published_at, '%M %d, %Y') as published_date
          FROM health_articles ha
          JOIN health_categories hc ON ha.category_id = hc.id
          JOIN users u ON ha.author_id = u.id
          WHERE $where_clause
          ORDER BY $order_by
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $per_page;
$params[] = $offset;
$types = str_repeat('s', count($params) - 2) . 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$articles = $stmt->get_result();

// Get all categories for filter
$categories = $conn->query("
    SELECT hc.*, COUNT(ha.id) as article_count
    FROM health_categories hc
    LEFT JOIN health_articles ha ON hc.id = ha.category_id AND ha.status = 'published'
    WHERE hc.parent_id IS NULL
    GROUP BY hc.id
    ORDER BY hc.name
");

// Get current category info if filtered
$current_category = null;
if($category_slug) {
    $cat_stmt = $conn->prepare("SELECT * FROM health_categories WHERE slug = ?");
    $cat_stmt->bind_param('s', $category_slug);
    $cat_stmt->execute();
    $current_category = $cat_stmt->get_result()->fetch_assoc();
}

// Get trending articles
$trending = $conn->query("
    SELECT ha.*, hc.name as category_name, hc.slug as category_slug
    FROM health_articles ha
    JOIN health_categories hc ON ha.category_id = hc.id
    WHERE ha.status = 'published'
    ORDER BY ha.views DESC
    LIMIT 5
");

// Get recent articles
$recent = $conn->query("
    SELECT ha.*, hc.name as category_name, hc.slug as category_slug
    FROM health_articles ha
    JOIN health_categories hc ON ha.category_id = hc.id
    WHERE ha.status = 'published'
    ORDER BY ha.published_at DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $current_category ? htmlspecialchars($current_category['name']) . ' - ' : '' ?>Health Articles - MediConnect360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        body {
            background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            color: white;
            padding: 80px 0 60px;
            position: relative;
            overflow: hidden;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,149.3C960,160,1056,160,1152,138.7C1248,117,1344,75,1392,53.3L1440,32L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') bottom;
            background-size: cover;
        }

        .page-header-content {
            position: relative;
            z-index: 1;
        }

        /* Breadcrumb */
        .breadcrumb {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            padding: 12px 25px;
            display: inline-flex;
        }

        .breadcrumb-item a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.3s;
        }

        .breadcrumb-item a:hover {
            opacity: 1;
        }

        .breadcrumb-item.active {
            color: white;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            color: white;
        }

        /* Navbar */
        .education-navbar {
            background: white;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-brand {
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Filter Sidebar */
        .filter-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            position: sticky;
            top: 90px;
        }

        .filter-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .category-filter-item {
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 8px;
            transition: all 0.3s;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .category-filter-item:hover {
            background: linear-gradient(to right, #667eea15, #764ba215);
            transform: translateX(5px);
        }

        .category-filter-item.active {
            background: var(--primary-gradient);
            color: white;
        }

        .category-filter-item.active .badge {
            background: white !important;
            color: #667eea !important;
        }

        /* Article Cards */
        .article-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            height: 100%;
            border: 2px solid transparent;
        }

        .article-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 60px rgba(102, 126, 234, 0.25);
            border-color: #667eea;
        }

        .article-image {
            height: 250px;
            overflow: hidden;
            position: relative;
        }

        .article-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .article-card:hover .article-image img {
            transform: scale(1.15) rotate(2deg);
        }

        .article-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            color: #667eea;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .reading-time {
            position: absolute;
            bottom: 15px;
            right: 15px;
            padding: 6px 15px;
            border-radius: 50px;
            font-size: 0.8rem;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(10px);
            color: white;
        }

        .article-content {
            padding: 25px;
        }

        .article-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.4;
            color: #2d3748;
            transition: color 0.3s;
        }

        .article-card:hover .article-title {
            color: #667eea;
        }

        .article-excerpt {
            color: #718096;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .article-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 20px;
            border-top: 2px solid #f7fafc;
        }

        .author-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .article-stats {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            color: #a0aec0;
        }

        /* Sidebar Widgets */
        .widget {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .widget-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .widget-title i {
            color: #667eea;
            font-size: 1.4rem;
        }

        .trending-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 12px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .trending-item:hover {
            background: #f7fafc;
            transform: translateX(5px);
        }

        .trending-number {
            width: 35px;
            height: 35px;
            background: var(--primary-gradient);
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }

        .trending-content h6 {
            font-size: 0.95rem;
            margin-bottom: 5px;
            color: #2d3748;
        }

        .trending-content small {
            color: #a0aec0;
        }

        /* Search Bar */
        .search-widget {
            position: relative;
        }

        .search-widget input {
            padding: 15px 50px 15px 20px;
            border-radius: 50px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
        }

        .search-widget input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-widget button {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Pagination */
        .custom-pagination {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 50px;
        }

        .custom-pagination .page-link {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 20px;
            color: #4a5568;
            transition: all 0.3s;
        }

        .custom-pagination .page-link:hover {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
        }

        .custom-pagination .page-item.active .page-link {
            background: var(--primary-gradient);
            border-color: transparent;
        }

        /* Sort Buttons */
        .sort-btn {
            padding: 10px 20px;
            border-radius: 50px;
            border: 2px solid #e2e8f0;
            background: white;
            color: #4a5568;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .sort-btn:hover, .sort-btn.active {
            background: var(--primary-gradient);
            border-color: transparent;
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-state i {
            font-size: 5rem;
            color: #cbd5e0;
            margin-bottom: 20px;
        }

        /* Loading Skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                padding: 50px 0 40px;
            }

            .filter-card {
                position: relative;
                top: 0;
                margin-bottom: 30px;
            }

            .article-image {
                height: 200px;
            }
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="education-navbar">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <a href="index.php" class="nav-brand text-decoration-none">
                <i class="bi bi-arrow-left me-2"></i>Health Center
            </a>
            <div>
                <a href="videos.php" class="btn btn-sm btn-outline-danger me-2">
                    <i class="bi bi-play-circle me-1"></i>Videos
                </a>
                <?php if(isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['admin', 'doctor'])): ?>
                <a href="manage_articles.php" class="btn btn-sm btn-gradient" style="background: var(--primary-gradient); color: white; border: none;">
                    <i class="bi bi-pencil-square me-1"></i>Manage
                </a>
                <?php else: ?>
                <a href="../login.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Login
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Page Header -->
<section class="page-header">
    <div class="container page-header-content">
        <nav aria-label="breadcrumb" data-aos="fade-down">
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="index.php">Health Center</a></li>
                <?php if($current_category): ?>
                <li class="breadcrumb-item active"><?= htmlspecialchars($current_category['name']) ?></li>
                <?php else: ?>
                <li class="breadcrumb-item active">All Articles</li>
                <?php endif; ?>
            </ol>
        </nav>

        <div data-aos="fade-up">
            <?php if($current_category): ?>
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width: 70px; height: 70px; background: rgba(255,255,255,0.2); border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                    <i class="bi bi-<?= $current_category['icon'] ?: 'heart-pulse' ?>"></i>
                </div>
                <div>
                    <h1 class="display-4 fw-bold mb-0"><?= htmlspecialchars($current_category['name']) ?></h1>
                    <p class="lead mb-0 opacity-75"><?= htmlspecialchars($current_category['description']) ?></p>
                </div>
            </div>
            <?php else: ?>
            <h1 class="display-4 fw-bold mb-3">
                <?= $search_query ? 'Search Results' : 'All Health Articles' ?>
            </h1>
            <p class="lead opacity-75">
                <?= $search_query ? "Results for: \"$search_query\"" : 'Browse our comprehensive health knowledge base' ?>
            </p>
            <?php endif; ?>

            <div class="mt-4">
                <span class="badge bg-white text-dark px-3 py-2">
                    <i class="bi bi-file-text me-2"></i><?= $total_articles ?> Articles
                </span>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3" data-aos="fade-right">
                <!-- Search Widget -->
                <div class="widget">
                    <h5 class="widget-title">
                        <i class="bi bi-search"></i>
                        Search Articles
                    </h5>
                    <form action="" method="GET" class="search-widget">
                        <?php if($category_slug): ?>
                        <input type="hidden" name="category" value="<?= $category_slug ?>">
                        <?php endif; ?>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search..." 
                               value="<?= htmlspecialchars($search_query ?? '') ?>">
                        <button type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>

                <!-- Categories -->
                <div class="filter-card">
                    <h5 class="filter-title">
                        <i class="bi bi-grid me-2"></i>Categories
                    </h5>
                    <a href="articles.php" class="text-decoration-none">
                        <div class="category-filter-item <?= !$category_slug ? 'active' : '' ?>">
                            <span>All Articles</span>
                            <span class="badge bg-light text-dark"><?= $total_articles ?></span>
                        </div>
                    </a>
                    <?php 
                    $categories->data_seek(0);
                    while($cat = $categories->fetch_assoc()): 
                    ?>
                    <a href="articles.php?category=<?= $cat['slug'] ?>" class="text-decoration-none">
                        <div class="category-filter-item <?= $category_slug === $cat['slug'] ? 'active' : '' ?>">
                            <span>
                                <i class="bi bi-<?= $cat['icon'] ?: 'heart-pulse' ?> me-2"></i>
                                <?= htmlspecialchars($cat['name']) ?>
                            </span>
                            <span class="badge bg-light text-dark"><?= $cat['article_count'] ?></span>
                        </div>
                    </a>
                    <?php endwhile; ?>
                </div>

                <!-- Trending Articles -->
                <div class="widget">
                    <h5 class="widget-title">
                        <i class="bi bi-fire"></i>
                        Trending Now
                    </h5>
                    <?php 
                    $rank = 1;
                    while($trend = $trending->fetch_assoc()): 
                    ?>
                    <a href="article_view.php?slug=<?= $trend['slug'] ?>" class="text-decoration-none">
                        <div class="trending-item">
                            <div class="trending-number"><?= $rank ?></div>
                            <div class="trending-content">
                                <h6><?= htmlspecialchars($trend['title']) ?></h6>
                                <small>
                                    <i class="bi bi-eye me-1"></i><?= $trend['views'] ?> views
                                </small>
                            </div>
                        </div>
                    </a>
                    <?php 
                    $rank++;
                    endwhile; 
                    ?>
                </div>

                <!-- Recent Articles -->
                <div class="widget">
                    <h5 class="widget-title">
                        <i class="bi bi-clock-history"></i>
                        Recently Added
                    </h5>
                    <?php while($rec = $recent->fetch_assoc()): ?>
                    <a href="article_view.php?slug=<?= $rec['slug'] ?>" class="text-decoration-none">
                        <div class="trending-item">
                            <div class="trending-content">
                                <h6><?= htmlspecialchars($rec['title']) ?></h6>
                                <small class="text-muted">
                                    <i class="bi bi-tag me-1"></i><?= $rec['category_name'] ?>
                                </small>
                            </div>
                        </div>
                    </a>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Articles Grid -->
            <div class="col-lg-9">
                <!-- Sort & Filter Bar -->
                <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-left">
                    <div>
                        <h5 class="mb-0">
                            <?php if($search_query): ?>
                            Found <?= $total_articles ?> results
                            <?php else: ?>
                            Showing <?= min($per_page, $total_articles) ?> of <?= $total_articles ?> articles
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'latest'])) ?>" 
                           class="sort-btn <?= $sort === 'latest' ? 'active' : '' ?>">
                            <i class="bi bi-clock me-1"></i>Latest
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'popular'])) ?>" 
                           class="sort-btn <?= $sort === 'popular' ? 'active' : '' ?>">
                            <i class="bi bi-fire me-1"></i>Popular
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'oldest'])) ?>" 
                           class="sort-btn <?= $sort === 'oldest' ? 'active' : '' ?>">
                            <i class="bi bi-archive me-1"></i>Oldest
                        </a>
                    </div>
                </div>

                <?php if($articles->num_rows > 0): ?>
                <!-- Articles Grid -->
                <div class="row g-4">
                    <?php 
                    $delay = 0;
                    while($article = $articles->fetch_assoc()): 
                        $read_time = ceil(str_word_count(strip_tags($article['content'])) / 200);
                    ?>
                    <div class="col-md-6" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
                        <div class="article-card">
                            <div class="article-image">
                                <?php if($article['featured_image']): ?>
                                <img src="<?= UPLOADS_URL . $article['featured_image'] ?>" 
                                     alt="<?= htmlspecialchars($article['title']) ?>">
                                <?php else: ?>
                                <img src="https://via.placeholder.com/600x300/667eea/ffffff?text=<?= urlencode(substr($article['title'], 0, 20)) ?>" 
                                     alt="<?= htmlspecialchars($article['title']) ?>">
                                <?php endif; ?>
                                <span class="article-badge">
                                    <i class="bi bi-tag-fill me-1"></i><?= $article['category_name'] ?>
                                </span>
                                <span class="reading-time">
                                    <i class="bi bi-clock me-1"></i><?= $read_time ?> min read
                                </span>
                            </div>
                            <div class="article-content">
                                <h5 class="article-title">
                                    <a href="article_view.php?slug=<?= $article['slug'] ?>" 
                                       class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($article['title']) ?>
                                    </a>
                                </h5>
                                <p class="article-excerpt">
                                    <?= htmlspecialchars(substr($article['excerpt'], 0, 120)) ?>...
                                </p>
                                <div class="article-meta">
                                    <div class="author-info">
                                        <div class="author-avatar">
                                            <i class="bi bi-person-fill"></i>
                                        </div>
                                        <div>
                                            <small class="fw-bold d-block"><?= htmlspecialchars($article['author_name']) ?></small>
                                            <small class="text-muted"><?= $article['published_date'] ?></small>
                                        </div>
                                    </div>
                                    <div class="article-stats">
                                        <span><i class="bi bi-eye me-1"></i><?= $article['views'] ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php 
                    $delay += 50;
                    endwhile; 
                    ?>
                </div>

                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <nav aria-label="Page navigation" data-aos="fade-up">
                    <ul class="pagination custom-pagination">
                        <?php if($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
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
                <div class="empty-state" data-aos="fade-up">
                    <i class="bi bi-file-earmark-x"></i>
                    <h3 class="mb-3">No Articles Found</h3>
                    <p class="text-muted mb-4">
                        <?php if($search_query): ?>
                        Try adjusting your search terms or browse all articles
                        <?php else: ?>
                        No articles available in this category yet
                        <?php endif; ?>
                    </p>
                    <a href="articles.php" class="btn btn-gradient" style="background: var(--primary-gradient); color: white; border: none;">
                        <i class="bi bi-arrow-left me-2"></i>View All Articles
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Back to Top -->
<button id="backToTop" class="btn position-fixed bottom-0 end-0 m-4 rounded-circle" 
        style="width: 50px; height: 50px; display: none; z-index: 999; background: var(--primary-gradient); color: white; border: none;">
    <i class="bi bi-arrow-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    // Initialize AOS
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true,
        mirror: false
    });

    // Back to Top
    const backToTop = document.getElementById('backToTop');
    
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            backToTop.style.display = 'block';
        } else {
            backToTop.style.display = 'none';
        }
    });

    backToTop.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Clear search on category click
    document.querySelectorAll('.category-filter-item').forEach(item => {
        item.addEventListener('click', function() {
            const url = new URL(this.parentElement.href);
            url.searchParams.delete('search');
            window.location.href = url.toString();
        });
    });
</script>

</body>
</html>
