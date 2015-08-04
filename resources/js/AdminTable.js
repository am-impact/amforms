(function($) {

Craft.AmFormsAdminTable = Craft.AdminTable.extend(
{
    formId: null,
    criteriaCounter: 0,

    $spinner: null,
    $criteriaSelector: null,

    init: function(settings) {
        if ('formId' in settings) {
            this.formId = settings.formId;
            this.criteriaCounter = settings.criteriaCounter;
            this.$criteriaSelector = $(settings.criteriaSelector);
            this.$spinner = this.$criteriaSelector.parent().find('.spinner');

            this.addListener(this.$criteriaSelector, 'click', 'addCriteria');
        };

        this.base(settings);
    },

    /**
     * Override Craft's Admin Table.
     */
    reorderObjects: function() {
        // Don't do anything!
    },

    /**
     * Override Craft's Admin Table.
     */
    handleDeleteBtnClick: function(event) {
        var $row = $(event.target).closest('tr');

        $row.remove();
        this.totalObjects--;
        this.updateUI();
    },

    /**
     * Override Craft's Admin Table.
     */
    addRow: function(row) {
        if (this.settings.maxObjects && this.totalObjects >= this.settings.maxObjects)
        {
            // Sorry pal.
            return;
        }

        var $row = $(row).appendTo(this.$tbody),
            $deleteBtn = $row.find('.delete'),
            $switcher = $row.find('.criteriaSwitcher select'),
            $lightSwitches = $row.find('.lightswitch');

        $switcher.fieldtoggle();
        $lightSwitches.lightswitch();

        if (this.settings.sortable)
        {
            this.sorter.addItems($row);
        }

        this.$deleteBtns = this.$deleteBtns.add($deleteBtn);

        this.addListener($deleteBtn, 'click', 'handleDeleteBtnClick');
        this.totalObjects++;

        this.updateUI();
    },

    /**
     * Add criteria row.
     */
    addCriteria: function(event) {
        var data = {
            formId: this.formId,
            counter: this.criteriaCounter
        };

        this.$spinner.removeClass('hidden');

        Craft.postActionRequest('amForms/exports/getCriteria', data, $.proxy(function(response, textStatus) {
            if (textStatus == 'success') {
                if (response.success) {
                    this.criteriaCounter ++;
                    this.addRow(response.row);
                    Craft.appendHeadHtml(response.headHtml);
                    Craft.appendFootHtml(response.footHtml);

                    this.$spinner.addClass('hidden');
                }
            }
        }, this));

        event.preventDefault();
    }
});

})(jQuery);
