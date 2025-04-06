import {SeverityEnum} from "@typo3/backend/enum/severity.js"
import MultiStepWizard from "@typo3/backend/multi-step-wizard.js"
import $ from "jquery";

export default class AccessTokenModule {
  constructor(triggerAccessTokenWizardButtonClass) {
    $("." + triggerAccessTokenWizardButtonClass).on("click", function () {
      let $getAccessTokenButton = $(this);
      const codeVerifier = generateCodeVerifier();

      MultiStepWizard.addSlide(
        "AppKeySecretForm",
        "Set Dropbox App key and secret",
        getFormForAppKeyAndSecret(),
        SeverityEnum.info,
        "App Key",
        function ($slide) {
          let $modal = $slide.closest(".modal");
          let $nextButton = $modal.find(".modal-footer").find("button[name='next']");
          let $appKeyElement = $("[data-formengine-input-name='" + $getAccessTokenButton.data("appkeyfieldname") + "']");
          $slide.find("input#appKey").val($appKeyElement.val());
          MultiStepWizard.lockPrevStep();
          MultiStepWizard.unlockNextStep();
          $nextButton.off().on("click", function () {
            MultiStepWizard.set("appKey", $slide.find("input#appKey").val());
            MultiStepWizard.next();
          });
        }
      );

      MultiStepWizard.addSlide(
        "RequestAuthCode",
        "Use link to retrieve Dropbox Auth Code",
        getPanelWithAuthCodeLink(),
        SeverityEnum.info,
        "Request Auth Code",
        function ($slide, settings) {
          MultiStepWizard.unlockPrevStep();
          $slide.find("a#authCodeLink").on("click", function () {
            MultiStepWizard.next();
          }).attr(
            "href",
            "https://www.dropbox.com/oauth2/authorize?client_id=" + settings.appKey + "&response_type=code&code_challenge=" + codeVerifier + "&code_challenge_method=plain&token_access_type=offline"
          );
        }
      );

      MultiStepWizard.addSlide(
        "AuthCodeForm",
        "Insert AuthCode retrieved by link of previous step",
        getFormForAuthCode(),
        SeverityEnum.info,
        "Set Auth Code",
        function ($slide) {
          let $modal = $slide.closest(".modal");
          let $nextButton = $modal.find(".modal-footer").find("button[name='next']");
          MultiStepWizard.lockPrevStep();
          MultiStepWizard.unlockNextStep();
          MultiStepWizard.setup.forceSelection = false;
          $nextButton.off().on("click", function () {
            MultiStepWizard.set("authCode", $slide.find("input#authCode").val());
            MultiStepWizard.next();
          });
        }
      );

      MultiStepWizard.addFinalProcessingSlide(function () {
        $.ajax({
          url: "https://api.dropboxapi.com/oauth2/token",
          dataType: "json",
          method: "POST",
          data: {
            code: MultiStepWizard.setup.settings["authCode"],
            grant_type: "authorization_code",
            client_id: MultiStepWizard.setup.settings["appKey"],
            code_verifier: codeVerifier
          },
          success: function (response) {
            let $appKeyElement = $("[data-formengine-input-name='" + $getAccessTokenButton.data("appkeyfieldname") + "']");
            $appKeyElement.val(MultiStepWizard.setup.settings["appKey"]);
            const tokenValue = response.refresh_token || response.access_token;
            if (tokenValue) {
              let $accessTokenElement = $("[data-formengine-input-name='" + $getAccessTokenButton.data("itemname") + "']");
              if ($accessTokenElement && $accessTokenElement.length) {
                let humanReadableField = $accessTokenElement.get(0);
                humanReadableField.value = tokenValue;
                humanReadableField.dispatchEvent(new Event('change'));
              }
            }
            MultiStepWizard.dismiss();
          },
          fail: function () {
            console.log("Ups");
            MultiStepWizard.dismiss();
          }
        });
      }).then(function () {
        MultiStepWizard.show();
      });
    });
  }
}

function getFormForAppKeyAndSecret() {
  return $(`
    <div class="form-group">
      <label for="appKey">AppKey</label>
      <input type="text" class="form-control" id="appKey" autocomplete="off" placeholder="App Key" />
    </div>
  `);
}

function getPanelWithAuthCodeLink() {
  return `<div class="panel panel-info">
    <div class="panel-heading">Authorize your Dropbox App</div>
    <div class="panel-body">
      <a href="#" id="authCodeLink" target="_blank">Authorization Link (opens in new tab)</a>
    </div>
  </div>`;
}

function getFormForAuthCode() {
  return `<div class="form-group">
    <label for="authCode">AuthCode</label>
    <input type="text" class="form-control" id="authCode" autocomplete="off" placeholder="Type in the AuthCode from the link above" />
  </div>`;
}

function generateCodeVerifier(len) {
  const uint8Array = new Uint8Array((len || 128) / 2);
  const arrayBuffer = window.crypto.getRandomValues(uint8Array);
  return Array.from(new Uint8Array(arrayBuffer))
    .map((item) => item.toString(16).padStart(2, "0"))
    .join("");
}
