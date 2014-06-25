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

// Include the syndicate functions only once
require_once __DIR__ . '/helper.php';

$app = JFactory::getApplication();
ModBwFormMailerHelper::init($params);
ModBwFormMailerHelper::fixRecaptchaApiServerBug();
ModBwFormMailerHelper::run();

$form = $params->get('form', '');
$form = preg_replace_callback('/\{([0-9A-Za-z_-]+)\}/i', 'ModBwFormMailerHelper::replaceWildcards', $form);

require JModuleHelper::getLayoutPath('mod_bw_form_mailer', $params->get('layout', 'default'));