/*
 * This file is part of the stefanfroemken/dropbox project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
define(["exports", "TYPO3/CMS/Backend/Enum/Severity", "TYPO3/CMS/Backend/MultiStepWizard", "jquery"], function(exports, severity, multiStep, $) {
  class AccessTokenModule {
    constructor(triggerAccessTokenWizardButtonClass) {
      $("." + triggerAccessTokenWizardButtonClass).on("click", function () {
        let $getAccessTokenButton = $(this);
        const codeVerifier = generateCodeVerifier();
        multiStep.addSlide(
          "AppKeySecretForm",
          "Set Dropbox App key and secret",
          getFormForAppKeyAndSecret(),
          severity.info,
          "Step 1/3",
          function ($slide) {
            let $modal = $slide.closest('.modal');
            let $nextButton = $modal.find(".modal-footer").find('button[name="next"]');
            let $appKeyElement = $('[data-formengine-input-name="' + $getAccessTokenButton.data("appkeyfieldname") + '"]');
            $slide.find("input#appKey").val($appKeyElement.val());
            multiStep.lockPrevStep();
            $nextButton.off().on("click", function () {
              multiStep.set("appKey", $slide.find("input#appKey").val());
              multiStep.setup.$carousel.carousel("next")
            });
          }
        );

        multiStep.addSlide(
          "RequestAuthCode",
          "Use link to retrieve Dropbox Auth Code",
          getPanelWithAuthCodeLink(),
          severity.info,
          "Step 2/3",
          function ($slide, settings) {
            multiStep.unlockPrevStep();
            $slide.find("a#authCodeLink").on("click", function () {
              multiStep.setup.$carousel.carousel("next")
            }).attr(
              "href",
              "https://www.dropbox.com/oauth2/authorize?client_id=" + settings.appKey + "&response_type=code&code_challenge=" + codeVerifier + "&code_challenge_method=plain&token_access_type=offline"
            );
          }
        );

        multiStep.addSlide(
          "AuthCodeForm",
          "Insert AuthCode retrieved by link of previous step",
          getFormForAuthCode(),
          severity.info,
          "Finish",
          function ($slide) {
            let $modal = $slide.closest('.modal');
            let $nextButton = $modal.find(".modal-footer").find('button[name="next"]');
            multiStep.lockPrevStep();
            multiStep.unlockNextStep();
            multiStep.setup.forceSelection = false;
            $nextButton.off().on("click", function () {
              multiStep.set("authCode", $slide.find("input#authCode").val());
              multiStep.setup.$carousel.carousel("next")
            });
          }
        );

        multiStep.addFinalProcessingSlide(function () {
          $.ajax({
            url: "https://api.dropboxapi.com/oauth2/token",
            dataType: "json",
            method: "POST",
            data: {
              code: multiStep.setup.settings["authCode"],
              grant_type: "authorization_code",
              client_id: multiStep.setup.settings["appKey"],
              code_verifier: codeVerifier
            },
            success: function(response) {
              let $appKeyElement = $('[data-formengine-input-name="' + $getAccessTokenButton.data("appkeyfieldname") + '"]');
              $appKeyElement.val(multiStep.setup.settings["appKey"]);
              if (response.refresh_token) {
                let $accessTokenElement = $('[data-formengine-input-name="' + $getAccessTokenButton.data("itemname") + '"]');
                $accessTokenElement.val(response.refresh_token);
                $accessTokenElement.trigger("change");
              } else if (response.access_token) {
                let $accessTokenElement = $('[data-formengine-input-name="' + $getAccessTokenButton.data("itemname") + '"]');
                $accessTokenElement.val(response.access_token);
                $accessTokenElement.trigger("change");
              }
              multiStep.dismiss();
            },
            fail: function() {
              console.log("Ups");
              multiStep.dismiss();
            }
          });
        }).then(function () {
          multiStep.show();
        });
      });
    }
  }

  return AccessTokenModule;

  function getFormForAppKeyAndSecret()
  {
    return '<div class="form-group">' +
      '  <label for="appKey">AppKey</label>' +
      '  <input type="text" class="form-control" id="appKey" autocomplete="off" placeholder="App Key" />' +
      '</div>';
  }

  function getPanelWithAuthCodeLink()
  {
    return '<div class="panel panel-info">' +
      '  <div class="panel-heading">Authorize your Dropbox App</div>' +
      '  <div class="panel-body">' +
      '    <a href="#" id="authCodeLink" target="_blank">Authorization Link (opens in new tab)<a>' +
      '  </div>' +
      '</div>'
  }

  function getFormForAuthCode()
  {
    return '<div class="form-group">' +
      '  <label for="authCode">AuthCode</label>' +
      '  <input type="text" class="form-control" id="authCode" autocomplete="off" placeholder="Type in the AuthCode from the link above" />' +
      '</div>';
  }

  function generateCodeVerifier(len) {
    const uint8Array = new Uint8Array((len || 128) / 2);
    const arrayBuffer = window.crypto.getRandomValues(uint8Array);
    return Array.from(new Uint8Array(arrayBuffer))
      .map((item) => item.toString(16).padStart(2, "0"))
      .join("");
  }
});
