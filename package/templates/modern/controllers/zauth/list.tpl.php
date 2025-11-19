<div class="dbcauth_icons">
<?php
$icons['vk'] = ['brands','vk'];
$icons['ok'] = ['brands','odnoklassniki'];
$icons['fb'] = ['brands','facebook-f'];
$icons['ya'] = ['brands','yandex'];
$icons['gl'] = ['brands','google'];
$icons['mr'] = ['solid','at'];
$icons['tm'] = ['brands','telegram-plane'];
foreach ($links as $key => $link){
    if(!empty($link)){
    ?>
<a rel="nofollow" title="<?php echo constant('LANG_DBCAUTH_LINKS_'.strtoupper($key)); ?>" href="<?php echo href_to('dbcauth',$key); ?>" class="btn btn-primary dbcauth_<?php echo $key; ?>"><?php html_svg_icon($icons[$key][0], $icons[$key][1]); ?></a>
<?php
}}
?>
</div>
<?php
if(isset($size) && $size){
    $size = $size / 16;//px to rem
    ?>
<style>
    .dbcauth_icons a{
        padding: <?php echo 0.375*$size; ?>rem <?php echo 0.75*$size; ?>rem;
    }
    .dbcauth_icons svg{
        font-size: <?php echo $size; ?>rem;
    }
</style>
<?php }
