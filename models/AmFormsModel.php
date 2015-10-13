<?php
namespace Craft;

class AmFormsModel extends BaseModel
{
    // Element types
    const ElementTypeForm = 'AmForms_Form';
    const ElementTypeSubmission = 'AmForms_Submission';

    // Field context
    const FieldContext = 'amForms';

    //Field content
    const FieldContent = 'amforms_content';

    // Setting types
    const SettingGeneral = 'general';
    const SettingExport = 'export';
    const SettingAntispam = 'antispam';
    const SettingRecaptcha = 'recaptcha';
    const SettingsTemplatePaths = 'templates';
}
