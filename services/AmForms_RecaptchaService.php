<?php
namespace Craft;

/**
 * AmForms - reCAPTCHA service
 */
class AmForms_RecaptchaService extends BaseApplicationComponent
{
    /**
     * Render a reCAPTCHA widget.
     *
     * @param bool $renderTwig
     *
     * @return bool|string
     */
    public function render()
    {
        // Get reCAPTCHA settings
        $recaptchaSettings = craft()->amForms_settings->getSettingsByType(AmFormsModel::SettingRecaptcha);

        // Is reCAPTCHA enabled?
        if ($recaptchaSettings && $recaptchaSettings['googleRecaptchaEnabled']->value) {
            // Plugin's default template path
            $templatePath = craft()->path->getPluginsPath() . 'amforms/templates/_display/templates/';

            // Build reCAPTCHA HTML
            $oldPath = method_exists(craft()->templates, 'getTemplatesPath') ? craft()->templates->getTemplatesPath() : craft()->path->getTemplatesPath();
            method_exists(craft()->templates, 'setTemplatesPath') ? craft()->templates->setTemplatesPath($templatePath) : craft()->path->setTemplatesPath($templatePath);
            $html = craft()->templates->render('recaptcha', array(
                'siteKey' => $recaptchaSettings['siteKey']->value
            ));

            // Reset templates path
            method_exists(craft()->templates, 'setTemplatesPath') ? craft()->templates->setTemplatesPath($oldPath) : craft()->path->setTemplatesPath($oldPath);

            // Include Google's reCAPTCHA API
            craft()->templates->includeJsFile('https://www.google.com/recaptcha/api.js?onload=CaptchaCallback&render=explicit');
            craft()->templates->includeJs('var CaptchaCallback = function() { var captchas = document.querySelectorAll(\'.g-recaptcha\'); for (i = 0; i < captchas.length; i += 1) { grecaptcha.render(captchas[i], {\'sitekey\' : \''.$recaptchaSettings['siteKey']->value.'\'}); }; };');

            // Parse widget
            return new \Twig_Markup($html, craft()->templates->getTwig()->getCharset());
        }

        return false;
    }

    /**
     * Verify a reCAPTCHA submission.
     *
     * @return bool
     */
    public function verify()
    {
        // Get reCAPTCHA value
        $captcha = craft()->request->getPost('g-recaptcha-response');

        // Get reCAPTCHA secret key
        $secretKey = craft()->amForms_settings->getSettingByHandleAndType('secretKey', AmFormsModel::SettingRecaptcha);
        if (! $secretKey) {
            return false;
        }

        // Google API parameters
        $params = array(
            'secret'   => $secretKey->value,
            'response' => $captcha
        );

        // Set request
        $client = new \Guzzle\Http\Client();
        $request = $client->post('https://www.google.com/recaptcha/api/siteverify');
        $request->addPostFields($params);
        $result = $client->send($request);

        // Handle response
        if($result->getStatusCode() == 200) {
            $json = $result->json();

            if($json['success']) {
                return true;
            }
        }

        return false;
    }
}
