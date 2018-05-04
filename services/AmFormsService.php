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
            if (!array_key_exists('path', $assetSettings)) {
                $assetSettings['path'] = '';
            }
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

        $settingsName = $defaultTemplate . 'Template';
        $templateSetting = craft()->amForms_settings->getSettingByHandleAndType($settingsName, AmFormsModel::SettingsTemplatePaths);

        if (empty($overrideTemplate) && $templateSetting) {
            $overrideTemplate = $templateSetting->value;
        }

        // Is the override template set?
        if ($overrideTemplate) {
            // Is the value a folder, or folder with template?
            $templateFile = craft()->path->getSiteTemplatesPath() . $overrideTemplate;
            if (is_dir($templateFile)) {
                // Only a folder was given, so still the default template template
                $templatePath = $templateFile;
            }
            else {
                // Try to find the template for each available template extension
                foreach (craft()->config->get('defaultTemplateExtensions') as $extension) {
                    if (IOHelper::fileExists($templateFile . '.' . $extension)) {
                        $pathParts = explode('/', $overrideTemplate);
                        $defaultTemplate = $pathParts[ (count($pathParts) - 1) ];
                        $templatePath = craft()->path->getSiteTemplatesPath() . implode('/', array_slice($pathParts, 0, (count($pathParts) - 1)));
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
        $oldPath = method_exists(craft()->templates, 'getTemplatesPath') ? craft()->templates->getTemplatesPath() : craft()->path->getTemplatesPath();
        method_exists(craft()->templates, 'setTemplatesPath') ? craft()->templates->setTemplatesPath($templateInfo['path']) : craft()->path->setTemplatesPath($templateInfo['path']);

        // Get template HTML
        $html = craft()->templates->render($templateInfo['template'], $variables);

        // Reset templates path
        method_exists(craft()->templates, 'setTemplatesPath') ? craft()->templates->setTemplatesPath($oldPath) : craft()->path->setTemplatesPath($oldPath);

        // Return rendered template!
        return $html;
    }
}
