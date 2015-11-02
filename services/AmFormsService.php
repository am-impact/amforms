<?php
namespace Craft;

/**
 * AmForms service
 */
class AmFormsService extends BaseApplicationComponent
{
    private $_assetFolders = array();

    /**
     * Handle an error message.
     *
     * @param string $message
     */
    public function handleError($message)
    {
        $e = new Exception($message);
        if (craft()->amForms_settings->isSettingValueEnabled('quietErrors', AmFormsModel::SettingGeneral)) {
            AmFormsPlugin::log('Error::', $e->getMessage(), LogLevel::Warning);
        }
        else {
            throw $e;
        }
    }

    /**
     * Get the server path for an asset.
     *
     * @param AssetFileModel $asset
     *
     * @return string
     */
    public function getPathForAsset($asset)
    {
        // Do we know the source folder path?
        if (! isset($this->_assetFolders[ $asset->folderId ])) {
            $assetFolder = craft()->assets->getFolderById($asset->folderId);
            $assetSource = $assetFolder->getSource();
            $assetSettings = $assetSource->settings;
            if ($assetFolder->path) {
                $assetSettings['path'] = $assetSettings['path'] . $assetFolder->path;
            }
            $this->_assetFolders[ $asset->folderId ] = $assetSettings['path'];
        }

        return craft()->config->parseEnvironmentString($this->_assetFolders[ $asset->folderId ]);
    }

    /**
     * Get a display (front-end displayForm) template information.
     *
     * @param string $defaultTemplate  Which default template are we looking for?
     * @param string $overrideTemplate Which override template was given?
     *
     * @return array
     */
    public function getDisplayTemplateInfo($defaultTemplate, $overrideTemplate)
    {
        // Plugin's default template path
        $templatePath = craft()->path->getPluginsPath() . 'amforms/templates/_display/templates/';

        $settingsName = $defaultTemplate == 'email' ? 'notificationTemplate' : $defaultTemplate . 'Template';
        $templateSetting = craft()->amForms_settings->getSettingsByHandleAndType($settingsName, AmFormsModel::SettingsTemplatePaths);

        if (empty($overrideTemplate) && $templateSetting) {
            $overrideTemplate = $templateSetting->value;
        }

        // Is the override template set?
        if ($overrideTemplate) {
            // Is the value a folder, or folder with template?
            $pathParts = explode(DIRECTORY_SEPARATOR, $overrideTemplate);
            $templateFile = craft()->path->getSiteTemplatesPath() . $overrideTemplate;
            if (count($pathParts) < 2) {
                // Seems we only have a folder that will use the default template name
                $templateFile .= DIRECTORY_SEPARATOR . $defaultTemplate;
            }

            // Try to find the template for each available template extension
            foreach (craft()->config->get('defaultTemplateExtensions') as $extension) {
                if (IOHelper::fileExists($templateFile . '.' . $extension)) {
                    if (count($pathParts) > 1) {
                        // We set a specific template
                        $defaultTemplate = $pathParts[ (count($pathParts) - 1) ];
                        $templatePath = craft()->path->getSiteTemplatesPath() . str_replace(DIRECTORY_SEPARATOR . $defaultTemplate, '', implode(DIRECTORY_SEPARATOR, $pathParts));
                    }
                    else {
                        // Only a folder was given, so still the default template template
                        $templatePath = craft()->path->getSiteTemplatesPath() . $overrideTemplate;
                    }
                }
            }
        }

        return array('path' => $templatePath, 'template' => $defaultTemplate);
    }

    /**
     * Render a display (front-end displayForm) template.
     *
     * @param string $defaultTemplate  Which default template are we looking for?
     * @param string $overrideTemplate Which override template was given?
     * @param array  $variables        Template variables.
     *
     * @return string
     */
    public function renderDisplayTemplate($defaultTemplate, $overrideTemplate, $variables)
    {
        // Get the template path
        $templateInfo = $this->getDisplayTemplateInfo($defaultTemplate, $overrideTemplate);

        // Override Craft template path
        craft()->path->setTemplatesPath($templateInfo['path']);

        // Get template HTML
        $html = craft()->templates->render($templateInfo['template'], $variables);

        // Reset templates path
        craft()->path->setTemplatesPath(craft()->path->getSiteTemplatesPath());

        // Return rendered template!
        return $html;
    }
}
