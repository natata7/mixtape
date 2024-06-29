/**
 * JavaScript Client Detection
 * (C) viazenetti GmbH (Christian Ludwig)
 */
(function (window) {
  var unknown = "-";

  var screenSize = screen.width ? `${screen.width} x ${screen.height}` : "";

  var nVer = navigator.appVersion;
  var nAgt = navigator.userAgent;
  var browser = navigator.appName;
  var version = `${parseFloat(navigator.appVersion)}`;
  var majorVersion = parseInt(navigator.appVersion, 10);

  var browsers = [
    { name: "Opera", key: "Opera", versionKey: "Version" },
    { name: "Opera", key: "OPR", versionKey: "Version" },
    { name: "UCBrowser", key: "UCBrowser", versionKey: "Version" },
    { name: "Microsoft Internet Explorer", key: "MSIE" },
    { name: "Edge", key: "Edge" },
    { name: "Chrome", key: "Chrome" },
    { name: "Safari", key: "Safari", versionKey: "Version" },
    { name: "Firefox", key: "Firefox" },
    { name: "Microsoft Internet Explorer", key: "Trident/", versionKey: "rv:" },
  ];

  for (let b of browsers) {
    let verOffset = nAgt.indexOf(b.key);
    if (verOffset !== -1) {
      browser = b.name;
      version = nAgt.substring(verOffset + b.key.length + 1);
      if (b.versionKey) {
        let versionOffset = nAgt.indexOf(b.versionKey);
        if (versionOffset !== -1) {
          version = nAgt.substring(versionOffset + b.versionKey.length + 1);
        }
      }
      break;
    }
  }

  let verEndings = [";", " ", ")"];
  for (let end of verEndings) {
    let ix = version.indexOf(end);
    if (ix !== -1) version = version.substring(0, ix);
  }

  majorVersion = parseInt(version, 10);
  if (isNaN(majorVersion)) {
    version = `${parseFloat(navigator.appVersion)}`;
    majorVersion = parseInt(navigator.appVersion, 10);
  }

  var mobile = /Mobile|mini|Fennec|Android|iP(ad|od|hone)/.test(nVer);
  var cookieEnabled =
    navigator.cookieEnabled ??
    ((document.cookie = "testcookie"),
    document.cookie.indexOf("testcookie") !== -1);

  var os = unknown;
  var clientStrings = [
    { s: "Windows 10", r: /(Windows 10.0|Windows NT 10.0)/ },
    { s: "Windows 8.1", r: /(Windows 8.1|Windows NT 6.3)/ },
    { s: "Windows 8", r: /(Windows 8|Windows NT 6.2)/ },
    { s: "Windows 7", r: /(Windows 7|Windows NT 6.1)/ },
    { s: "Windows Vista", r: /Windows NT 6.0/ },
    { s: "Windows Server 2003", r: /Windows NT 5.2/ },
    { s: "Windows XP", r: /(Windows NT 5.1|Windows XP)/ },
    { s: "Windows 2000", r: /(Windows NT 5.0|Windows 2000)/ },
    { s: "Windows ME", r: /(Win 9x 4.90|Windows ME)/ },
    { s: "Windows 98", r: /(Windows 98|Win98)/ },
    { s: "Windows 95", r: /(Windows 95|Win95|Windows_95)/ },
    { s: "Windows NT 4.0", r: /(Windows NT 4.0|WinNT4.0|WinNT|Windows NT)/ },
    { s: "Windows CE", r: /Windows CE/ },
    { s: "Windows 3.11", r: /Win16/ },
    { s: "Android", r: /Android/ },
    { s: "Open BSD", r: /OpenBSD/ },
    { s: "Sun OS", r: /SunOS/ },
    { s: "Linux", r: /(Linux|X11)/ },
    { s: "iOS", r: /(iPhone|iPad|iPod)/ },
    { s: "Mac OS X", r: /Mac OS X/ },
    { s: "Mac OS", r: /(MacPPC|MacIntel|Mac_PowerPC|Macintosh)/ },
    { s: "QNX", r: /QNX/ },
    { s: "UNIX", r: /UNIX/ },
    { s: "BeOS", r: /BeOS/ },
    { s: "OS/2", r: /OS\/2/ },
    {
      s: "Search Bot",
      r: /(nuhk|Googlebot|Yammybot|Openbot|Slurp|MSNBot|Ask Jeeves\/Teoma|ia_archiver)/,
    },
  ];

  for (let cs of clientStrings) {
    if (cs.r.test(nAgt)) {
      os = cs.s;
      break;
    }
  }

  if (/Windows/.test(os)) os = "Windows";

  window.jscd = {
    screen: screenSize,
    browser: browser,
    browserVersion: version,
    browserMajorVersion: majorVersion,
    mobile: mobile,
    os: os,
    cookies: cookieEnabled,
  };
})(window);

(function (window) {
  "use strict";

  function checkAnimationSupport() {
    var animation = false,
      animationstring = "animation",
      keyframeprefix = "",
      domPrefixes = "Webkit Moz O ms Khtml".split(" "),
      pfx = "",
      elm = document.createElement("div");

    if (elm.style.animationName !== undefined) {
      animation = true;
    }

    if (animation === false) {
      for (var i = 0; i < domPrefixes.length; i++) {
        if (elm.style[domPrefixes[i] + "AnimationName"] !== undefined) {
          pfx = domPrefixes[i];
          animationstring = pfx + "Animation";
          keyframeprefix = "-" + pfx.toLowerCase() + "-";
          animation = true;
          break;
        }
      }
    }

    return {
      supported: animation,
      pfx: pfx,
      animationstring: animationstring,
      keyframeprefix: keyframeprefix,
      eventName: animation
        ? pfx
          ? pfx + "AnimationEnd"
          : "animationend"
        : null,
    };
  }

  var animationSupport = checkAnimationSupport();

  // Визначення події завершення анімації
  var animEndEventNames = {
    WebkitAnimation: "webkitAnimationEnd",
    OAnimation: "oAnimationEnd",
    msAnimation: "MSAnimationEnd",
    animation: "animationend",
  };
  var animEndEventName = animationSupport.eventName;

  var onEndAnimation = function (el, callback) {
    var onEndCallbackFn = function (ev) {
      if (animationSupport.supported) {
        if (ev.target !== this) return;
        this.removeEventListener(animEndEventName, onEndCallbackFn);
      }
      if (callback && typeof callback === "function") {
        callback.call();
      }
    };
    if (animationSupport.supported) {
      el.addEventListener(animEndEventName, onEndCallbackFn);
    } else {
      onEndCallbackFn();
    }
  };

  function extend(a, b) {
    for (var key in b) {
      if (b.hasOwnProperty(key)) {
        a[key] = b[key];
      }
    }
    return a;
  }

  function DialogFx(el, options) {
    this.el = el;
    this.options = extend({}, this.options);
    extend(this.options, options);
    this.isOpen = false;
    this._initEvents();
  }

  DialogFx.prototype.options = {
    // callbacks
    onOpenDialog: function () {
      return false;
    },
    onCloseDialog: function () {
      return false;
    },
    onOpenAnimationEnd: function () {
      return false;
    },
    onCloseAnimationEnd: function () {
      return false;
    },
  };

  DialogFx.prototype._initEvents = function () {
    var self = this;

    // esc key closes dialog
    document.addEventListener("keydown", function (ev) {
      var keyCode = ev.keyCode || ev.which;
      if (keyCode === 27 && self.isOpen) {
        self.toggle();
      }
    });

    this.el
      .querySelector(".dialog__overlay")
      .addEventListener("click", this.toggle.bind(this));
  };

  DialogFx.prototype.toggle = function () {
    var self = this;
    if (this.isOpen) {
      jQuery(this.el).removeClass("dialog--open");
      jQuery(self.el).addClass("dialog--close");

      onEndAnimation(this.el.querySelector(".dialog__content"), function () {
        jQuery(self.el).removeClass("dialog--close");
        self.options.onCloseAnimationEnd(self);
      });

      // callback on close
      this.options.onCloseDialog(this);
    } else {
      jQuery(this.el).addClass("dialog--open");

      // callback on open
      this.options.onOpenDialog(this);

      onEndAnimation(this.el.querySelector(".dialog__content"), function () {
        jQuery(self.el).removeClass("dialog--close");
        self.options.onOpenAnimationEnd(self);
      });
    }
    this.isOpen = !this.isOpen;
  };

  // add to global namespace
  window.DialogFx = DialogFx;
})(window);

/**
 * mixtape
 */

jQuery(function ($) {
  const Mixtape = {
    reportButton: null,

    onReady() {
      this.initDialogFx();

      var $dialog = $(this.dlg.el);

      $(document).on("click", ".mixtape_action", function () {
        if ($(this).is("[data-action=send]")) {
          var data;
          if (!$dialog.data("dry-run") && (data = $dialog.data("report"))) {
            if ($dialog.data("mode") === "comment") {
              data.comment = $dialog.find("#mixtape_comment").val();
              $("#mixtape_comment").val("");
            }
            data.post_id = $(this).data("id");
            data.nonce = $dialog.data("nonce");
            Mixtape.reportSpellError(data);
          }
          Mixtape.animateLetter();
          Mixtape.hideReportButton();
        }
      });

      $(document).on("click", "#mixtape-close-btn", function () {
        Mixtape.dlg.toggle();
      });

      $(document).keyup(function (ev) {
        if (
          ev.ctrlKey &&
          ev.keyCode === 13 &&
          ev.target.nodeName.toLowerCase() !== "textarea" &&
          $("#mixtape_dialog.dialog--open").length === 0
        ) {
          var report = Mixtape.getSelectionData();
          if (report) Mixtape.showDialog(report);
        }
      });

      document.addEventListener("selectionchange", function () {
        if ($("#mixtape_dialog.dialog--open").length == 0) {
          var selection = window.getSelection().toString().trim();
          if (selection !== "") {
            Mixtape.showReportButton();
          } else {
            Mixtape.hideReportButton();
          }
        }
      });
    },

    getSelectionData() {
      if (!window.getSelection) return false;

      const sel = window.getSelection();
      if (sel.isCollapsed) return;

      const selChars = sel.toString();
      const maxContextLength = 140;

      //if (selChars.length > maxContextLength) return;

      const parentEl = this._getParentElement(sel);
      if (!parentEl) return;

      const { context, selWithContext, initialSel, backwards, direction } =
        this._prepareSelection(sel, parentEl);
      if (!selWithContext) return;

      const { selToFindInContext, truncatedContext, previewText } =
        this._getContextAndPreviewText(
          selWithContext,
          context,
          maxContextLength,
          selChars
        );

      return {
        selection: selChars,
        word: selWithContext,
        replace_context: selToFindInContext,
        context: truncatedContext,
        preview_text: previewText,
        nonce: $('input[name="mixtape_nonce"]').val(),
      };
    },

    reportSpellError(data) {
      data.action = "mixtape_report_error";
      $.ajax({
        type: "post",
        dataType: "json",
        url: MixtapeLocalize.ajaxurl,
        data: data,
      });
    },

    resetDialog() {
      var $dialog = $(this.dlg.el);

      if ($dialog.data("mode") !== "notify") {
        $dialog.find("#mixtape_confirm_dialog").css("display", "");
        $dialog.find("#mixtape_success_dialog").remove();
      }

      $dialog.find(".dialog__content").removeClass("show-letter");
      $dialog
        .find(
          ".mixtape-letter-top, .mixtape-letter-front, .mixtape-letter-back, .dialog-wrap, .mixtape-letter-back-top"
        )
        .removeAttr("style");
      $dialog.find(".mixtape-letter-top").removeClass("close");
    },

    showDialog(report) {
      if (report.selection && report.context) {
        var $dialog = $(this.dlg.el);

        if ($dialog.data("mode") === "notify") {
          this.reportSpellError(report);
          this.dlg.toggle();
        } else {
          $dialog.data("report", report);
          $dialog.find("#mixtape_reported_text").html(report.preview_text);
          this.dlg.toggle();
        }
      }
    },

    animateLetter() {
      var dialog = $(this.dlg.el),
        content = dialog.find(".dialog__content"),
        letterTop = dialog.find(".mixtape-letter-top"),
        letterFront = dialog.find(".mixtape-letter-front"),
        letterBack = dialog.find(".mixtape-letter-back"),
        dialogWrap = dialog.find(".dialog-wrap");

      content.addClass("show-letter");

      setTimeout(function () {
        var y =
          letterTop.offset().top -
          letterFront.offset().top +
          letterTop.outerHeight();
        letterTop.css({ bottom: Math.floor(y), opacity: 1 });
        jQuery(".mixtape-letter-back-top").hide();
        var scaleX = content.hasClass("with-comment") ? 0.28 : 0.4;
        dialogWrap.css("transform", `scaleY(0.5) scaleX(${scaleX})`);
        setTimeout(function () {
          var translateY = content.hasClass("with-comment") ? "12%" : "28%";
          dialogWrap.css(
            "transform",
            `translateY(${translateY}) scaleY(0.5) scaleX(${scaleX + 0.05})`
          );
          setTimeout(function () {
            letterTop.css("z-index", "9").addClass("close");
            setTimeout(function () {
              dialogWrap.css({ visibility: "hidden", opacity: "0" });
              letterFront.add(letterBack).css("animation", "send-letter1 0.7s");
              letterTop.css("animation", "send-letter2 0.7s");
              setTimeout(function () {
                Mixtape.dlg.toggle();
              }, 400);
            }, 400);
          }, 400);
        }, 300);
      }, 400);
    },

    initDialogFx() {
      this.dlg = new DialogFx(document.getElementById("mixtape_dialog"), {
        onOpenDialog: function (dialog) {
          $(dialog.el).css("display", "flex");
        },
        onCloseAnimationEnd: function (dialog) {
          $(dialog.el).css("display", "none");
          Mixtape.resetDialog();
        },
      });
    },

    _getParentElement(sel) {
      if (sel.rangeCount) {
        let parentEl = sel.getRangeAt(0).commonAncestorContainer;
        while (parentEl && parentEl.nodeType !== 1) {
          parentEl = parentEl.parentNode;
        }
        return parentEl;
      }
      return null;
    },

    _prepareSelection(sel, parentEl) {
      const selChars = sel.toString().trim();
      console.log('Initial Selection Chars:', selChars); // Логування початкового виділення
    
      if (!selChars) {
        return {
          context: '',
          selWithContext: '',
          initialSel: {},
          backwards: false,
          direction: {}
        };
      }
    
      const direction = this._determineDirection(sel);
      console.log('Direction:', direction); // Логування напрямку
    
      const initialSel = this._saveInitialSelection(sel);
      console.log('Initial Selection:', initialSel); // Логування початкового стану виділення
    
      const context = this._getContext(parentEl);
      console.log('Context:', context); // Логування контексту
    
      this._extendSelection(sel, direction, context, selChars);
    
      const selWithContext = sel.toString().trim();
      console.log('Selection with Context:', selWithContext); // Логування виділення з контекстом
    
      return {
        context,
        selWithContext,
        initialSel,
        backwards: direction.backwards,
        direction,
      };
    },

    _determineDirection(sel) {
      const range = document.createRange();
      range.setStart(sel.anchorNode, sel.anchorOffset);
      range.setEnd(sel.focusNode, sel.focusOffset);
      const backwards = range.collapsed;
      range.detach();
      return {
        forward: backwards ? "backward" : "forward",
        backward: backwards ? "forward" : "backward",
        backwards,
      };
    },

    _saveInitialSelection(sel) {
      return {
        focusNode: sel.focusNode,
        focusOffset: sel.focusOffset,
        anchorNode: sel.anchorNode,
        anchorOffset: sel.anchorOffset,
      };
    },

    _getContext(element) {
      const context = element ? element.textContent.trim() : '';
      console.log('Element Context:', context); // Логування контексту елемента
      return this._stringifyContent(context);
    },

    _extendSelection(sel, direction, context, selChars) {
      console.log('Before Extend - Selection:', sel.toString(), 'Direction:', direction); // Логування перед розширенням
    
      // Зберігаємо початкове положення виділення
      const initialRange = sel.getRangeAt(0).cloneRange();
    
      // Розширюємо виділення вперед
      sel.modify("extend", direction.forward, "character");
    
      if (!/\w/.test(selChars.charAt(0))) {
        sel.modify("extend", direction.forward, "character");
      }
    
      sel.modify("extend", direction.backward, "word");
    
      if (!/\w/.test(selChars.charAt(selChars.length - 1))) {
        sel.modify("extend", direction.backward, "character");
      }
    
      const extendedSelection = sel.toString();
      console.log('After Extend - Selection:', extendedSelection); // Логування після розширення
    
      // Якщо розширення виділення не дало результату, відновлюємо початкове виділення
      if (extendedSelection.trim() === '') {
        sel.removeAllRanges();
        sel.addRange(initialRange);
      }
    },

    _getContextAndPreviewText(
      selWithContext,
      context,
      maxContextLength,
      selChars
    ) {
      const selPos = this._getExactSelPos(selWithContext, context);
      let truncatedContext = context;

      if (context.length > maxContextLength) {
        truncatedContext = this._truncateContext(
          context,
          selPos,
          selWithContext.length,
          maxContextLength
        );
      }

      const selWithContextHighlighted = selWithContext.replace(
        selChars,
        `<span class="mixtape_mistake_inner">${selChars}</span>`
      );

      const previewText = truncatedContext.replace(
        selWithContext,
        selWithContextHighlighted
      );
      return {
        selToFindInContext: selWithContext,
        truncatedContext,
        previewText,
      };
    },

    _getExactSelPos(selection, context) {
      return context.indexOf(selection);
    },

    _truncateContext(context, selPos, selLength, maxContextLength) {
      let start = Math.max(0, selPos - Math.floor(maxContextLength / 2));
      let end = Math.min(context.length, start + maxContextLength);

      if (start > 0) {
        start = context.lastIndexOf(" ", start) + 1;
      }
      if (end < context.length) {
        end = context.indexOf(" ", end);
      }

      return (
        (start > 0 ? "..." : "") +
        context.slice(start, end) +
        (end < context.length ? "..." : "")
      );
    },

    _stringifyContent(string) {
      return typeof string === "string"
        ? string.replace(/\s+/g, " ").trim()
        : "";
    },

    restoreInitSelection(sel, initialSel) {
      sel.collapse(initialSel.anchorNode, initialSel.anchorOffset);
      sel.extend(initialSel.focusNode, initialSel.focusOffset);
    },

    showReportButton() {
      if (!this.reportButton) {
        this.reportButton = $("<button>")
          .text(MixtapeLocalize.reportError)
          .addClass("mixtape-report-button")
          .on("click", () => {
            const report = Mixtape.getSelectionData();
            if (report) {
              this.showDialog(report);
            } else {
              console.error("No report generated");
            }
          });

        $("body").append(this.reportButton);
      }

      const selection = window.getSelection();
      if (selection.rangeCount === 0) {
        console.error("No selection range found");
        return;
      }

      const range = selection.getRangeAt(0);
      const rect = range.getBoundingClientRect();
      const buttonHeight = this.reportButton.outerHeight();
      const topPosition = rect.top + window.scrollY - 20 - buttonHeight;

      this.reportButton.css({
        top: `${topPosition}px`,
        left: `${rect.left + window.scrollX}px`,
        position: "absolute",
      });
    },

    hideReportButton() {
      if (this.reportButton) {
        this.reportButton.remove();
        this.reportButton = null;
      }
    },

    init() {
      this.onReady();
    },
  };

  $(document).ready(function () {
    Mixtape.init();
  });
});
