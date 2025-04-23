<?php
session_start();
// Database Connection
$DB_HOST = 'localhost';
$DB_NAME = 'ecnews';
$DB_USER = 'dbuser';
$DB_PASS = 'dbpass';
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Create tables if not exists
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    reward_points INT DEFAULT 0
)");
$conn->query("CREATE TABLE IF NOT EXISTS blogs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    views INT DEFAULT 0,
    FOREIGN KEY(user_id) REFERENCES users(id)
)");
$conn->query("CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blog_id INT NOT NULL,
    user_id INT NOT NULL,
    UNIQUE(blog_id, user_id),
    FOREIGN KEY(blog_id) REFERENCES blogs(id),
    FOREIGN KEY(user_id) REFERENCES users(id)
)");
$conn->query("CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blog_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(blog_id) REFERENCES blogs(id),
    FOREIGN KEY(user_id) REFERENCES users(id)
)");
$conn->query("CREATE TABLE IF NOT EXISTS follows (
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    UNIQUE(follower_id, following_id),
    FOREIGN KEY(follower_id) REFERENCES users(id),
    FOREIGN KEY(following_id) REFERENCES users(id)
)");

// Helper: redirect
define('BASE_URL','');
function redirect($url) {
    header('Location: ' . BASE_URL . $url);
    exit;
}

// Registration
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='register') {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (username,email,password) VALUES (?,?,?)");
    $stmt->bind_param('sss',$username,$email,$password);
    if ($stmt->execute()) {
        $_SESSION['user_id'] = $stmt->insert_id;
        redirect('');
    } else {
        $error = 'Registration failed. Username or email may be taken.';
    }
}

// Login
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='login') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT id,password FROM users WHERE username=?");
    $stmt->bind_param('s',$username);
    $stmt->execute();
    $stmt->bind_result($uid,$hash);
    if ($stmt->fetch() && password_verify($password,$hash)) {
        $_SESSION['user_id'] = $uid;
        redirect('');
    } else {
        $error = 'Invalid credentials.';
    }
}

// Logout
if (isset($_GET['action']) && $_GET['action']==='logout') {
    session_destroy();
    redirect('');
}

// Ensure user logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT id,username,reward_points FROM users WHERE id=?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}

// Publish Blog
if ($user && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='publish') {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $stmt = $conn->prepare("INSERT INTO blogs (user_id,title,content) VALUES (?,?,?)");
    $stmt->bind_param('iss',$user['id'],$title,$content);
    $stmt->execute();
    // reward points
    $conn->query("UPDATE users SET reward_points = reward_points + 10 WHERE id={$user['id']}");
    redirect('');
}

// Like Blog
if ($user && isset($_GET['like'])) {
    $blog_id = (int)$_GET['like'];
    $stmt = $conn->prepare("INSERT IGNORE INTO likes (blog_id,user_id) VALUES (?,?)");
    $stmt->bind_param('ii',$blog_id,$user['id']);
    $stmt->execute();
    redirect('');
}

// Comment on Blog
if ($user && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='comment') {
    $blog_id = (int)$_POST['blog_id'];
    $comment = $conn->real_escape_string($_POST['comment']);
    $stmt = $conn->prepare("INSERT INTO comments (blog_id,user_id,comment) VALUES (?,?,?)");
    $stmt->bind_param('iis',$blog_id,$user['id'],$comment);
    $stmt->execute();
    $conn->query("UPDATE users SET reward_points = reward_points + 2 WHERE id={$user['id']}");
    redirect('');
}

// Follow User
if ($user && isset($_GET['follow'])) {
    $followId = (int)$_GET['follow'];
    $stmt = $conn->prepare("INSERT IGNORE INTO follows (follower_id,following_id) VALUES (?,?)");
    $stmt->bind_param('ii',$user['id'],$followId);
    $stmt->execute();
    redirect('');
}

// Fetch Blogs
$blogs = $conn->query("SELECT b.*, u.username, 
    (SELECT COUNT(*) FROM likes l WHERE l.blog_id=b.id) AS like_count,
    (SELECT COUNT(*) FROM comments c WHERE c.blog_id=b.id) AS comment_count
  FROM blogs b JOIN users u ON b.user_id=u.id ORDER BY b.created_at DESC");

// International News Feed (BBC RSS)
$feed = @simplexml_load_file('http://feeds.bbci.co.uk/news/world/rss.xml');
$news_items = [];
if ($feed) {
    foreach (array_slice($feed->channel->item,0,5) as $item) {
        $news_items[] = ['title'=>(string)$item->title,'link'=>(string)$item->link];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EC News App</title>
  <script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'GA_MEASUREMENT_ID', { 'user_id': '<?php echo $user['id'] ?? ''; ?>' });
  </script>
  <script>
    var _paq = window._paq || [];
    _paq.push(['trackPageView']);
    _paq.push(['enableLinkTracking']);
    _paq.push(['setUserId','<?php echo $user['id'] ?? ''; ?>']);
    (function() {
      var u="//your-matomo-domain.com/";
      _paq.push(['setTrackerUrl', u+'matomo.php']);
      _paq.push(['setSiteId', '1']);
      var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
      g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
    })();
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.1.0/cookieconsent.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.1.0/cookieconsent.min.css" />
  <style>
    body { font-family: Arial, sans-serif; margin:20px; }
    header, section { margin-bottom:30px; }
    form { margin-bottom:15px; }
    button { padding:8px 12px; }
    .blog { border:1px solid #ddd; padding:15px; margin-bottom:20px; }
    .blog h3 { margin-top:0; }
    .metrics { font-size:0.9em; color:#555; }
  </style>
</head>
<body>
  <header>
    <h1>EC News App</h1>
    <?php if($user): ?>
      <p>Welcome, <?php echo htmlspecialchars($user['username']); ?> | <a href="?action=logout">Logout</a></p>
      <p>Reward Points: <?php echo $user['reward_points']; ?></p>
    <?php else: ?>
      <h2>Login / Register</h2>
      <form method="POST"><input type="hidden" name="action" value="login"><input name="username" placeholder="Username" required><input type="password" name="password" placeholder="Password" required><button type="submit">Login</button></form>
      <form method="POST"><input type="hidden" name="action" value="register"><input name="username" placeholder="Username" required><input name="email" type="email" placeholder="Email" required><input type="password" name="password" placeholder="Password" required><button type="submit">Register</button></form>
      <?php if(isset($error)) echo '<p style="color:red;">'.htmlspecialchars($error).'</p>'; ?>
    <?php endif; ?>
  </header>

  <?php if($user): ?>
  <section id="write-blog">
    <h2>Write a Blog</h2>
    <form method="POST"><input type="hidden" name="action" value="publish"><input name
