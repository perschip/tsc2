<?php
// Include database connection and auth check
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Check if page ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = 'Invalid page ID';
    header('Location: list.php');
    exit;
}

$page_id = (int)$_GET['id'];

// Get page data
try {
    $query = "SELECT * FROM pages WHERE id = :id LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $page_id);
    $stmt->execute();
    $page = $stmt->fetch();
    
    if (!$page) {
        $_SESSION['error_message'] = 'Page not found';
        header('Location: list.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    header('Location: list.php');
    exit;
}

// Initialize variables
$errors = [];

// Make sure upload directory exists
$upload_dir = '../../uploads/pages/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data with null coalescing to handle undefined indexes
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $meta_description = trim($_POST['meta_description'] ?? '');
    $status = $_POST['status'] ?? 'published';
    $slug = isset($_POST['slug']) && !empty($_POST['slug']) ? createSlug($_POST['slug']) : createSlug($title);
    
    // Initialize the featured image variable
    $featured_image = $page['featured_image'] ?? ''; // Default to existing or empty if not set
    
    // Validate inputs
    if (empty($title)) {
        $errors[] = 'Page title is required';
    }
    
    if (empty($content)) {
        $errors[] = 'Page content is required';
    }
    
    if (empty($meta_description)) {
        // Generate meta description from content if empty
        $meta_description = generateExcerpt($content, 160);
    }
    
    // Check if a file was uploaded
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['featured_image']['name'];
        $file_tmp = $_FILES['featured_image']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Check if the file type is allowed
        if (in_array($file_ext, $allowed)) {
            // Generate unique filename
            $new_file_name = uniqid('page_') . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;
            
            // Move the file to the uploads directory
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $featured_image = '/uploads/pages/' . $new_file_name;
                
                // Delete the old image if it exists
                if (!empty($page['featured_image']) && file_exists('../../' . ltrim($page['featured_image'], '/'))) {
                    unlink('../../' . ltrim($page['featured_image'], '/'));
                }
            } else {
                $errors[] = 'Failed to upload image';
            }
        } else {
            $errors[] = 'Only JPG, JPEG, PNG, and GIF files are allowed';
        }
    }
    
    // Check for duplicate slug
    if ($slug !== $page['slug']) {
        $check_query = "SELECT COUNT(*) as count FROM pages WHERE slug = :slug AND id != :id";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->bindParam(':slug', $slug);
        $check_stmt->bindParam(':id', $page_id);
        $check_stmt->execute();
        $row = $check_stmt->fetch();
        
        if ($row['count'] > 0) {
            // Add unique identifier to make slug unique
            $slug = $slug . '-' . date('mdY');
        }
    }
    
    // Process menu options - checking for columns first
    $columnsExist = true;
    try {
        $checkQuery = "SELECT show_in_menu, show_in_footer FROM pages LIMIT 0";
        $pdo->query($checkQuery);
    } catch (PDOException $e) {
        $columnsExist = false;
        error_log('Menu columns do not exist in pages table: ' . $e->getMessage());
    }
    
    $show_in_menu = isset($_POST['show_in_menu']) ? 1 : 0;
    $show_in_footer = isset($_POST['show_in_footer']) ? 1 : 0;
    
    // If no errors, update the page
    if (empty($errors)) {
        try {
            // Build the SQL query based on column existence
            if ($columnsExist) {
                $query = "UPDATE pages 
                          SET title = :title, 
                              slug = :slug, 
                              content = :content, 
                              meta_description = :meta_description, 
                              featured_image = :featured_image,
                              status = :status,
                              show_in_menu = :show_in_menu,
                              show_in_footer = :show_in_footer,
                              updated_at = NOW()
                          WHERE id = :id";
            } else {
                $query = "UPDATE pages 
                          SET title = :title, 
                              slug = :slug, 
                              content = :content, 
                              meta_description = :meta_description, 
                              featured_image = :featured_image,
                              status = :status,
                              updated_at = NOW()
                          WHERE id = :id";
            }
            
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':slug', $slug);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':meta_description', $meta_description);
            $stmt->bindParam(':featured_image', $featured_image);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $page_id);
            
            if ($columnsExist) {
                $stmt->bindParam(':show_in_menu', $show_in_menu);
                $stmt->bindParam(':show_in_footer', $show_in_footer);
            }
            $stmt->execute();
            
            // Set success message
            $_SESSION['success_message'] = 'Page updated successfully!';
            
            // Redirect back to list
            header('Location: list.php');
            exit;
        } catch (PDOException $e) {
            // Database error
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Page variables
$page_title = 'Edit Page';
$use_tinymce = true;

// Header actions
$header_actions = '
<a href="list.php" class="btn btn-sm btn-outline-secondary">
    <i class="fas fa-arrow-left me-1"></i> Back to Pages
</a>
';

// Extra head content
$extra_head = '<style>
    #featured-image-preview {
        max-width: 100%;
        height: auto;
        max-height: 200px;
        border-radius: 0.25rem;
    }
</style>';

// Include admin header
include_once '../includes/header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-body">
        <form action="edit.php?id=<?php echo $page_id; ?>" method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <!-- Main Content Fields -->
                    <div class="mb-3">
                        <label for="content" class="form-label">Content *</label>
                        <textarea class="form-control editor" id="content" name="content" rows="15" required><?php echo htmlspecialchars($page['content']); ?></textarea>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- Sidebar Fields -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Publishing</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="published" <?php echo $page['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                                    <option value="draft" <?php echo $page['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                </select>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Page</button>
                                <a href="/<?php echo htmlspecialchars($page['slug']); ?>" class="btn btn-outline-secondary" target="_blank">
                                    <i class="fas fa-eye me-1"></i> View Page
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Featured Image</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($page['featured_image'])): ?>
                                <div class="mb-3 text-center">
                                    <img src="<?php echo htmlspecialchars($page['featured_image']); ?>" alt="Featured Image" id="featured-image-preview">
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image">
                                        <label class="form-check-label" for="remove_image">
                                            Remove current image
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="featured_image" class="form-label">Upload New Image</label>
                                <input class="form-control" type="file" id="featured_image" name="featured_image">
                                <div class="form-text">Recommended size: 1200x630 pixels</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">SEO Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="meta_description" class="form-label">Meta Description</label>
                                <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($page['meta_description']); ?></textarea>
                                <div class="form-text">If left empty, it will be generated from the content. Recommended length: 150-160 characters.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Page Options</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="show_in_menu" name="show_in_menu" 
                                       <?php echo (isset($page['show_in_menu']) && (int)$page['show_in_menu'] === 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="show_in_menu">
                                    Show in Navigation Menu
                                </label>
                                <div class="form-text">Display this page in the main navigation menu.</div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="show_in_footer" name="show_in_footer"
                                       <?php echo (isset($page['show_in_footer']) && (int)$page['show_in_footer'] === 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="show_in_footer">
                                    Show in Footer Menu
                                </label>
                                <div class="form-text">Display this page in the footer menu.</div>
                            </div>
                            
                            <div class="text-muted small">
                                <i class="fas fa-info-circle me-1"></i> Created: <?php echo isset($page['created_at']) ? date('F j, Y \a\t g:i A', strtotime($page['created_at'])) : 'Unknown'; ?>
                                <?php if (isset($page['updated_at']) && $page['updated_at']): ?>
                                <br>Last updated: <?php echo date('F j, Y \a\t g:i A', strtotime($page['updated_at'])); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php 
// Include admin footer
include_once '../includes/footer.php'; 
?>

<script>
// Image preview
document.getElementById('featured_image').addEventListener('change', function(event) {
    var preview = document.getElementById('featured-image-preview');
    var file = event.target.files[0];
    
    if (file) {
        var reader = new FileReader();
        
        reader.onload = function(e) {
            if (preview) {
                preview.src = e.target.result;
            } else {
                const newPreview = document.createElement('img');
                newPreview.src = e.target.result;
                newPreview.id = 'featured-image-preview';
                newPreview.alt = 'Featured Image';
                newPreview.classList.add('img-fluid');
                document.querySelector('#featured_image').parentNode.prepend(newPreview);
            }
        };
        
        reader.readAsDataURL(file);
    }
});

// Handle remove image checkbox
const removeImageCheckbox = document.getElementById('remove_image');
if (removeImageCheckbox) {
    removeImageCheckbox.addEventListener('change', function() {
        const preview = document.getElementById('featured-image-preview');
        const fileInput = document.getElementById('featured_image');
        
        if (this.checked && preview) {
            preview.style.opacity = '0.3';
            fileInput.disabled = true;
        } else {
            if (preview) preview.style.opacity = '1';
            fileInput.disabled = false;
        }
    });
}

// Slug generation from title
document.getElementById('title').addEventListener('blur', function() {
    const slugField = document.getElementById('slug');
    if (slugField.value === "") {
        // Create a simple slug from the title
        slugField.value = this.value
            .toLowerCase()
            .replace(/[^\w\s-]/g, "")
            .replace(/\s+/g, "-")
            .replace(/-+/g, "-")
            .trim();
    }
});
</script>
                        <label for="title" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($page['title']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($page['slug']); ?>">
                        <div class="form-text">Leave empty to generate from title. Current URL: <a href="/<?php echo htmlspecialchars($page['slug']); ?>" target="_blank">/<?php echo htmlspecialchars($page['slug']); ?></a></div>
                    </div>
                    
                    <div class="mb-3">