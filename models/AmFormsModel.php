<?php
namespace Craft;

class AmFormsModel extends BaseModel
{
    // Element types
    const ElementTypeForm = 'AmForms_Form';
    const ElementTypeSubmission = 'AmForms_Submission';

    // Field context
    const FieldContext = 'amForms';

    // Setting types
    const SettingGeneral = 'general';
    const SettingRecaptcha = 'recaptcha';
}