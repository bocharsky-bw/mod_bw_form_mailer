<?php
/**
 * @package     Joomla BW
 * @subpackage  mod_bw_form_mailer
 *
 * @copyright   (C) 2013 BW - Bocharsky Victor. BrainForce Labs. All rights reserved.
 * @author      Bocharsky Victor <mail@brainforce.kiev.ua>
 * @license     All right reserved by Bocharsky Victor
 */

defined('_JEXEC') or die;
?>

<?php print $params->get('preform', '') ?>
<form action="" method="POST" enctype="multipart/form-data">
    <?php print $form ?>
    <?php print ModBwFormMailerHelper::getCaptchaHtml() ?>
    <input type="hidden" name="_bw_form_unique_key" value="<?php print $params->get('unique_key', 'undefined') ?>">
    <button class="btn btn-default bw_form_mailer_submit" type="submit" name="_bw_form_mailer_submit"><?php print $params->get('submit', JText::_('MOD_BW_FORM_MAILER_SUBMIT_DEFAULT')) ?></button>
</form>
<?php print $params->get('postform', '') ?>