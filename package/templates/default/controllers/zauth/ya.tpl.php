<?php ob_start(); ?>
<script>
    if(/access_token=([^&]+)/.exec(document.location.hash)){
        var token = /access_token=([^&]+)/.exec(document.location.hash)[1];
        window.location = "<?php echo href_to('zauth', 'ya') ?>?token="+token;
    }
    if(/error_description=([^&]+)/.exec(document.location.hash)){
        var error = /error_description=([^&]+)/.exec(document.location.hash)[1];
        window.location = "<?php echo href_to('zauth', 'ya') ?>?error="+error;
    }
    if((/access_token=([^&]+)/.exec(document.location.hash) === null) && (/error_description=([^&]+)/.exec(document.location.hash) === null)){
        window.location = "<?php echo href_to('zauth', 'ya') ?>?first=1";
    }
</script>
<?php $this->addBottom(ob_get_clean());