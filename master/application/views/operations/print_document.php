<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($title); ?></title>
    <style>
        body { margin: 0; color: #111827; background: #f5f6fa; font-family: Arial, sans-serif; }
        main { width: min(820px, calc(100% - 32px)); margin: 24px auto; padding: 44px; background: #fff; border: 1px solid #e5e7eb; }
        h1 { margin: 0 0 28px; font-size: 24px; text-align: center; }
        pre { white-space: pre-wrap; font: inherit; line-height: 1.7; }
        .actions { display: flex; justify-content: flex-end; gap: 10px; margin: 0 auto 16px; width: min(820px, calc(100% - 32px)); }
        button, a { padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; color: #111827; text-decoration: none; cursor: pointer; }
        @media print { body { background: #fff; } .actions { display: none; } main { width: auto; margin: 0; padding: 0; border: 0; } }
    </style>
</head>
<body>
    <div class="actions">
        <a href="javascript:history.back()">Kembali</a>
        <button type="button" onclick="window.print()">Print / PDF</button>
    </div>
    <main>
        <h1><?= html_escape($heading); ?></h1>
        <pre><?= html_escape($body); ?></pre>
    </main>
</body>
</html>
