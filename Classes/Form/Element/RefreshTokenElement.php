<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Wizard/Control next to field refresh_token in Dropbox XML configuration
 */
class RefreshTokenElement extends AbstractFormElement
{
    /**
     * @var array
     */
    protected $defaultFieldWizard = [
        'localizationStateSelector' => [
            'renderType' => 'localizationStateSelector',
        ],
        'otherLanguageContent' => [
            'renderType' => 'otherLanguageContent',
            'after' => [
                'localizationStateSelector',
            ],
        ],
        'defaultLanguageDifferences' => [
            'renderType' => 'defaultLanguageDifferences',
            'after' => [
                'otherLanguageContent',
            ],
        ],
    ];

    public function render(): array
    {
        $fieldId = StringUtility::getUniqueId('tceforms-trigger-access-token-wizard-');
        $resultArray = $this->initializeResultArray();
        $resultArray['requireJsModules'][] = ['TYPO3/CMS/Dropbox/AccessTokenModule' => '
            function(AccessTokenElement) {
                new AccessTokenElement(' . GeneralUtility::quoteJSvalue($fieldId) . ');
            }',
        ];

        $parameterArray = $this->data['parameterArray'];
        $itemName = $parameterArray['itemFormElName'];
        $appKeyFieldName = str_replace($this->data['flexFormFieldName'], $parameterArray['fieldConf']['config']['fieldControl']['accessToken']['appKeyFieldName'], $parameterArray['itemFormElName']);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];

        $mainFieldHtml = [];
        $mainFieldHtml[] = '<div class="form-control-wrap">';
        $mainFieldHtml[] =  '<div class="form-wizards-wrap">';
        $mainFieldHtml[] =      '<div class="form-wizards-element">';
        // Main HTML of element done here ...
        $mainFieldHtml[] =      '</div>';
        $mainFieldHtml[] =      '<div class="form-wizards-items-bottom">';
        $mainFieldHtml[] =          $fieldWizardHtml;
        $mainFieldHtml[] =      '</div>';
        $mainFieldHtml[] =  '</div>';
        $mainFieldHtml[] = '</div>';

        $resultArray['iconIdentifier'] = 'actions-add';
        $resultArray['title'] = 'Generate Access Token';
        $resultArray['linkAttributes'] = [
            'class' => $fieldId,
            'data-itemname' => $itemName,
            'data-appkeyfieldname' => $appKeyFieldName,
            'onClick' => 'return false;',
        ];
        $resultArray['html'] = implode(LF, $mainFieldHtml);

        return $resultArray;
    }
}
