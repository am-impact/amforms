(function($) {

Craft.AmFormsFieldLayoutDesigner = Craft.FieldLayoutDesigner.extend(
{
    initField: function($field)
    {
        var $editBtn = $field.find('.settings'),
            $menu = $('<div class="menu" data-align="center"/>').insertAfter($editBtn),
            $ul = $('<ul/>').appendTo($menu);

        if ($field.hasClass('fld-required'))
        {
            $('<li><a data-action="toggle-required">'+Craft.t('Make not required')+'</a></li>').appendTo($ul);
        }
        else
        {
            $('<li><a data-action="toggle-required">'+Craft.t('Make required')+'</a></li>').appendTo($ul);
        }

        $('<li><a data-action="edit">'+Craft.t('Edit field')+'</a></li>').appendTo($ul);
        $('<li><a data-action="remove">'+Craft.t('Remove')+'</a></li>').appendTo($ul);

        new Garnish.MenuBtn($editBtn, {
            onOptionSelect: $.proxy(this, 'onFieldOptionSelect')
        });
    },

    onFieldOptionSelect: function(option)
    {
        var $option = $(option),
            $field = $option.data('menu').$anchor.parent(),
            action = $option.data('action');

        switch (action)
        {
            case 'toggle-required':
            {
                this.toggleRequiredField($field, $option);
                break;
            }
            case 'edit':
            {
                window.open(this.settings.editCpUrl + '/' + $field.data('id'));
                break;
            }
            case 'remove':
            {
                this.removeField($field);
                break;
            }
        }
    },
});

})(jQuery);
