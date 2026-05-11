<?php

use Wonder\Plugin\Rsvp\Resources\ResponseResource;

\Wonder\View\View::layout('backend.list');
?>

<wi-card class="col-12">
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        <a class="btn btn-dark btn-sm" href="<?=htmlspecialchars(__r('backend.resource.'.ResponseResource::slug().'.export', ['format' => 'csv']), ENT_QUOTES, 'UTF-8')?>">
            <i class="bi bi-filetype-csv"></i> Export CSV
        </a>
        <a class="btn btn-outline-dark btn-sm" href="<?=htmlspecialchars(__r('backend.resource.'.ResponseResource::slug().'.export', ['format' => 'xls']), ENT_QUOTES, 'UTF-8')?>">
            <i class="bi bi-filetype-xls"></i> Export Excel
        </a>
    </div>
</wi-card>

<?=$TABLE_HTML?>

<?php \Wonder\View\View::end(); ?>
