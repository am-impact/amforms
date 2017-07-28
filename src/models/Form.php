<?php
/**
 * Form manager for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\formmanager\models;

use amimpact\formmanager\records\Form as FormRecord;

use Craft;
use craft\base\Model;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

class Form extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID.
     */
    public $id;

    /**
     * @var string|null Name.
     */
    public $name;

    /**
     * @var string|null Handle.
     */
    public $handle;

    /**
     * @var int|null Author.
     */
    public $authorId;

    /**
     * @var int|null Field layout.
     */
    public $fieldLayoutId;

    /**
     * @var string|null Submission title format.
     */
    public $titleFormat;

    /**
     * @var string|null The alternative URL to submit the form to.
     */
    public $submitActionUrl;

    /**
     * @var string|null The alternative submit button text, that'll send the form data.
     */
    public $submitButtonText;

    /**
     * @var string|null What to do after the form has been submitted.
     */
    public $afterSubmit;

    /**
     * @var string|null The text that'll be displayed when a form has been submitted.
     */
    public $afterSubmitText;

    /**
     * @var int|null The Entry to redirect to, when the form has been submitted.
     */
    public $redirectEntryId;

    /**
     * @var string|null The notifications to use when the form has been submitted.
     */
    public $notificationIds;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'number', 'integerOnly' => true],
            [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
            [['name', 'handle'], UniqueValidator::class, 'targetClass' => FormRecord::class],
            [['name', 'handle'], 'string', 'max' => 255],
        ];
    }

    /**
     * Use the form name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
