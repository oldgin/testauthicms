<?php
$this->setPageTitle(LANG_DBCAUTH_PROFILE_MENU_BREAD);

if ($this->controller->listIsAllowed()) {
    $this->addBreadcrumb(LANG_USERS, href_to('users'));
}
$this->addBreadcrumb($profile['nickname'], href_to_profile($profile));
$this->addBreadcrumb(LANG_USERS_EDIT_PROFILE, href_to_profile($profile, ['edit']));
$this->addBreadcrumb(LANG_DBCAUTH_PROFILE_MENU_BREAD);

$this->renderChild('profile_edit_header', ['profile' => $profile]);
$this->addControllerCSS('profile','dbcauth');
$this->addTplCSS('controllers/dbcauth/widgets/dbcauth/dbcauth');
?>
<?php if (isset($text)) { ?>
    <div class="alert alert-info">
        <?php echo $text; ?>
    </div>
<?php return; } ?>
<div class="sess_messages"><div class="message_info_in_page">
    <?php echo LANG_DBCAUTH_PROFILE_HINT; ?>
</div></div>
<div class="dbcauth-edit">
    <div class="table-responsive-sm">
        <table class="data_list">
            <thead>
            <th><?php echo LANG_DBCAUTH_PROFILE_TABLE_SOC; ?></th>
            <th class="actions"></th>
            </thead>
            <?php if ($dbcauths) { ?>
                <?php foreach ($dbcauths as $dbcauth) { ?>
                    <tr>
                        <td>
                            <?php echo $providers[$dbcauth['soc']]; ?>
                        </td>
                        <td>
                            <a href="<?php echo href_to('dbcauth', 'delete', $dbcauth['id']) . '?csrf_token=' . cmsForm::getCSRFToken(); ?>" onclick="if (!confirm('<?php echo LANG_DBCAUTH_PROFILE_DELETE_CONFIRM; ?>')) {
                                            return false;
                                        }">
                                <?php echo LANG_DBCAUTH_PROFILE_DELETE; ?>
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td class="empty" colspan="2">
                        <?php echo LANG_DBCAUTH_PROFILE_NOT_FOUND; ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>
<?php if (!empty($available_links)) { ?>
    <div class="dbcauth-add">
        <h3><?php echo LANG_DBCAUTH_ADD; ?></h3>
        <div class="dbcauth-add__info"><?php echo LANG_DBCAUTH_ADD_HINT; ?></div>
        <?php
        $this->renderControllerChild('dbcauth', $liststyle, [
            'links' => $available_links,
            'size' => $size
        ]);
        ?>
    </div>
<?php } ?>
