<?php
// 啟用輸出緩衝以避免 header 錯誤
ob_start();

// 用戶物件類別
class User {
    public $username;
    public $isAdmin;
    
    public function __construct($username = 'Vincent55', $isAdmin = 0) {
        $this->username = $username;
        $this->isAdmin = $isAdmin;
    }
}

// 序列化和反序列化用戶物件的函數
function serializeUser($user) {
    return base64_encode(serialize($user));
}

function unserializeUser($data) {
    // 檢查資料是否為空或無效
    if (empty($data)) {
        return new User(); // 返回預設用戶
    }
    
    // 嘗試 base64 解碼，使用嚴格模式
    $decoded = @base64_decode($data, true);
    if ($decoded === false) {
        return new User(); // base64 解碼失敗，返回預設用戶
    }
    
    // 使用 @ 抑制錯誤並檢查結果
    $user = @unserialize($decoded);
    
    // 檢查反序列化是否成功且為 User 物件
    if ($user === false || !($user instanceof User)) {
        return new User(); // 反序列化失敗，返回預設用戶
    }
    
    // 檢查用戶物件是否有效（有用戶名）
    if (empty($user->username)) {
        return new User(); // 用戶名為空，返回預設用戶
    }
    
    return $user;
}

// 檢查 whoami cookie，預設創建 Vincent55 用戶但 isAdmin=0
if (!isset($_COOKIE['whoami'])) {
    $default_user = new User('Vincent55', 0);
    $cookie_data = serializeUser($default_user);
    setcookie('whoami', $cookie_data, time() + (86400 * 30), '/'); // 30 天
    $_COOKIE['whoami'] = $cookie_data;
}

// 處理身份設置請求
if (isset($_POST['set_identity']) && isset($_POST['username']) && isset($_POST['isAdmin'])) {
    $username = trim($_POST['username']);
    $isAdmin = intval($_POST['isAdmin']);
    
    // 檢查 username 不得為空
    if (empty($username)) {
        // 可以在這裡添加錯誤處理，暫時忽略空用戶名的請求
    } else {
        // 驗證 username 只能包含英文、數字、底線和點
        if (preg_match('/^[a-zA-Z0-9_.]+$/', $username)) {
            $user = new User($username, $isAdmin);
            $cookie_data = serializeUser($user);
            setcookie('whoami', $cookie_data, time() + (86400 * 30), '/');
            $_COOKIE['whoami'] = $cookie_data;
            header("Location: filebrowser.php");
            exit;
        }
    }
}

// 獲取當前用戶物件
$current_user_obj = unserializeUser($_COOKIE['whoami']);

// 定義一個標記來追蹤是否需要重設 cookie
$need_cookie_reset = false;

// 確保用戶物件有效，否則使用預設值
if (!($current_user_obj instanceof User) || empty($current_user_obj->username)) {
    $current_user_obj = new User('Vincent55', 0);
    $need_cookie_reset = true;
}

$current_user = $current_user_obj->username;
$is_admin = $current_user_obj->isAdmin;

// 檢查用戶名不得為空，如果為空則重設為預設值
if (empty($current_user)) {
    $current_user = 'Vincent55';
    $is_admin = 0;
    $need_cookie_reset = true;
}

// 如果是管理員，處理文件瀏覽器邏輯
if ($is_admin == 1) {
    // 安全的文件瀏覽器 - 只允許瀏覽當前目錄
    $allowed_directory = __DIR__; // 只允許瀏覽當前目錄
    $current_dir = $allowed_directory;
    $view_file = null;
    $upload_message = '';
    $upload_error = '';
    
    // 管理員密碼 SHA512 hash
    $admin_password_hash = '1329e47d9141ce1400f7fcd552b47935f91fd8fbb10edfc5ba917f2cf2e1e6335b5c49d49d95ae96f4e8e1c3a0381e43f42034850602141de851620409fcafb8';
    
    // 處理文件上傳
    if (isset($_POST['upload']) && isset($_POST['admin_password'])) {
        $provided_password = $_POST['admin_password'];
        $provided_hash = hash('sha512', $provided_password);
        
        // 驗證管理員密碼
        if ($provided_hash === $admin_password_hash) {
            if (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] === UPLOAD_ERR_OK) {
                $upload_file = $_FILES['upload_file'];
                $original_filename = trim(basename($upload_file['name']));
                
                // 檢查文件名不得為空
                if (empty($original_filename)) {
                    $upload_error = 'Error: Filename cannot be empty.';
                } else {
                    $file_ext = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
                    $file_size = $upload_file['size'];
                
                // 檢查文件大小 (1MB = 1048576 bytes)
                $max_file_size = 1048576; // 1MB
                if ($file_size > $max_file_size) {
                    $upload_error = 'Error: File size exceeds 1MB limit. Current size: ' . number_format($file_size) . ' bytes.';
                } else {
                    // 禁止 PHP 系列文件
                    $forbidden_extensions = ['php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'pht', 'phps'];
                    
                    if (in_array($file_ext, $forbidden_extensions)) {
                        $upload_error = 'Error: PHP files are not allowed for security reasons.';
                    } else {
                    // 驗證檔名只能包含英文、數字、底線和點
                    if (!preg_match('/^[a-zA-Z0-9_.]+$/', $original_filename)) {
                        $upload_error = 'Error: Filename can only contain English letters, numbers, underscores and dots.';
                    } else {
                         // 創建新的文件名：{username}{original filename}
                         $new_filename = $original_filename;
                         
                         // 確保 uploads 目錄存在
                         $uploads_dir = $allowed_directory . '/uploads';
                         if (!is_dir($uploads_dir)) {
                             if (!mkdir($uploads_dir, 0755, true)) {
                                 $upload_error = 'Error: Cannot create uploads directory. Please check permissions.';
                             } else {
                                 // 目錄創建成功，繼續處理文件上傳
                                 $target_file = $uploads_dir . '/' . $new_filename;
                                 
                                 // 移動上傳的文件 (直接覆蓋如果檔案已存在)
                                 if (move_uploaded_file($upload_file['tmp_name'], $target_file)) {
                                     $upload_message = 'File uploaded successfully!<br>Original: ' . htmlspecialchars($original_filename) . '<br>Saved as: ' . htmlspecialchars($new_filename);
                                     // 設置文件權限
                                     chmod($target_file, 0644);
                                 } else {
                                     $upload_error = 'Error: Failed to upload file.';
                                 }
                             }
                         } else {
                             // 目錄已存在，直接處理文件上傳
                             $target_file = $uploads_dir . '/' . $new_filename;
                             
                             // 移動上傳的文件 (直接覆蓋如果檔案已存在)
                             if (move_uploaded_file($upload_file['tmp_name'], $target_file)) {
                                 $upload_message = 'File uploaded successfully!<br>Original: ' . htmlspecialchars($original_filename) . '<br>Saved as: ' . htmlspecialchars($new_filename);
                                 // 設置文件權限
                                 chmod($target_file, 0644);
                             } else {
                                 $upload_error = 'Error: Failed to upload file.';
                             }
                         }
                    }
                }
                }
                }
            } else {
                $upload_error = 'Error: No file selected or upload error.';
            }
        } else {
            $upload_error = 'Error: Invalid admin password.';
        }
    }
    
    // 處理文件查看請求
    if (isset($_GET['view'])) {
        $requested_file = $_GET['view'];
        $file_path = $allowed_directory . '/' . $requested_file;
        
        // 檢查文件是否存在且在允許的目錄內
        if (file_exists($file_path) && strpos(realpath($file_path), $allowed_directory) === 0) {
            $view_file = $file_path;
        }
    }
    
    // 獲取目錄參數，但進行嚴格驗證
    if (isset($_GET['dir'])) {
        $requested_dir = $_GET['dir'];
        
        // 解析路徑並確保在允許的目錄內
        $full_path = realpath($allowed_directory . '/' . $requested_dir);
        
        // 檢查路徑是否在允許的目錄內
        if ($full_path && strpos($full_path, $allowed_directory) === 0) {
            $current_dir = $full_path;
        } else {
            $dir_error = 'Error: Cannot access requested directory.';
            $current_dir = $allowed_directory;
        }
    }
    
    // 顯示當前路徑
    $relative_path = str_replace($allowed_directory, '', $current_dir);
    if (empty($relative_path)) {
        $relative_path = '/';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>:: File Browser - Vincent55</title>
  
  <?php if ($need_cookie_reset): ?>
  <script>
    // 重設損壞的 cookie
    document.cookie = "whoami=<?php echo serializeUser(new User('Vincent55', 0)); ?>; expires=" + new Date(Date.now() + 30*24*60*60*1000).toUTCString() + "; path=/";
    // 重新載入頁面以應用新的 cookie
    if (window.location.search === '') {
      window.location.reload();
    }
  </script>
  <?php endif; ?>
  <link rel="stylesheet" href="style.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Source+Code+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .file-browser {
      background: transparent;
      padding: 0;
    }
    
    .breadcrumb {
      background: rgba(36, 35, 43, 0.3);
      padding: 12px 16px;
      margin-bottom: 20px;
      border: 1px solid #24232b;
      color: #cec6c8;
      font-size: 13px;
    }
    
    .breadcrumb strong {
      color: #8f512c;
    }
    
    .file-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .file-item {
      padding: 8px 12px;
      border-bottom: 1px dotted #595155;
      display: flex;
      align-items: center;
      transition: background-color 0.2s ease;
    }
    
    .file-item:hover {
      background: rgba(36, 35, 43, 0.3);
    }
    
    .file-icon {
      margin-right: 12px;
      font-size: 16px;
      width: 20px;
      text-align: center;
    }
    
    .file-name {
      flex: 1;
      font-size: 14px;
    }
    
    .file-name a {
      text-decoration: underline;
      color: #cec6c8;
      transition: color 0.2s ease;
    }
    
    .file-name a:hover {
      color: #8f512c;
    }
    
    .file-size {
      color: #7c7a84;
      font-size: 13px;
      min-width: 100px;
      text-align: right;
    }
    
    .error-message {
      background: rgba(143, 81, 44, 0.2);
      color: #cec6c8;
      padding: 12px 16px;
      margin-bottom: 20px;
      border: 1px solid #8f512c;
      border-radius: 2px;
    }

    .file-content {
      background: rgba(36, 35, 43, 0.3);
      border: 1px solid #24232b;
      padding: 20px;
      margin-top: 20px;
      font-family: 'JetBrains Mono', monospace;
      font-size: 13px;
      line-height: 1.5;
      white-space: pre-wrap;
      overflow-x: auto;
    }

    .file-actions {
      margin-bottom: 20px;
    }

    .file-actions a {
      color: #8f512c;
      text-decoration: underline;
      margin-right: 20px;
      font-size: 14px;
    }

    .file-actions a:hover {
      color: #a55d32;
    }

    .upload-section {
      background: rgba(36, 35, 43, 0.3);
      border: 1px solid #24232b;
      padding: 20px;
      margin-bottom: 20px;
      border-radius: 2px;
    }

    .upload-form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .form-group label {
      color: #8f512c;
      font-size: 14px;
      font-weight: 600;
    }

    .form-group input[type="password"],
    .form-group input[type="file"],
    .form-group select {
      background: rgba(36, 35, 43, 0.5);
      border: 1px solid #595155;
      padding: 8px 12px;
      color: #cec6c8;
      font-family: 'JetBrains Mono', monospace;
      font-size: 13px;
      border-radius: 2px;
    }

    .form-group input[type="password"]:focus,
    .form-group input[type="file"]:focus,
    .form-group select:focus {
      outline: none;
      border-color: #8f512c;
      background: rgba(36, 35, 43, 0.7);
    }

    .upload-btn {
      background: #8f512c;
      color: #ffffff;
      border: none;
      padding: 10px 20px;
      font-family: 'JetBrains Mono', monospace;
      font-size: 14px;
      cursor: pointer;
      border-radius: 2px;
      transition: background-color 0.2s ease;
      align-self: flex-start;
    }

    .upload-btn:hover {
      background: #a55d32;
    }

    .upload-btn:disabled {
      background: #595155;
      cursor: not-allowed;
    }

    .success-message {
      background: rgba(76, 175, 80, 0.2);
      color: #cec6c8;
      padding: 12px 16px;
      margin-bottom: 20px;
      border: 1px solid #4caf50;
      border-radius: 2px;
    }

    .warning-message {
      background: rgba(255, 193, 7, 0.2);
      color: #cec6c8;
      padding: 12px 16px;
      margin-bottom: 20px;
      border: 1px solid #ffc107;
      border-radius: 2px;
    }
  </style>
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
          <li><a href="index.php">Home</a></li>
          <li><a href="index.php?page=about">About</a></li>
          <li><a href="index.php?page=blog">Blog</a></li>
          <li><a href="index.php?page=contacts">Contacts</a></li>
          <li><a href="index.php?page=cv">CV</a></li>
          <li><a href="filebrowser.php">Files</a></li>
        </ul>
      </div>
    </nav>

    <main class="main-content">
      <div class="container">
        <div class="content-divider">
          ········································································································································································································································································································································································································································································································································································································································································································································································
        </div>

        <section class="section">
          <h2 class="section-title">File Browser</h2>
          
          <div class="file-browser">
            <?php
            // 只有管理員可以使用文件瀏覽器
            if ($is_admin != 1) {
                ?>
                <div class="error-message">
                    <strong>Hey 這是管理員專用的功能!</strong><br>
                    只有管理員可以使用文件瀏覽器。
                </div>
                
                <?php
            } else {
                // 顯示歡迎訊息和上傳結果訊息
                echo '<div class="success-message">歡迎管理員 ' . htmlspecialchars($current_user) . '! 你現在可以使用文件瀏覽器功能。</div>';
                
                if (!empty($upload_message)) {
                    echo '<div class="success-message">' . $upload_message . '</div>';
                }
                if (!empty($upload_error)) {
                    echo '<div class="error-message">' . $upload_error . '</div>';
                }
                if (isset($dir_error)) {
                    echo '<div class="error-message">' . $dir_error . '</div>';
                }
            


            
            // 如果正在查看文件，顯示文件內容
            if ($view_file) {
                $filename = basename($view_file);
                $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                ?>
                
                <div class="file-actions">
                    <a href="filebrowser.php">← Back to file list</a>
                    <a href="<?php echo htmlspecialchars(str_replace($allowed_directory . '/', '', $view_file)); ?>" target="_blank">Download</a>
                </div>
                
                <div class="breadcrumb">
                    <strong>Viewing file:</strong> <?php echo htmlspecialchars($filename); ?>
                </div>
                
                <?php
                // 檢查是否為可讀的文本文件
                $text_extensions = ['txt', 'php', 'html', 'css', 'js', 'json', 'xml', 'md', 'log'];
                
                if (in_array($file_ext, $text_extensions)) {
                    $content = file_get_contents($view_file);
                    if ($content !== false) {
                        echo '<div class="file-content">' . htmlspecialchars($content) . '</div>';
                    } else {
                        echo '<div class="error-message">Error: Cannot read file content.</div>';
                    }
                } elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    echo '<div class="file-content" style="text-align: center;">';
                    echo '<img src="' . htmlspecialchars(str_replace($allowed_directory . '/', '', $view_file)) . '" alt="' . htmlspecialchars($filename) . '" style="max-width: 100%; height: auto; border: 1px solid #8f512c;">';
                    echo '</div>';
                } else {
                    echo '<div class="error-message">File type not supported for preview. Use the download link above.</div>';
                }
            } else {
                // 顯示文件列表
                ?>
                
                <div class="breadcrumb">
                    <strong>Current location:</strong> <?php echo htmlspecialchars($relative_path); ?>
                </div>
                

                
                <?php
            }
            ?>
            <?php
            if (!$view_file) {
                // 只有在不查看文件時才顯示文件列表
                if (is_dir($current_dir)) {
                    $files = scandir($current_dir);
                    $files = array_diff($files, array('.', '..'));
                    
                    if (!empty($files)) {
                        echo '<ul class="file-list">';
                        
                        // 如果不在根目錄，顯示返回上級目錄的鏈接
                        if ($current_dir !== $allowed_directory) {
                            $parent_dir = dirname(str_replace($allowed_directory, '', $current_dir));
                            if ($parent_dir === '/') $parent_dir = '';
                            echo '<li class="file-item">';
                            echo '<span class="file-icon">📁</span>';
                            echo '<span class="file-name"><a href="?dir=' . urlencode($parent_dir) . '">.. (parent directory)</a></span>';
                            echo '<span class="file-size">directory</span>';
                            echo '</li>';
                        }
                        
                        // 分別處理目錄和文件
                        $directories = array();
                        $regular_files = array();
                        
                        foreach ($files as $file) {
                            if ($file[0] !== '.') { // 隱藏以點開頭的文件
                                $file_path = $current_dir . '/' . $file;
                                if (is_dir($file_path)) {
                                    $directories[] = $file;
                                } else {
                                    $regular_files[] = $file;
                                }
                            }
                        }
                        
                        // 先顯示目錄
                        sort($directories);
                        foreach ($directories as $dir) {
                            $dir_path = str_replace($allowed_directory, '', $current_dir . '/' . $dir);
                            echo '<li class="file-item">';
                            echo '<span class="file-icon">📁</span>';
                            echo '<span class="file-name"><a href="?dir=' . urlencode($dir_path) . '">' . htmlspecialchars($dir) . '</a></span>';
                            echo '<span class="file-size">directory</span>';
                            echo '</li>';
                        }
                        
                        // 再顯示文件
                        sort($regular_files);
                        foreach ($regular_files as $file) {
                            $file_path = $current_dir . '/' . $file;
                            $file_size = filesize($file_path);
                            $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            
                            // 根據文件類型選擇圖標
                            $icon = '📄';
                            switch ($file_ext) {
                                case 'php':
                                    $icon = '🐘';
                                    break;
                                case 'css':
                                    $icon = '🎨';
                                    break;
                                case 'html':
                                case 'htm':
                                    $icon = '🌐';
                                    break;
                                case 'js':
                                    $icon = '⚡';
                                    break;
                                case 'txt':
                                    $icon = '📝';
                                    break;
                                case 'jpg':
                                case 'jpeg':
                                case 'png':
                                case 'gif':
                                    $icon = '🖼️';
                                    break;
                            }
                            
                            // 計算相對路徑用於查看文件
                            $relative_file_path = str_replace($allowed_directory . '/', '', $file_path);
                            
                            echo '<li class="file-item">';
                            echo '<span class="file-icon">' . $icon . '</span>';
                            echo '<span class="file-name"><a href="?view=' . urlencode($relative_file_path) . '">' . htmlspecialchars($file) . '</a></span>';
                            echo '<span class="file-size">' . number_format($file_size) . ' bytes</span>';
                            echo '</li>';
                        }
                        
                        echo '</ul>';
                    } else {
                        echo '<p style="color: #7c7a84;">This directory is empty.</p>';
                    }
                } else {
                    echo '<div class="error-message">Error: Cannot read directory.</div>';
                }
                
                // 在文件列表後面顯示上傳表單
                ?>
                
                <!-- 上傳表單 -->
                <div class="upload-section" style="margin-top: 40px;">
                    <h3 style="color: #8f512c; margin-bottom: 15px; font-size: 16px;">Upload Files to /uploads</h3>
                    <div class="warning-message">
                        <strong>Upload Restrictions:</strong><br>
                        • File size must be less than 1MB<br>
                    </div>
                    <form method="post" enctype="multipart/form-data" class="upload-form">
                        <div class="form-group">
                            <label for="admin_password">Admin Password:</label>
                            <input type="password" id="admin_password" name="admin_password" required>
                        </div>
                        <div class="form-group">
                            <label for="upload_file">Select File (No PHP files):</label>
                            <input type="file" id="upload_file" name="upload_file" required>
                        </div>
                        <button type="submit" name="upload" class="upload-btn">Upload File</button>
                    </form>
                </div>
                
                <?php
            } // 結束 vincent 身份的文件瀏覽器功能
            } // 結束整個身份檢查
            ?>
        </div>
        </section>

        <footer class="footer">
          <p>© 2025 <a href="https://github.com/Vincent550102" target="_blank" rel="noopener">Vincent55</a> Powered by <a href="http://gohugo.io" target="_blank" rel="noopener">Hugo</a> and <a href="https://github.com/panr/hugo-theme-terminal.git" target="_blank" rel="noopener">Terminal</a></p>
        </footer>
      </div>
    </main>
  </div>
</body>
</html>