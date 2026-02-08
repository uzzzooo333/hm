<?php
require_once '../config.php';

// Fetch categories with article count
$categories = $conn->query("
    SELECT hc.*, COUNT(ha.id) as article_count
    FROM health_categories hc
    LEFT JOIN health_articles ha ON hc.id = ha.category_id AND ha.status = 'published'
    WHERE hc.parent_id IS NULL
    GROUP BY hc.id
    ORDER BY hc.order_position
");

// Fetch featured articles
$featured = $conn->query("
    SELECT ha.*, hc.name as category_name, hc.slug as category_slug, 
           u.name as author_name, u.specialization
    FROM health_articles ha
    JOIN health_categories hc ON ha.category_id = hc.id
    JOIN users u ON ha.author_id = u.id
    WHERE ha.status = 'published'
    ORDER BY ha.views DESC, ha.published_at DESC
    LIMIT 6
");

// Fetch latest videos
$videos = $conn->query("
    SELECT hv.*, hc.name as category_name, hc.slug as category_slug
    FROM health_videos hv
    JOIN health_categories hc ON hv.category_id = hc.id
    WHERE hv.status = 'active'
    ORDER BY hv.created_at DESC
    LIMIT 8
");

// Fetch featured tips
$tips = $conn->query("
    SELECT ht.*, hc.name as category_name
    FROM health_tips ht
    JOIN health_categories hc ON ht.category_id = hc.id
    WHERE ht.is_featured = 1
    ORDER BY ht.priority DESC, RAND()
    LIMIT 6
");

// Get statistics
$stats = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM health_articles WHERE status = 'published') as total_articles,
        (SELECT COUNT(*) FROM health_videos WHERE status = 'active') as total_videos,
        (SELECT COUNT(*) FROM health_tips) as total_tips,
        (SELECT COUNT(*) FROM health_categories WHERE parent_id IS NULL) as total_categories
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Education Center - CHEP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        body {
            background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Animated Gradient Hero */
        .hero-section {
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            color: white;
            padding: 100px 0 80px;
            position: relative;
            overflow: hidden;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,149.3C960,160,1056,160,1152,138.7C1248,117,1344,75,1392,53.3L1440,32L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') bottom;
            background-size: cover;
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        /* Search Bar */
        .search-wrapper {
            position: relative;
            max-width: 700px;
            margin: 30px auto 0;
        }

        .search-input {
            height: 60px;
            border-radius: 50px;
            padding: 0 60px 0 30px;
            font-size: 1.1rem;
            border: 3px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.95);
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .search-input:focus {
            border-color: white;
            box-shadow: 0 15px 50px rgba(0,0,0,0.2);
            transform: translateY(-2px);
        }

        .search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            height: 45px;
            width: 45px;
            border-radius: 50%;
            border: none;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .search-btn:hover {
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            border-color: #667eea;
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 15px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            background: var(--primary-gradient);
            color: white;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Category Cards */
        .category-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--primary-gradient);
            transition: all 0.4s;
            z-index: 0;
        }

        .category-card:hover::before {
            left: 0;
        }

        .category-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 25px 50px rgba(102, 126, 234, 0.4);
            border-color: #667eea;
        }

        .category-card * {
            position: relative;
            z-index: 1;
            transition: all 0.3s;
        }

        .category-card:hover * {
            color: white !important;
        }

        .category-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #667eea;
            transition: all 0.3s;
        }

        .category-card:hover .category-icon {
            background: rgba(255,255,255,0.2);
            transform: scale(1.1) rotate(5deg);
        }

        /* Article Cards */
        .article-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.4s;
            height: 100%;
            border: 2px solid transparent;
        }

        .article-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            border-color: #667eea;
        }

        .article-image {
            height: 220px;
            overflow: hidden;
            position: relative;
        }

        .article-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .article-card:hover .article-image img {
            transform: scale(1.15);
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
            color: #667eea;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .article-content {
            padding: 25px;
        }

        .article-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }

        /* Video Cards */
        .video-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.4s;
            position: relative;
        }

        .video-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        }

        .video-thumbnail {
            position: relative;
            height: 200px;
            overflow: hidden;
        }

        .video-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .video-card:hover .video-thumbnail img {
            transform: scale(1.1);
        }

        .play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 70px;
            height: 70px;
            background: rgba(255,255,255,0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #dc3545;
            transition: all 0.3s;
            cursor: pointer;
        }

        .video-card:hover .play-button {
            transform: translate(-50%, -50%) scale(1.2);
            background: white;
        }

        /* Health Tips */
        .tip-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            border-left: 5px solid #10b981;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
        }

        .tip-card:hover {
            transform: translateX(10px);
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.2);
        }

        .tip-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        /* Floating Animation */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .float-animation {
            animation: float 6s ease-in-out infinite;
        }

        /* Section Titles */
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-subtitle {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 50px;
        }

        /* Buttons */
        .btn-gradient {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.5);
            color: white;
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
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="education-navbar">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <a href="../index.php" class="nav-brand text-decoration-none">
                <i class="bi bi-hospital-fill me-2"></i>CHEP
            </a>
            <div>
                <a href="articles.php" class="btn btn-sm btn-outline-primary me-2">
                    <i class="bi bi-newspaper me-1"></i>Articles
                </a>
                <a href="videos.php" class="btn btn-sm btn-outline-danger me-2">
                    <i class="bi bi-play-circle me-1"></i>Videos
                </a>
                <?php if(isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['admin', 'doctor'])): ?>
                <a href="manage_articles.php" class="btn btn-sm btn-gradient">
                    <i class="bi bi-pencil-square me-1"></i>Manage Content
                </a>
                <?php else: ?>
                <a href="../login.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Staff Login
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container hero-content">
        <div class="text-center" data-aos="fade-down">
            <h1 class="display-3 fw-bold mb-3">Health Education Center</h1>
            <p class="lead mb-4">Your trusted source for evidence-based health information</p>
            
            <!-- Search Bar -->
            <div class="search-wrapper" data-aos="fade-up" data-aos-delay="200">
                <form action="search.php" method="GET">
                    <input type="text" name="q" class="form-control search-input" 
                           placeholder="Search articles, videos, diseases..." required>
                    <button type="submit" class="search-btn">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>

            <!-- Quick Stats -->
            <div class="row mt-5 g-4">
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-file-text"></i>
                        </div>
                        <div class="stat-number"><?= $stats['total_articles'] ?></div>
                        <small class="text-muted">Articles</small>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-play-circle"></i>
                        </div>
                        <div class="stat-number"><?= $stats['total_videos'] ?></div>
                        <small class="text-muted">Videos</small>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-lightbulb"></i>
                        </div>
                        <div class="stat-number"><?= $stats['total_tips'] ?></div>
                        <small class="text-muted">Health Tips</small>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-grid"></i>
                        </div>
                        <div class="stat-number"><?= $stats['total_categories'] ?></div>
                        <small class="text-muted">Categories</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Browse Categories -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="section-title">Browse by Category</h2>
            <p class="section-subtitle">Explore health topics organized by specialty</p>
        </div>

        <div class="row g-4">
            <?php 
            $delay = 0;
            while($cat = $categories->fetch_assoc()): 
            ?>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
                <a href="articles.php?category=<?= $cat['slug'] ?>" class="text-decoration-none">
                    <div class="category-card">
                        <div class="category-icon">
                            <i class="bi bi-<?= $cat['icon'] ?: 'heart-pulse' ?>"></i>
                        </div>
                        <h5 class="mb-2"><?= htmlspecialchars($cat['name']) ?></h5>
                        <p class="text-muted small mb-2"><?= htmlspecialchars($cat['description']) ?></p>
                        <span class="badge bg-light text-dark">
                            <?= $cat['article_count'] ?> Articles
                        </span>
                    </div>
                </a>
            </div>
            <?php 
            $delay += 100;
            endwhile; 
            ?>
        </div>
    </div>
</section>

<!-- Featured Articles -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-5" data-aos="fade-up">
            <div>
                <h2 class="section-title mb-0">Featured Articles</h2>
                <p class="text-muted">Most popular health articles</p>
            </div>
                        <a href="articles.php" class="btn btn-gradient">
                View All Articles <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php 
            $delay = 0;
            while($article = $featured->fetch_assoc()): 
            ?>
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
                <div class="article-card">
                    <div class="article-image">
                        <?php if($article['featured_image']): ?>
                        <img src="<?= UPLOADS_URL . $article['featured_image'] ?>" 
                             alt="<?= htmlspecialchars($article['title']) ?>">
                        <?php else: ?>
                        <img src="https://via.placeholder.com/400x220/667eea/ffffff?text=<?= urlencode($article['title']) ?>" 
                             alt="<?= htmlspecialchars($article['title']) ?>">
                        <?php endif; ?>
                        <span class="article-badge">
                            <i class="bi bi-tag-fill me-1"></i><?= $article['category_name'] ?>
                        </span>
                    </div>
                    <div class="article-content">
                        <h5 class="mb-3">
                            <a href="article_view.php?slug=<?= $article['slug'] ?>" 
                               class="text-decoration-none text-dark">
                                <?= htmlspecialchars($article['title']) ?>
                            </a>
                        </h5>
                        <p class="text-muted small mb-3">
                            <?= htmlspecialchars(substr($article['excerpt'], 0, 100)) ?>...
                        </p>
                        <div class="article-meta">
                            <div class="d-flex align-items-center flex-grow-1">
                                <div class="bg-gradient rounded-circle p-2 me-2" 
                                     style="width: 35px; height: 35px; background: var(--primary-gradient);">
                                    <i class="bi bi-person-fill text-white"></i>
                                </div>
                                <div>
                                    <small class="fw-bold d-block"><?= $article['author_name'] ?></small>
                                    <small class="text-muted"><?= $article['specialization'] ?: 'Doctor' ?></small>
                                </div>
                            </div>
                            <div class="text-end">
                                <small class="text-muted">
                                    <i class="bi bi-eye me-1"></i><?= $article['views'] ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
            $delay += 100;
            endwhile; 
            ?>
        </div>
    </div>
</section>

<!-- Video Library -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-5" data-aos="fade-up">
            <div>
                <h2 class="section-title mb-0">Video Library</h2>
                <p class="text-muted">Learn through expert video tutorials</p>
            </div>
            <a href="videos.php" class="btn btn-gradient">
                Browse All Videos <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php 
            $delay = 0;
            while($video = $videos->fetch_assoc()): 
                $thumbnail = $video['thumbnail_url'] ?: "https://img.youtube.com/vi/{$video['youtube_id']}/maxresdefault.jpg";
            ?>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
                <div class="video-card">
                    <div class="video-thumbnail">
                        <img src="<?= $thumbnail ?>" alt="<?= htmlspecialchars($video['title']) ?>">
                        <div class="play-button" data-bs-toggle="modal" 
                             data-bs-target="#videoModal<?= $video['id'] ?>">
                            <i class="bi bi-play-fill"></i>
                        </div>
                        <span class="position-absolute top-0 end-0 m-2">
                            <span class="badge bg-danger">
                                <i class="bi bi-youtube me-1"></i>Video
                            </span>
                        </span>
                    </div>
                    <div class="p-3">
                        <span class="badge bg-light text-danger mb-2">
                            <?= $video['category_name'] ?>
                        </span>
                        <h6 class="mb-2"><?= htmlspecialchars($video['title']) ?></h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i><?= $video['duration'] ?: '5:30' ?>
                            </small>
                            <small class="text-muted">
                                <i class="bi bi-eye me-1"></i><?= $video['views'] ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Video Modal -->
            <div class="modal fade" id="videoModal<?= $video['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                            <h5 class="modal-title"><?= htmlspecialchars($video['title']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="ratio ratio-16x9">
                                <iframe src="https://www.youtube.com/embed/<?= $video['youtube_id'] ?>?autoplay=1" 
                                        allowfullscreen allow="autoplay"></iframe>
                            </div>
                            <?php if($video['description']): ?>
                            <div class="p-4">
                                <h6>About this video</h6>
                                <p class="text-muted mb-0"><?= nl2br(htmlspecialchars($video['description'])) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
            $delay += 100;
            endwhile; 
            ?>
        </div>
    </div>
</section>

<!-- Health Tips -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="section-title">Daily Health Tips</h2>
            <p class="section-subtitle">Quick tips for better health and wellness</p>
        </div>

        <div class="row g-4">
            <?php 
            $delay = 0;
            $tip_colors = [
                'preventive' => '#10b981',
                'nutrition' => '#f59e0b',
                'exercise' => '#3b82f6',
                'mental_health' => '#8b5cf6',
                'general' => '#06b6d4'
            ];
            while($tip = $tips->fetch_assoc()): 
                $color = $tip_colors[$tip['tip_type']] ?? '#10b981';
            ?>
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
                <div class="tip-card" style="border-left-color: <?= $color ?>;">
                    <div class="tip-icon" style="background: linear-gradient(135deg, <?= $color ?>, <?= $color ?>dd);">
                        <i class="bi bi-lightbulb-fill"></i>
                    </div>
                    <span class="badge mb-2" style="background-color: <?= $color ?>;">
                        <?= ucwords(str_replace('_', ' ', $tip['tip_type'])) ?>
                    </span>
                    <h6 class="mb-2"><?= htmlspecialchars($tip['title']) ?></h6>
                    <p class="text-muted small mb-0"><?= htmlspecialchars($tip['content']) ?></p>
                </div>
            </div>
            <?php 
            $delay += 100;
            endwhile; 
            ?>
        </div>

        <div class="text-center mt-5" data-aos="fade-up">
            <a href="tips.php" class="btn btn-gradient btn-lg">
                View All Health Tips <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center" data-aos="zoom-in">
            <div class="col-lg-8">
                <div class="card border-0 shadow-lg" 
                     style="background: var(--primary-gradient); color: white; border-radius: 25px;">
                    <div class="card-body p-5">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="mb-3">Get Personalized Health Guidance</h3>
                                <p class="mb-0 opacity-75">
                                    Book an appointment with our specialist doctors for comprehensive health assessment 
                                    and personalized treatment plans.
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <a href="../public/patient_portal.php" class="btn btn-light btn-lg">
                                    <i class="bi bi-calendar-check me-2"></i>Book Appointment
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="card border-0 shadow" style="border-radius: 25px;">
                    <div class="card-body p-4 text-center">
                        <div class="mb-3">
                            <i class="bi bi-phone-fill" style="font-size: 3rem; color: #667eea;"></i>
                        </div>
                        <h5 class="mb-3">Download Our Mobile App</h5>
                        <p class="text-muted small mb-3">
                            Access health articles, book appointments, and view reports on the go.
                        </p>
                        <a href="../pwa/index.html" target="_blank" class="btn btn-outline-primary">
                            <i class="bi bi-download me-2"></i>Get App
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Subscription -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4" data-aos="fade-up">
                    <i class="bi bi-envelope-heart-fill float-animation" 
                       style="font-size: 4rem; color: #667eea;"></i>
                    <h2 class="section-title mt-3">Stay Informed</h2>
                    <p class="section-subtitle">Subscribe to receive weekly health tips and latest articles</p>
                </div>
                <form class="row g-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="col-md-8">
                        <input type="email" class="form-control form-control-lg" 
                               placeholder="Enter your email address" required 
                               style="border-radius: 50px; padding: 15px 25px;">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-gradient btn-lg w-100">
                            Subscribe <i class="bi bi-send ms-2"></i>
                        </button>
                    </div>
                </form>
                <p class="text-center text-muted small mt-3">
                    <i class="bi bi-shield-check me-1"></i>We respect your privacy. Unsubscribe anytime.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="py-5 bg-dark text-white">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h5 class="mb-3">
                    <i class="bi bi-hospital-fill me-2"></i>MediConnect360
                </h5>
                <p class="text-white-50 mb-3">
                    Your trusted partner in health education and medical care. Evidence-based information 
                    you can rely on.
                </p>
                <div class="d-flex gap-2">
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle" style="width: 40px; height: 40px;">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle" style="width: 40px; height: 40px;">
                        <i class="bi bi-twitter"></i>
                    </a>
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle" style="width: 40px; height: 40px;">
                        <i class="bi bi-youtube"></i>
                    </a>
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle" style="width: 40px; height: 40px;">
                        <i class="bi bi-linkedin"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-2 col-md-3">
                <h6 class="mb-3">Quick Links</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="articles.php" class="text-white-50 text-decoration-none">Articles</a></li>
                    <li class="mb-2"><a href="videos.php" class="text-white-50 text-decoration-none">Videos</a></li>
                    <li class="mb-2"><a href="tips.php" class="text-white-50 text-decoration-none">Health Tips</a></li>
                    <li class="mb-2"><a href="search.php" class="text-white-50 text-decoration-none">Search</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-3">
                <h6 class="mb-3">Categories</h6>
                <ul class="list-unstyled">
                    <?php 
                    $categories->data_seek(0);
                    $count = 0;
                    while($cat = $categories->fetch_assoc()): 
                        if($count >= 4) break;
                    ?>
                    <li class="mb-2">
                        <a href="articles.php?category=<?= $cat['slug'] ?>" 
                           class="text-white-50 text-decoration-none">
                            <?= $cat['name'] ?>
                        </a>
                    </li>
                    <?php 
                    $count++;
                    endwhile; 
                    ?>
                </ul>
            </div>
            <div class="col-lg-2 col-md-3">
                <h6 class="mb-3">Resources</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="../public/patient_portal.php" class="text-white-50 text-decoration-none">Patient Portal</a></li>
                    <li class="mb-2"><a href="../index.php" class="text-white-50 text-decoration-none">Hospital System</a></li>
                    <li class="mb-2"><a href="../pwa/index.html" class="text-white-50 text-decoration-none">Mobile App</a></li>
                    <li class="mb-2"><a href="../login.php" class="text-white-50 text-decoration-none">Staff Login</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-3">
                <h6 class="mb-3">Contact</h6>
                <ul class="list-unstyled text-white-50">
                    <li class="mb-2"><i class="bi bi-telephone me-2"></i>+91 98765 43210</li>
                    <li class="mb-2"><i class="bi bi-envelope me-2"></i>info@mediconnect360.com</li>
                    <li class="mb-2"><i class="bi bi-geo-alt me-2"></i>123 Hospital Road, City</li>
                </ul>
            </div>
        </div>
        <hr class="my-4 bg-white opacity-25">
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <small class="text-white-50">
                    &copy; <?= date('Y') ?> MediConnect360. All rights reserved.
                </small>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <small class="text-white-50">
                    <a href="#" class="text-white-50 text-decoration-none me-3">Privacy Policy</a>
                    <a href="#" class="text-white-50 text-decoration-none me-3">Terms of Service</a>
                    <a href="#" class="text-white-50 text-decoration-none">Disclaimer</a>
                </small>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTop" class="btn btn-gradient position-fixed bottom-0 end-0 m-4 rounded-circle" 
        style="width: 50px; height: 50px; display: none; z-index: 999;">
    <i class="bi bi-arrow-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    // Initialize AOS (Animate On Scroll)
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true,
        mirror: false
    });

    // Back to Top Button
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

    // Stop video when modal closes
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function () {
            const iframe = this.querySelector('iframe');
            if (iframe) {
                iframe.src = iframe.src;
            }
        });
    });

    // Search suggestions (optional enhancement)
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value;
            if (query.length >= 3) {
                // You can add AJAX call here for live search suggestions
                console.log('Searching for:', query);
            }
        });
    }

    // Newsletter form submission
    document.querySelector('form[class*="newsletter"]')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const email = this.querySelector('input[type="email"]').value;
        
        // Here you would normally send to your backend
        alert('Thank you for subscribing! You will receive our weekly health newsletter.');
        this.reset();
    });

    // View counter for articles (AJAX call)
    function incrementViewCount(articleId) {
        fetch(`increment_view.php?id=${articleId}`, { method: 'POST' })
            .catch(err => console.error('View count error:', err));
    }

    // Auto-hide login notice after 5 seconds
    const loginNotice = document.querySelector('.alert');
    if (loginNotice) {
        setTimeout(() => {
            loginNotice.style.transition = 'opacity 0.5s';
            loginNotice.style.opacity = '0';
            setTimeout(() => loginNotice.remove(), 500);
        }, 5000);
    }
</script>

</body>
</html>

