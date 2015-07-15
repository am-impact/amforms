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
        $recaptchaSettings = craft()->amForms_settings->getAllSettingsByType(AmFormsModel::SettingRecaptcha);

        // Is reCAPTCHA enabled?
        if ($recaptchaSettings && $recaptchaSettings['googleRecaptchaEnabled']->value) {
            // Plugin's default template path
            $templatePath = craft()->path->getPluginsPath() . 'amforms/templates/_display/templates/';

            // Build reCAPTCHA HTML
            craft()->path->setTemplatesPath($templatePath);
            $html = craft()->templates->render('recaptcha', array(
                'siteKey' => $recaptchaSettings['siteKey']->value
            ));

            // Reset templates path
            craft()->path->setTemplatesPath(craft()->path->getSiteTemplatesPath());

            // Include Google's reCAPTCHA API
            craft()->templates->includeJsFile('https://www.google.com/recaptcha/api.js');

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
        $secretKey = craft()->amForms_settings->getSettingsByHandleAndType('secretKey', AmFormsModel::SettingRecaptcha);
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
