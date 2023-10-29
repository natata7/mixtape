(function ($) {
  var Mixtape_Admin = {
    init: function () {
      // remove tab query arg
      $('input[name="_wp_http_referer"]').val(function (i, v) {
        return v.replace(/&tab=.*/i, "");
      });

      // mail recipient switch
      $('input[id^="mixtape_email_recipient_type-"]').change(function () {
        var checked = $(
          'input[id^="mixtape_email_recipient_type-"]:checked'
        ).val();
        $.when(
          $(
            'div[id^="mixtape_email_recipient_list-"]:not([id$=checked])'
          ).slideUp("fast")
        ).then(function () {
          $("#mixtape_email_recipient_list-" + checked).slideDown("fast");
        });
      });

      // shortcode option
      $("#mixtape_shortcode_option").change(function () {
        if ($(this).is(":checked")) {
          $("#mixtape_shortcode_help").slideDown("fast");
        } else {
          $("#mixtape_shortcode_help").slideUp("fast");
        }
      });

      // caption format switch
      $('input[id^="mixtape_caption_format-"]').change(function () {
        if ($('input[id^="mixtape_caption_format-"]:checked').val() === "image"){
          $("#mixtape_caption_image").slideDown("fast");
        } else {
          $("#mixtape_caption_image").slideUp("fast");
        }
      });

      // caption text mode switch
      $('input[id^="mixtape_caption_text_mode-"]').change(function () {
        var $textarea = $("#mixtape_custom_caption_text");
        if ($(this).val() == "default") {
          $textarea.data("custom", $textarea.val());
          $textarea.val($textarea.data("default"));
          $textarea.attr("disabled", true);
        } else {
          if ($textarea.data("custom")) {
            $textarea.val($textarea.data("custom"));
          }
          $textarea.attr("disabled", false);
        }
      });

      // dialog preview

      $("#preview-dialog-btn").on("click", function (e) {
        e.preventDefault(e);
        var mode = $(".dialog_mode_choice:checked").val();
        Mixtape_Admin.previewDialog(mode);
      });

      // Tab switching without reload
      $(".nav-tab").click(function (ev) {
        ev.preventDefault();
        if (!$(this).hasClass("nav-tab-active")) {
          $(this).siblings().removeClass("nav-tab-active");
          $(this).addClass("nav-tab-active");
          $(".mixtape-tab-contents").hide();
          $("#" + $(this).data("bodyid")).show();
          Mixtape_Admin.ChangeUrl($(this).text(), $(this).attr("href"));
        }
      });

      // Tooltip image
      $(".hover-image").hover(
        function () {
          if (!$(this).find(".tooltip-img").length) {
            var imgSrc = $(this).data("img-url");
            $(this).append('<img class="tooltip-img" src="' + imgSrc + '"/>');
          }
          $(this).addClass("tooltip-show");
        },
        function () {
          $(this).removeClass("tooltip-show");
        }
      );

      //Color picker
      var colorPickerInp = $(".mixtape_color_picker");
      colorPickerInp.wpColorPicker({
        change: function () {
          setTimeout(function () {
            Mixtape_Admin.colorScheme($(".mixtape_color_picker").val());
          }, 10);
        },
      });
      Mixtape_Admin.colorScheme(colorPickerInp.val());
    },

    previewDialog: function (mode) {
      var currentMode = $("#mixtape_dialog").data("mode");
      // request updated dialog if mode was changed
      if (mode == currentMode) {
        $("#mixtape_dialog").css("display", "flex");
        mixtape.dlg.toggle();
      } else {
        $("#preview-dialog-spinner").addClass("is-active");
        $.ajax({
          type: "post",
          dataType: "json",
          url: mixtape.ajaxurl,
          data: {
            action: "mixtape_preview_dialog",
            nonce: $('input[name="_wpnonce"]').val(),
            mode: mode,
          },
          success: function (response) {
            if (response.success === true) {
              $("#mixtape_dialog")
                .replaceWith(response.data)
                .css("display", "flex");
              mixtape.initDialogFx();
              mixtape.dlg.toggle();
            }
          },
          complete: function () {
            $("#preview-dialog-spinner").removeClass("is-active");
          },
        });
      }
    },

    ChangeUrl: function (title, url) {
      if (typeof history.pushState != "undefined") {
        var obj = { Title: title, Url: url };
        history.pushState(obj, obj.Title, obj.Url);
      }
    },

    colorScheme: function (color) {
      var css =
          ".mixtape-test, .mixtape_mistake_inner {color: " +
          color +
          " !important;}" +
          "#mixtape_dialog h2::before, #mixtape_dialog .mixtape_action, .mixtape-letter-back {background-color: " +
          color +
          " !important; }" +
          "#mixtape_reported_text:before, #mixtape_reported_text:after {border-color: " +
          color +
          " !important;}" +
          ".mixtape-letter-front .front-left {border-left-color: " +
          color +
          " !important;}" +
          ".mixtape-letter-front .front-right {border-right-color: " +
          color +
          " !important;}" +
          ".mixtape-letter-front .front-bottom, .mixtape-letter-back > .mixtape-letter-back-top, .mixtape-letter-top {border-bottom-color: " +
          color +
          " !important;}" +
          ".mixtape-logo svg, .select-logo__img svg {fill: " +
          color +
          " !important;}",
        head = document.head || document.getElementsByTagName("head")[0],
        style = document.createElement("style");

      style.type = "text/css";
      if (style.styleSheet) {
        style.styleSheet.cssText = css;
      } else {
        style.appendChild(document.createTextNode(css));
      }

      head.appendChild(style);
    },
  };

  $(document).ready(Mixtape_Admin.init);
})(jQuery);
