<?php
/**
 * @package     Joomla BW
 * @subpackage  mod_bw_form_mailer
 *
 * @copyright   (C) 2013 BW - Bocharsky Victor. BrainForce Labs. All rights reserved.
 * @author      Bocharsky Victor <mail@brainforce.kiev.ua>
 * @license     All right reserved by Bocharsky Victor
 */

defined('_JEXEC') or die('Access restricted');

/**
 * Helper for mod_bw_form_mailer
 */
class ModBwFormMailerHelper
{
    /**
     * Joomla Application Object
     * @var JApplication object
     */
    public static $app;
    
    /**
     * Объект для получения параметров модуля
     * @var JRegistry Module params
     */
    public static $params;

    
    /** 
     * Инициализация хэлпера модуля
     * @global 
     */
    public static function init($params) {
        self::$app = JFactory::getApplication();
        self::$params = $params;
    }
    
    public static function run() {
        //var_dump(self::$params);
        if ( ! self::isFormSend()) {
            
            return;
        }
           
        if (self::$params->get('captcha')) {
            if (JCaptcha::getInstance('recaptcha') instanceof JCaptcha) {
                if ( ! JCaptcha::getInstance('recaptcha')->checkAnswer(self::$app->input->post->getString('recaptcha_response_field')) == TRUE) {
                    self::$app->enqueueMessage(JText::_('MOD_BW_FORM_MAILER_CAPTCHA_CHECK_FALSE'), 'error');

                    return;
                }
            }
        }
        
        if (self::sendMessage() == TRUE) {
            self::$app->enqueueMessage(JText::_('MOD_BW_FORM_MAILER_MESSAGE_SENT'), 'success');
        } else {
            self::$app->enqueueMessage(JText::_('MOD_BW_FORM_MAILER_MESSAGE_DONT_SENT'), 'error');
        }
        
        self::redirect();
    }
    
    /**
     * Проверяет была ли отправлена форма
     * @return integer
     */
    public static function isFormSend() 
    {
        return true
            && self::$app->input->post->get('_bw_form_unique_key')
            && (self::$params->get('unique_key') == self::$app->input->post->get('_bw_form_unique_key'));
    }
        
    /**
     * Отправка письма на email
     */
    public static function sendMessage() {
        $mailer = JFactory::getMailer();
        
        // Set a sender
        $config = JFactory::getConfig();
        $sender = array( 
            $config->get('mailfrom'),
            $config->get('fromname'),
        );
        $mailer->setSender($sender);
        
        // Recipient
        $user = JFactory::getUser();
        $recipient = self::$params->get('email', $config->get('mailfrom'));;
        $mailer->addRecipient($recipient);
        
        // Create the mail 
        $mailer->setSubject(self::$params->get('subject', JText::printf('MOD_BW_FORM_MAILER_MESSAGE_SUBJECT', $_SERVER['HTTP_HOST'])));
        $mailer->isHTML(true);
        $mailer->Encoding = 'base64';
        $messageBody = self::$params->get('message', '');
        $messageBody = preg_replace_callback('/\{([0-9A-Za-z_-]+)\}/i', 'self::replaceWildcards', $messageBody);
        $mailer->setBody($messageBody);
        
        // Sending the mail
        return $mailer->Send();
    }
    
    /**
     * Замена псевдопеременных шаблона тела письма
     * @param array $matches
     */
    public static function replaceWildcards(array $matches) {
        // как обычно: $matches[0] -  полное вхождение шаблона
        // $matches[1] - вхождение первой подмаски,
        // заключенной в круглые скобки, и так далее...
        
        //return self::$app->input->post->getString(trim( isset($matches[1]) ? $matches[1] : '', ''));
        return htmlspecialchars(self::$app->input->post->getString(trim( isset($matches[1]) ? $matches[1] : '', '')));
    }
    
    /**
     * Redirect после отправки
     */
    public static function redirect() {
        $redirect = trim(self::$params->get('redirect', false));
        
        if ($redirect) {
            $path = $redirect;
        } else {
            $path = self::$app->input->server->getString('REQUEST_URI');
        }
        
        return self::$app->redirect($path);
    }
    
    /**
     * Возвращает HTML код каптчи
     * @return string
     */
    public static function getCaptchaHtml() {
        /* ReCaptcha */
        $isCaptchaOn = (int)self::$params->get('captcha', 0);
        
        if ($isCaptchaOn) {
            if (JCaptcha::getInstance('recaptcha') instanceof JCaptcha) {
                return JCaptcha::getInstance('recaptcha')->display('recaptcha', 'recaptcha');
            } else {
                self::$app->enqueueMessage(JText::_('MOD_BW_FORM_MAILER_CAPTCHA_PLUGIN_DISABLE'), 'error');
            }
        }
        
        return '';
    }

    /**
     * Fix Recaptcha API Server Bug with wrong address
     * From 'http://api.recaptcha.net' to 'http://www.google.com/recaptcha/api'
     */
    public static function fixRecaptchaApiServerBug() {
        $filePath = __DIR__ .'/../../plugins/captcha/recaptcha/recaptcha.php';
        $fixedFlagFilePath = __DIR__ .'/fixed-recaptcha-api-server-bug.bin';
        
        if ( ! self::isRecaptchaApiServerBugFixed($filePath, $fixedFlagFilePath)) {
            //die('fixing');
            $statusMessage = JText::_('MOD_BW_FORM_MAILER_CAPTCHA_PLUGIN_PATCH_FAILED');
            $statusAlias = 'error';
            if ( file_put_contents($filePath, str_replace('http://api.recaptcha.net', 'http://www.google.com/recaptcha/api', file_get_contents($filePath))) ) {
                if (file_put_contents($fixedFlagFilePath, 'patch done!')) {
                    $statusMessage = JText::_('MOD_BW_FORM_MAILER_CAPTCHA_PLUGIN_PATCH_PASSED');
                    $statusAlias = 'success';
                }
            }
            self::$app->enqueueMessage($statusMessage, $statusAlias);
        }
        
    }
    
    /**
     * Check is Recaptcha API Server Bug with wrong address fixed
     * @param type $filePath
     * @param type $fixedFlagFilePath
     * @return boolean
     */
    public static function isRecaptchaApiServerBugFixed($filePath, $fixedFlagFilePath) {
        
        if (file_exists($fixedFlagFilePath)) {
            if (filemtime($filePath) <= filemtime($fixedFlagFilePath)) {
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
}
