<?php

declare(strict_types=1);

/*
 * This file is part of the package sfroemken/fal_dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace SFroemken\FalDropbox\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Wizard/Control next to field access_token in Dropbox XML configuration
 */
class AccessTokenElement extends AbstractFormElement
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
                'localizationStateSelector'
            ],
        ],
        'defaultLanguageDifferences' => [
            'renderType' => 'defaultLanguageDifferences',
            'after' => [
                'otherLanguageContent',
            ],
        ],
    ];

    /**
     * @return array
     * @throws RouteNotFoundException
     */
    public function render(): array
    {
        $options = $this->data['renderData']['fieldControlOptions'];
        $parameterArray = $this->data['parameterArray'];
        $itemName = $parameterArray['itemFormElName'];
        $windowOpenParameters = $options['windowOpenParameters'] ?? 'height=800,width=600,status=0,menubar=0,scrollbars=1';

        $urlParameters = [
            'P' => [
                'table' => $this->data['tableName'],
                'uid' => $this->data['databaseRow']['uid'],
                'pid' => $this->data['databaseRow']['pid'],
                'field' => $this->data['fieldName'],
                'formName' => 'editform',
                'itemName' => $itemName,
                'hmac' => GeneralUtility::hmac('editform' . $itemName, 'wizard_js'),
                'fieldChangeFunc' => $parameterArray['fieldChangeFunc'],
                'fieldChangeFuncHash' => GeneralUtility::hmac(serialize($parameterArray['fieldChangeFunc'])),
            ],
        ];

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $url = $uriBuilder->buildUriFromRoute('access_token', $urlParameters);

        $onClick = [];
        $onClick[] = 'this.blur();';
        $onClick[] = 'vHWin=window.open(';
        $onClick[] =    GeneralUtility::quoteJSvalue($url);
        $onClick[] =    ',';
        $onClick[] =    '\'\',';
        $onClick[] =    GeneralUtility::quoteJSvalue($windowOpenParameters);
        $onClick[] = ');';
        $onClick[] = 'vHWin.focus();';
        $onClick[] = 'return false;';

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

        return [
            'iconIdentifier' => 'actions-add',
            'title' => 'Generate Access Token',
            'linkAttributes' => [
                'onClick' => implode('', $onClick)
            ],
            'html' => implode(LF, $mainFieldHtml)
        ];
    }
}
