<?php
$this->setPageTitle(LANG_ZAUTH_PROFILE_MENU_BREAD);

if ($this->controller->listIsAllowed()) {
    $this->addBreadcrumb(LANG_USERS, href_to('users'));
}
$this->addBreadcrumb($profile['nickname'], href_to_profile($profile));
$this->addBreadcrumb(LANG_USERS_EDIT_PROFILE, href_to_profile($profile, ['edit']));
$this->addBreadcrumb(LANG_ZAUTH_PROFILE_MENU_BREAD);

$this->renderChild('profile_edit_header', ['profile' => $profile]);
$this->addControllerCSSFromContext('profile','zauth');
$this->addTplCSSFromContext('controllers/zauth/widgets/zauth/zauth');
?>
<?php if (isset($text)) { ?>
    <div class="alert alert-info">
        <?php echo $text; ?>
    </div>
<?php return; } ?>
<div class="alert alert-info">
    <?php echo LANG_ZAUTH_PROFILE_HINT; ?>
</div>
<div class="zauth-edit">
    <div class="table-responsive-sm">
        <table class="table table-hover table-striped">
            <thead>
            <th><?php echo LANG_ZAUTH_PROFILE_TABLE_SOC; ?></th>
            <th class="actions"></th>
            </thead>
            <?php if ($zauths) { ?>
                <?php foreach ($zauths as $zauth) { ?>
                    <tr>
                        <td class="align-middle">
                            <?php echo $providers[$zauth['soc']]; ?>
                        </td>
                        <td class="align-middle">
                            <a class="text-danger" href="<?php echo href_to('zauth', 'delete', $zauth['id']) . '?csrf_token=' . cmsForm::getCSRFToken(); ?>" onclick="if (!confirm('<?php echo LANG_ZAUTH_PROFILE_DELETE_CONFIRM; ?>')) {
                                            return false;
                                        }">
                                <?php echo LANG_ZAUTH_PROFILE_DELETE; ?>
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr class="table-secondary">
                    <td colspan="2">
                        <?php echo LANG_ZAUTH_PROFILE_NOT_FOUND; ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>
<?php if (!empty($available_links)) { ?>
    <div class="zauth-add">
        <h3><?php echo LANG_ZAUTH_ADD; ?></h3>
        <div class="zauth-add__info pb-3"><?php echo LANG_ZAUTH_ADD_HINT; ?></div>
        <?php
        $this->renderControllerChild('zauth', $liststyle, [
            'links' => $available_links,
            'size' => $size
        ]);
        ?>
    </div>
<?php } ?>
