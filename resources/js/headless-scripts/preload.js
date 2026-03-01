window.alertHandlerCalled = false;
window.alertMessage = null;
window.alert = window.confirm = function(str) {
    window.alertHandlerCalled = true;
    window.alertMessage = str;
    return true
};