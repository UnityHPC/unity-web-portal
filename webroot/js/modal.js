function openModal(title, link, message = "") {
    $("span.modalTitle").html(title);
    $("div.modalMessages").html(message);
    $.ajax({url: link, success: function(result) {
        $("div.modalBody").html(result);
    }});

    $("div.modalWrapper").fadeIn(100);  // With Animation
    //$("div.modalWrapper").show();
}

$("button.btnClose").click(function() {
    //$("div.modalWrapper").fadeOut(50);  // With Animation
    $("div.modalWrapper").hide();
});

$("button.btnOkay").click(function() {
    //$("div.modalWrapper").fadeOut(50);  // With Animation
    $("div.modalWrapper").hide();
});

$("button.btnNo").click(function() {
    //$("div.modalWrapper").fadeOut(50);  // With Animation
    $("div.modalWrapper").hide();
});

$("button.btnYes").click(function() {
    // invoke form
    var form = $(this).attr("data-form");
    console.log($(form).length);
    $(form).submit();  // submit form
});

function messageModal(message) {
    $("span.modalTitle").html("Message");
    $("div.modalBody").html(message);
    $("div.modalButtons > *").hide()
    $("div.modalButtons > div.messageButtons").show()

    $("div.modalWrapper").fadeIn(100);  // With Animation
    //$("div.modalWrapper").show();
}

function confirmModal(message, form) {
    $("span.modalTitle").html("Confirm");
    $("div.modalBody").html(message);
    $("div.modalButtons > *").hide()
    $("div.modalButtons > div.yesnoButtons").show()
    $("div.modalButtons > div.yesnoButtons > button.btnYes").attr("data-form", form);

    $("div.modalWrapper").fadeIn(100);  // With Animation
    //$("div.modalWrapper").show();
}