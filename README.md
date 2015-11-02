# amforms

_Forms in Craft, made easy_

## CP functionality

When users have permission to access the plugin, they can do various stuff in the CP. There's an extra setting that'll allow users to control the plugin's settings.

### Forms

You're able to activate submissions and / or notifications. This means you could choose to ignore the submissions if you want to, but only receive notifications when a form was submitted.

![NewForm](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amforms/newform.png "NewForm")

Notifications are activated, and all information will be filled out by default. The **Sender name** and **email addresses** fields will automatically contain the information from your email settings in Craft's CP.

![Notifications](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amforms/notifications.png "Notifications")

The plugin will use it's own templates to display forms and email submissions. You have the option to override these templates in general **or** per form! Just create your own folder in the templates folder (e.g.: _amforms) and create a template that you would like to override. You can see the default template names in the placeholders, so if you create your own template with the same name, you could choose to make this your default templates for all your forms. When you create a template with a different name, you could create a template per form.

**This tab is only available to admins.**

![Templates](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amforms/templates.png "Templates")

### Fields

When you install the plugin, you get some commonly used fields by default. The fields that are created are stored in a different context / scope than Craft's fields. This means that you're able to reuse these fields in any form and that they won't be shown in Craft's field list.

![Fields](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amforms/fields.png "Fields")

### Export

You have the option to export your submissions per form. You'll be able to choose which fields you'd like to be included in your export (Matrix supported!). When you create an export, it'll start a task that export your submissions into a file located in your storage folder. When it's done, you're able to download the file or restart the export.

![NewExport](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amforms/newexport.png "NewExport")

![ExportCriteria](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amforms/exportcriteria.png "ExportCriteria")

![ExportOverview](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amforms/exportoverview.png "ExportOverview")

### Settings

There's a general setting called **Use Mandrill for email**, which will be implemented later on. By default, the anti-spam is activated for your forms, but there's also an option to activate Google reCAPTCHA.

![General](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amforms/general.png "General")

![Exports](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amforms/exports.png "Exports")

![AntiSpam](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amforms/antispam.png "AntiSpam")

![reCAPTCHA](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amforms/recaptcha.png "reCAPTCHA")


## Display your forms

### Simple tag

This will only display basic fields!

```
{{ craft.amForms.displayForm('formHandle') }}
```

### Simple field tag

```
{% set form = craft.amForms.getForm('formHandle') %}

{{ form.displayField('fieldHandle') }}
```

### Custom HTML

```
<form method="post" action="" accept-charset="UTF-8">
    {{ getCsrfInput() }}

    {# This should always be here! #}
    <input type="hidden" name="action" value="amForms/submissions/saveSubmission">

    {# Insert your form's handle. #}
    <input type="hidden" name="handle" value="formHandle">

    {# Optional: Redirect URL. Will redirect to current page by default. #}
    <input type="hidden" name="redirect" value="contact?message=thankyou">

    {# Optional: Anti-spam protection. #}
    {{ craft.amForms.displayAntispam() }}

    {# Optional: Google reCAPTCHA protection. #}
    {{ craft.amForms.displayRecaptcha() }}

    {# Place the HTML of your fields here #}

    <input type="submit" value="Submit">
</form>
```

### Custom HTML with displayField

```
{% set form = craft.amForms.getForm('formHandle') %}

<form method="post" action="" accept-charset="UTF-8">
    {{ getCsrfInput() }}

    {# This should always be here! #}
    <input type="hidden" name="action" value="amForms/submissions/saveSubmission">

    {# Insert your form's handle. #}
    <input type="hidden" name="handle" value="{{ form.handle }}">

    {# Optional: Anti-spam protection. #}
    {{ craft.amForms.displayAntispam() }}

    {# Optional: Google reCAPTCHA protection. #}
    {{ craft.amForms.displayRecaptcha() }}

    {# Place the HTML of your fields here #}
    {{ form.displayField('fieldHandle') }}
    {{ form.displayField('aFieldHandle') }}
    {{ form.displayField('anotherFieldHandle') }}

    <input type="submit" value="Submit">
</form>
```

### Custom field HTML

Change **formHandle** to your form's handle.

```
<div class="field">
    <label for="frm_comment">Comment</label>
    <input type="text" id="frm_comment" name="fields[comment]" value="{% if formHandle.comment is defined %}{{ formHandle.comment }}{% endif %}">
    {% if formHandle is defined %}
        {{ errorList(formHandle.getErrors('comment')) }}
    {% endif %}
</div>
```

### Custom Matrix field

```
<div class="field">
    {#
        Notify Craft which Matrix block (handle) will be inserted.

        Our field name for this example is: Persons
        Our block name for this example is: Person
    #}
    <input type="hidden" name="fields[persons][new1][type]" value="person">

    {# Block fields #}
    <label for="frm_firstname">First name</label>
    <input type="text" id="frm_firstname" name="fields[persons][new1][fields][firstName]">
    <label for="frm_lastname">Last name</label>
    <input type="text" id="frm_lastname" name="fields[persons][new1][fields][lastName]">
    {% if formHandle is defined %}
        {{ errorList(formHandle.getErrors('persons')) }}
    {% endif %}
</div>
```

### Errorlist macro

```
{% macro errorList(errors) %}
    {% if errors %}
        <ul class="errors">
            {% for error in errors %}
                <li>{{ error }}</li>
            {% endfor %}
        </ul>
    {% endif %}
{% endmacro %}
```

If you want to include it on the template itself, use:
```
{% from _self import errorList %}
```

## Dashboard widget

Display your recent submissions on your dashboard.

![WidgetSettings](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amforms/widgetsettings.png "WidgetSettings")

![WidgetSmall](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amforms/widgetsmall.png "WidgetSmall")

![WidgetBig](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amforms/widgetbig.png "WidgetBig")
