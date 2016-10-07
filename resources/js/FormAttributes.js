(function($) {

Craft.FormAttributes = Garnish.Base.extend(
{
    $container: $('.amforms__container'),
    $inputs: $('input[type=text], textarea', '.pane:first'),

    init: function() {
        this.createSelectors();
    },

    /**
     * Create selectors for every input.
     */
    createSelectors: function() {
        // Add a selector to every input that should have one
        this.$inputs.each(function() {
            new Craft.FormAttributeSelector(this);
        });
    },
});

Craft.FormAttributeSelector = Garnish.Base.extend(
{
    $container: $('.amforms__container'),
    $attributes: null,
    $currentField: null,
    $trigger: null,
    hud: null,

    init: function(selector) {
        this.$currentField = $(selector);

        // Only create the trigger when needed
        if (this.$currentField.hasClass('amforms__selector--on')) {
            // Create a trigger to show the available attributes
            this.$currentField.addClass('text--tags');
            this.$trigger = $('<span class="amforms__tags" data-icon="tags"></span>').insertAfter(this.$currentField);
            this.addListener(this.$trigger, 'click', 'showHud');
        }
    },

    /**
     * Show available attributes for input.
     */
    showHud: function() {
        if (! this.hud) {
            // Create the HUD
            this.hud = new Garnish.HUD(this.$currentField, this.$container.html(), {
                hudClass: 'hud amforms__hud',
                closeOtherHUDs: true
            });

            // Init panes
            this.hud.$body.find('.pane').pane();

            // Get our attributes
            this.$attributes = this.hud.$body.find('.amforms__attributes span');

            // Add attribute to focused field
            this.addListener(this.$attributes, 'click', function(ev) {
                this.addAttribute( $(ev.currentTarget).data('attribute') );
                ev.stopPropagation();
            });
        }
        else {
            this.hud.show();
        }

        // Refocus field
        var $field = this.$currentField[0],
            startPos = $field.selectionStart,
            endPos = $field.selectionEnd,
            value = $field.value.length;

        if (endPos == 0) {
            endPos = value;
        }

        $field.focus();
        $field.setSelectionRange(endPos, endPos);
    },

    /**
     * Add attribute to input field.
     */
    addAttribute: function(attribute) {
        if (! this.$currentField || this.$currentField.length === 0) {
            return;
        }

        var $field = this.$currentField[0],
            startPos = $field.selectionStart,
            endPos = $field.selectionEnd,
            newPos = (endPos + attribute.length) + 2; // 2 for the brackets

        $field.value = $field.value.substring(0, startPos) + '{' + attribute + '}' + $field.value.substring(endPos, $field.value.length);
        $field.focus();
        $field.setSelectionRange(newPos, newPos);
    }
});

})(jQuery);
