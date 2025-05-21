<?php
// Include database connection and helper functions
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get page slug from URL (default to friendly URL format)
$slug = trim(strtok($_SERVER["REQUEST_URI"], '?'), '/');

// If slug is empty, redirect to homepage
if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// Get the page data
try {
    $query = "SELECT * FROM pages WHERE slug = :slug AND status = 'published' LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':slug', $slug);
    $stmt->execute();
    $page = $stmt->fetch();
    
    if (!$page) {
        // Page not found, show 404 page
        header("HTTP/1.0 404 Not Found");
        include_once '404.php';
        exit;
    }
} catch (PDOException $e) {
    // Database error
    error_log('Database error: ' . $e->getMessage());
    header('Location: index.php');
    exit;
}

// Set page variables
$page_title = $page['title'];
$meta_description = $page['meta_description'];
$content = $page['content'];
$featured_image = $page['featured_image'];

// Include header
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section text-center">
    <div class="container">
        <h1><?php echo htmlspecialchars($page['title']); ?></h1>
        <?php if (!empty($page['meta_description'])): ?>
            <p class="lead"><?php echo htmlspecialchars(substr($page['meta_description'], 0, 160)); ?></p>
        <?php endif; ?>
    </div>
</section>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <?php if (!empty($page['featured_image'])): ?>
                        <div class="text-center mb-4">
                            <img src="<?php echo htmlspecialchars($page['featured_image']); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($page['title']); ?>">
                        </div>
                    <?php endif; ?>
                    
                    <div class="page-content">
                        <?php echo $page['content']; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>