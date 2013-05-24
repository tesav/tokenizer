<!-- Start div Result -->
<div>
    <? if ($result): ?><p>
            <? foreach ($result as $key => $value): ?>
                <span style="color:grey"><?=$key?></span> <?=$value?><br />
            <? endforeach; ?></p>
        <? endif; ?>
</div>
<!-- End div Result -->
