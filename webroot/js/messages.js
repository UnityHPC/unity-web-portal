function hideClearAllMessagesButtonIfAllMessagesAlreadyCleared() {
    var visibleMessages = $('#messages .message:visible').length;
    if (visibleMessages === 0) {
        $('#clear_all_messages_button').hide();
    }
}
