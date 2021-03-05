$(document).ready(setNav);
$(window).resize(setNav);

function setNav() {
  if ($("button.hamburger").is(":visible")) {
    $("nav.mainNav").hide();  // Mobile View
  } else {
    $("nav.mainNav").show();  // Desktop View
  }
}

$("button.hamburger").on("click", function () {
  var mainNav = $("nav.mainNav");
  if (mainNav.is(":visible")) {
    mainNav.fadeOut(100);
  } else {
    mainNav.fadeIn(100);
  }
});

$(window).click(function (e) {
  if (!$(e.target).parent().hasClass("hamburger") && $("button.hamburger").is(":visible")) {
    $("nav.mainNav").fadeOut(100);
  }
});

// Functions to set nav links as active. Sub links can activate parents by naming files with same prefix, for example: documentation.php and documentation_view.php activate the same link
var url = location.pathname;
if (url.lastIndexOf('.') >= 0) {
  url = url.substring(0, url.lastIndexOf('.'));
}

if (url.lastIndexOf('/') >= 0) {
  url = url.substring(url.lastIndexOf('/') + 1);
}

$('nav.mainNav a').each(function () {
  var href = $(this).attr("href");

  if (href.lastIndexOf('.') >= 0) {
    href = href.substring(0, href.lastIndexOf('.'));
  }

  if (href.lastIndexOf('/') >= 0) {
    href = href.substring(href.lastIndexOf('/') + 1);
  }

  if (url.indexOf(href) == 0) {
    $(this).addClass("active");
  }
})

/**
 * btnDropdown Click Events
 */
$("div.btnDropdown > button").click(function () {
  $("div.btnDropdown > div").toggle();
});

$(window).click(function (e) {
  if (!e.target.matches("div.btnDropdown > button")) {
    $("div.btnDropdown > div").hide();
  }
});

$(function () {
  setTimeout(function () {
    $("div.checkmark").fadeOut(500);
  }, 5000);
});

function downloadFile(text, filename) {
  var element = document.createElement('a');
  element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
  element.setAttribute('download', filename);

  element.style.display = "none";
  $("body").append(element);
  element.click();
  element.remove();
}