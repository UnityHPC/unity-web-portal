function openModal(title, link) {
  $("span.modalTitle").html(title);
  $.ajax({
    url: link,
    success: function (result) {
      $("div.modalBody").html(result);
    },
    error: function () {
      $("div.modalBody").html("Something went wrong...");
    },
  });

  $("div.modalWrapper").fadeIn(100); // With Animation
  //$("div.modalWrapper").show();
}

$("button.btnClose").click(function () {
  //$("div.modalWrapper").fadeOut(50);  // With Animation
  $("div.modalWrapper").hide();
});
