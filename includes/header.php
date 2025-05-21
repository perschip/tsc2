<?php
// Get site settings
$site_name = getSetting('site_name', 'Tristate Cards');
$site_description = getSetting('site_description', 'Your trusted source for sports cards, collectibles, and memorabilia');
$meta_keywords = getSetting('meta_keywords', 'sports cards, trading cards, collectibles, memorabilia, card breaks, eBay listings, Whatnot');

// Check if this is a blog page for navigation highlighting
$is_blog_page = strpos($_SERVER['PHP_SELF'], 'blog') !== false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-LBD5R9TRZ7"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-LBD5R9TRZ7');
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo htmlspecialchars($site_name); ?></title>
    
    <!-- Meta tags -->
    <meta name="description" content="<?php echo isset($meta_description) ? htmlspecialchars($meta_description) : htmlspecialchars($site_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords); ?>">
    
    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?><?php echo htmlspecialchars($site_name); ?>">
    <meta property="og:description" content="<?php echo isset($meta_description) ? htmlspecialchars($meta_description) : htmlspecialchars($site_description); ?>">
    <meta property="og:type" content="<?php echo strpos($_SERVER['PHP_SELF'], 'blog-post.php') !== false ? 'article' : 'website'; ?>">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
    <?php if (isset($post) && !empty($post['featured_image'])): ?>
        <meta property="og:image" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . htmlspecialchars($post['featured_image']); ?>">
    <?php else: ?>
        <meta property="og:image" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]"; ?>/assets/images/og-image.jpg">
    <?php endif; ?>
    
    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?><?php echo htmlspecialchars($site_name); ?>">
    <meta name="twitter:description" content="<?php echo isset($meta_description) ? htmlspecialchars($meta_description) : htmlspecialchars($site_description); ?>">
    <?php if (isset($post) && !empty($post['featured_image'])): ?>
        <meta name="twitter:image" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . htmlspecialchars($post['featured_image']); ?>">
    <?php else: ?>
        <meta name="twitter:image" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]"; ?>/assets/images/og-image.jpg">
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png">
    
    <!-- CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    
    <?php if (isset($extra_css) && !empty($extra_css)): ?>
    <!-- Page Specific CSS -->
    <link href="<?php echo $extra_css; ?>" rel="stylesheet">
    <?php endif; ?>
    
    <!-- Google Analytics -->
    <?php if ($ga_id = getSetting('google_analytics_id')): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($ga_id); ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?php echo htmlspecialchars($ga_id); ?>');
    </script>
    <?php endif; ?>
    
    <?php if (isset($extra_head)): ?>
    <!-- Extra head content -->
    <?php echo $extra_head; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <?php echo htmlspecialchars($site_name); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $is_blog_page ? 'active' : ''; ?>" href="blog.php">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'about.php' ? 'active' : ''; ?>" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'contact.php' ? 'active' : ''; ?>" href="contact.php">Contact</a>
                    </li>
                    <?php if ($whatnot_username = getSetting('whatnot_username')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="https://www.whatnot.com/user/<?php echo htmlspecialchars($whatnot_username); ?>" target="_blank">
                            <i class="fas fa-video me-1"></i> Whatnot
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <?php if (isset($is_blog_post) && $is_blog_post): ?>
    <!-- Blog Post Breadcrumb Navigation -->
    <div class="blog-breadcrumb bg-light py-2">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="blog.php">Blog</a></li>
                    <?php if (isset($post_categories) && !empty($post_categories)): ?>
                        <li class="breadcrumb-item"><a href="blog.php?category=<?php echo htmlspecialchars($post_categories[0]['slug']); ?>"><?php echo htmlspecialchars($post_categories[0]['name']); ?></a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo isset($post) ? htmlspecialchars(substr($post['title'], 0, 30) . (strlen($post['title']) > 30 ? '...' : '')) : ''; ?></li>
                </ol>
            </nav>
        </div>
    </div>
    <?php endif; ?>