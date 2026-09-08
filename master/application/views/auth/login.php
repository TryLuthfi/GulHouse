<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($title); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/master/css/master.css'); ?>">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card">
            <div class="auth-brand">
                <span>GH</span>
                <div>
                    <strong>GUL HOUSE</strong>
                    <p>Web Master</p>
                </div>
            </div>
            <h1>Masuk Dashboard</h1>
            <p class="auth-lead">Kelola properti, kamar, dan booking dari satu panel operasional.</p>

            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert"><?= html_escape($this->session->flashdata('error')); ?></div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('login'); ?>" class="auth-form">
                <label>Username
                    <input type="text" name="username" autocomplete="username" required autofocus>
                </label>
                <label>Password
                    <input type="password" name="password" autocomplete="current-password" required>
                </label>
                <button type="submit">Login</button>
            </form>
        </section>
    </main>
</body>
</html>
