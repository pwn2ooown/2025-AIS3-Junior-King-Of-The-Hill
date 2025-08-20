<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>:: Vincent55' CV</title>
  <meta name="description" content="Last Update: 2025/6/3" />
  <link rel="stylesheet" href="style.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Source+Code+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
  <div id="root">
    <nav class="navigation">
      <div class="nav-container">
        <div class="nav-brand">
          <a href="/" class="brand-link">Vincent55</a>
          <span class="nav-divider">|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||</span>
        </div>
        <ul class="nav-links">
          <li><a href="?page=about">About</a></li>
          <li><a href="?page=blog">Blog</a></li>
          <li><a href="?page=contacts">Contacts</a></li>
          <li><a href="?page=cv">CV</a></li>
          <li><a href="filebrowser.php">Files</a></li>
        </ul>
      </div>
    </nav>

    <main class="main-content">
      <div class="container">
        <div class="content-divider">
          ········································································································································································································································································································································································································································································································································································································································································································································································
        </div>

        <div class="last-update">
          Last Update: 2025/8/11
        </div>
        <div class="hall-of-fame">
            <h1>Hall of Fame: Chumy Tsai</h1>
            <img src="https://blog.chummydns.com/images/me.png" alt="Chumy Tsai" />
        </div>
        <div class="content">
          <?php
          if (isset($_GET['page'])) {
              $page = $_GET['page'];
              if ($page !== 'index') {
                include($page . '.php');
              } else {
                include('about.php');
              }
          } else {
              include('about.php');
          }
          ?>
        </div>

        

        <footer class="footer">
          <p>© 2025 <a href="https://github.com/Vincent550102" target="_blank" rel="noopener">Vincent55</a> Powered by <a href="http://gohugo.io" target="_blank" rel="noopener">Hugo</a> and <a href="https://github.com/panr/hugo-theme-terminal.git" target="_blank" rel="noopener">Terminal</a></p>
        </footer>
      </div>
    </main>
  </div>
</body>

</html>
