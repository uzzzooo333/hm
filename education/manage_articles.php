<?php
require_once '../config.php';
require_role(['admin', 'doctor']);
require_once '../includes/header.php';

// Handle article creation/update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_article'])) {
    $title = sanitize($_POST['title']);
    $slug = sanitize($_POST['slug']) ?: generateSlug($title);
    $category_id = (int)$_POST['category_id'];
    $content = $_POST['content']; // Rich text content
    $excerpt = sanitize($_POST['excerpt']);
    $tags = sanitize($_POST['tags']);
    $status = sanitize($_POST['status']);
    $author_id = $_SESSION['user_id'];
    
    // Handle image upload
    $featured_image = null;
    if(isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
        $upload_dir = UPLOADS_PATH . 'articles/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $file_ext = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
        $filename = 'article_' . time() . '_' . uniqid() . '.' . $file_ext;
        $filepath = $upload_dir . $filename;
        
        if(move_uploaded_file($_FILES['featured_image']['tmp_name'], $filepath)) {
            $featured_image = 'uploads/articles/' . $filename;
        }
    }
    
    if(isset($_POST['article_id']) && $_POST['article_id'] > 0) {
        // Update existing
        $article_id = (int)$_POST['article_id'];
        $image_sql = $featured_image ? ", featured_image = '$featured_image'" : "";
        
        $conn->query("UPDATE health_articles SET 
            title = '$title', 
            slug = '$slug',
            category_id = $category_id,
            content = '$content',
            excerpt = '$excerpt',
            tags = '$tags',
            status = '$status'
            $image_sql,
            published_at = " . ($status == 'published' ? 'NOW()' : 'NULL') . "
            WHERE id = $article_id
        ");
        
        logActivity($_SESSION['user_id'], 'update_article', "Updated article: $title");
        redirect('manage_articles.php', 'Article updated successfully!', 'success');
    } else {
        // Insert new
        $conn->query("INSERT INTO health_articles 
            (title, slug, category_id, author_id, content, excerpt, tags, featured_image, status, published_at)
            VALUES 
            ('$title', '$slug', $category_id, $author_id, '$content', '$excerpt', '$tags', '$featured_image', '$status', " . 
            ($status == 'published' ? 'NOW()' : 'NULL') . ")
        ");
        
        logActivity($_SESSION['user_id'], 'create_article', "Created article: $title");
        redirect('manage_articles.php', 'Article created successfully!', 'success');
    }
}

// Fetch all articles
$articles = $conn->query("
    SELECT ha.*, hc.name as category_name, u.name as author_name
    FROM health_articles ha
    JOIN health_categories hc ON ha.category_id = hc.id
    JOIN users u ON ha.author_id = u.id
    ORDER BY ha.created_at DESC
");

$categories = $conn->query("SELECT * FROM health_categories ORDER BY name");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-file-text"></i> Manage Articles</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#articleModal">
            <i class="bi bi-plus-lg"></i> New Article
        </button>
    </div>

    <!-- Articles Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="articlesTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Published</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($article = $articles->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($article['title']) ?></strong>
                                <br><small class="text-muted"><?= $article['slug'] ?></small>
                            </td>
                            <td><span class="badge bg-primary"><?= $article['category_name'] ?></span></td>
                            <td><?= $article['author_name'] ?></td>
                            <td>
                                <span class="badge bg-<?= $article['status'] == 'published' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($article['status']) ?>
                                </span>
                            </td>
                            <td><?= $article['views'] ?></td>
                            <td><?= $article['published_at'] ? date('M j, Y', strtotime($article['published_at'])) : '-' ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary edit-article" 
                                        data-id="<?= $article['id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="article_view.php?slug=<?= $article['slug'] ?>" 
                                   class="btn btn-sm btn-outline-info" target="_blank">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-danger delete-article" 
                                        data-id="<?= $article['id'] ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Article Modal with TinyMCE -->
<div class="modal fade" id="articleModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data" id="articleForm">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-file-text"></i> Create/Edit Article</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="article_id" id="article_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Article Title</label>
                            <input type="text" name="title" id="title" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">URL Slug</label>
                            <input type="text" name="slug" id="slug" class="form-control" 
                                   placeholder="auto-generated">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Category</label>
                            <select name="category_id" class="form-select" required>
                                <?php 
                                $categories->data_seek(0);
                                while($cat = $categories->fetch_assoc()): 
                                ?>
                                <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Featured Image</label>
                        <input type="file" name="featured_image" class="form-control" 
                               accept="image/*">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Excerpt (Brief Summary)</label>
                        <textarea name="excerpt" class="form-control" rows="2" 
                                  placeholder="Short description for previews..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Article Content</label>
                        <textarea name="content" id="articleContent" rows="15"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tags (comma separated)</label>
                        <input type="text" name="tags" class="form-control" 
                               placeholder="diabetes, prevention, nutrition">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_article" class="btn btn-success">
                        <i class="bi bi-save"></i> Save Article
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.tiny.cloud/1/YOUR_API_KEY/tinymce/6/tinymce.min.js"></script>
<script>
// Initialize TinyMCE Rich Text Editor
tinymce.init({
    selector: '#articleContent',
    height: 500,
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px }',
    images_upload_url: 'upload_image.php',
    automatic_uploads: true,
    file_picker_types: 'image',
    paste_data_images: true
});

// Auto-generate slug from title
document.getElementById('title').addEventListener('input', function() {
    const slug = this.value.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
    document.getElementById('slug').value = slug;
});

// DataTable initialization
$(document).ready(function() {
    $('#articlesTable').DataTable({
        order: [[5, 'desc']]
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
