<div class="zauth_small">Войти с помощью
<?php
foreach ($links as $key => $link){
    if(!empty($link)){
    ?>
<a rel="nofollow" title="<?php echo constant('LANG_ZAUTH_LINKS_'.strtoupper($key)); ?>" href="<?php echo href_to('zauth',$key); ?>" class="zauth_<?php echo $key; ?>"></a>
<?php
}}
?>
</div>
<?php
if(isset($size) && $size){
    $size = $size / 16;//px to rem
    ?>
<style>
    .zauth_small a{
        height: <?php echo $size; ?>rem;
        width: <?php echo $size; ?>rem;
    }
</style>
<?php }

