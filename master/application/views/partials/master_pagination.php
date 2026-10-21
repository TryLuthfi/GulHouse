<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if (!empty($pagination_controls)): ?>
    <label>Baris per halaman
        <select name="per_page">
            <?php foreach (array(10,25,50,100) as $size): ?><option value="<?= $size; ?>" <?= (int)$pagination['per_page']===$size ? 'selected' : ''; ?>><?= $size; ?></option><?php endforeach; ?>
        </select>
    </label>
    <label>Urutkan
        <select name="sort">
            <?php foreach ($allowed_sorts as $key=>$label): ?><option value="<?= html_escape((string)$key); ?>" <?= (string)$pagination['sort']===(string)$key ? 'selected' : ''; ?>><?= html_escape((string)$label); ?></option><?php endforeach; ?>
        </select>
    </label>
    <label>Arah urutan
        <select name="dir"><option value="asc" <?= $pagination['dir']==='asc' ? 'selected' : ''; ?>>Naik</option><option value="desc" <?= $pagination['dir']==='desc' ? 'selected' : ''; ?>>Turun</option></select>
    </label>
<?php else: ?>
    <?php
    $pagerParams=array_merge($filters,array('per_page'=>$pagination['per_page'],'sort'=>$pagination['sort'],'dir'=>$pagination['dir']));
    $pageLink=static function($page) use($master_route,$pagerParams) { return base_url($master_route.'?'.http_build_query(array_merge($pagerParams,array('page'=>$page)))); };
    $currentPage=(int)$pagination['page'];$lastPage=(int)$pagination['pages'];
    $visiblePages=array_unique(array_merge(array(1),range(max(1,$currentPage-2),min($lastPage,$currentPage+2)),array($lastPage)));
    ?>
    <div class="master-pagination">
        <p class="master-page-summary" aria-live="polite">Menampilkan <strong><?= (int)$pagination['from']; ?>–<?= (int)$pagination['to']; ?></strong> dari <strong><?= number_format((int)$pagination['total'],0,',','.'); ?></strong> hasil</p>
        <nav class="master-page-links" aria-label="Halaman tabel">
            <?php if ($currentPage>1): ?><a href="<?= html_escape($pageLink($currentPage-1)); ?>" rel="prev" aria-label="Halaman sebelumnya">‹</a><?php else: ?><span aria-disabled="true" aria-label="Halaman sebelumnya">‹</span><?php endif; ?>
            <?php $previous=0;foreach($visiblePages as $page):if($page<1)continue; ?>
                <?php if ($previous && $page>$previous+1): ?><span class="page-gap" aria-hidden="true">…</span><?php endif; ?>
                <?php if ($page===$currentPage): ?><span class="is-current" aria-current="page" aria-label="Halaman <?= $page; ?>"><?= $page; ?></span><?php else: ?><a href="<?= html_escape($pageLink($page)); ?>" aria-label="Halaman <?= $page; ?>"><?= $page; ?></a><?php endif; ?>
            <?php $previous=$page;endforeach; ?>
            <?php if ($currentPage<$lastPage): ?><a href="<?= html_escape($pageLink($currentPage+1)); ?>" rel="next" aria-label="Halaman berikutnya">›</a><?php else: ?><span aria-disabled="true" aria-label="Halaman berikutnya">›</span><?php endif; ?>
        </nav>
    </div>
<?php endif; ?>
