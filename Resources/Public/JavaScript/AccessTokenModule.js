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
	exports.initialize = function () {
		$(".triggerAccessToken").on("click", function () {
			let $getAccessTokenButton = $(this);
			multiStep.addSlide(
				"AppKeySecretForm",
				"Set Dropbox App key and secret",
				getFormForAppKeyAndSecret(),
				severity.info,
				"Step 1/3",
				function ($slide) {
					let $modal = $slide.closest('.modal');
					let $nextButton = $modal.find(".modal-footer").find('button[name="next"]');
					multiStep.lockPrevStep();
					$nextButton.off().on("click", function () {
						multiStep.set("appKey", $slide.find("input#appKey").val());
						multiStep.set("appSecret", $slide.find("input#appSecret").val());
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
						"https://www.dropbox.com/oauth2/authorize?client_id=" + settings.appKey + "&response_type=code"
					);
				}
			);
			multiStep.addSlide(
				"AuthCodeForm",
				"Insert AuthCode retrieved by link of previous step",
				getFormForAuthCode(),
				severity.info,
				"Step 3/3",
				function ($slide) {
					let $modal = $slide.closest(".modal");
					let $nextButton = $modal.find(".modal-footer").find('button[name="next"]');
					multiStep.unlockNextStep();
					multiStep.setup.forceSelection = false;
					$nextButton.off().on("click", function () {
						multiStep.set("authCode", $slide.find("input#authCode").val());

						$.ajax({
							url: "https://api.dropboxapi.com/oauth2/token",
							dataType: "json",
							method: "POST",
							data: {
								code: multiStep.setup.settings["authCode"],
								grant_type: "authorization_code",
								client_id: multiStep.setup.settings["appKey"],
								client_secret: multiStep.setup.settings["appSecret"]
							},
							success: function(response) {
								if (response.access_token) {
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
					});
				}
			);
			multiStep.show();
		});
	}

	function getFormForAppKeyAndSecret()
	{
		return '<div class="form-group">' +
			'  <label for="appKey">AppKey</label>' +
			'  <input type="text" class="form-control" id="appKey" placeholder="App Key" />' +
			'</div>' +
			'<div class="form-group">\n' +
			'  <label for="appSecret">AppSecret</label>\n' +
			'  <input type="text" class="form-control" id="appSecret" placeholder="App Secret" />' +
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
			'  <input type="text" class="form-control" id="authCode" placeholder="Type in the AuthCode from the link above" />' +
			'</div>';
	}
});
