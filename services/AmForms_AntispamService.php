<?php
namespace Craft;

/**
 * AmForms - AntiSpam service
 */
class AmForms_AntispamService extends BaseApplicationComponent
{
    /**
     * Render AntiSpam functionality.
     *
     * @return bool|string
     */
    public function render()
    {
        // Get AntiSpam settings
        $antispamSettings = craft()->amForms_settings->getAllSettingsByType(AmFormsModel::SettingAntispam);

        // Do we have proper settings?
        if ($antispamSettings) {
            $rendered = array();

            // Plugin's default template path
            $templatePath = craft()->path->getPluginsPath() . 'amforms/templates/_display/templates/_antispam/';
            craft()->path->setTemplatesPath($templatePath);

            // Honeypot enabled?
            if ($antispamSettings['honeypotEnabled']->value) {
                if(($result = $this->_renderHoneypot($antispamSettings['honeypotName']->value)) !== false) {
                    $rendered[] = $result;
                }
            }

            // Time check enabled?
            if ($antispamSettings['timeCheckEnabled']->value) {
                if(($result = $this->_renderTime($antispamSettings['minimumTimeInSeconds']->value)) !== false) {
                    $rendered[] = $result;
                }
            }

            // Duplicate check enabled?
            if ($antispamSettings['duplicateCheckEnabled']->value) {
                $this->_renderDuplicate();
            }

            // Origin check enabled?
            if ($antispamSettings['originCheckEnabled']->value) {
                if(($result = $this->_renderOrigin()) !== false) {
                    $rendered[] = $result;
                }
            }

            // Reset templates path
            craft()->path->setTemplatesPath(craft()->path->getSiteTemplatesPath());

            // Parse antispam protection
            if (count($rendered)) {
                return new \Twig_Markup(implode("\n", $rendered), craft()->templates->getTwig()->getCharset());
            }
        }

        return false;
    }

    /**
     * Verify AntiSpam submission.
     *
     * @return bool
     */
    public function verify()
    {
        // Get AntiSpam settings
        $antispamSettings = craft()->amForms_settings->getAllSettingsByType(AmFormsModel::SettingAntispam);

        // Do we have proper settings?
        if ($antispamSettings) {
            // Honeypot enabled?
            if ($antispamSettings['honeypotEnabled']->value) {
                if (! $this->_verifyHoneypot($antispamSettings['honeypotName']->value)) {
                    return false;
                }
            }

            // Time check enabled?
            if ($antispamSettings['timeCheckEnabled']->value) {
                if (! $this->_verifyTime($antispamSettings['minimumTimeInSeconds']->value)) {
                    return false;
                }
            }

            // Duplicate check enabled?
            if ($antispamSettings['duplicateCheckEnabled']->value) {
                if (! $this->_verifyDuplicate()) {
                    return false;
                }
            }

            // Origin check enabled?
            if ($antispamSettings['originCheckEnabled']->value) {
                if (! $this->_verifyOrigin()) {
                    return false;
                }
            }
        }

        // We didn't encounter any problems
        return true;
    }

    /**
     * Render honeypot.
     *
     * @param string $fieldName
     *
     * @return bool|string
     */
    private function _renderHoneypot($fieldName)
    {
        // Validate field name
        if (empty($fieldName)) {
            return false;
        }

        // Render HTML
        return craft()->templates->render('honeypot', array(
            'fieldName' => $fieldName
        ));
    }

    /**
     * Verify honeypot submission.
     *
     * @param string $fieldName
     *
     * @return bool
     */
    private function _verifyHoneypot($fieldName)
    {
        // Validate field name
        if (empty($fieldName)) {
            return false;
        }

        // Validate submission
        if (craft()->request->getPost($fieldName)) {
            return false;
        }
        return true;
    }

    /**
     * Render time.
     *
     * @param int $seconds
     *
     * @return bool|string
     */
    private function _renderTime($seconds)
    {
        // Validate seconds
        if (empty($seconds) || ! is_numeric($seconds) || $seconds <= 0) {
            return false;
        }

        // Render HTML
        return craft()->templates->render('time', array(
            'time' => time()
        ));
    }

    /**
     * Verify time submission.
     *
     * @param int $seconds
     *
     * @return bool|string
     */
    private function _verifyTime($seconds)
    {
        // Validate seconds
        if (empty($seconds) || ! is_numeric($seconds) || $seconds <= 0) {
            return false;
        }

        // Validate submission
        $currentTime = time();
        $renderTime  = (int) craft()->request->getPost('__UATIME', time());
        $difference  = ($currentTime - $renderTime);
        $minimumTime = (int) $seconds;

        return (bool) ($difference > $minimumTime);
    }

    /**
     * Render duplicate.
     */
    private function _renderDuplicate()
    {
        // Create a unique token
        $token = uniqid();

        // Create session variable
        craft()->httpSession->add('amFormsDuplicateToken', $token);
    }

    /**
     * Verify duplicate submission.
     *
     * @return bool
     */
    private function _verifyDuplicate()
    {
        if (craft()->httpSession->get('amFormsDuplicateToken')) {
            // We got a token, so this is a valid submission
            craft()->httpSession->remove('amFormsDuplicateToken');
            return true;
        }

        return false;
    }

    /**
     * Render origin.
     *
     * @return string
     */
    private function _renderOrigin()
    {
        // Render HTML
        return craft()->templates->render('origin', array(
            'domain'    => $this->_getHash(craft()->request->getHostInfo()),
            'userAgent' => $this->_getHash(craft()->request->getUserAgent())
        ));
    }

    /**
     * Verify origin submission.
     *
     * @return bool
     */
    private function _verifyOrigin()
    {
        $renderDomain = craft()->request->getPost('__UAHOME');
        $renderUserAgent = craft()->request->getPost('__UAHASH');

        $domain = $this->_getHash(craft()->request->getHostInfo());
        $userAgent = $this->_getHash(craft()->request->getUserAgent());

        if (! $renderDomain || $renderDomain != $domain) {
            return false;
        }
        elseif (! $renderUserAgent || $renderUserAgent != $userAgent) {
            return false;
        }

        return true;
    }

    /**
     * Create a hash from string.
     *
     * @param string $str
     *
     * @return string
     */
    private function _getHash($str)
    {
        return md5(sha1($str));
    }
}
