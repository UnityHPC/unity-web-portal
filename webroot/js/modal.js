function openModal(title, link, message = "") {
  $("span.modalTitle").html(title);
  $("div.modalMessages").html(message);
  $.ajax({
    url: link,
    success: function (result) {
      $("div.modalBody").html(result);
    },
  });

  $("div.modalWrapper").fadeIn(100); // With Animation
  //$("div.modalWrapper").show();
}

$("button.btnClose").click(function () {
  //$("div.modalWrapper").fadeOut(50);  // With Animation
  $("div.modalWrapper").hide();
});
