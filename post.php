<?php
require_once __DIR__ . '/includes/function.php';

$postId = $_GET['id'] ?? '';
if (empty($postId)) {
    header('Location: index.php');
    exit;
}

$post = getPostById($postId);
if (!$post) {
    header('Location: index.php');
    exit;
}

$postComments = getCommentsByPostId($postId);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_comment') {
        if (!isset($_SESSION['user_id'])) {
            $error = 'Только авторизованные пользователи могут оставлять комментарии';
        } else {
            $content = trim($_POST['content'] ?? '');

            if (empty($content)) {
                $error = 'Комментарий не может быть пустым';
            } elseif (strlen($content) < 3) {
                $error = 'Комментарий должен содержать минимум 3 символа';
            } else {
                $newComment = [
                    'id' => generateId('comment_'),
                    'post_id' => $postId,
                    'author_id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'content' => $content,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $allComments = loadData('comments.json');
                $allComments[] = $newComment;

                if (saveData('comments.json', $allComments)) {
                    header('Location: post.php?id=' . $postId);
                    exit;
                } else {
                    $error = 'Ошибка при сохранении комментария';
                }
            }
        }
    }
   
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name "viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?> | Мой блог</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin: 20px 0;
        }
        
        .gallery-item {
            flex: 0 0 calc(33.333% - 15px);
            max-width: calc(33.333% - 15px);
        }
        
        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease;
        }
        
        .gallery-item img:hover {
            transform: scale(1.05);
        }
        
        @media (max-width: 768px) {
            .gallery-item {
                flex: 0 0 calc(50% - 15px);
                max-width: calc(50% - 15px);
            }
        }
        
        @media (max-width: 480px) {
            .gallery-item {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Мой интернет-блог</h1>
            <nav class="nav">
                <a href="index.php">Главная</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="create.php">Создать пост</a>
                    <a href="logout.php">Выход (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
                <?php else: ?>
                    <a href="login.php">Войти</a>
                    <a href="register.php">Регистрация</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
        <article class="post-detail">
            <h2><?= htmlspecialchars($post['title']) ?></h2>
            
            <div class="post-meta">
                Автор: <?= htmlspecialchars($post['username'] ?? 'Неизвестен') ?>
                | <?= date('d.m.Y H:i', strtotime($post['created_at'])) ?>
            </div>
<?php if (!empty($post['media']) && is_array($post['media'])): ?>
                <div class="gallery">
                    <?php foreach ($post['media'] as $mediaPath): ?>
                        <div class="gallery-item">
                            <a href="<?= htmlspecialchars($mediaPath) ?>" target="_blank">
                                <img src="<?= htmlspecialchars($mediaPath) ?>" 
                                     alt="Изображение к записи"
                                     loading="lazy">
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="post-content">
                <?= nl2br(htmlspecialchars($post['content'])) ?>
            </div>
        </article>

        <section class="comments-section">
            <h3>Комментарии (<?= count($postComments) ?>)</h3>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST" action="" class="comment-form">
                    <input type="hidden" name="action" value="add_comment">
                    
                    <div class="form-group">
                        <label for="comment-content">Ваш комментарий:</label>
                        <textarea id="comment-content" name="content" required minlength="3" 
                                  placeholder="Введите ваш комментарий..."><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Добавить комментарий</button>
                </form>
            <?php else: ?>
                <p class="empty-state">
                    Только <a href="login.php">авторизованные пользователи</a> могут оставлять комментарии.
                </p>
            <?php endif; ?>

            <?php if (empty($postComments)): ?>
                <p class="empty-state">Пока нет комментариев. Будьте первым!</p>
            <?php else: ?>
                <div class="comments-list">
                    <?php foreach ($postComments as $comment): ?>
                        <div class="comment">
                            <div class="comment-author">
                                <?= htmlspecialchars($comment['username']) ?>
                            </div>
                            <div class="comment-date">
                                <?= date('d.m.Y в H:i', strtotime($comment['created_at'])) ?>
                            </div>
                            <div class="comment-content">
                                <?= nl2br(htmlspecialchars($comment['content'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <p>Мой блог © <?= date('Y') ?> – Практический проект на PHP</p>
        </div>
    </footer>
</body>
</html>