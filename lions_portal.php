<?php
// lions_portal.php — एकल फाइल में रजिस्ट्रेशन, लॉगिन, Live TV और सुरक्षा

session_start();
// HTTPS अनिवार्य
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS']!=='on') {
    header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    exit;
}
session_regenerate_id(true);

// PDO से DB कनेक्शन
$dsn = 'mysql:host=localhost;dbname=news_app;charset=utf8mb4';
$dbUser = 'root'; $dbPass = '';
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    die('DB Error: '.$e->getMessage());
}

// तालिका बनाएँ (यदि नहीं हैं)
$pdo->exec("
CREATE TABLE IF NOT EXISTS lions_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  club_id VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  // रजिस्टर
  if (isset($_POST['action']) && $_POST['action']==='register') {
    $n=trim($_POST['name']); 
    $e=filter_var($_POST['email'],FILTER_VALIDATE_EMAIL);
    $p=$_POST['password'];
    if ($n && $e && strlen($p)>=6) {
      $h=password_hash($p,PASSWORD_BCRYPT);
      $stmt=$pdo->prepare("INSERT INTO lions_members(name,email,password,club_id) VALUES(?,?,?,?)");
      $ok=$stmt->execute([$n,$e,$h,trim($_POST['club_id'])]);
      $msg = $ok ? 'सदस्यता सफल—अब लॉगिन करें।' : 'ईमेल पहले से प्रयोग में।';
    } else $msg='सभी फ़ील्ड सही भरें, पासवर्ड 6+ अक्षर।';
  }
  // लॉगिन
  if (isset($_POST['action']) && $_POST['action']==='login') {
    $e=filter_var($_POST['email'],FILTER_VALIDATE_EMAIL);
    $p=$_POST['password'];
    if ($e && $p) {
      $stmt=$pdo->prepare("SELECT * FROM lions_members WHERE email=?");
      $stmt->execute([$e]); $u=$stmt->fetch(PDO::FETCH_ASSOC);
      if ($u && password_verify($p,$u['password'])) {
        $_SESSION['member_id']=$u['id'];
        $_SESSION['member_name']=$u['name'];
        header('Location: lions_portal.php'); exit;
      } else $msg='गलत ईमेल या पासवर्ड।';
    }
  }
  // logout
  if (isset($_POST['action']) && $_POST['action']==='logout') {
    session_unset(); session_destroy();
    header('Location: lions_portal.php'); exit;
  }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Lions Member Portal</title>
  <style>
    body {font-family:sans-serif;background:#f0f0f0;margin:0;padding:20px;}
    .box{max-width:500px;margin:auto;background:#fff;padding:20px;border-radius:8px;}
    input,button{width:100%;padding:10px;margin:5px 0;}
    .msg{background:#e0ffe0;padding:10px;border-radius:4px;margin-bottom:10px;}
    .tv{margin-top:20px;text-align:center;}
    .tv video{width:100%;border-radius:8px;}
  </style>
</head>
<body>
  <div class="box">
    <?php if($msg): ?><div class="msg"><?=htmlspecialchars($msg)?></div><?php endif; ?>

    <?php if(empty($_SESSION['member_id'])): ?>
      <h2>नया सदस्य पंजीकरण</h2>
      <form method="post">
        <input type="hidden" name="action" value="register">
        <input name="name"     placeholder="पूरा नाम" required>
        <input name="email"    placeholder="ईमेल" type="email" required>
        <input name="club_id"  placeholder="Lions Club ID">
        <input name="password" placeholder="पासवर्ड (6+ अक्षर)" type="password" required>
        <button type="submit">रजिस्टर करें</button>
      </form>
      <hr>
      <h2>सदस्य लॉगिन</h2>
      <form method="post">
        <input type="hidden" name="action" value="login">
        <input name="email"    placeholder="ईमेल" type="email" required>
        <input name="password" placeholder="पासवर्ड" type="password" required>
        <button type="submit">लॉगिन करें</button>
      </form>
    <?php else: ?>
      <h2>स्वागत है, <?=htmlspecialchars($_SESSION['member_name'])?>!</h2>
      <form method="post">
        <input type="hidden" name="action" value="logout">
        <button type="submit">लॉगआउट</button>
      </form>
      <div class="tv">
        <h3>🎥 Lions Club Live TV</h3>
        <video controls autoplay>
          <source src="https://stream.lionsclub.org/live/stream.m3u8" type="application/x-mpegURL">
        </video>
      </div>
      <p style="margin-top:20px;">
        इस पोर्टल पर आप:
        <ul>
          <li>Lions Club इवेंट्स देख सकते हैं</li>
          <li>सदस्य-संवर्धित कंटेंट एक्सेस कर सकते हैं</li>
          <li>सुरक्षित रूप से लॉगिन/लॉगआउट कर सकते हैं</li>
        </ul>
      </p>
    <?php endif; ?>
  </div>
</body>
</html>
